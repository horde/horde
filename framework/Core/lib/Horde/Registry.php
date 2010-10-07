<?php
/**
 * The Horde_Registry:: class provides a set of methods for communication
 * between Horde applications and keeping track of application
 * configuration information.
 *
 * Copyright 1999-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @author   Jon Parise <jon@horde.org>
 * @author   Anil Madhavapeddy <anil@recoil.org>
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @package  Core
 */
class Horde_Registry
{
    /* Session flags. */
    const SESSION_NONE = 1;
    const SESSION_READONLY = 2;

    /* Error codes for pushApp(). */
    const AUTH_FAILURE = 1;
    const NOT_ACTIVE = 2;
    const PERMISSION_DENIED = 3;
    const HOOK_FATAL = 4;

    /**
     * Cached information.
     *
     * @var array
     */
    protected $_cache = array();

    /**
     * NLS cached information.
     *
     * @var array
     */
    protected $_nlscache = array();

    /**
     * The last modified time of the newest modified registry file.
     *
     * @var integer
     */
    protected $_regmtime;

    /**
     * Indicate that a new session ID has been generated for this page load.
     *
     * @var boolean
     */
    protected $_cleansession = false;

    /**
     * Stack of in-use applications.
     *
     * @var array
     */
    protected $_appStack = array();

    /**
     * The list of APIs.
     *
     * @param array
     */
    protected $_apis = array();

    /**
     * Hash storing information on each registry-aware application.
     *
     * @var array
     */
    public $applications = array();

    /**
     * The application that called appInit().
     *
     * @var string
     */
    public $initialApp;

    /**
     * NLS configuration.
     *
     * @var array
     */
    public $nlsconfig = array();

    /**
     * The session handler object.
     *
     * @var Horde_SessionHandler
     */
    public $sessionHandler = null;

    /**
     * Application bootstrap initialization.
     * Solves chicken-and-egg problem - need a way to init Horde environment
     * from application without an active Horde_Registry object.
     *
     * Page compression will be started (if configured).
     * init() will be called after the initialization is completed.
     *
     * Global variables defined:
     *   $browser - Horde_Browser object
     *   $cli - Horde_Cli object (if 'cli' is true)
     *   $conf - Configuration array
     *   $injector - Horde_Injector object
     *   $language - Language
     *   $notification - Horde_Notification object
     *   $prefs - Horde_Prefs object
     *   $registry - Horde_Registry object
     *
     * @param string $app  The application to initialize.
     * @param array $args  Optional arguments:
     * <pre>
     * 'admin' - (boolean) Require authenticated user to be an admin?
     *           DEFAULT: false
     * 'authentication' - (string) The type of authentication to use:
     *   'none'  - Do not authenticate
     *   'throw' - Authenticate; on no auth, throw a Horde_Exception
     *   [DEFAULT] - Authenticate; on no auth redirect to login screen
     * 'cli' - (boolean) Initialize a CLI interface.
     *         DEFAULT: false
     * 'nocompress' - (boolean) If set, the page will not be compressed.
     *                DEFAULT: false
     * 'nologintasks' - (boolean) If set, don't perform logintasks (never
     *                  performed if authentication is 'none').
     *                  DEFAULT: false
     * 'session_cache_limiter' - (string) Use this value for the session cache
     *                           limiter.
     *                           DEFAULT: Uses the value in the configuration.
     * 'session_control' - (string) Sets special session control limitations:
     *   'netscape' - TODO; start read/write session
     *   'none' - Do not start a session
     *   'readonly' - Start session readonly
     *   [DEFAULT] - Start read/write session
     * 'timezone' - (boolean) Set the time zone?
     *              DEFAULT: false
     * 'user_admin' - (boolean) Set authentication to an admin user?
     *                DEFAULT: false
     * </pre>
     *
     * @return Horde_Registry_Application  The application object.
     * @throws Horde_Exception
     */
    static public function appInit($app, $args = array())
    {
        if (isset($GLOBALS['registry'])) {
            $appOb = $GLOBALS['registry']->getApiInstance($app, 'application');
            $appOb->init();
            return $appOb;
        }

        $args = array_merge(array(
            'admin' => false,
            'authentication' => null,
            'cli' => null,
            'nocompress' => false,
            'nologintasks' => false,
            'session_cache_limiter' => null,
            'session_control' => null,
            'timezone' => false,
            'user_admin' => null
        ), $args);

        /* CLI initialization. */
        if ($args['cli']) {
            /* Make sure no one runs from the web. */
            if (!Horde_Cli::runningFromCLI()) {
                throw new Horde_Exception('Script must be run from the command line');
            }

            /* Load the CLI environment - make sure there's no time limit,
             * init some variables, etc. */
            $GLOBALS['cli'] = Horde_Cli::init();

            $args['nocompress'] = true;
        }

        // Registry.
        $s_ctrl = 0;
        switch ($args['session_control']) {
        case 'netscape':
            // Chicken/egg: Browser object doesn't exist yet.
            $browser = new Horde_Browser();
            if ($browser->isBrowser('mozilla')) {
                session_cache_limiter('private, must-revalidate');
            }
            break;

        case 'none':
            $s_ctrl = self::SESSION_NONE;
            break;

        case 'readonly':
            $s_ctrl = self::SESSION_READONLY;
            break;
        }

        $classname = __CLASS__;
        $registry = $GLOBALS['registry'] = new $classname($s_ctrl);

        $appob = $registry->getApiInstance($app, 'application');
        $appob->initParams = $args;

        try {
            $registry->pushApp($app, array('check_perms' => ($args['authentication'] != 'none'), 'logintasks' => !$args['nologintasks'], 'notransparent' => !empty($args['notransparent'])));

            if ($args['admin'] && !$registry->isAdmin()) {
                throw new Horde_Exception('Not an admin');
            }
        } catch (Horde_Exception $e) {
            $appob->appInitFailure($e);

            if ($args['authentication'] == 'throw') {
                throw $e;
            }

            $registry->authenticateFailure($app, $e);
        }

        $registry->initialApp = $app;

        if ($args['timezone']) {
            $registry->setTimeZone();
        }

        if (!$args['nocompress']) {
            Horde::compressOutput();
        }

        if ($args['user_admin']) {
            if (empty($GLOBALS['conf']['auth']['admins'])) {
                throw new Horde_Exception('No admin users defined in configuration.');
            }
            $registry->setAuth(reset($GLOBALS['conf']['auth']['admins']), array());
        }

        $appob->init();

        return $appob;
    }

