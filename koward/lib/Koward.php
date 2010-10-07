<?php
/**
 * Copyright 2009-2010 The Horde Project (http://www.horde.org/)
 *
 * @author   Gunnar Wrobel <p@rdus.de>
 * @license  http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html
 * @category Kolab
 * @package  Koward
 */

class Koward {

    const PERM_SHOW = 1;
    const PERM_READ = 2;
    const PERM_EDIT = 4;
    const PERM_DELETE = 8;

    /**
     * The singleton instance.
     *
     * @var Koward
     */
    static protected $instance = null;

    static protected $server = null;

    static protected $map_class_type = null;

    public $objectconf;

    public function __construct()
    {
        global $registry, $notification, $browser, $conf;

        $this->registry     = &$registry;
        $this->notification = &$notification;

        $this->auth = $GLOBALS['injector']->getInstance('Horde_Core_Factory_Auth')->create();

        $this->conf       = Horde::loadConfiguration('conf.php', 'conf');
        $this->objects    = Horde::loadConfiguration('objects.php', 'objects');
        $this->attributes = Horde::loadConfiguration('attributes.php', 'attributes');
        $this->labels     = Horde::loadConfiguration('labels.php', 'labels');
        $this->perms      = Horde::loadConfiguration('perms.php', 'perms');
        $this->order      = Horde::loadConfiguration('order.php', 'order');
        $this->visible    = Horde::loadConfiguration('visible.php', 'visible');
        $this->search     = Horde::loadConfiguration('search.php', 'search');
    }

    public static function dispatch($koward, $request_class = 'Horde_Controller_Request_Http',
                                    $webroot = null)
    {
        global $registry, $notification, $browser;

        if ($webroot === null) {
            $webroot = $registry->get('webroot', 'koward');
        }

        // Set up our request and routing objects
        $request = new $request_class();
        $mapper = new Horde_Routes_Mapper();

        // Application routes are relative only to the application. Let the mapper know
        // where they start.
        $mapper->prefix = $webroot;

        // Check for route definitions.
        $routeFile = dirname($koward) . '/../../koward/config/routes.php';
        if (!file_exists($routeFile)) {
            throw new Horde_Controller_Exception('Not routable');
        }

        // Load application routes.
        include $routeFile;

        $context = array(
            'mapper' => $mapper,
            'controllerDir' => dirname(__FILE__) . '/Koward/Controller',
            'viewsDir' => dirname(__FILE__) . '/Koward/View',
            // 'logger' => '',
        );

        $dispatcher = Horde_Controller_Dispatcher::singleton($context);
        $dispatcher->dispatch($request);
    }

    public function getServer()
    {
        if (!isset(self::$server)) {
            self::$server = Horde_Kolab_Server::singleton(array('user' => $GLOBALS['registry']->getAuth(),
                                                                'pass' => $GLOBALS['registry']->getAuthCredential('password')));
        }

        return self::$server;
    }

    /**
     * Get a token for protecting a form.
     *
     * @param string $seed  TODO
     *
     * @return  TODO
     */
    static public function getRequestToken($seed)
    {
        $token = Horde_Token::generateId($seed);
        $_SESSION['horde_form_secrets'][$token] = time();
        return $token;
    }

    /**
     * Check if a token for a form is valid.
     *
     * @param string $seed   TODO
     * @param string $token  TODO
     *
     * @throws Horde_Exception
     */
    static public function checkRequestToken($seed, $token)
    {
        if (empty($_SESSION['horde_form_secrets'][$token])) {
            throw new Horde_Exception(_("We cannot verify that this request was really sent by you. It could be a malicious request. If you intended to perform this action, you can retry it now."));
        }

        if ($_SESSION['horde_form_secrets'][$token] + $GLOBALS['conf']['server']['token_lifetime'] < time()) {
            throw new Horde_Exception(sprintf(_("This request cannot be completed because the link you followed or the form you submitted was only valid for %d minutes. Please try again now."), round($GLOBALS['conf']['server']['token_lifetime'] / 60)));
        }
    }

    public function getObject($uid)
    {
        return $this->getServer()->fetch($uid);
    }


    public function getType($mixed = null)
    {
        if ($mixed instanceOf Horde_Kolab_Server_Object) {
            $class_name = get_class($mixed);
        } else if (!empty($mixed)) {
            $class_name = $mixed;
        } else {
            $session = Horde_Kolab_Session::singleton();
            $object = $this->getObject($session->user_uid);
            $class_name = get_class($object);
        }

        if (empty(self::$map_class_type)) {
            foreach ($this->objects as $name => $config) {
                self::$map_class_type[$config['class']]['types'][] = $name;
                if (!empty($config['preferred'])) {
                    self::$map_class_type['preferred'] = $name;
                }
            }
        }

        if (isset(self::$map_class_type[$class_name]['types'])) {
            return self::$map_class_type[$class_name]['types'][0];
        } else {
            return self::$map_class_type['preferred'];
        }
    }


    public function hasAccess($id, $permission = Koward::PERM_SHOW)
    {
        return $this->hasPermission($id, null, $permission);
    }

    /**
     * In the long run we might wish to use the Horde permission system
     * here. But for the first draft this would be too much as the permission
     * system would also require integration with the group system etc.
     */
    public function hasPermission($id, $user = null, $perm = null)
    {
        $global = $this->_hasPermission($this->perms,
                                        $id, $perm);

        if ($user === null) {
            try {
                $session = Horde_Kolab_Session::singleton();
                if (!empty($session->user_uid)) {
                    $user = $this->getObject($session->user_uid);
                    if (get_class($user) == $this->conf['koward']['cli_admin']
                        && Horde_Cli::runningFromCLI()) {
                        return true;
                    }
                    $type = $this->getType($user);
                    if (isset($this->objects[$type]['permission'])) {
                        return $this->_hasPermission($this->objects[$type]['permission'],
                                                     $id, $perm);
                    }
                }
            } catch (Exception $e) {
                Horde::logMessage($e, 'DEBUG');
            }
        }
        return $global;
    }

    private function _hasPermission(&$root, $id, $perm)
    {
        if (empty($root)) {
            return false;
        }
        if (is_int($root)) {
            return $perm & $root;
        }
        if (is_array($root)) {
            if (empty($id)) {
                return true;
            }
            list($sub, $path) = explode('/', $id, 2);
            if (!isset($root[$sub])) {
                return false;
            }
            return $this->_hasPermission($root[$sub], $path, $perm);
        }
    }

    static public function singleton()
    {
        if (!isset(self::$instance)) {
            self::$instance = new Koward();
        }

        return self::$instance;
    }
}
