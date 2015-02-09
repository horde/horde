<?php
/**
 * Copyright 2010-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2010-2015 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */

/* Determine the base directories. */
if (!defined('IMP_BASE')) {
    define('IMP_BASE', realpath(__DIR__ . '/..'));
}

if (!defined('HORDE_BASE')) {
    /* If Horde does not live directly under the app directory, the HORDE_BASE
     * constant should be defined in config/horde.local.php. */
    if (file_exists(IMP_BASE . '/config/horde.local.php')) {
        include IMP_BASE . '/config/horde.local.php';
    } else {
        define('HORDE_BASE', realpath(IMP_BASE . '/..'));
    }
}

/* Load the Horde Framework core (needed to autoload
 * Horde_Registry_Application::). */
require_once HORDE_BASE . '/lib/core.php';

/**
 * IMP application API.
 *
 * This file defines Horde's core API interface. Other core Horde libraries
 * can interact with IMP through this API.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2010-2015 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
class IMP_Application extends Horde_Registry_Application
{
    /**
     */
    public $auth = array(
        'add',
        'authenticate',
        'list',
        'remove',
        'transparent'
    );

    /**
     */
    public $features = array(
        'dynamicView' => true,
        'notificationHandler' => true,
        'smartmobileView' => true
    );

    /**
     */
    public $version = 'H6 (7.0.0-git)';

    /**
     * Server key used in logged out session.
     *
     * @var string
     */
    protected $_oldserver = null;

    /**
     */
    public function appInitFailure($e)
    {
        global $injector;

        switch ($e->getCode()) {
        case Horde_Registry::AUTH_FAILURE:
            $injector->getInstance('IMP_Factory_Compose')->create()->sessionExpireDraft($injector->getInstance('Horde_Variables'));
            break;
        }
    }

    /**
     */
    protected function _bootstrap()
    {
        global $injector;

        /* Add IMP-specific factories. */
        $factories = array(
            'IMP_AuthImap' => 'IMP_Factory_AuthImap',
            'IMP_Contacts' => 'IMP_Factory_Contacts',
            'IMP_Crypt_Pgp' => 'IMP_Factory_Pgp',
            'IMP_Crypt_Smime' => 'IMP_Factory_Smime',
            'IMP_Flags' => 'IMP_Factory_Flags',
            'IMP_Identity' => 'IMP_Factory_Identity',
            'IMP_Ftree' => 'IMP_Factory_Ftree',
            'IMP_Mail' => 'IMP_Factory_Mail',
            'IMP_Mail_Autoconfig' => 'IMP_Factory_MailAutoconfig',
            'IMP_Mailbox_SessionCache' => 'IMP_Factory_MailboxCache',
            'IMP_Maillog' => 'IMP_Factory_Maillog',
            'IMP_Prefs_Sort' => 'IMP_Factory_PrefsSort',
            'IMP_Quota' => 'IMP_Factory_Quota',
            'IMP_Search' => 'IMP_Factory_Search',
            'IMP_Sentmail' => 'IMP_Factory_Sentmail'
        );

        foreach ($factories as $key => $val) {
            $injector->bindFactory($key, $val, 'create');
        }

        /* Methods only available if admin config is set for this
         * server/login. */
        if (empty($injector->getInstance('IMP_Factory_Imap')->create()->config->admin)) {
            $this->auth = array_diff($this->auth, array('add', 'list', 'remove'));
        }

        /* Set exception handler to handle uncaught
         * Horde_Imap_Client_Exceptions. */
        set_exception_handler(array($this, 'exceptionHandler'));
    }

    /**
     */
    protected function _authenticated()
    {
        IMP_Auth::authenticateCallback();
    }

    /**
     */
    public function logout()
    {
        global $injector;

        /* Clean up dangling IMP_Compose objects. */
        foreach ($injector->getInstance('IMP_Factory_Compose')->getAllObs() as $val) {
            $val->destroy('cancel');
        }

        /* Destroy any IMP_Mailbox_List cached entries, since they will not
         * be used in the next session. */
        $injector->getInstance('IMP_Factory_MailboxList')->expireAll();

        /* Grab the current server from the session to correctly populate
         * login form. */
        $this->_oldserver = $injector->getInstance('IMP_Factory_Imap')->create()->server_key;
    }

    /**
     */
    public function getInitialPage()
    {
        return strval(IMP::getInitialPage()->url->setRaw(true));
    }

    /* Horde permissions. */

    /**
     */
    public function perms()
    {
        return $GLOBALS['injector']->getInstance('IMP_Perms')->perms();
    }

    /**
     */
    public function hasPermission($permission, $allowed, $opts = array())
    {
        return $GLOBALS['injector']->getInstance('IMP_Perms')->hasPermission($permission, $allowed, $opts);
    }

    /* Horde_Notification methods. */

    /**
     * Modifies the global notification handler.
     *
     * @param Horde_Notification_Handler $handler  A notification handler.
     */
    public function setupNotification(Horde_Notification_Handler $handler)
    {
        $handler->addDecorator(new IMP_Notification_Handler_Decorator_NewmailNotify());
        $handler->addType('status', 'imp.*', 'IMP_Notification_Event_Status');
    }

    /* Horde_Core_Auth_Application methods. */

    /**
     */
    public function authLoginParams()
    {
        $params = array();

        if ($GLOBALS['conf']['server']['server_list'] == 'shown') {
            $server_list = array();
            $selected = is_null($this->_oldserver)
                ? $GLOBALS['injector']->getInstance('Horde_Variables')->get('imp_server_key', IMP_Auth::getAutoLoginServer())
                : $this->_oldserver;

            foreach (IMP_Imap::loadServerConfig() as $key => $val) {
                $server_list[$key] = array(
                    'name' => $val->name,
                    'selected' => ($selected == $key)
                );
            }
            $params['imp_server_key'] = array(
                'label' => _("Server"),
                'type' => 'select',
                'value' => $server_list
            );
        }

        /* Show selection of alternate views. */
        $js_code = array(
            'HordeLogin.server_key_error' => _("Please choose a mail server.")
        );

        return array(
            'js_code' => $js_code,
            'js_files' => array(
                array('login.js', 'imp')
            ),
            'params' => $params
        );
    }

    /**
     * @param array $credentials  Credentials of the user. Allowed keys:
     *                            'imp_server_key', 'password'.
     */
    public function authAuthenticate($userId, $credentials)
    {
        if (isset($credentials['server'])) {
            $server = $credentials['server'];
        } else {
            $server = empty($credentials['imp_server_key'])
                ? IMP_Auth::getAutoLoginServer()
                : $credentials['imp_server_key'];
        }

        IMP_Auth::authenticate(array(
            'password' => $credentials['password'],
            'server' => $server,
            'userId' => $userId
        ));
    }

    /**
     */
    public function authTransparent($auth_ob)
    {
        return IMP_Auth::transparent($auth_ob);
    }

    /**
     * @param array $credentials  An array of login credentials. For IMP,
     *                            this must contain a password entry.
     */
    public function authAddUser($userId, $credentials)
    {
        try {
            $GLOBALS['injector']->getInstance('IMP_AuthImap')->addUser($userId, $credentials);
        } catch (Exception $e) {
            throw new Horde_Auth_Exception($e);
        }
    }

    /**
     */
    public function authRemoveUser($userId)
    {
        try {
            $GLOBALS['injector']->getInstance('IMP_AuthImap')->removeUser($userId);
        } catch (Exception $e) {
            throw new Horde_Auth_Exception($e);
        }
    }

    /**
     */
    public function authUserList()
    {
        try {
            return $GLOBALS['injector']->getInstance('IMP_AuthImap')->listUsers();
        } catch (Exception $e) {
            throw new Horde_Auth_Exception($e);
        }
    }

    /* Topbar method. */

    /**
     */
    public function topbarCreate(Horde_Tree_Renderer_Base $tree,
                                 $parent = null, array $params = array())
    {
        global $injector, $registry;

        if (IMP_Compose::canCompose()) {
            $clink = new IMP_Compose_Link();
            $tree->addNode(array(
                'id' => strval($parent) . 'compose',
                'parent' => $parent,
                'label' => _("New Message"),
                'expanded' => false,
                'params' => array(
                    'icon' => Horde_Themes::img('compose.png'),
                    'url' => $clink->link()->setRaw(true)
                )
            ));
        }

        $imp_imap = $injector->getInstance('IMP_Factory_Imap')->create();
        if ($imp_imap->access(IMP_Imap::ACCESS_SEARCH)) {
            $onclick = null;
            switch ($registry->getView()) {
            case $registry::VIEW_DYNAMIC:
                $url = Horde::url('dynamic.php', true)
                    ->add('page', 'mailbox')
                    ->setAnchor('search');
                $onclick = 'if (window.ImpBase) { ImpBase.go(\'search\') }';
                break;

            default:
                $url = IMP_Basic_Search::url(array('full' => true));
                break;
            }

            $tree->addNode(array(
                'id' => strval($parent) . 'search',
                'parent' => $parent,
                'label' => _("Search"),
                'expanded' => false,
                'params' => array(
                    'icon' => Horde_Themes::img('search.png'),
                    'url' => $url,
                    'onclick' => $onclick,
                )
            ));
        }
    }

    /* Language change callback. */

    /**
     */
    public function changeLanguage()
    {
        global $injector;

        $injector->getInstance('IMP_Mailbox_SessionCache')->expire(array(
            IMP_Mailbox_SessionCache::CACHE_DISPLAY,
            IMP_Mailbox_SessionCache::CACHE_LABEL
        ));

        $injector->getInstance('IMP_Ftree')->init();
    }

    /* NoSQL methods. */

    /**
     */
    public function nosqlDrivers()
    {
        global $injector;

        $backends = array(
            'Horde_Imap_Client_Cache_Backend_Mongo' => function() use ($injector) {
                $backend = $injector
                    ->getInstance('IMP_Factory_Imap')
                    ->create()
                    ->config
                    ->cache_params['backend'];
                if (isset($backend->backend)) {
                    return $backend->backend;
                }
            },
            'IMP_Sentmail_Mongo' => function() use ($injector) {
                return $injector->getInstance('IMP_Sentmail');
            },
        );
        $out = array();

        foreach ($backends as $key => $func) {
            try {
                $val = $func();
                if ($val instanceof $key) {
                    $out[] = $val;
                }
            } catch (Horde_Exception $e) {}
        }

        return $out;
    }

    /* Download data. */

    /**
     * URL parameters:
     *   - actionID
     *
     * @throws IMP_Exception
     */
    public function download(Horde_Variables $vars)
    {
        global $injector, $registry;

        /* Check for an authenticated user. */
        if (!$registry->isAuthenticated(array('app' => 'imp'))) {
            $e = new IMP_Exception(_("User is not authenticated."));
            $e->logged = true;
            throw $e;
        }

        switch ($vars->actionID) {
        case 'download_all':
            $view_ob = new IMP_Contents_View(new IMP_Indices_Mailbox($vars));
            $view_ob->checkToken($vars);
            return $view_ob->downloadAll();

        case 'download_attach':
            $view_ob = new IMP_Contents_View(new IMP_Indices_Mailbox($vars));
            $view_ob->checkToken($vars);
            return $view_ob->downloadAttach($vars->id);

        case 'download_mbox':
            $mlist = IMP_Mailbox::formFrom($vars->mbox_list);
            $mbox = $injector->getInstance('IMP_Mbox_Generate')->generate($mlist);
            $name = is_array($mlist)
                ? reset($mlist)
                : $mlist;

            switch ($vars->type) {
            case 'mbox':
                return array(
                    'data' => $mbox,
                    'name' => $name . '.mbox',
                    'type' => 'text/plain; charset=UTF-8'
                );

            case 'mboxzip':
                try {
                    $data = Horde_Compress::factory('Zip')->compress(array(
                        array(
                            'data' => $mbox,
                            'name' => $name . '.mbox'
                        )
                    ), array(
                        'stream' => true
                    ));
                    fclose($mbox);
                } catch (Horde_Exception $e) {
                    fclose($mbox);
                    throw $e;
                }

                return array(
                    'data' => $data,
                    'name' => $name . '.zip',
                    'type' => 'application/zip'
                );
            }
            break;

        case 'download_render':
            $view_ob = new IMP_Contents_View(new IMP_Indices_Mailbox($vars));
            $view_ob->checkToken($vars);
            return $view_ob->downloadRender($vars->id, $vars->mode, $vars->ctype);

        case 'save_message':
            $view_ob = new IMP_Contents_View(new IMP_Indices_Mailbox($vars));
            return $view_ob->saveMessage();
        }

        return array();
    }

    /* Exception handler. */

    /**
     */
    public function exceptionHandler(Exception $e)
    {
        if ($e instanceof Horde_Imap_Client_Exception) {
            $e = new Horde_Exception_AuthenticationFailure(
                $e->getMessage(),
                Horde_Auth::REASON_MESSAGE
            );
        }

        Horde_ErrorHandler::fatal($e);
    }

}