    /**
     * Create a new Horde_Registry instance.
     *
     * @param integer $session_flags  Any session flags.
     *
     * @throws Horde_Exception
     */
    public function __construct($session_flags = 0)
    {
        /* Define autoloader callbacks. */
        $callbacks = array(
            'Horde_Mime' => 'Horde_Core_Autoloader_Callback_Mime',
            'Horde_Nls' => 'Horde_Core_Autoloader_Callback_Nls'
        );

        /* Define binders. */
        $binders = array(
            'Horde_Auth_Factory' => new Horde_Core_Binder_AuthFactory(),
            'Horde_Core_Auth_Signup' => new Horde_Core_Binder_AuthSignup(),
            'Horde_Crypt' => new Horde_Core_Binder_Crypt(),
            'Horde_Data' => new Horde_Core_Binder_Data(),
            'Horde_Db' => new Horde_Core_Binder_Db(),
            'Horde_Db_Adapter' => new Horde_Core_Binder_DbBase(),
            'Horde_Db_Pear' => new Horde_Core_Binder_DbPear(),
            'Horde_History' => new Horde_Core_Binder_History(),
            'Horde_Http_Client' => new Horde_Core_Binder_HttpClient(),
            'Horde_Log_Logger' => new Horde_Core_Binder_Logger(),
            'Horde_Mail' => new Horde_Core_Binder_Mail(),
            'Horde_Mime_Viewer' => new Horde_Core_Binder_MimeViewer(),
            'Horde_Prefs_Identity' => new Horde_Core_Binder_Identity(),
            'Horde_Share_Factory' => new Horde_Core_Binder_ShareFactory(),
            'Horde_Template' => new Horde_Core_Binder_Template(),
            'Horde_Text_Filter' => new Horde_Core_Binder_TextFilter(),
            'Horde_Tree' => new Horde_Core_Binder_Tree(),
            'Horde_Token' => new Horde_Core_Binder_Token(),
            'Horde_Vfs' => new Horde_Core_Binder_Vfs(),
        );

        /* Define factories. */
        $factories = array(
            'Horde_Alarm' => array(
                'Horde_Core_Factory_Alarm',
                'create'
            ),
            'Horde_Cache' => array(
                'Horde_Core_Factory_Cache',
                'create',
            ),
            'Horde_Cache_Session' => array(
                'Horde_Core_Factory_Cache',
                'createSession',
            ),
            'Horde_Controller_Request' => array(
                'Horde_Core_Factory_Request',
                'create',
            ),
            'Horde_Controller_RequestConfiguration' => array(
                'Horde_Core_Controller_RequestMapper',
                'getRequestConfiguration',
            ),
            'Horde_Editor' => array(
                'Horde_Core_Factory_Editor',
                'create',
            ),
            'Horde_Service_Facebook' => array(
                'Horde_Core_Factory_Facebook',
                'create',
            ),
            'Horde_Kolab_Server_Composite' => array(
                'Horde_Core_Factory_KolabServer',
                'getComposite',
            ),
            'Horde_Kolab_Session' => array(
                'Horde_Core_Factory_KolabSession',
                'getSession',
            ),
            'Horde_Kolab_Storage' => array(
                'Horde_Core_Factory_KolabStorage',
                'getStorage',
            ),
            'Horde_Lock' => array(
                'Horde_Core_Factory_Lock',
                'create',
            ),
            'Horde_Memcache' => array(
                'Horde_Core_Factory_Memcache',
                'create',
            ),
            'Horde_Notification' => array(
                'Horde_Core_Factory_Notification',
                'create',
            ),
            'Horde_Perms' => array(
                'Horde_Core_Factory_Perms',
                'create',
            ),
            'Horde_Secret' => array(
                'Horde_Core_Factory_Secret',
                'create',
            ),
            'Horde_Service_Twitter' => array(
                'Horde_Core_Factory_Twitter',
                'create',
            ),
            'Horde_View' => array(
                'Horde_Core_Factory_View',
                'create',
            ),
            'Horde_View_Base' => array(
                'Horde_Core_Factory_View',
                'create',
            ),
            'Net_DNS_Resolver' => array(
                'Horde_Core_Factory_Dns',
                'create',
            ),
        );

        /* Define implementations. */
        $implementations = array(
            'Horde_Controller_ResponseWriter' => 'Horde_Controller_ResponseWriter_Web',
        );

        /* Setup injector. */
        $GLOBALS['injector'] = $injector = new Horde_Injector(new Horde_Injector_TopLevel());

        foreach ($binders as $key => $val) {
            $injector->addBinder($key, $val);
        }
        foreach ($factories as $key => $val) {
            $injector->bindFactory($key, $val[0], $val[1]);
        }
        foreach ($implementations as $key => $val) {
            $injector->bindImplementation($key, $val);
        }

        $GLOBALS['registry'] = $this;
        $injector->setInstance('Horde_Registry', $this);

        /* Setup autoloader instance and callbacks.
         * $__autoloader is defined in horde/lib/core.php */
        $injector->setInstance('Horde_Autoloader', $GLOBALS['__autoloader']);
        foreach ($callbacks as $key => $val) {
            $GLOBALS['__autoloader']->addCallback($key, array($val, 'callback'));
        }

        /* Initialize browser object. */
        $GLOBALS['browser'] = $injector->getInstance('Horde_Browser');

        /* Import and global Horde's configuration values. Almost a chicken
         * and egg issue - since loadConfiguration() uses registry in certain
         * instances. However, if HORDE_BASE is defined, and app is
         * 'horde', registry is not used in the method so we are free to
         * call it here (prevents us from duplicating a bunch of code). */
        $this->_cache['conf-horde'] = Horde::loadConfiguration('conf.php', 'conf', 'horde');
        $conf = $GLOBALS['conf'] = &$this->_cache['conf-horde'];

        /* Initial Horde-wide settings. */

        /* Set the maximum execution time in accordance with the config
         * settings. */
        error_reporting(0);
        set_time_limit($conf['max_exec_time']);

        /* Set the error reporting level in accordance with the config
         * settings. */
        error_reporting($conf['debug_level']);

        /* Set the umask according to config settings. */
        if (isset($conf['umask'])) {
            umask($conf['umask']);
        }

        $vhost = null;
        if (!empty($conf['vhosts'])) {
            $vhost = HORDE_BASE . '/config/registry-' . $conf['server']['name'] . '.php';
            if (file_exists($vhost)) {
                $this->_regmtime = max($this->_regmtime, filemtime($vhost));
            } else {
                $vhost = null;
            }
        }

        /* Start a session. */
        if ($session_flags & self::SESSION_NONE ||
            (PHP_SAPI == 'cli') ||
            (((PHP_SAPI == 'cgi') || (PHP_SAPI == 'cgi-fcgi')) &&
             empty($_SERVER['SERVER_NAME']))) {
            /* Never start a session if the session flags include
               SESSION_NONE. */
            $_SESSION = array();
            $this->setupSessionHandler(false);
        } else {
            $this->setupSessionHandler();
            if ($session_flags & self::SESSION_READONLY) {
                /* Close the session immediately so no changes can be
                   made but values are still available. */
                session_write_close();
            }
        }

        /* Always need to load applications information. */
        $this->_loadApplicationsCache($vhost);

        /* Load the language configuration. */
        $this->nlsconfig = Horde::loadConfiguration('nls.php', 'horde_nls_config', 'horde');

        /* Initialize the localization routines and variables. */
        $this->setLanguageEnvironment(null, 'horde');

        $this->_regmtime = max(filemtime(HORDE_BASE . '/config/registry.php'),
                               filemtime(HORDE_BASE . '/config/registry.d'));

        /* Stop system if Horde is inactive. */
        if ($this->applications['horde']['status'] == 'inactive') {
            throw new Horde_Exception(_("This system is currently deactivated."));
        }

        /* Initialize notification object. Always attach status listener by
         * default. Default status listener can be overriden through the
         * $_SESSION['horde_notification']['override'] variable. */
        $GLOBALS['notification'] = $injector->getInstance('Horde_Notification');
        if (Horde_Util::getFormData('ajaxui') &&
            isset($_SESSION['horde_notification']['override'])) {
            require_once $_SESSION['horde_notification']['override'][0];
            $GLOBALS['notification']->attach('status', null, $_SESSION['horde_notification']['override'][1]);
        } else {
            $GLOBALS['notification']->attach('status');
        }

        register_shutdown_function(array($this, 'shutdown'));
    }

    /**
     * Events to do on shutdown.
     */
    public function shutdown()
    {
        /* Register access key logger for translators. */
        if (!empty($GLOBALS['conf']['log_accesskeys'])) {
            Horde::getAccessKey(null, null, true);
        }

        /* Register memory tracker if logging in debug mode. */
        if (function_exists('memory_get_peak_usage')) {
            Horde::logMessage('Max memory usage: ' . memory_get_peak_usage(true) . ' bytes', 'DEBUG');
        }
    }

    /**
     * TODO
     */
    public function __get($api)
    {
        if (in_array($api, $this->listAPIs())) {
            return new Horde_Registry_Caller($this, $api);
        }
    }

    /**
     * Clone should never be called on this object. If it is, die.
     *
     * @throws Horde_Exception
     */
    public function __clone()
    {
        throw new Horde_Exception('Horde_Registry objects should never be cloned.');
    }

    /**
     * serialize() should never be called on this object. If it is, die.
     *
     * @throws Horde_Exception
     */
    public function __sleep()
    {
        throw new Horde_Exception('Horde_Registry objects should never be serialized.');
    }

    /**
     * Clear the registry cache.
     */
    public function clearCache()
    {
        unset($_SESSION['_registry']);
        $this->_saveCacheVar('api', true);
        $this->_saveCacheVar('appcache', true);
    }

    /**
     * Fills the registry's application cache with application information.
     *
     * @param string $vhost  TODO
     */
    protected function _loadApplicationsCache($vhost)
    {
        /* First, try to load from cache. */
        if ($this->_loadCacheVar('appcache')) {
            $this->applications = $this->_cache['appcache'][0];
            $this->_cache['interfaces'] = $this->_cache['appcache'][1];
            return;
        }

        $this->_cache['interfaces'] = array();

        /* Read the registry configuration files. */
        if (!file_exists(HORDE_BASE . '/config/registry.php')) {
            throw new Horde_Exception('Missing registry.php configuration file');
        }
        require HORDE_BASE . '/config/registry.php';
        $files = glob(HORDE_BASE . '/config/registry.d/*.php');
        foreach ($files as $r) {
            include $r;
        }

        if ($vhost) {
            include $vhost;
        }

        /* Scan for all APIs provided by each app, and set other common
         * defaults like templates and graphics. */
        foreach (array_keys($this->applications) as $appName) {
            $app = &$this->applications[$appName];
            if ($app['status'] == 'heading') {
                continue;
            }

            if (isset($app['fileroot'])) {
                $app['fileroot'] = rtrim($app['fileroot'], ' /');
                if (!file_exists($app['fileroot']) ||
                    (file_exists($app['fileroot'] . '/config/conf.xml') &&
                    !file_exists($app['fileroot'] . '/config/conf.php'))) {
                    $app['status'] = 'inactive';
                    Horde::logMessage('Setting ' . $appName . ' inactive because the fileroot does not exist or the application is not configured yet.', 'DEBUG');
                }
            }

            if (isset($app['webroot'])) {
                $app['webroot'] = rtrim($app['webroot'], ' /');
            }

            if (($app['status'] != 'inactive') &&
                isset($app['provides']) &&
                (($app['status'] != 'admin') || $this->isAdmin())) {
                if (is_array($app['provides'])) {
                    foreach ($app['provides'] as $interface) {
                        $this->_cache['interfaces'][$interface] = $appName;
                    }
                } else {
                    $this->_cache['interfaces'][$app['provides']] = $appName;
                }
            }

            if (!isset($app['templates']) && isset($app['fileroot'])) {
                $app['templates'] = $app['fileroot'] . '/templates';
            }
            if (!isset($app['jsuri']) && isset($app['webroot'])) {
                $app['jsuri'] = $app['webroot'] . '/js';
            }
            if (!isset($app['jsfs']) && isset($app['fileroot'])) {
                $app['jsfs'] = $app['fileroot'] . '/js';
            }
            if (!isset($app['themesuri']) && isset($app['webroot'])) {
                $app['themesuri'] = $app['webroot'] . '/themes';
            }
            if (!isset($app['themesfs']) && isset($app['fileroot'])) {
                $app['themesfs'] = $app['fileroot'] . '/themes';
            }
        }

        $this->_cache['appcache'] = array(
            // Index 0
            $this->applications,
            // Index 1
            $this->_cache['interfaces']
        );
        $this->_saveCacheVar('appcache');
    }

    /**
     * Fills the registry's API cache with the available external services.
     *
     * @throws Horde_Exception
     */
    protected function _loadApiCache()
    {
        /* First, try to load from cache. */
        if ($this->_loadCacheVar('api')) {
            return;
        }

        /* Generate api/type cache. */
        $status = array('active', 'notoolbar', 'hidden');
        if ($this->isAdmin()) {
            $status[] = 'admin';
        }

        $this->_cache['api'] = array();

        foreach (array_keys($this->applications) as $app) {
            if (in_array($this->applications[$app]['status'], $status)) {
                try {
                    $api = $this->getApiInstance($app, 'api');
                    $this->_cache['api'][$app] = array(
                        'api' => array_diff(get_class_methods($api), array('__construct'), $api->disabled),
                        'links' => $api->links,
                        'noperms' => $api->noPerms
                    );
                } catch (Horde_Exception $e) {
                    Horde::logMessage($e, 'DEBUG');
                }
            }
        }

        $this->_saveCacheVar('api');
    }

    /**
     * Retrieve an API object.
     *
     * @param string $app   The application to load.
     * @param string $type  Either 'application' or 'api'.
     *
     * @return Horde_Registry_Api|Horde_Registry_Application  The API object.
     * @throws Horde_Exception
     */
    public function getApiInstance($app, $type)
    {
        if (isset($this->_cache['ob'][$app][$type])) {
            return $this->_cache['ob'][$app][$type];
        }

        $cname = Horde_String::ucfirst($type);

        /* Can't autoload here, since the application may not have been
         * initialized yet. */
        $classname = Horde_String::ucfirst($app) . '_' . $cname;
        $path = $this->get('fileroot', $app) . '/lib/' . $cname . '.php';
        if (file_exists($path)) {
            include_once $path;
        } else {
            $classname = 'Horde_Registry_' . $cname;
        }

        if (!class_exists($classname, false)) {
            throw new Horde_Exception("$app does not have an API");
        }

        $this->_cache['ob'][$app][$type] = new $classname();
        return $this->_cache['ob'][$app][$type];
    }

    /**
     * Return a list of the installed and registered applications.
     *
     * @param array $filter   An array of the statuses that should be
     *                        returned. Defaults to non-hidden.
     * @param boolean $assoc  Associative array with app names as keys and
     *                        config parameters as values.
     * @param integer $perms  The permission level to check for in the list.
     *                        If null, skips permission check.
     *
     * @return array  List of apps registered with Horde. If no
     *                applications are defined returns an empty array.
     */
    public function listApps($filter = null, $assoc = false,
                             $perms = Horde_Perms::SHOW)
    {
        if (is_null($filter)) {
            $filter = array('notoolbar', 'active');
        }

        $apps = array();
        foreach ($this->applications as $app => $params) {
            if (in_array($params['status'], $filter) &&
                (is_null($perms) || $this->hasPermission($app, $perms))) {
                $apps[$app] = $params;
            }
        }

        return $assoc ? $apps : array_keys($apps);
    }

    /**
     * Return a list of all applications, ignoring permissions.
     *
     * @return array  List of all apps registered with Horde.
     */
    public function listAllApps($filter = null)
    {
        // Default to all installed (but possibly not configured) applications.
        if (is_null($filter)) {
            $filter = array(
                'active', 'admin', 'hidden', 'inactive', 'notoolbar'
            );
        }

        return $this->listApps($filter, false, null);
    }

    /**
     * Returns all available registry APIs.
     *
     * @return array  The API list.
     */
    public function listAPIs()
    {
        if (empty($this->_apis)) {
            if (!empty($this->_cache['interfaces'])) {
                foreach (array_keys($this->_cache['interfaces']) as $interface) {
                    list($api,) = explode('/', $interface, 2);
                    $this->_apis[$api] = true;
                }
            }
        }

        return array_keys($this->_apis);
    }

    /**
     * Returns all of the available registry methods, or alternately
     * only those for a specified API.
     *
     * @param string $api  Defines the API for which the methods shall be
     *                     returned.
     *
     * @return array  The method list.
     */
    public function listMethods($api = null)
    {
        $methods = array();

        $this->_loadApiCache();

        foreach (array_keys($this->applications) as $app) {
            if (isset($this->applications[$app]['provides'])) {
                $provides = $this->applications[$app]['provides'];
                if (!is_array($provides)) {
                    $provides = array($provides);
                }
                foreach ($provides as $method) {
                    if (strpos($method, '/') !== false) {
                        if (is_null($api) ||
                            (substr($method, 0, strlen($api)) == $api)) {
                            $methods[$method] = true;
                        }
                    } elseif (is_null($api) || ($method == $api)) {
                        if (isset($this->_cache['api'][$app])) {
                            foreach ($this->_cache['api'][$app]['api'] as $service) {
                                $methods[$method . '/' . $service] = true;
                            }
                        }
                    }
                }
            }
        }

        return array_keys($methods);
    }

    /**
     * Determine if an interface is implemented by an active application.
     *
     * @param string $interface  The interface to check for.
     *
     * @return mixed  The application implementing $interface if we have it,
     *                false if the interface is not implemented.
     */
    public function hasInterface($interface)
    {
        return !empty($this->_cache['interfaces'][$interface]) ?
            $this->_cache['interfaces'][$interface] :
            false;
    }

    /**
     * Determine if a method has been registered with the registry.
     *
     * @param string $method  The full name of the method to check for.
     * @param string $app     Only check this application.
     *
     * @return mixed  The application implementing $method if we have it,
     *                false if the method doesn't exist.
     */
    public function hasMethod($method, $app = null)
    {
        if (is_null($app)) {
            list($interface, $call) = explode('/', $method, 2);
            if (!empty($this->_cache['interfaces'][$method])) {
                $app = $this->_cache['interfaces'][$method];
            } elseif (!empty($this->_cache['interfaces'][$interface])) {
                $app = $this->_cache['interfaces'][$interface];
            } else {
                return false;
            }
        } else {
            $call = $method;
        }

        $this->_loadApiCache();

        return (isset($this->_cache['api'][$app]) && in_array($call, $this->_cache['api'][$app]['api']))
            ? $app
            : false;
    }

    /**
     * Determine if an application method exists for a given application.
     *
     * @param string $app     The application name.
     * @param string $method  The full name of the method to check for.
     *
     * @return boolean  Existence of the method.
     */
    public function hasAppMethod($app, $method)
    {
        try {
            $appob = $this->getApiInstance($app, 'application');
        } catch (Horde_Exception $e) {
            return false;
        }
        return (method_exists($appob, $method) && !in_array($method, $appob->disabled));
    }

    /**
     * Return the hook corresponding to the default package that
     * provides the functionality requested by the $method
     * parameter. $method is a string consisting of
     * "packagetype/methodname".
     *
     * @param string $method  The method to call.
     * @param array $args     Arguments to the method.
     *
     * @return mixed  TODO
     * @throws Horde_Exception
     */
    public function call($method, $args = array())
    {
        list($interface, $call) = explode('/', $method, 2);

        if (!empty($this->_cache['interfaces'][$method])) {
            $app = $this->_cache['interfaces'][$method];
        } elseif (!empty($this->_cache['interfaces'][$interface])) {
            $app = $this->_cache['interfaces'][$interface];
        } else {
            throw new Horde_Exception('The method "' . $method . '" is not defined in the Horde Registry.');
        }

        return $this->callByPackage($app, $call, $args);
    }

    /**
     * Output the hook corresponding to the specific package named.
     *
     * @param string $app     The application being called.
     * @param string $call    The method to call.
     * @param array $args     Arguments to the method.
     * @param array $options  Additional options:
     * <pre>
     * 'noperms' - (boolean) If true, don't check the perms.
     * </pre>
     *
     * @return mixed  TODO
     * @throws Horde_Exception
     */
    public function callByPackage($app, $call, $args = array(),
                                  $options = array())
    {
        /* Note: calling hasMethod() makes sure that we've cached
         * $app's services and included the API file, so we don't try
         * to do it again explicitly in this method. */
        if (!$this->hasMethod($call, $app)) {
            throw new Horde_Exception(sprintf('The method "%s" is not defined in the API for %s.', $call, $app));
        }

        /* Load the API now. */
        $api = $this->getApiInstance($app, 'api');

        /* Make sure that the function actually exists. */
        if (!method_exists($api, $call)) {
            throw new Horde_Exception('The function implementing ' . $call . ' is not defined in ' . $app . '\'s API.');
        }

        /* Switch application contexts now, if necessary, before
         * including any files which might do it for us. Return an
         * error immediately if pushApp() fails. */
        $pushed = $this->pushApp($app, array('check_perms' => !in_array($call, $this->_cache['api'][$app]['noperms']) && empty($options['noperms'])));

        try {
            $result = call_user_func_array(array($api, $call), $args);
            if ($result instanceof PEAR_Error) {
                throw new Horde_Exception_Prior($result);
            }
        } catch (Horde_Exception $e) {
            $result = $e;
        }

        /* If we changed application context in the course of this
         * call, undo that change now. */
        if ($pushed === true) {
            $this->popApp();
        }

        if ($result instanceof Horde_Exception) {
            throw $e;
        }

        return $result;
    }

    /**
     * Call a private Horde application method.
     *
     * @param string $app     The application name.
     * @param string $call    The method to call.
     * @param array $options  Additional options:
     * <pre>
     * 'args' - (array) Additional parameters to pass to the method.
     * 'noperms' - (boolean) If true, don't check the perms.
     * </pre>
     *
     * @return mixed  Various. Returns null if the method doesn't exist.
     * @throws Horde_Exception  Application methods should throw this if there
     *                          is a fatal error.
     */
    public function callAppMethod($app, $call, $options = array())
    {
        /* Make sure that the method actually exists. */
        if (!$this->hasAppMethod($app, $call)) {
            return null;
        }

        /* Load the API now. */
        $api = $this->getApiInstance($app, 'application');

        /* Switch application contexts now, if necessary, before
         * including any files which might do it for us. Return an
         * error immediately if pushApp() fails. */
        $pushed = $this->pushApp($app, array('check_perms' => empty($options['noperms'])));

        try {
            $result = call_user_func_array(array($api, $call), empty($options['args']) ? array() : $options['args']);
        } catch (Horde_Exception $e) {
            $result = $e;
        }

        /* If we changed application context in the course of this
         * call, undo that change now. */
        if ($pushed === true) {
            $this->popApp();
        }

        if ($result instanceof Exception) {
            throw $e;
        }

        return $result;
    }

    /**
     * Return the hook corresponding to the default package that
     * provides the functionality requested by the $method
     * parameter. $method is a string consisting of
     * "packagetype/methodname".
     *
     * @param string $method  The method to link to.
     * @param array $args     Arguments to the method.
     * @param mixed $extra    Extra, non-standard arguments to the method.
     *
     * @return mixed  TODO
     * @throws Horde_Exception
     */
    public function link($method, $args = array(), $extra = '')
    {
        list($interface, $call) = explode('/', $method, 2);

        if (!empty($this->_cache['interfaces'][$method])) {
            $app = $this->_cache['interfaces'][$method];
        } elseif (!empty($this->_cache['interfaces'][$interface])) {
            $app = $this->_cache['interfaces'][$interface];
        } else {
            throw new Horde_Exception('The method "' . $method . '" is not defined in the Horde Registry.');
        }

        return $this->linkByPackage($app, $call, $args, $extra);
    }

    /**
     * Output the hook corresponding to the specific package named.
     *
     * @param string $app   The application being called.
     * @param string $call  The method to link to.
     * @param array $args   Arguments to the method.
     * @param mixed $extra  Extra, non-standard arguments to the method.
     *
     * @return mixed  TODO
     * @throws Horde_Exception
     */
    public function linkByPackage($app, $call, $args = array(), $extra = '')
    {
        /* Make sure the link is defined. */
        $this->_loadApiCache();
        if (empty($this->_cache['api'][$app]['links'][$call])) {
            throw new Horde_Exception('The link ' . $call . ' is not defined in ' . $app . '\'s API.');
        }

        /* Initial link value. */
        $link = $this->_cache['api'][$app]['links'][$call];

        /* Fill in html-encoded arguments. */
        foreach ($args as $key => $val) {
            $link = str_replace('%' . $key . '%', htmlentities($val), $link);
        }
        if (isset($this->applications[$app]['webroot'])) {
            $link = str_replace('%application%', $this->get('webroot', $app), $link);
        }

        /* Replace htmlencoded arguments that haven't been specified with
           an empty string (this is where the default would be substituted
           in a stricter registry implementation). */
        $link = preg_replace('|%.+%|U', '', $link);

        /* Fill in urlencoded arguments. */
        foreach ($args as $key => $val) {
            $link = str_replace('|' . Horde_String::lower($key) . '|', urlencode($val), $link);
        }

        /* Append any extra, non-standard arguments. */
        if (is_array($extra)) {
            $extra_args = '';
            foreach ($extra as $key => $val) {
                $extra_args .= '&' . urlencode($key) . '=' . urlencode($val);
            }
        } else {
            $extra_args = $extra;
        }
        $link = str_replace('|extra|', $extra_args, $link);

        /* Replace html-encoded arguments that haven't been specified with
           an empty string (this is where the default would be substituted
           in a stricter registry implementation). */
        $link = preg_replace('|\|.+\||U', '', $link);

        return $link;
    }

    /**
     * Replace any %application% strings with the filesystem path to the
     * application.
     *
     * @param string $path  The application string.
     * @param string $app   The application being called.
     *
     * @return string  The application file path.
     * @throws Horde_Exception
     */
    public function applicationFilePath($path, $app = null)
    {
        if (is_null($app)) {
            $app = $this->getApp();
        }

        if (!isset($this->applications[$app])) {
            throw new Horde_Exception(sprintf(_("\"%s\" is not configured in the Horde Registry."), $app));
        }

        return str_replace('%application%', $this->applications[$app]['fileroot'], $path);
    }

    /**
     * Replace any %application% strings with the web path to the application.
     *
     * @param string $path  The application string.
     * @param string $app   The application being called.
     *
     * @return string  The application web path.
     */
    public function applicationWebPath($path, $app = null)
    {
        if (!isset($app)) {
            $app = $this->getApp();
        }

        return str_replace('%application%', $this->applications[$app]['webroot'], $path);
    }

    /**
     * Set the current application, adding it to the top of the Horde
     * application stack. If this is the first application to be
     * pushed, retrieve session information as well.
     *
     * pushApp() also reads the application's configuration file and
     * sets up its global $conf hash.
     *
     * @param string $app          The name of the application to push.
     * @param array $options       Additional options:
     * <pre>
     * 'check_perms' - (boolean) Make sure that the current user has
     *                 permissions to the application being loaded. Should
     *                 ONLY be disabled by system scripts (cron jobs, etc.)
     *                 and scripts that handle login.
     *                 DEFAULT: true
     * 'noinit' - (boolean) Do not init the application.
     *            DEFAULT: false
     * 'logintasks' - (boolean) Perform login tasks? Only performed if
     *                'check_perms' is also true. System tasks are always
     *                peformed if the user is authorized.
     *                DEFAULT: false
     * </pre>
     *
     * @return boolean  Whether or not the _appStack was modified.
     * @throws Horde_Exception
     *         Code can be one of the following:
     *         Horde_Registry::AUTH_FAILURE
     *         Horde_Registry::NOT_ACTIVE
     *         Horde_Registry::PERMISSION_DENIED
     *         Horde_Registry::HOOK_FATAL
     */
    public function pushApp($app, $options = array())
    {
        if ($app == $this->getApp()) {
            return false;
        }

        /* Bail out if application is not present or inactive. */
        if (!isset($this->applications[$app]) ||
            $this->applications[$app]['status'] == 'inactive' ||
            ($this->applications[$app]['status'] == 'admin' && !$this->isAdmin())) {
            throw new Horde_Exception($app . ' is not activated.', self::NOT_ACTIVE);
        }

        /* Set up autoload paths for the current application. This needs to
         * be done here because it is possible to try to load app-specific
         * libraries from other applications. */
        $app_lib = $this->get('fileroot', $app) . '/lib';
        $autoloader = $GLOBALS['injector']->getInstance('Horde_Autoloader');
        $autoloader->addClassPathMapper(new Horde_Autoloader_ClassPathMapper_Prefix('/^' . $app . '(?:$|_)/i', $app_lib));
        $applicationMapper = new Horde_Autoloader_ClassPathMapper_Application($this->get('fileroot', $app) . '/app');
        $applicationMapper->addMapping('Controller', 'controllers');
        $applicationMapper->addMapping('SettingsExporter', 'settings');
        $autoloader->addClassPathMapper($applicationMapper);

        $checkPerms = !isset($options['check_perms']) || !empty($options['check_perms']);

        /* If permissions checking is requested, return an error if the
         * current user does not have read perms to the application being
         * loaded. We allow access:
         *  - To all admins.
         *  - To all authenticated users if no permission is set on $app.
         *  - To anyone who is allowed by an explicit ACL on $app. */
        if ($checkPerms) {
            if ($this->getAuth() && !$this->checkExistingAuth()) {
                throw new Horde_Exception('User is not authorized', self::AUTH_FAILURE);
            }

            if (!$this->hasPermission($app, Horde_Perms::READ, array('notransparent' => !empty($options['notransparent'])))) {
                if (!$this->isAuthenticated(array('app' => $app))) {
                    throw new Horde_Exception('User is not authorized', self::AUTH_FAILURE);
                }

                Horde::logMessage(sprintf('%s does not have READ permission for %s', $this->getAuth() ? 'User ' . $this->getAuth() : 'Guest user', $app), 'DEBUG');
                throw new Horde_Exception(sprintf(_('%s is not authorized for %s.'), $this->getAuth() ? 'User ' . $this->getAuth() : 'Guest user', $this->applications[$app]['name']), self::PERMISSION_DENIED);
            }
        }

        /* Push application on the stack. */
        $this->_appStack[] = $app;

        /* Chicken and egg problem: the language environment has to be loaded
         * before loading the configuration file, because it might contain
         * gettext strings. Though the preferences can specify a different
         * language for this app, they have to be loaded after the
         * configuration, because they rely on configuration settings. So try
         * with the current language, and reset the language later. */
        $this->setLanguageEnvironment($GLOBALS['language'], $app);

        /* Load config and prefs and set proper language from the prefs. */
        $this->_onAppSwitch($app);

        /* Call post-push hook. */
        try {
            Horde::callHook('pushapp', array(), $app);
        } catch (Horde_Exception $e) {
            $e->setCode(self::HOOK_FATAL);
            $this->popApp();
            throw $e;
        } catch (Horde_Exception_HookNotSet $e) {}

        /* Initialize application. */
        if ($checkPerms || empty($options['noinit'])) {
            try {
                if (file_exists($app_lib . '/base.php')) {
                    // TODO: Remove once there is no more base.php files
                    require_once $app_lib . '/base.php';
                } else {
                    $this->callAppMethod($app, 'init');
                }
            } catch (Horde_Exception $e) {
                $this->popApp();
                throw $e;
            }
        }

        /* Do login tasks. */
        if ($checkPerms &&
            ($tasks = $GLOBALS['injector']->getInstance('Horde_Core_Factory_LoginTasks')->create($app)) &&
            !empty($options['logintasks'])) {
            $tasks->runTasks(array(
                'url' => Horde::selfUrl(true, true, true)
            ));
        }

        return true;
    }

    /**
     * Remove the current app from the application stack, setting the current
     * app to whichever app was current before this one took over.
     *
     * @return string  The name of the application that was popped.
     * @throws Horde_Exception
     */
    public function popApp()
    {
        /* Pop the current application off of the stack. */
        $previous = array_pop($this->_appStack);

        /* Import the new active application's configuration values
         * and set the gettext domain and the preferred language. */
        $app = $this->getApp();
        if ($app) {
            $this->_onAppSwitch($app);
            $this->setTextdomain(
                $app,
                $this->get('fileroot', $app) . '/locale'
            );
        }

        return $previous;
    }

    /**
     * Code to run when switching to an application.
     *
     * @param string $app  The application name.
     *
     * @throws Horde_Exception
     */
    protected function _onAppSwitch($app)
    {
        /* Import this application's configuration values. */
        $this->importConfig($app);

        /* Load preferences after the configuration has been loaded to make
         * sure the prefs file has all the information it needs. */
        $this->loadPrefs($app);

        /* Reset the language in case there is a different one selected in the
         * preferences. */
        $language = $GLOBALS['prefs']->getValue('language');
        if ($language != $GLOBALS['language']) {
            $this->setLanguageEnvironment($language, $app);
        }
    }

    /**
     * Return the current application - the app at the top of the application
     * stack.
     *
     * @return string  The current application.
     */
    public function getApp()
    {
        return end($this->_appStack);
    }

    /**
     * Check permissions on an application.
     *
     * @param string $app     The name of the application
     * @param integer $perms  The permission level to check for.
     * @param array $options  Additional options:
     * <pre>
     * 'notransparent' - (boolean) Do not attempt transparent authentication.
     *                   DEFAULT: false
     * </pre>
     *
     * @return boolean  Whether access is allowed.
     */
    public function hasPermission($app, $perms = Horde_Perms::READ,
                                  array $params = array())
    {
        /* Always do isAuthenticated() check first. You can be an admin, but
         * application auth != Horde admin auth. And there can *never* be
         * non-SHOW access to an application that requires authentication. */
        if (!$this->isAuthenticated(array('app' => $app, 'notransparent' => !empty($params['notransparent']))) &&
            $GLOBALS['injector']->getInstance('Horde_Auth_Factory')->getAuth($app)->requireAuth() &&
            ($perms != Horde_Perms::SHOW)) {
            return false;
        }

        /* Otherwise, allow access for admins, for apps that do not have any
         * explicit permissions, or for apps that allow the given permission. */
        return $this->isAdmin() ||
            ($GLOBALS['injector']->getInstance('Horde_Perms')->exists($app)
             ? $GLOBALS['injector']->getInstance('Horde_Perms')->hasPermission($app, $this->getAuth(), $perms)
             : (bool)$this->getAuth());
    }

    /**
     * Reads the configuration values for the given application and imports
     * them into the global $conf variable.
     *
     * @param string $app  The name of the application.
     */
    public function importConfig($app)
    {
        if (($app != 'horde') && !$this->_loadCacheVar('conf-' . $app)) {
            $appConfig = Horde::loadConfiguration('conf.php', 'conf', $app);
            if (empty($appConfig)) {
                $appConfig = array();
            }
            $this->_cache['conf-' . $app] = Horde_Array::array_merge_recursive_overwrite($this->_cache['conf-horde'], $appConfig);
            $this->_saveCacheVar('conf-' . $app);
        }

        $GLOBALS['conf'] = &$this->_cache['conf-' . $app];
    }

    /**
     * Loads the preferences for the current user for the current application
     * and imports them into the global $prefs variable.
     * $app will be the active application after calling this function.
     *
     * @param string $app  The name of the application.
     * @throws Horde_Exception
     */
    public function loadPrefs($app = null)
    {
        global $injector, $prefs;

        if (is_null($app)) {
            $app = $this->getApp();
        } else {
            $this->pushApp($app);
        }

        if ($this->getAuth()) {
            if (isset($prefs) && ($prefs->getUser() == $this->getAuth())) {
                $prefs->retrieve($app);
                return;
            }

            $opts = array(
                'password' => $this->getAuthCredential('password'),
                'user' => $this->getAuth()
            );
        } else {
            /* If there is no logged in user, return an empty Horde_Prefs::
             * object with just default preferences. */
            $opts = array(
                'cache' => false,
                'session' => true
            );
        }

        $prefs = $injector->getInstance('Horde_Core_Factory_Prefs')->create($app, $opts);
    }

    /**
     * Return the requested configuration parameter for the specified
     * application. If no application is specified, the value of
     * the current application is used. However, if the parameter is not
     * present for that application, the Horde-wide value is used instead.
     * If that is not present, we return null.
     *
     * @param string $parameter  The configuration value to retrieve.
     * @param string $app        The application to get the value for.
     *
     * @return string  The requested parameter, or null if it is not set.
     */
    public function get($parameter, $app = null)
    {
        if (is_null($app)) {
            $app = $this->getApp();
        }

        if (isset($this->applications[$app][$parameter])) {
            $pval = $this->applications[$app][$parameter];
        } else {
            $pval = ($parameter == 'icon')
                ? Horde_Themes::img($app . '.png', $app)
                : (isset($this->applications['horde'][$parameter]) ? $this->applications['horde'][$parameter] : null);
        }

        return ($parameter == 'name')
            ? _($pval)
            : $pval;
    }

    /**
     * Return the version string for a given application.
     *
     * @param string $app      The application to get the value for.
     * @param boolean $number  Return the raw version number, suitable for
     *                         comparison purposes.
     *
     * @return string  The version string for the application.
     */
    public function getVersion($app = null, $number = false)
    {
        if (empty($app)) {
            $app = $this->getApp();
        }

        try {
            $api = $this->getApiInstance($app, 'application');
        } catch (Horde_Exception $e) {
            return 'unknown';
        }

        return $number
            ? preg_replace('/H\d \((.*)\)/', '$1', $api->version)
            : $api->version;
    }

    /**
     * Does the given application have a mobile view?
     *
     * @param string $app  The application to check.
     *
     * @return boolean  Whether app has mobile view.
     */
    public function hasMobileView($app = null)
    {
        if (empty($app)) {
            $app = $this->getApp();
        }

        try {
            $api = $this->getApiInstance($app, 'application');
            return !empty($api->mobileView);
        } catch (Horde_Exception $e) {
            return false;
        }
    }

    /**
     * Does the given application have an ajax view?
     *
     * @param string $app  The application to check.
     *
     * @return boolean  Whether app has an ajax view.
     */
    public function hasAjaxView($app = null)
    {
        if (empty($app)) {
            $app = $this->getApp();
        }

        try {
            $api = $this->getApiInstance($app, 'application');
            return !empty($api->ajaxView);
        } catch (Horde_Exception $e) {
            return false;
        }
    }

    /**
     * Returns a list of available drivers for a library that are available
     * in an application.
     *
     * @param string $app     The application name.
     * @param string $prefix  The library prefix.
     *
     * @return array  The list of available class names.
     */
    public function getAppDrivers($app, $prefix)
    {
        $classes = array();
        $fileprefix = strtr($prefix, '_', '/');
        $fileroot = $this->get('fileroot', $app);

        if (!is_null($fileroot) &&
            is_dir($fileroot . '/lib/' . $fileprefix)) {
            foreach (scandir($fileroot . '/lib/' . $fileprefix) as $file) {
                $classname = $app . '_' . $prefix . '_' . basename($file, '.php');
                if (class_exists($classname)) {
                    $classes[] = $classname;
                }
            }
        }

        return $classes;
    }

    /**
     * Query the initial page for an application - the webroot, if there is no
     * initial_page set, and the initial_page, if it is set.
     *
     * @param string $app  The name of the application.
     *
     * @return string  URL pointing to the initial page of the application.
     * @throws Horde_Exception
     */
    public function getInitialPage($app = null)
    {
        if (is_null($app)) {
            $app = $this->getApp();
        }

        if (isset($this->applications[$app])) {
            return $this->applications[$app]['webroot'] . '/' . (isset($this->applications[$app]['initial_page']) ? $this->applications[$app]['initial_page'] : '');
        }

        throw new Horde_Exception(sprintf(_("\"%s\" is not configured in the Horde Registry."), $app));
    }

    /**
     * Saves a cache variable.
     *
     * @param string $name     Cache variable name.
     * @param boolean $expire  Expire the entry?
     */
    protected function _saveCacheVar($name, $expire = false)
    {
        /* Using cache while not authenticated isn't possible because,
         * although storage is possible, retrieval isn't since there is no
         * MD5 sum in the session to use to build the cache IDs. */
        if (!$this->getAuth()) {
            return;
        }

        $ob = $GLOBALS['injector']->getInstance('Horde_Cache');

        if ($expire) {
            if ($id = $this->_getCacheId($name)) {
                $ob->expire($id);
            }
        } else {
            $data = serialize($this->_cache[$name]);
            $_SESSION['_registry']['md5'][$name] = $md5sum = hash('md5', $data);
            $id = $this->_getCacheId($name, false) . '|' . $md5sum;
            if ($ob->set($id, $data, 86400)) {
                Horde::logMessage('Horde_Registry: stored ' . $name . ' with cache ID ' . $id, 'DEBUG');
            }
        }
    }

    /**
     * Retrieves a cache variable.
     *
     * @param string $name  Cache variable name.
     *
     * @return boolean  True if value loaded from cache.
     */
    protected function _loadCacheVar($name)
    {
        if (isset($this->_cache[$name])) {
            return true;
        }

        /* Using cache while not authenticated isn't possible because,
         * although storage is possible, retrieval isn't since there is no
         * MD5 sum in the session to use to build the cache IDs. */
        if ($this->getAuth() &&
            ($id = $this->_getCacheId($name))) {
            $result = $GLOBALS['injector']->getInstance('Horde_Cache')->get($id, 86400);
            if ($result !== false) {
                $this->_cache[$name] = unserialize($result);
                Horde::logMessage('Horde_Registry: retrieved ' . $name . ' with cache ID ' . $id, 'DEBUG');
                return true;
            }
        }

        return false;
    }

    /**
     * Get the cache storage ID for a particular cache name.
     *
     * @param string $name  Cache variable name.
     * @param string $md5   Append MD5 value?
     *
     * @return mixed  The cache ID or false if cache entry doesn't exist in
     *                the session.
     */
    protected function _getCacheId($name, $md5 = true)
    {
        $id = 'horde_registry_' . $name . '|' . $this->_regmtime;

        if (!$md5) {
            return $id;
        } elseif (isset($_SESSION['_registry']['md5'][$name])) {
            return $id . '|' . $_SESSION['_registry']['md5'][$name];
        }

        return false;
    }

    /**
     * Sets a custom session handler up, if there is one.
     *
     * The custom session handler object will be contained in the
     * $sessionHandler public member variable.
     *
     * @param boolean $start  Initiate the session?
     *
     * @throws Horde_Exception
     */
    public function setupSessionHandler($start = true)
    {
        global $conf;

        ini_set('url_rewriter.tags', 0);
        if (empty($conf['session']['use_only_cookies'])) {
            ini_set('session.use_only_cookies', 0);
        } else {
            ini_set('session.use_only_cookies', 1);
            if (!empty($conf['cookie']['domain']) &&
                (strpos($conf['server']['name'], '.') === false)) {
                throw new Horde_Exception('Session cookies will not work without a FQDN and with a non-empty cookie domain. Either use a fully qualified domain name like "http://www.example.com" instead of "http://example" only, or set the cookie domain in the Horde configuration to an empty value, or enable non-cookie (url-based) sessions in the Horde configuration.');
            }
        }

        session_set_cookie_params(
            $conf['session']['timeout'],
            $conf['cookie']['path'],
            $conf['cookie']['domain'],
            $conf['use_ssl'] == 1 ? 1 : 0
        );
        session_cache_limiter(is_null($this->initParams['session_cache_limiter']) ? $conf['session']['cache_limiter'] : $this->initParams['session_cache_limiter']);
        session_name(urlencode($conf['session']['name']));

        /* We want to create an instance here, not get, since we may be
         * destroying the previous instances in the page. */
        $this->sessionHandler = $GLOBALS['injector']->createInstance('Horde_Core_Factory_SessionHandler');

        if ($start) {
            session_start();
        }
    }

    /**
     * Destroys any existing session on login and make sure to use a new
     * session ID, to avoid session fixation issues. Should be called before
     * checking a login.
     */
    public function getCleanSession()
    {
        if ($this->_cleansession) {
            return;
        }

        // Make sure to force a completely new session ID and clear all
        // session data.
        session_regenerate_id(true);
        session_unset();

        /* Reset cookie timeouts, if necessary. */
        if (!empty($GLOBALS['conf']['session']['timeout'])) {
            $app = $this->getApp();
            $secret = $GLOBALS['injector']->getInstance('Horde_Secret');
            if ($secret->clearKey($app)) {
                $secret->setKey($app);
            }
            $secret->setKey('auth');
        }

        $this->_cleansession = true;
    }

    /**
     * Clears any authentication tokens in the current session.
     *
     * @param boolean $destroy  Destroy the session?
     */
    public function clearAuth($destroy = true)
    {
        /* Do logout tasks. */
        if (!empty($_SESSION['horde_auth']['app'])) {
            foreach (array_keys($_SESSION['horde_auth']['app']) as $app) {
                try {
                    $this->callAppMethod($app, 'logout');
                } catch (Horde_Exception $e) {}
            }
        }

        unset($_SESSION['horde_auth']);

        /* Remove the user's cached preferences if they are present. */
        $GLOBALS['injector']->getInstance('Horde_Core_Factory_Prefs')->clearCache();

        if ($destroy) {
            session_destroy();
            $this->_cleansession = true;
        }
    }

    /**
     * Is a user an administrator?
     *
     * @param array $options  Options:
     * <pre>
     * 'permission' - (string) Allow users with this permission admin access
     *                in the current context.
     * 'permlevel' - (integer) The level of permissions to check for.
     *               Defaults to Horde_Perms::EDIT.
     * 'user' - (string) The user to check.
     *          Defaults to self::getAuth().
     * </pre>
     *
     * @return boolean  Whether or not this is an admin user.
     */
    public function isAdmin(array $options = array())
    {
        $user = isset($options['user'])
            ? $options['user']
            : $this->getAuth();

        if ($user &&
            @is_array($GLOBALS['conf']['auth']['admins']) &&
            in_array($user, $GLOBALS['conf']['auth']['admins'])) {
            return true;
        }

        return isset($options['permission'])
            ? $GLOBALS['injector']->getInstance('Horde_Perms')->hasPermission($options['permission'], $user, isset($options['permlevel']) ? $options['permlevel'] : Horde_Perms::EDIT)
            : false;
    }

    /**
     * Checks if there is a session with valid auth information. If there
     * isn't, but the configured Auth driver supports transparent
     * authentication, then we try that.
     *
     * @param array $options  Additional options:
     * <pre>
     * 'app' - (string) Check authentication for this app.
     *         DEFAULT: Checks horde-wide authentication.
     * 'notransparent' - (boolean) Do not attempt transparent authentication.
     *                   DEFAULT: false
     * </pre>
     *
     * @return boolean  Whether or not the user is authenticated.
     */
    public function isAuthenticated(array $options = array())
    {
        $app = empty($options['app'])
            ? 'horde'
            : $options['app'];

        /* Check for cached authentication results. */
        if ($this->getAuth() &&
            (($app == 'horde') ||
             isset($_SESSION['horde_auth']['app'][$app]))) {
            return $this->checkExistingAuth();
        }

        /* Try transparent authentication. */
        if (empty($options['notransparent'])) {
            if (!$this->getAuth()) {
                $this->getCleanSession();
            }
            return $GLOBALS['injector']->getInstance('Horde_Auth_Factory')->getAuth($app)->transparent();
        }

        return false;
    }

    /**
     * Handle authentication failures, redirecting to the login page
     * when appropriate.
     *
     * @param string $app         The app which failed authentication.
     * @param Horde_Exception $e  An exception thrown by pushApp().
     *
     * @throws Horde_Exception
     */
    public function authenticateFailure($app = 'horde', $e = null)
    {
        if (Horde_Cli::runningFromCLI()) {
            $cli = new Horde_Cli();
            $cli->fatal(_("You are not authenticated."));
        }

        if (is_null($e)) {
            $params = array();
        } else {
            switch ($e->getCode()) {
            case self::PERMISSION_DENIED:
                $params = array('app' => $app, 'reason' => Horde_Auth::REASON_MESSAGE, 'msg' => $e->getMessage());
                break;

            case self::AUTH_FAILURE:
                $params = array('app' => $app);
                break;

            default:
                throw $e;
            }
        }

        header('Location: ' . $this->getLogoutUrl($params));
        exit;
    }

    /**
     * Return a URL to the login screen, adding the necessary logout
     * parameters.
     * If no reason/msg is passed in, use the current global authentication
     * error message.
     *
     * @param array $options  Additional options:
     * <pre>
     * 'app' - (string) Authenticate to this application
     *         DEFAULT: Horde
     * 'msg' - (string) If reason is Horde_Auth::REASON_MESSAGE, the message
     *         to display to the user.
     *         DEFAULT: None
     * 'params' - (array) Additional params to add to the URL (not allowed:
     *            'app', 'horde_logout_token', 'msg', 'reason', 'url').
     *            DEFAULT: None
     * 'reason' - (integer) The reason for logout
     *            DEFAULT: None
     * </pre>
     *
     * @return string The formatted URL
     */
    public function getLogoutUrl(array $options = array())
    {
        if (!isset($options['reason'])) {
            // TODO: This only returns the error for Horde-wide
            // authentication, not for application auth.
            $options['reason'] = $GLOBALS['injector']->getInstance('Horde_Auth_Factory')->getAuth()->getError();
        }

        if (empty($options['app']) ||
            ($options['app'] == 'horde') ||
            ($options['reason'] == Horde_Auth::REASON_LOGOUT)) {
            $params = array(
                'horde_logout_token' => Horde::getRequestToken('horde.logout'),
            );
        } else {
            $params = array(
                'url' => Horde::selfUrl(true, true, true)
            );
        }

        if (isset($options['app'])) {
            $params['app'] = $options['app'];
        }

        if ($options['reason']) {
            $params['logout_reason'] = $options['reason'];
            if ($options['reason'] == Horde_Auth::REASON_MESSAGE) {
                $params['logout_msg'] = empty($options['msg'])
                    ? $GLOBALS['injector']->getInstance('Horde_Auth_Factory')->getAuth()->getError(true)
                    : $options['msg'];
            }
        }

        return Horde::getServiceLink('login', 'horde')->add($params)->setRaw(true);
    }

    /**
     * Converts an authentication username to a unique Horde username.
     *
     * @param string $username  The username to convert.
     * @param boolean $toHorde  If true, convert to a Horde username. If
     *                          false, convert to the auth username.
     *
     * @return string  The converted username.
     * @throws Horde_Exception
     */
    public function convertUsername($userId, $toHorde)
    {
        try {
            return Horde::callHook('authusername', array($userId, $toHorde));
        } catch (Horde_Exception_HookNotSet $e) {
            return $userId;
        }
    }

    /**
     * Returns the currently logged in user, if there is one.
     *
     * @param string $format  The return format, defaults to the unique Horde
     *                        ID. Alternative formats:
     *                        - bare: Horde ID without any domain information
     *                          (e.g., foo@example.com would be returned as
     *                          'foo').
     *                        - domain: Domain of the Horde ID (e.g.,
     *                          foo@example.com would be returned as
     *                          'example.com').
     *                        - original: The username used to originally login
     *                          to Horde.
     *
     * @return mixed  The user ID or false if no user is logged in.
     */
    public function getAuth($format = null)
    {
        if ($format == 'original') {
            return empty($_SESSION['horde_auth']['authId'])
                ? false
                : $_SESSION['horde_auth']['authId'];
        }

        if (empty($_SESSION['horde_auth']['userId'])) {
            return false;
        }

        $user = $_SESSION['horde_auth']['userId'];

        switch ($format) {
        case 'bare':
            return (($pos = strpos($user, '@')) === false)
                ? $user
                : substr($user, 0, $pos);

        case 'domain':
            return (($pos = strpos($user, '@')) === false)
                ? false
                : substr($user, $pos + 1);

        default:
            return $user;
        }
    }

    /**
     * Return whether the authentication backend requested a password change.
     *
     * @return boolean Whether the backend requested a password change.
     */
    public function passwordChangeRequested()
    {
        return !empty($_SESSION['horde_auth']['change']);
    }

    /**
     * Returns the requested credential for the currently logged in user, if
     * present.
     *
     * @param string $credential  The credential to retrieve.
     * @param string $app         The app to query. Defaults to Horde.
     *
     * @return mixed  The requested credential, all credentials if $credential
     *                is null, or false if no user is logged in.
     */
    public function getAuthCredential($credential = null, $app = null)
    {
        if (!$this->getAuth()) {
            return false;
        }

        $credentials = $this->_getAuthCredentials($app);

        return is_null($credential)
            ? $credentials
            : ((is_array($credentials) && isset($credentials[$credential]))
                   ? $credentials[$credential]
                   : false);
    }

    /**
     * Sets the requested credential for the currently logged in user.
     *
     * @param mixed $credential  The credential to set.  If an array,
     *                           overwrites the current credentials array.
     * @param string $value      The value to set the credential to. If
     *                           $credential is an array, this value is
     *                           ignored.
     * @param string $app        The app to update. Defaults to Horde.
     */
    public function setAuthCredential($credential, $value = null, $app = null)
    {
        if (!$this->getAuth()) {
            return;
        }

        if (is_array($credential)) {
            $credentials = $credential;
        } else {
            if (($credentials = $this->_getAuthCredentials($app)) === false) {
                return;
            }

            if (!is_array($credentials)) {
                $credentials = array();
            }

            $credentials[$credential] = $value;
        }

        $secret = $GLOBALS['injector']->getInstance('Horde_Secret');
        $_SESSION['horde_auth']['app'][$app] = $secret->write($secret->getKey('auth'), serialize($credentials));
    }

    /**
     * Get the list of credentials for a given app.
     *
     * @param string $app  The application name.
     *
     * @return mixed  True, false, or the credential list.
     */
    protected function _getAuthCredentials($app)
    {
        if (!isset($_SESSION['horde_auth']['app'])) {
            return false;
        }

        if (is_null($app)) {
            $app = $_SESSION['horde_auth']['credentials'];
        }

        $secret = $GLOBALS['injector']->getInstance('Horde_Secret');
        return @unserialize($secret->read($secret->getKey('auth'), $_SESSION['horde_auth']['app'][$app]));
    }

    /**
     * Sets a variable in the session saying that authorization has succeeded,
     * note which userId was authorized, and note when the login took place.
     *
     * If a user name hook was defined in the configuration, it gets applied
     * to the $userId at this point.
     *
     * Horde authentication data is stored in the session in the 'horde_auth'
     * array key.  That key has the following structure:
     * <pre>
     * 'app' - (array) Application-specific authentication. Keys are the
     *         app names, values are an array containing credentials. If true,
     *         application does not require any specific credentials.
     * 'authId' - (string) The username used during the original
                  authentication.
     * 'browser' - (string) The remote browser string.
     * 'change' - (boolean) Is a password change requested?
     * 'credentials' - (string) The 'app' entry that contains the Horde
     *                 credentials.
     * 'remoteAddr' - (string) The remote IP address of the user.
     * 'timestamp' - (integer) The login time.
     * 'userId' - (string) The unique Horde username.
     * </pre>
     *
     * @param string $authId      The userId that has been authorized.
     * @param array $credentials  The credentials of the user.
     * @param array $options      Additional options:
     * <pre>
     * 'app' - (string) The app to set authentication credentials for.
     *         DEFAULT: 'horde'
     * 'change' - (boolean) Whether to request that the user change their
     *            password.
     *            DEFAULT: No
     * </pre>
     */
    public function setAuth($authId, $credentials, array $options = array())
    {
        $app = empty($options['app']) ? 'horde' : $options['app'];

        if ($this->getAuth()) {
            /* Store app credentials. */
            $this->setAuthCredential($credentials, null, $app);
            return;
        }

        $_SESSION['horde_auth'] = array(
            'app' => array(),
            'authId' => $authId,
            'browser' => $GLOBALS['browser']->getAgentString(),
            'change' => !empty($options['change']),
            'credentials' => $app,
            'remoteAddr' => isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : null,
            'timestamp' => time(),
            'userId' => $this->convertUsername(trim($authId), true)
        );

        $this->setAuthCredential($credentials, null, $app);

        /* Reload preferences for the new user. */
        unset($GLOBALS['prefs']);
        $GLOBALS['injector']->getInstance('Horde_Core_Factory_Prefs')->clearCache();
        $this->loadPrefs();

        $this->setLanguageEnvironment($GLOBALS['prefs']->getValue('language'), $app);
    }

    /**
     * Check existing auth for triggers that might invalidate it.
     *
     * @return boolean  Is existing auth valid?
     */
    public function checkExistingAuth()
    {
        $auth = $GLOBALS['injector']->getInstance('Horde_Auth_Factory')->getAuth();

        if (!empty($GLOBALS['conf']['auth']['checkip']) &&
            !empty($_SESSION['horde_auth']['remoteAddr']) &&
            ($_SESSION['horde_auth']['remoteAddr'] != $_SERVER['REMOTE_ADDR'])) {
            $auth->setError(Horde_Core_Auth_Application::REASON_SESSIONIP);
            return false;
        }

        if (!empty($GLOBALS['conf']['auth']['checkbrowser']) &&
            ($_SESSION['horde_auth']['browser'] != $GLOBALS['browser']->getAgentString())) {
            $auth->setError(Horde_Core_Auth_Application::REASON_BROWSER);
            return false;
        }

        return $auth->validateAuth();
    }

    /* NLS functions. */

    /**
     * Returns the charset for the current language.
     *
     * @return string  The character set that should be used with the current
     *                 locale settings.
     */
    public function getLanguageCharset()
    {
        return empty($this->nlsconfig['charsets'][$GLOBALS['language']])
            ? 'ISO-8859-1'
            : $this->nlsconfig['charsets'][$GLOBALS['language']];
    }

    /**
     * Returns the charset to use for outgoing emails.
     *
     * @return string  The preferred charset for outgoing mails based on
     *                 the user's preferences and the current language.
     */
    public function getEmailCharset()
    {
        $charset = $GLOBALS['prefs']->getValue('sending_charset');
        if (!empty($charset)) {
            return $charset;
        }

        return isset($this->nlsconfig['emails'][$GLOBALS['language']])
            ? $this->nlsconfig['emails'][$GLOBALS['language']]
            : (isset($this->nlsconfig['charsets'][$GLOBALS['language']]) ? $this->nlsconfig['charsets'][$GLOBALS['language']] : 'ISO-8859-1');
    }

    /**
     * Selects the most preferred language for the current client session.
     *
     * @param string $lang  Force to use this language.
     *
     * @return string  The selected language abbreviation.
     */
    public function preferredLang($lang = null)
    {
        /* First, check if language pref is locked and, if so, set it to its
         * value */
        if (isset($GLOBALS['prefs']) &&
            $GLOBALS['prefs']->isLocked('language')) {
            $language = $GLOBALS['prefs']->getValue('language');
        /* Check if the user selected a language from the login screen */
        } elseif (!empty($lang) && $this->isValidLang($lang)) {
            $language = $lang;
        /* Check if we have a language set in the session */
        } elseif (isset($_SESSION['horde_language'])) {
            $language = $_SESSION['horde_language'];
        /* Use site-wide default, if one is defined */
        } elseif (!empty($this->nlsconfig['defaults']['language'])) {
            $language = $this->nlsconfig['defaults']['language'];
        /* Try browser-accepted languages. */
        } elseif (!empty($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            /* The browser supplies a list, so return the first valid one. */
            $browser_langs = explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE']);
            foreach ($browser_langs as $lang) {
                /* Strip quality value for language */
                if (($pos = strpos($lang, ';')) !== false) {
                    $lang = substr($lang, 0, $pos);
                }
                $lang = $this->_mapLang(trim($lang));
                if ($this->isValidLang($lang)) {
                    $language = $lang;
                    break;
                }

                /* In case there's no full match, save our best guess. Try
                 * ll_LL, followed by just ll. */
                if (!isset($partial_lang)) {
                    $ll_LL = Horde_String::lower(substr($lang, 0, 2)) . '_' . Horde_String::upper(substr($lang, 0, 2));
                    if ($this->isValidLang($ll_LL)) {
                        $partial_lang = $ll_LL;
                    } else {
                        $ll = $this->_mapLang(substr($lang, 0, 2));
                        if ($this->isValidLang($ll))  {
                            $partial_lang = $ll;
                        }
                    }
                }
            }
        }

        if (!isset($language)) {
            $language = isset($partial_lang)
                ? $partial_lang
                /* No dice auto-detecting, default to US English. */
                : 'en_US';
        }

        return basename($language);
    }

    /**
     * Determines whether the supplied language is valid.
     *
     * @param string $language  The abbreviated name of the language.
     *
     * @return boolean  True if the language is valid, false if it's not
     *                  valid or unknown.
     */
    public function isValidLang($language)
    {
        return !empty($this->nlsconfig['languages'][$language]);
    }

    /**
     * Sets the language.
     *
     * @param string $lang  The language abbreviation.
     *
     * @throws Horde_Exception
     */
    public function setLanguage($lang = null)
    {
        if (empty($lang) || !$this->isValidLang($lang)) {
            $lang = $this->preferredLang();
        }

        $_SESSION['horde_language'] = $lang;

        if (isset($GLOBALS['language'])) {
            if ($GLOBALS['language'] == $lang) {
                return;
            }
            $this->clearCache();
        }
        $GLOBALS['language'] = $lang;

        $lang_charset = $lang . '.UTF-8';
        setlocale(LC_ALL, $lang_charset);
        @putenv('LC_ALL=' . $lang_charset);
        @putenv('LANG=' . $lang_charset);
        @putenv('LANGUAGE=' . $lang_charset);
    }

    /**
     * Sets the language and reloads the whole NLS environment.
     *
     * When setting the language, the gettext catalogs have to be reloaded
     * too, charsets have to be updated etc. This method takes care of all
     * this.
     *
     * @param string $language  The new language.
     * @param string $app       The application for reloading the gettext
     *                          catalog. The current application if empty.
     */
    public function setLanguageEnvironment($lang = null, $app = null)
    {
        if (empty($app)) {
            $app = $this->getApp();
        }

        $this->setLanguage($lang);
        $this->setTextdomain(
            $app,
            $this->get('fileroot', $app) . '/locale'
        );
    }

    /**
     * Sets the gettext domain.
     *
     * @param string $app        The application name.
     * @param string $directory  The directory where the application's
     *                           LC_MESSAGES directory resides.
     */
    public function setTextdomain($app, $directory)
    {
        bindtextdomain($app, $directory);
        textdomain($app);
    }

    /**
     * Sets the current timezone, if available.
     */
    public function setTimeZone()
    {
        $tz = $GLOBALS['prefs']->getValue('timezone');
        if (!empty($tz)) {
            @date_default_timezone_set($tz);
        }
    }

    /**
     * Maps languages with common two-letter codes (such as nl) to the full
     * locale code (in this case, nl_NL). Returns the language unmodified if
     * it isn't an alias.
     *
     * @param string $language  The language code to map.
     *
     * @return string  The mapped language code.
     */
    protected function _mapLang($language)
    {
        // Translate the $language to get broader matches.
        // (eg. de-DE should match de_DE)
        $trans_lang = str_replace('-', '_', $language);
        $lang_parts = explode('_', $trans_lang);
        $trans_lang = Horde_String::lower($lang_parts[0]);
        if (isset($lang_parts[1])) {
            $trans_lang .= '_' . Horde_String::upper($lang_parts[1]);
        }

        return empty($this->nlsconfig['aliases'][$trans_lang])
            ? $trans_lang
            : $this->nlsconfig['aliases'][$trans_lang];
    }
}
