<?php
/**
 * Copyright 1999-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category  Horde
 * @copyright 1999-2015 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Core
 */

/**
 * The registry provides a set of methods for communication between Horde
 * applications and keeping track of application configuration information.
 *
 * @author    Chuck Hagenbuch <chuck@horde.org>
 * @author    Jon Parise <jon@horde.org>
 * @author    Anil Madhavapeddy <anil@recoil.org>
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 1999-2015 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Core
 */
class Horde_Registry implements Horde_Shutdown_Task
{
    /* Session flags. */
    const SESSION_NONE = 1;
    const SESSION_READONLY = 2;

    /* Error codes for pushApp(). */
    const AUTH_FAILURE = 1;
    const NOT_ACTIVE = 2;
    const PERMISSION_DENIED = 3;
    const HOOK_FATAL = 4;
    const INITCALLBACK_FATAL = 5;

    /* View types. */
    const VIEW_BASIC = 1;
    const VIEW_DYNAMIC = 2;
    const VIEW_MINIMAL = 3;
    const VIEW_SMARTMOBILE = 4;

    /* Session keys. */
    const REGISTRY_CACHE = 'registry_cache';

    /**
     * Hash storing information on each registry-aware application.
     *
     * @var array
     */
    public $applications = array();

    /**
     * Original authentication exception. Set if 'fallback' auth is used, and
     * authentication fails.
     *
     * @since 2.11.0
     * @todo Fix this up for H6 (framework needs to do better job of
     *       supporting bootstrapping before authentication).
     *
     * @var Exception
     */
    public $authException;

    /**
     * A flag that is set once the basic horde application has been
     * minimally configured.
     *
     * @var boolean
     */
    public $hordeInit = false;

    /**
     * The application that called appInit().
     *
     * @var string
     */
    public $initialApp;

    /**
     * NLS configuration.
     *
     * @var Horde_Registry_Nlsconfig
     */
    public $nlsconfig;

    /**
     * The current virtual host configuration file.
     *
     * @since 2.12.0
     *
     * @var string
     */
    public $vhost = null;

    /**
     * The list of APIs.
     *
     * @var array
     */
    protected $_apiList = array();

    /**
     * Stack of in-use applications.
     *
     * @var array
     */
    protected $_appStack = array();

    /**
     * The list of applications initialized during this access.
     *
     * @var array
     */
    protected $_appsInit = array();

    /**
     * The arguments that have been passed when instantiating the registry.
     *
     * @var array
     */
    protected $_args = array();

    /**
     * Internal cached data.
     *
     * @var array
     */
    protected $_cache = array(
        'auth' => null,
        'cfile' => array(),
        'conf' => array(),
        'existing' => array(),
        'ob' => array()
    );

    /**
     * Interfaces list.
     *
     * @var array
     */
    protected $_interfaces = array();

    /**
     * The last modified time of the newest modified registry file.
     *
     * @var integer
     */
    protected $_regmtime;

    /**
     * Application bootstrap initialization.
     * Solves chicken-and-egg problem - need a way to init Horde environment
     * from application without an active Horde_Registry object.
     *
     * Page compression will be started (if configured).
     *
     * Global variables defined:
     * <pre>
     *   - $browser: Horde_Browser object
     *   - $cli: Horde_Cli object (if 'cli' is true)
     *   - $conf: Configuration array
     *   - $injector: Horde_Injector object
     *   - $language: Language
     *   - $notification: Horde_Notification object
     *   - $page_output: Horde_PageOutput object
     *   - $prefs: Horde_Prefs object
     *   - $registry: Horde_Registry object
     *   - $session: Horde_Session object
     * </pre>
     *
     * @param string $app  The application to initialize.
     * @param array $args  Optional arguments:
     * <pre>
     *   - admin: (boolean) Require authenticated user to be an admin?
     *            DEFAULT: false
     *   - authentication: (string) The type of authentication to use:
     *     - none: Do not authenticate
     *     - fallback: Attempt to authenticate; if failure, then don't auth
     *                 (@since 2.11.0).
     *     - [DEFAULT]: Authenticate; on no auth redirect to login screen
     *   - cli: (boolean) Initialize a CLI interface. Setting this to true
     *          implicitly sets 'authentication' to 'none' and 'admin' and
     *          'nocompress' to true.
     *          DEFAULT: false
     *   - nocompress: (boolean) If set, the page will not be compressed.
     *                 DEFAULT: false
     *   - nologintasks: (boolean) If set, don't perform logintasks (never
     *                   performed if authentication is 'none').
     *                   DEFAULT: false
     *   - nonotificationinit: (boolean) If set, don't initialize the
     *                         application handlers for the notification
     *                         system (@since 2.12.0).
     *   - permission: (array) The permission required by the user to access
     *                 the page. The first element (REQUIRED) is the permission
     *                 name. The second element (OPTION; defaults to SHOW) is
     *                 the permission level.
     *   - session_cache_limiter: (string) Use this value for the session
     *                            cache limiter.
     *                            DEFAULT: Uses the value in the config.
     *   - session_control: (string) Special session control limitations:
     *     - netscape: TODO; start read/write session
     *     - none: Do not start a session
     *     - readonly: Start session readonly
     *     - [DEFAULT] - Start read/write session
     *   - test: (boolean) Is this the test script? If so, we relax several
     *           sanity checks and don't load things from the cache.
     *           DEFAULT: false
     *   - timezone: (boolean) Set the time zone?
     *               DEFAULT: false
     *   - user_admin: (boolean) Set authentication to an admin user?
     *                 DEFAULT: false
     * </pre>
     *
     * @return Horde_Registry_Application  The application object.
     * @throws Horde_Exception
     */
    public static function appInit($app, array $args = array())
    {
        if (isset($GLOBALS['registry'])) {
            return $GLOBALS['registry']->getApiInstance($app, 'application');
        }

        $args = array_merge(array(
            'admin' => false,
            'authentication' => null,
            'cli' => null,
            'nocompress' => false,
            'nologintasks' => false,
            'nonotificationinit' => false,
            'permission' => false,
            'session_cache_limiter' => null,
            'session_control' => null,
            'timezone' => false,
            'user_admin' => null,
        ), $args);

        /* CLI initialization. */
        if ($args['cli']) {
            /* Make sure no one runs from the web. */
            if (!Horde_Cli::runningFromCLI()) {
                throw new Horde_Exception(Horde_Core_Translation::t("Script must be run from the command line"));
            }

            /* Load the CLI environment - make sure there's no time limit,
             * init some variables, etc. */
            $GLOBALS['cli'] = Horde_Cli::init();

            $args['nocompress'] = true;
            $args['authentication'] = 'none';
        }

        // For 'fallback' authentication, try authentication first.
        if ($args['authentication'] === 'fallback') {
            $fallback_auth = true;
            $args['authentication'] = null;
        } else {
            $fallback_auth = false;
        }

        // Registry.
        $s_ctrl = 0;
        switch ($args['session_control']) {
        case 'netscape':
            // Chicken/egg: Browser object doesn't exist yet.
            // Can't use Horde_Core_Browser since it depends on registry to be
            // configured.
            $browser = new Horde_Browser();
            if ($browser->isBrowser('mozilla')) {
                $args['session_cache_limiter'] = 'private, must-revalidate';
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
        $registry = $GLOBALS['registry'] = new $classname($s_ctrl, $args);
        $registry->initialApp = $app;

        $appob = $registry->getApiInstance($app, 'application');
        $appob->initParams = $args;

        do {
            try {
                $registry->pushApp($app, array(
                    'check_perms' => ($args['authentication'] != 'none'),
                    'logintasks' => !$args['nologintasks'],
                    'notransparent' => !empty($args['notransparent'])
                ));

                if ($args['admin'] && !$registry->isAdmin()) {
                    throw new Horde_Exception(Horde_Core_Translation::t("Not an admin"));
                }

                $e = null;
            } catch (Horde_Exception_PushApp $e) {
                if ($fallback_auth) {
                    $registry->authException = $e;
                    $registry->setAuthenticationSetting('none');
                    $args['authentication'] = 'none';
                    $fallback_auth = false;
                    continue;
                }
            }

            break;
        } while (true);

        if (!is_null($e)) {
            $appob->appInitFailure($e);

            switch ($e->getCode()) {
            case self::AUTH_FAILURE:
                $failure = new Horde_Exception_AuthenticationFailure($e->getMessage());
                $failure->application = $app;
                throw $failure;

            case self::NOT_ACTIVE:
                /* Try redirect to Horde if an app is not active. */
                if (!$args['cli'] && $app != 'horde') {
                    $GLOBALS['notification']->push($e, 'horde.error');
                    Horde::url($registry->getInitialPage('horde'))->redirect();
                }

                /* Shouldn't reach here, but fall back to permission denied
                 * error if we can't even access Horde. */
                // Fall-through

            case self::PERMISSION_DENIED:
                $failure = new Horde_Exception_AuthenticationFailure($e->getMessage(), Horde_Auth::REASON_MESSAGE);
                $failure->application = $app;
                throw $failure;
            }

            throw $e;
        }

        if ($args['timezone']) {
            $registry->setTimeZone();
        }

        if (!$args['nocompress']) {
            $GLOBALS['page_output']->startCompression();
        }

        if ($args['user_admin']) {
            if (empty($GLOBALS['conf']['auth']['admins'])) {
                throw new Horde_Exception(Horde_Core_Translation::t("Admin authentication requested, but no admin users defined in configuration."));
            }
            $registry->setAuth(reset($GLOBALS['conf']['auth']['admins']), array());
        }

        if ($args['permission']) {
            $admin_opts = array(
                'permission' => $args['permission'][0],
                'permlevel' => (isset($args['permission'][1]) ? $args['permission'][1] : Horde_Perms::SHOW)
            );
            if (!$registry->isAdmin($admin_opts)) {
                throw new Horde_Exception_PermissionDenied(Horde_Core_Translation::t("Permission denied."));
            }
        }

        return $appob;
    }

    /**
     * Create a new Horde_Registry instance.
     *
     * @param integer $session_flags  Any session flags.
     * @param array $args             See appInit().
     *
     * @throws Horde_Exception
     */
    public function __construct($session_flags = 0, array $args = array())
    {
        /* Set a valid timezone. */
        date_default_timezone_set(
            ini_get('date.timezone') ?: getenv('TZ') ?: 'UTC'
        );

        /* Save arguments. */
        $this->_args = $args;

        /* Define factories. By default, uses the 'create' method in the given
         * classname (string). If other function needed, define as the second
         * element in an array. */
        $factories = array(
            'Horde_ActiveSyncBackend' => 'Horde_Core_Factory_ActiveSyncBackend',
            'Horde_ActiveSyncServer' => 'Horde_Core_Factory_ActiveSyncServer',
            'Horde_ActiveSyncState' => 'Horde_Core_Factory_ActiveSyncState',
            'Horde_Alarm' => 'Horde_Core_Factory_Alarm',
            'Horde_Browser' => 'Horde_Core_Factory_Browser',
            'Horde_Cache' => 'Horde_Core_Factory_Cache',
            'Horde_Controller_Request' => 'Horde_Core_Factory_Request',
            'Horde_Controller_RequestConfiguration' => array(
                'Horde_Core_Controller_RequestMapper',
                'getRequestConfiguration',
            ),
            'Horde_Core_Auth_Signup' => 'Horde_Core_Factory_AuthSignup',
            'Horde_Core_CssCache' => 'Horde_Core_Factory_CssCache',
            'Horde_Core_JavascriptCache' => 'Horde_Core_Factory_JavascriptCache',
            'Horde_Core_Perms' => 'Horde_Core_Factory_PermsCore',
            'Horde_Dav_Server' => 'Horde_Core_Factory_DavServer',
            'Horde_Dav_Storage' => 'Horde_Core_Factory_DavStorage',
            'Horde_Db_Adapter' => 'Horde_Core_Factory_DbBase',
            'Horde_Editor' => 'Horde_Core_Factory_Editor',
            'Horde_ElasticSearch_Client' => 'Horde_Core_Factory_ElasticSearch',
            'Horde_Group' => 'Horde_Core_Factory_Group',
            'Horde_HashTable' => 'Horde_Core_Factory_HashTable',
            'Horde_History' => 'Horde_Core_Factory_History',
            'Horde_Kolab_Server_Composite' => 'Horde_Core_Factory_KolabServer',
            'Horde_Kolab_Session' => 'Horde_Core_Factory_KolabSession',
            'Horde_Kolab_Storage' => 'Horde_Core_Factory_KolabStorage',
            'Horde_Lock' => 'Horde_Core_Factory_Lock',
            'Horde_Log_Logger' => 'Horde_Core_Factory_Logger',
            'Horde_Mail' => 'Horde_Core_Factory_MailBase',
            'Horde_Memcache' => 'Horde_Core_Factory_Memcache',
            'Horde_Nosql_Adapter' => 'Horde_Core_Factory_NosqlBase',
            'Horde_Notification' => 'Horde_Core_Factory_Notification',
            'Horde_Perms' => 'Horde_Core_Factory_Perms',
            'Horde_Queue_Storage' => 'Horde_Core_Factory_QueueStorage',
            'Horde_Routes_Mapper' => 'Horde_Core_Factory_Mapper',
            'Horde_Routes_Matcher' => 'Horde_Core_Factory_Matcher',
            'Horde_Secret' => 'Horde_Core_Factory_Secret',
            'Horde_Service_Facebook' => 'Horde_Core_Factory_Facebook',
            'Horde_Service_Twitter' => 'Horde_Core_Factory_Twitter',
            'Horde_Service_UrlShortener' => 'Horde_Core_Factory_UrlShortener',
            'Horde_SessionHandler' => 'Horde_Core_Factory_SessionHandler',
            'Horde_Template' => 'Horde_Core_Factory_Template',
            'Horde_Timezone' => 'Horde_Core_Factory_Timezone',
            'Horde_Token' => 'Horde_Core_Factory_Token',
            'Horde_Variables' => 'Horde_Core_Factory_Variables',
            'Horde_View' => 'Horde_Core_Factory_View',
            'Horde_View_Base' => 'Horde_Core_Factory_View',
            'Horde_Weather' => 'Horde_Core_Factory_Weather',
            'Net_DNS2_Resolver' => 'Horde_Core_Factory_Dns',
            'Text_LanguageDetect' => 'Horde_Core_Factory_LanguageDetect',
        );

        /* Define implementations. */
        $implementations = array(
            'Horde_Controller_ResponseWriter' => 'Horde_Controller_ResponseWriter_Web'
        );

        /* Setup injector. */
        $GLOBALS['injector'] = $injector = new Horde_Injector(new Horde_Injector_TopLevel());

        foreach ($factories as $key => $val) {
            if (is_string($val)) {
                $val = array($val, 'create');
            }
            $injector->bindFactory($key, $val[0], $val[1]);
        }
        foreach ($implementations as $key => $val) {
            $injector->bindImplementation($key, $val);
        }

        $GLOBALS['registry'] = $this;
        $injector->setInstance(__CLASS__, $this);

        /* Setup autoloader instance. */
        $injector->setInstance('Horde_Autoloader', $GLOBALS['__autoloader']);

        /* Import and global Horde's configuration values. */
        $this->importConfig('horde');
        $conf = $GLOBALS['conf'];

        /* Set the umask according to config settings. */
        if (isset($conf['umask'])) {
            umask($conf['umask']);
        }

        /* Set the error reporting level in accordance with the config
         * settings. */
        error_reporting($conf['debug_level']);

        /* Set the maximum execution time in accordance with the config
         * settings, but only if not running from the CLI */
        if (!isset($GLOBALS['cli'])) {
            set_time_limit($conf['max_exec_time']);
        }

        /* The basic framework is up and loaded, so set the init flag. */
        $this->hordeInit = true;

        /* Initial Horde-wide settings. */

        /* Initialize browser object. */
        $GLOBALS['browser'] = $injector->getInstance('Horde_Browser');

        /* Get modified time of registry files. */
        $regfiles = array(
            HORDE_BASE . '/config/registry.php',
            HORDE_BASE . '/config/registry.d'
        );
        if (file_exists(HORDE_BASE . '/config/registry.local.php')) {
            $regfiles[] = HORDE_BASE . '/config/registry.local.php';
        }
        if (!empty($conf['vhosts'])) {
            $vhost = HORDE_BASE . '/config/registry-' . $conf['server']['name'] . '.php';
            if (file_exists($vhost)) {
                $regfiles[] = $this->vhost = $vhost;
            }
        }
        $this->_regmtime = max(array_map('filemtime', $regfiles));

        /* Start a session. */
        if ($session_flags & self::SESSION_NONE) {
            /* Never start a session if the session flags include
               SESSION_NONE. */
            $GLOBALS['session'] = $session = new Horde_Session_Null();
            $session->setup(true, $args['session_cache_limiter']);
        } elseif ((PHP_SAPI === 'cli') ||
                  (empty($_SERVER['SERVER_NAME']) &&
                   ((PHP_SAPI === 'cgi') || (PHP_SAPI === 'cgi-fcgi')))) {
            $GLOBALS['session'] = $session = new Horde_Session();
            $session->setup(false, $args['session_cache_limiter']);
        } else {
            $GLOBALS['session'] = $session = new Horde_Session();
            $session->setup(true, $args['session_cache_limiter']);
            if ($session_flags & self::SESSION_READONLY) {
                /* Close the session immediately so no changes can be made but
                   values are still available. */
                $session->close();
            }
        }
        $injector->setInstance('Horde_Session', $session);

        /* Always need to load applications information. */
        $this->_loadApplications();

        /* Stop system if Horde is inactive. */
        if ($this->applications['horde']['status'] == 'inactive') {
            throw new Horde_Exception(Horde_Core_Translation::t("This system is currently deactivated."));
        }

        /* Initialize language configuration object. */
        $this->nlsconfig = new Horde_Registry_Nlsconfig();

        /* Initialize the localization routines and variables. */
        $this->setLanguageEnvironment(null, 'horde');

        /* Initialize global page output object. */
        $GLOBALS['page_output'] = $injector->getInstance('Horde_PageOutput');

        /* Initialize notification object. Always attach status listener by
         * default. */
        $nclass = null;
        switch ($this->getView()) {
        case self::VIEW_DYNAMIC:
            $nclass = 'Horde_Core_Notification_Listener_DynamicStatus';
            break;

        case self::VIEW_SMARTMOBILE:
            $nclass = 'Horde_Core_Notification_Listener_SmartmobileStatus';
            break;
        }
        $GLOBALS['notification'] = $injector->getInstance('Horde_Notification');
        if (empty($args['nonotificationinit'])) {
            $GLOBALS['notification']->attachAllAppHandlers();
        }
        $GLOBALS['notification']->attach('status', null, $nclass);

        Horde_Shutdown::add($this);
    }

    /**
     * (Re)set the authentication parameter. Useful for requests, such as Rpc
     * requests where we actually don't perform authentication until later in
     * the request, but still need Horde bootstrapped early in the request. Also
     * clears the local app/api cache since applications will probably already
     * have been initialized during Notification polling.
     *
     * @see appInit()
     *
     * @param string $authentication  The authentication setting.
     */
    public function setAuthenticationSetting($authentication)
    {
        $this->_args['authentication'] = $authentication;
        $this->_cache['cfile'] = $this->_cache['ob'] = array();
        $this->_cache['isauth'] = array();
        $this->_appsInit = array();
        while ($this->popApp());
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
        Horde::log('Max memory usage: ' . memory_get_peak_usage(true) . ' bytes', 'DEBUG');
    }

    /**
     * A property call to the registry object will return a Caller object.
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
        throw new LogicException('Registry objects should never be cloned.');
    }

    /**
     * serialize() should never be called on this object. If it is, die.
     *
     * @throws Horde_Exception
     */
    public function __sleep()
    {
        throw new LogicException('Registry objects should never be serialized.');
    }

    /**
     * Rebuild the registry configuration.
     */
    public function rebuild()
    {
        global $session;

        $app = $this->getApp();

        $this->applications = $this->_apiList = $this->_cache['conf'] = $this->_cache['ob'] = $this->_interfaces = array();

        $session->remove('horde', 'nls/');
        $session->remove('horde', 'registry/');
        $session->remove('horde', self::REGISTRY_CACHE);

        $this->_loadApplications();

        $this->importConfig('horde');
        $this->importConfig($app);
    }

    /**
     * Load application information from registry config files.
     */
    protected function _loadApplications()
    {
        global $cli, $injector;

        if (!empty($this->_interfaces)) {
            return;
        }

        /* First, try to load from cache. */
        if (!isset($cli) && !$this->isTest()) {
            if (Horde_Util::extensionExists('apc')) {
                $cstorage = 'Horde_Cache_Storage_Apc';
            } elseif (Horde_Util::extensionExists('xcache')) {
                $cstorage = 'Horde_Cache_Storage_Xcache';
            } else {
                $cstorage = 'Horde_Cache_Storage_File';
            }

            $cache = new Horde_Cache(
                new $cstorage(array(
                    'no_gc' => true,
                    'prefix' => 'horde_registry_cache_'
                )),
                array(
                    'lifetime' => 0,
                    'logger' => $injector->getInstance('Horde_Log_Logger')
                )
            );

            if (($cid = $this->_cacheId()) &&
                ($cdata = $cache->get($cid, 0))) {
                try {
                    list($this->applications, $this->_interfaces) =
                        $injector->getInstance('Horde_Pack')->unpack($cdata);
                    return;
                } catch (Horde_Pack_Exception $e) {}
            }
        }

        $config = new Horde_Registry_Registryconfig($this);
        $this->applications = $config->applications;
        $this->_interfaces = $config->interfaces;

        if (!isset($cache)) {
            return;
        }

        /* Need to determine hash of generated data, since it is possible that
         * there is dynamic data in the config files. This only needs to
         * be done once per session. */
        $packed_data = $injector->getInstance('Horde_Pack')->pack(array(
            $this->applications,
            $this->_interfaces
        ));
        $cid = $this->_cacheId($packed_data);

        if (!$cache->exists($cid, 0)) {
            $cache->set($cid, $packed_data);
        }
    }

    /**
     * Get the cache ID for the registry information.
     *
     * @param string $hash  If set, hash this value and use as the hash of the
     *                      registry. If false, uses session stored value.
     */
    protected function _cacheId($hash = null)
    {
        global $session;

        if (!is_null($hash)) {
            $hash = hash('md5', $hash);
            $session->set('horde', self::REGISTRY_CACHE, $hash);
        } elseif (!($hash = $session->get('horde', self::REGISTRY_CACHE))) {
            return false;
        }

        /* Generate cache ID. */
        return implode('|', array(
            gethostname() ?: php_uname(),
            __FILE__,
            $this->_regmtime,
            $hash
        ));
    }

    /**
     * Load an application's API object.
     *
     * @param string $app  The application to load.
     *
     * @return Horde_Registry_Api  The API object, or null if not available.
     */
    protected function _loadApi($app)
    {
        if (isset($this->_cache['ob'][$app]['api'])) {
            return $this->_cache['ob'][$app]['api'];
        }

        $api = null;
        $status = array('active', 'notoolbar', 'hidden');
        $status[] = $this->isAdmin()
            ? 'admin'
            : 'noadmin';

        if (in_array($this->applications[$app]['status'], $status)) {
            try {
                $api = $this->getApiInstance($app, 'api');
            } catch (Horde_Exception $e) {
                Horde::log($e, 'DEBUG');
            }
        }

        $this->_cache['ob'][$app]['api'] = $api;

        return $api;
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

        $path = $this->get('fileroot', $app) . '/lib';

        /* Set up autoload paths for the current application. This needs to
         * be done here because it is possible to try to load app-specific
         * libraries from other applications. */
        if (!isset($this->_cache['ob'][$app])) {
            $autoloader = $GLOBALS['injector']->getInstance('Horde_Autoloader');

            $app_mappers = array(
                'Controller' =>  'controllers',
                'Helper' => 'helpers',
                'SettingsExporter' => 'settings'
            );
            $applicationMapper = new Horde_Autoloader_ClassPathMapper_Application($this->get('fileroot', $app) . '/app');
            foreach ($app_mappers as $key => $val) {
                $applicationMapper->addMapping($key, $val);
            }
            $autoloader->addClassPathMapper($applicationMapper);

            /* Skip horde, since this was already setup in core.php. */
            if ($app != 'horde') {
                $autoloader->addClassPathMapper(
                    new Horde_Autoloader_ClassPathMapper_PrefixString($app, $path)
                );
            }
        }

        $cname = Horde_String::ucfirst($type);

        /* Can't autoload here, since the application may not have been
         * initialized yet. */
        $classname = Horde_String::ucfirst($app) . '_' . $cname;
        $path = $path . '/' . $cname . '.php';
        if (file_exists($path)) {
            include_once $path;
        } else {
            $classname = __CLASS__ . '_' . $cname;
        }

        if (!class_exists($classname, false)) {
            throw new Horde_Exception("$app does not have an API");
        }

        $this->_cache['ob'][$app][$type] = ($type == 'application')
            ? new $classname($app)
            : new $classname();

        return $this->_cache['ob'][$app][$type];
    }

    /**
     * Return a list of the installed and registered applications.
     *
     * @param array $filter   An array of the statuses that should be
     *                        returned. Defaults to non-hidden.
     * @param boolean $assoc  Return hash with app names as keys and config
     *                        parameters as values?
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
        if (!$this->isAdmin() &&
            in_array('active', $filter) &&
            !in_array('noadmin', $filter)) {
            $filter[] = 'noadmin';
        }

        $apps = array();
        foreach ($this->applications as $app => $params) {
            if (in_array($params['status'], $filter)) {
                /* Topbar apps can only be displayed if the parent app is
                 * active. */
                if (($params['status'] == 'topbar') &&
                    $this->isInactive($params['app'])) {
                        continue;
                }

                if ((is_null($perms) || $this->hasPermission($app, $perms))) {
                    $apps[$app] = $params;
                }
            }
        }

        return $assoc ? $apps : array_keys($apps);
    }

    /**
     * Return a list of all applications, ignoring permissions.
     *
     * @return array  List of all apps registered with Horde.
     */
    public function listAllApps()
    {
        // Default to all installed (but possibly not configured) applications.
        return $this->listApps(array(
            'active', 'admin', 'noadmin', 'hidden', 'inactive', 'notoolbar'
        ), false, null);
    }

    /**
     * Is the given application inactive?
     *
     * @param string $app  The application to check.
     *
     * @return boolean  True if inactive.
     */
    public function isInactive($app)
    {
        return (!isset($this->applications[$app]) ||
                ($this->applications[$app]['status'] == 'inactive') ||
                (($this->applications[$app]['status'] == 'admin') &&
                 !$this->isAdmin()) ||
                (($this->applications[$app]['status'] == 'noadmin') &&
                 $this->currentProcessAuth() &&
                 $this->isAdmin()));
    }

    /**
     * Returns all available registry APIs.
     *
     * @return array  The API list.
     */
    public function listAPIs()
    {
        if (empty($this->_apiList) && !empty($this->_interfaces)) {
            $apis = array();

            foreach (array_keys($this->_interfaces) as $interface) {
                list($api,) = explode('/', $interface, 2);
                $apis[$api] = true;
            }

            $this->_apiList = array_keys($apis);
        }

        return $this->_apiList;
    }

    /**
     * Returns all of the available registry methods, or alternately
     * only those for a specified API.
     *
     * @param string $api  Defines the API for which the methods shall be
     *                     returned. If null, returns all methods.
     *
     * @return array  The method list.
     */
    public function listMethods($api = null)
    {
        $methods = array();

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
                    } elseif (($api_ob = $this->_loadApi($app)) &&
                              (is_null($api) || ($method == $api))) {
                        foreach ($api_ob->methods() as $service) {
                            $methods[$method . '/' . $service] = true;
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
        return !empty($this->_interfaces[$interface])
            ? $this->_interfaces[$interface]
            : false;
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
        return $this->_doHasSearch($method, $app, 'methods');
    }

    /**
     * Determine if a link has been registered with the registry.
     *
     * @since 2.12.0
     *
     * @param string $method  The full name of the link method to check for.
     * @param string $app     Only check this application.
     *
     * @return mixed  The application implementing $method if we have it,
     *                false if the link method doesn't exist.
     */
    public function hasLink($method, $app = null)
    {
        return $this->_doHasSearch($method, $app, 'links');
    }

    /**
     * Do the has*() search.
     *
     * @see hasMethod
     * @see hasLink
     *
     * @param string $func  The API function to call to get the list of
     *                      elements to search. Either 'methods' or 'links'.
     *
     * @return mixed  The application implementing $method, false if it
     *                doesn't exist;
     */
    protected function _doHasSearch($method, $app, $func)
    {
        if (is_null($app)) {
            if (($lookup = $this->_methodLookup($method)) === false) {
                return false;
            }
            list($app, $call) = $lookup;
        } else {
            $call = $method;
        }

        if ($api_ob = $this->_loadApi($app)) {
            switch ($func) {
            case 'links':
                $links = $api_ob->links();
                return isset($links[$call]) ? $app : false;

            case 'methods':
                return in_array($call, $api_ob->methods()) ? $app : false;
            }
        }

        return false;
    }

    /**
     * Return the hook corresponding to the default package that provides the
     * functionality requested by the $method parameter.
     * $method is a string consisting of "packagetype/methodname".
     *
     * @param string $method  The method to call.
     * @param array $args     Arguments to the method.
     *
     * @return mixed  Return from method call.
     * @throws Horde_Exception
     */
    public function call($method, $args = array())
    {
        if (($lookup = $this->_methodLookup($method)) === false) {
            throw new Horde_Exception('The method "' . $method . '" is not defined in the Horde Registry.');
        }

        return $this->callByPackage($lookup[0], $lookup[1], $args);
    }

    /**
     * Output the hook corresponding to the specific package named.
     *
     * @param string $app     The application being called.
     * @param string $call    The method to call.
     * @param array $args     Arguments to the method.
     * @param array $options  Additional options:
     *   - noperms: (boolean) If true, don't check the perms.
     *
     * @return mixed  Return from application call.
     * @throws Horde_Exception_PushApp
     */
    public function callByPackage($app, $call, array $args = array(),
                                  array $options = array())
    {
        /* Note: calling hasMethod() makes sure that we've cached
         * $app's services and included the API file, so we don't try
         * to do it again explicitly in this method. */
        if (!$this->hasMethod($call, $app)) {
            throw new Horde_Exception(sprintf('The method "%s" is not defined in the API for %s.', $call, $app));
        }

        /* Load the API now. */
        $methods = ($api_ob = $this->_loadApi($app))
            ? $api_ob->methods()
            : array();

        /* Make sure that the function actually exists. */
        if (!in_array($call, $methods)) {
            throw new Horde_Exception('The function implementing ' . $call . ' is not defined in ' . $app . '\'s API.');
        }

        /* Switch application contexts now, if necessary, before
         * including any files which might do it for us. Return an
         * error immediately if pushApp() fails. */
        $pushed = $this->pushApp($app, array(
            'check_perms' => !in_array($call, $api_ob->noPerms()) && empty($options['noperms']) && $this->currentProcessAuth()
        ));

        try {
            $result = call_user_func_array(array($api_ob, $call), $args);
            if ($result instanceof PEAR_Error) {
                $result = new Horde_Exception_Wrapped($result);
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
            throw $result;
        }

        return $result;
    }

    /**
     * Call a private Horde application method.
     *
     * @param string $app     The application name.
     * @param string $call    The method to call.
     * @param array $options  Additional options:
     *   - args: (array) Additional parameters to pass to the method.
     *   - check_missing: (boolean) If true, throws an Exception if method
     *                    does not exist. Otherwise, will return null.
     *   - noperms: (boolean) If true, don't check the perms.
     *
     * @return mixed  Various.
     *
     * @throws Horde_Exception  Application methods should throw this if there
     *                          is a fatal error.
     * @throws Horde_Exception_PushApp
     */
    public function callAppMethod($app, $call, array $options = array())
    {
        /* Load the API now. */
        try {
            $api = $this->getApiInstance($app, 'application');
        } catch (Horde_Exception $e) {
            if (empty($options['check_missing'])) {
                return null;
            }
            throw $e;
        }

        if (!method_exists($api, $call)) {
            if (empty($options['check_missing'])) {
                return null;
            }
            throw new Horde_Exception('Method does not exist.');
        }

        /* Switch application contexts now, if necessary, before
         * including any files which might do it for us. Return an
         * error immediately if pushApp() fails. */
        $pushed = $this->pushApp($app, array(
            'check_perms' => empty($options['noperms']) && $this->currentProcessAuth()
        ));

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
     * Returns the link corresponding to the default package that provides the
     * functionality requested by the $method parameter.
     *
     * @param string $method  The method to link to, consisting of
     *                        "packagetype/methodname".
     * @param array $args     Arguments to the method.
     * @param mixed $extra    Extra, non-standard arguments to the method.
     *
     * @return string  The link for that method.
     * @throws Horde_Exception
     */
    public function link($method, $args = array(), $extra = '')
    {
        if (($lookup = $this->_methodLookup($method)) === false) {
            throw new Horde_Exception('The link "' . $method . '" is not defined in the Horde Registry.');
        }

        return $this->linkByPackage($lookup[0], $lookup[1], $args, $extra);
    }

    /**
     * Returns the link corresponding to the specific package named.
     *
     * @param string $app   The application being called.
     * @param string $call  The method to link to.
     * @param array $args   Arguments to the method.
     * @param mixed $extra  Extra, non-standard arguments to the method.
     *
     * @return string  The link for that method.
     * @throws Horde_Exception
     */
    public function linkByPackage($app, $call, $args = array(), $extra = '')
    {
        $links = ($api_ob = $this->_loadApi($app))
            ? $api_ob->links()
            : array();

        /* Make sure the link is defined. */
        if (!isset($links[$call])) {
            throw new Horde_Exception('The link "' . $call . '" is not defined in ' . $app . '\'s API.');
        }

        /* Initial link value. */
        $link = $links[$call];

        /* Fill in html-encoded arguments. */
        foreach ($args as $key => $val) {
            $link = str_replace('%' . $key . '%', htmlentities($val), $link);
        }

        $link = $this->applicationWebPath($link, $app);

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
     * Do a lookup of method name -> app call.
     *
     * @param string $method  The method name.
     *
     * @return mixed  An array containing the app and method call, or false
     *                if not found.
     */
    protected function _methodLookup($method)
    {
        list($interface, $call) = explode('/', $method, 2);
        if (!empty($this->_interfaces[$method])) {
            return array($this->_interfaces[$method], $call);
        } elseif (!empty($this->_interfaces[$interface])) {
            return array($this->_interfaces[$interface], $call);
        }

        return false;
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
            throw new Horde_Exception(sprintf(Horde_Core_Translation::t("\"%s\" is not configured in the Horde Registry."), $app));
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
        return str_replace('%application%', $this->get('webroot', $app), $path);
    }

    /**
     * TODO
     *
     * @param string $type       The type of link.
     * <pre>
     * The following must be defined in Horde's menu config, or else they
     * won't be displayed in the menu:
     * 'help', 'problem', 'logout', 'login', 'prefs'
     * </pre>
     *
     * @return boolean  True if the link is to be shown.
     */
    public function showService($type)
    {
        global $conf;

        if (!in_array($type, array('help', 'problem', 'logout', 'login', 'prefs'))) {
            return true;
        }

        if (empty($conf['menu']['links'][$type])) {
            return false;
        }

        switch ($conf['menu']['links'][$type]) {
        case 'all':
            return true;

        case 'authenticated':
            return (bool)$this->getAuth();

        default:
        case 'never':
            return false;
        }
    }

    /**
     * Returns the URL to access a Horde service.
     *
     * @param string $type       The service to display:
     *   - ajax: AJAX endpoint.
     *   - cache: Cached data output.
     *   - download: Download link.
     *   - emailconfirm: E-mail confirmation page.
     *   - go: URL redirection utility.
     *   - help: Help page.
     *   - imple: Imple endpoint.
     *   - login: Login page.
     *   - logintasks: Logintasks page.
     *   - logout: Logout page.
     *   - pixel: Pixel generation page.
     *   - portal: Main portal page.
     *   - prefs: Preferences UI.
     *   - problem: Problem reporting page.
     * @param string $app        The name of the current Horde application.
     * @param boolean $full      Return a full url? @since 2.4.0
     *
     * @return Horde_Url  The link.
     * @throws Horde_Exception
     */
    public function getServiceLink($type, $app = null, $full = false)
    {
        $opts = array('app' => 'horde');

        switch ($type) {
        case 'ajax':
            if (is_null($app)) {
                $app = 'horde';
            }
            return Horde::url('services/ajax.php/' . $app . '/', $full, $opts)
                       ->add('token', $GLOBALS['session']->getToken());

        case 'cache':
            $opts['append_session'] = -1;
            return Horde::url('services/cache.php', $full, $opts);

        case 'download':
            return Horde::url('services/download/', $full, $opts)
                ->add('app', $app);

        case 'emailconfirm':
            return Horde::url('services/confirm.php', $full, $opts);

        case 'go':
            return Horde::url('services/go.php', $full, $opts);

        case 'help':
            return Horde::url('services/help/', $full, $opts)
                ->add('module', $app);

        case 'imple':
            return Horde::url('services/imple.php', $full, $opts);

        case 'login':
            return Horde::url('login.php', $full, $opts);

        case 'logintasks':
            return Horde::url('services/logintasks.php', $full, $opts)
                ->add('app', $app);

        case 'logout':
            return $this->getLogoutUrl(array(
                'reason' => Horde_Auth::REASON_LOGOUT
            ));

        case 'pixel':
            return Horde::url('services/images/pixel.php', $full, $opts);

        case 'prefs':
            if (!in_array($GLOBALS['conf']['prefs']['driver'], array('', 'none'))) {
                $url = Horde::url('services/prefs.php', $full, $opts);
                if (!is_null($app)) {
                    $url->add('app', $app);
                }
                return $url;
            }
            break;

        case 'portal':
            return ($this->getView() == Horde_Registry::VIEW_SMARTMOBILE)
                ? Horde::url('services/portal/smartmobile.php', $full, $opts)
                : Horde::url('services/portal/', $full, $opts);
            break;

        case 'problem':
            return Horde::url('services/problem.php', $full, $opts)
                ->add('return_url', Horde::selfUrl(true, true, true));

        case 'sidebar':
            return Horde::url('services/sidebar.php', $full, $opts);

        case 'twitter':
            return Horde::url('services/twitter/', true);
        }

        throw new BadFunctionCallException('Invalid service requested: ' . print_r(debug_backtrace(false), true));
    }

    /**
     * Set the current application, adding it to the top of the Horde
     * application stack. If this is the first application to be
     * pushed, retrieve session information as well.
     *
     * pushApp() also reads the application's configuration file and
     * sets up its global $conf hash.
     *
     * @param string $app     The name of the application to push.
     * @param array $options  Additional options:
     *   - check_perms: (boolean) Make sure that the current user has
     *                  permissions to the application being loaded. Should
     *                  ONLY be disabled by system scripts (cron jobs, etc.)
     *                  and scripts that handle login.
     *                  DEFAULT: true
     *   - logintasks: (boolean) Perform login tasks? Only performed if
     *                 'check_perms' is also true. System tasks are always
     *                 peformed if the user is authorized.
     *                 DEFAULT: false
     *   - notransparent: (boolean) Do not attempt transparent authentication.
     *                    DEFAULT: false
     *
     * @return boolean  Whether or not the _appStack was modified.
     * @throws Horde_Exception_PushApp
     */
    public function pushApp($app, array $options = array())
    {
        global $injector, $notification, $language, $session;

        if ($app == $this->getApp()) {
            return false;
        }

        /* Bail out if application is not present or inactive. */
        if ($this->isInactive($app)) {
            throw new Horde_Exception_PushApp(
                sprintf(
                    Horde_Core_Translation::t("%s is not activated."),
                    $this->applications[$app]['name']
                ),
                self::NOT_ACTIVE,
                $app
            );
        }

        $checkPerms = ((!isset($options['check_perms']) ||
                       !empty($options['check_perms'])) &&
                       $this->currentProcessAuth());

        /* If permissions checking is requested, return an error if the
         * current user does not have read perms to the application being
         * loaded. We allow access:
         *  - To all admins.
         *  - To all authenticated users if no permission is set on $app.
         *  - To anyone who is allowed by an explicit ACL on $app. */
        if ($checkPerms) {
            $error = $error_log = null;
            $error_app = $this->applications[$app]['name'];
            $error_type = self::AUTH_FAILURE;

            if (($auth = $this->getAuth()) && !$this->checkExistingAuth()) {
                $error = '%s is not authorized %s(Remote host: %s)';
                $error_app = '';
            }

            if (!$error &&
                !$this->hasPermission($app, Horde_Perms::READ, array('notransparent' => !empty($options['notransparent'])))) {
                $error = '%s is not authorized for %s (Host: %s).';

                if ($this->isAuthenticated(array('app' => $app))) {
                    $error_log = '%s does not have READ permission for %s (Host: %s)';
                    $error_type = self::PERMISSION_DENIED;
                }
            }

            if ($error) {
                $auth = $auth ? 'User ' . $auth : 'Guest user';
                $remote = $this->remoteHost();

                if ($error_log) {
                    Horde::log(
                        sprintf($error_log, $auth, $error_app, $remote->host),
                        'DEBUG'
                    );
                }

                throw new Horde_Exception_PushApp(
                    sprintf($error, $auth, $error_app, $remote->host),
                    $error_type,
                    $app
                );
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
        $this->setLanguageEnvironment($language, $app);

        /* Load config and prefs. */
        $this->importConfig('horde');
        $this->importConfig($app);
        $this->loadPrefs($app);

        /* Reset language, since now we can grab language from prefs. */
        if (!$checkPerms && (count($this->_appStack) == 1)) {
            $this->setLanguageEnvironment(null, $app);
        }

        /* Run authenticated hooks, if necessary. */
        $hooks = $injector->getInstance('Horde_Core_Hooks');
        if ($session->get('horde', 'auth_app_init/' . $app)) {
            try {
                $error = self::INITCALLBACK_FATAL;
                $this->callAppMethod($app, 'authenticated');

                $error = self::HOOK_FATAL;
                $hooks->callHook('appauthenticated', $app);
            } catch (Exception $e) {
                $this->_pushAppError($e, $error);
            }

            $session->remove('horde', 'auth_app_init/' . $app);
            unset($this->_appsInit[$app]);
        }

        /* Initialize application. */
        if (!isset($this->_appsInit[$app])) {
            $notification->addAppHandler($app);

            try {
                $error = self::INITCALLBACK_FATAL;
                $this->callAppMethod($app, 'init');

                $error = self::HOOK_FATAL;
                $hooks->callHook('pushapp', $app);
            } catch (Exception $e) {
                $this->_pushAppError($e, $error);
            }

            $this->_appsInit[$app] = true;
        }

        /* Do login tasks. */
        if ($checkPerms &&
            !empty($options['logintasks']) &&
            ($tasks = $injector->getInstance('Horde_Core_Factory_LoginTasks')->create($app))) {
            $tasks->runTasks();
        }

        return true;
    }

    /**
     * Process Exceptions thrown when pushing app on stack.
     *
     * @param Exception $e    The thrown Exception.
     * @param integer $error  The pushApp() error type.
     *
     * @throws Horde_Exception_PushApp
     */
    protected function _pushAppError(Exception $e, $error)
    {
        if ($e instanceof Horde_Exception_HookNotSet) {
            return;
        }

        /* Hook errors are already logged. */
        if ($error == self::INITCALLBACK_FATAL) {
            Horde::log($e);
        }

        $app = $this->getApp();
        $this->applications[$app]['status'] = 'inactive';
        $this->popApp();

        throw new Horde_Exception_PushApp($e, $error, $app);
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
            /* Load config and prefs. */
            $this->importConfig('horde');
            $this->importConfig($app);
            $this->loadPrefs($app);
            $this->setTextdomain(
                $app,
                $this->get('fileroot', $app) . '/locale'
            );
        }

        return $previous;
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
     * @param array $params   Additional options:
     *   - notransparent: (boolean) Do not attempt transparent authentication.
     *                    DEFAULT: false
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
            $GLOBALS['injector']->getInstance('Horde_Core_Factory_Auth')->create($app)->requireAuth() &&
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
     * @param string $app  The application name.
     */
    public function importConfig($app)
    {
        /* Make sure Horde is always loaded. */
        if (!isset($this->_cache['conf']['horde'])) {
            $this->_cache['conf']['horde'] = new Horde_Registry_Hordeconfig(array('app' => 'horde'));
        }

        if (!isset($this->_cache['conf'][$app])) {
            $this->_cache['conf'][$app] = new Horde_Registry_Hordeconfig_Merged(array(
                'aconfig' => new Horde_Registry_Hordeconfig(array('app' => $app)),
                'hconfig' => $this->_cache['conf']['horde']
            ));
        }

        $GLOBALS['conf'] = $this->_cache['conf'][$app]->toArray();
    }

    /**
     * Loads the preferences for the current user for the current application
     * and imports them into the global $prefs variable.
     * $app will be the active application after calling this function.
     *
     * @param string $app  The name of the application.
     *
     * @throws Horde_Exception
     */
    public function loadPrefs($app = null)
    {
        global $injector, $prefs;

        if (strlen($app)) {
            $this->pushApp($app);
        } elseif (($app = $this->getApp()) === false) {
            $app = 'horde';
        }

        $user = $this->getAuth();
        if ($user) {
            if (isset($prefs) && ($prefs->getUser() == $user)) {
                $prefs->changeScope($app);
                return;
            }

            $opts = array(
                'user' => $user
            );
        } else {
            /* If there is no logged in user, return an empty Horde_Prefs
             * object with just default preferences. */
            $opts = array(
                'driver' => 'Horde_Prefs_Storage_Null'
            );
        }

        $prefs = $injector->getInstance('Horde_Core_Factory_Prefs')->create($app, $opts);
    }

    /**
     * Load a configuration file from a Horde application's config directory.
     * This call is cached (a config file is only loaded once, regardless of
     * the $vars value).
     *
     * @since 2.12.0
     *
     * @param string $conf_file  Configuration file name.
     * @param mixed $vars        List of config variables to load.
     * @param string $app        Application.
     *
     * @return Horde_Registry_Loadconfig  The config object.
     * @throws Horde_Exception
     */
    public function loadConfigFile($conf_file, $vars = null, $app = null)
    {
        if (is_null($app)) {
            $app = $this->getApp();
        }

        if (!isset($this->_cache['cfile'][$app][$conf_file])) {
            $this->_cache['cfile'][$app][$conf_file] = new Horde_Registry_Loadconfig(
                $app,
                $conf_file,
                $vars
            );
        }

        return $this->_cache['cfile'][$app][$conf_file];
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
            switch ($parameter) {
            case 'icon':
                $pval = Horde_Themes::img($app . '.png', $app);
                if ((string)$pval == '') {
                    $pval = Horde_Themes::img('app-unknown.png', 'horde');
                }
                break;

            case 'initial_page':
                $pval = null;
                break;

            default:
                $pval = isset($this->applications['horde'][$parameter])
                    ? $this->applications['horde'][$parameter]
                    : null;
                break;
            }
        }

        return ($parameter == 'name')
            ? (strlen($pval) ? _($pval) : '')
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
     * Does the application have the queried feature?
     *
     * @param string $id   Feature ID.
     * @param string $app  The application to check (defaults to current app).
     *
     * @return boolean  True if the application has the feature.
     */
    public function hasFeature($id, $app = null)
    {
        if (empty($app)) {
            $app = $this->getApp();
        }

        try {
            $api = $this->getApiInstance($app, 'application');
        } catch (Horde_Exception $e) {
            return false;
        }

        return !empty($api->features[$id]);
    }

    /**
     * Does the given application have the queried view?
     *
     * @param integer $view  The view type (VIEW_* constant).
     * @param string $app    The application to check (defaults to current
     *                       app).
     *
     * @return boolean  True if the view is available in the application.
     */
    public function hasView($view, $app = null)
    {
        switch ($view) {
        case self::VIEW_BASIC:
            // For now, consider all apps to have BASIC view.
            return true;

        case self::VIEW_DYNAMIC:
            return $this->hasFeature('dynamicView', $app);

        case self::VIEW_MINIMAL:
            return $this->hasFeature('minimalView', $app);

        case self::VIEW_SMARTMOBILE:
            return $this->hasFeature('smartmobileView', $app);
        }
    }

    /**
     * Set current view.
     *
     * @param integer $view  The view type.
     */
    public function setView($view = self::VIEW_BASIC)
    {
        $GLOBALS['session']->set('horde', 'view', $view);
    }

    /**
     * Get current view.
     *
     * @return integer  The view type.
     */
    public function getView()
    {
        global $session;

        return $session->exists('horde', 'view')
            ? $session->get('horde', 'view')
            : self::VIEW_BASIC;
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

        if (!is_null($fileroot)) {
            try {
                $pushed = $this->pushApp($app);
            } catch (Horde_Exception $e) {
                if ($e->getCode() == Horde_Registry::AUTH_FAILURE) {
                    return array();
                }
                throw $e;
            }

            if (is_dir($fileroot . '/lib/' . $fileprefix)) {
                try {
                    $di = new DirectoryIterator($fileroot . '/lib/' . $fileprefix);

                    foreach ($di as $val) {
                        if (!$val->isDir() && !$di->isDot()) {
                            $class = $app . '_' . $prefix . '_' . basename($val, '.php');
                            if (class_exists($class)) {
                                $classes[] = $class;
                            }
                        }
                    }
                } catch (UnexpectedValueException $e) {}
            }

            if ($pushed) {
                $this->popApp();
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
        try {
            if (($url = $this->callAppMethod($app, 'getInitialPage')) !== null) {
                return $url;
            }
        } catch (Horde_Exception $e) {}

        if (($webroot = $this->get('webroot', $app)) !== null) {
            return $webroot . '/' . strval($this->get('initial_page', $app));
        }

        throw new Horde_Exception(sprintf(
            Horde_Core_Translation::t("\"%s\" is not configured in the Horde Registry."),
            is_null($app) ? $this->getApp() : $app
        ));
    }

    /**
     * Clears any authentication tokens in the current session.
     *
     * @param boolean $destroy  Destroy the session?
     */
    public function clearAuth($destroy = true)
    {
        global $session;

        /* Do application logout tasks. */
        /* @todo: Replace with exclusively registered logout tasks. */
        foreach ($this->getAuthApps() as $app) {
            try {
                $this->callAppMethod($app, 'logout');
            } catch (Horde_Exception $e) {}
        }

        /* Do registered logout tasks. */
        $logout = new Horde_Registry_Logout();
        $logout->run();

        $session->remove('horde', 'auth');
        $session->remove('horde', 'auth_app/');

        $this->_cache['auth'] = null;
        $this->_cache['existing'] = $this->_cache['isauth'] = array();

        if ($destroy) {
            $session->destroy();
        }
    }

    /**
     * Clears authentication tokens for a given application in the current
     * session.
     *
     * @return boolean  If false, did not remove authentication token because
     *                  the application is in control of Horde's auth.
     */
    public function clearAuthApp($app)
    {
        global $session;

        if ($session->get('horde', 'auth/credentials') == $app) {
            return false;
        }

        if ($this->isAuthenticated(array('app' => $app, 'notransparent' => true))) {
            $this->callAppMethod($app, 'logout');
            $session->remove($app);
            $session->remove('horde', 'auth_app/' . $app);
            $session->remove('horde', 'auth_app_init/' . $app);
        }

        unset(
            $this->_cache['existing'][$app],
            $this->_cache['isauth'][$app]
        );

        return true;
    }

    /**
     * Is a user an administrator?
     *
     * @param array $options  Options:
     *   - permission: (string) Allow users with this permission admin access
     *                 in the current context.
     *   - permlevel: (integer) The level of permissions to check for.
     *                Defaults to Horde_Perms::EDIT.
     *   - user: (string) The user to check.
     *           Defaults to self::getAuth().
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
     * @param array $opts  Additional options:
     *   - app: (string) Check authentication for this app.
     *          DEFAULT: Checks horde-wide authentication.
     *   - notransparent: (boolean) Do not attempt transparent authentication.
     *                    DEFAULT: false
     *
     * @return boolean  Whether or not the user is authenticated.
     */
    public function isAuthenticated(array $opts = array())
    {
        global $injector, $session;

        $app = empty($opts['app'])
            ? 'horde'
            : $opts['app'];
        $transparent = intval(empty($opts['notransparent']));

        if (isset($this->_cache['isauth'][$app][$transparent])) {
            return $this->_cache['isauth'][$app][$transparent];
        }

        /* Check for cached authentication results. */
        if ($this->getAuth() &&
            (($app == 'horde') ||
             $session->exists('horde', 'auth_app/' . $app)) &&
            $this->checkExistingAuth($app)) {
            $res = true;
        } elseif ($transparent) {
            try {
                $res = $injector->getInstance('Horde_Core_Factory_Auth')->create($app)->transparent();
            } catch (Horde_Exception $e) {
                Horde::log($e);
                $res = false;
            }
        } else {
            $res = false;
        }

        $this->_cache['isauth'][$app][$transparent] = $res;

        return $res;
    }

    /**
     * Checks whether this process required authentication.
     *
     * @since 2.11.0
     *
     * @return boolean  True if the current process required authentication.
     */
    public function currentProcessAuth()
    {
        return ($this->_args['authentication'] !== 'none');
    }

    /**
     * Returns a URL to the login screen, adding the necessary logout
     * parameters.
     *
     * If no reason/msg is passed in, uses the current global authentication
     * error message.
     *
     * @param array $options  Additional options:
     *     - app: (string) Authenticate to this application
     *            DEFAULT: Horde
     *     - msg: (string) If reason is Horde_Auth::REASON_MESSAGE, the message
     *            to display to the user.
     *            DEFAULT: None
     *     - params: (array) Additional params to add to the URL (not allowed:
     *               'app', 'horde_logout_token', 'msg', 'reason', 'url').
     *               DEFAULT: None
     *     - reason: (integer) The reason for logout
     *               DEFAULT: None
     *
     * @return Horde_Url  The formatted URL.
     */
    public function getLogoutUrl(array $options = array())
    {
        if (!isset($options['reason'])) {
            // TODO: This only returns the error for Horde-wide
            // authentication, not for application auth.
            $options['reason'] = $GLOBALS['injector']->getInstance('Horde_Core_Factory_Auth')->create()->getError();
        }

        $params = array();
        if (!in_array($options['reason'], array(Horde_Auth::REASON_LOGOUT, Horde_Auth::REASON_MESSAGE))) {
            $params['url'] = Horde::selfUrl(true, true, true);
        }

        if (empty($options['app']) ||
            ($options['app'] == 'horde') ||
            ($options['reason'] == Horde_Auth::REASON_LOGOUT)) {
            $params['horde_logout_token'] = $GLOBALS['session']->getToken();
       }

        if (isset($options['app'])) {
            $params['app'] = $options['app'];
        }

        if ($options['reason']) {
            $params['logout_reason'] = $options['reason'];
            if ($options['reason'] == Horde_Auth::REASON_MESSAGE) {
                $params['logout_msg'] = empty($options['msg'])
                    ? $GLOBALS['injector']->getInstance('Horde_Core_Factory_Auth')->create()->getError(true)
                    : $options['msg'];
            }
        }

        return $this->getServiceLink('login', 'horde')->add($params)->setRaw(true);
    }

    /**
     * Returns a URL to be used for downloading data.
     *
     * @param string $filename  The filename of the download data.
     * @param array $params     Additional URL parameters needed.
     *
     * @return Horde_Url  The download URL. This URL should be used as-is,
     *                    since the filename MUST be the last parameter added
     *                    to the URL.
     */
    public function downloadUrl($filename, array $params = array())
    {
        $url = $this->getServiceLink('download', $this->getApp())
            /* Add parameters. */
            ->add($params);

        if (strlen($filename)) {
            /* Add the filename to the end of the URL. Although not necessary
             * for many browsers, this should allow every browser to download
             * correctly. */
            $url->add('fn', '/' . $filename);
        }

        return $url;
    }

    /**
     * Converts an authentication username to a unique Horde username.
     *
     * @param string $userId    The username to convert.
     * @param boolean $toHorde  If true, convert to a Horde username. If
     *                          false, convert to the auth username.
     *
     * @return string  The converted username.
     * @throws Horde_Exception
     */
    public function convertUsername($userId, $toHorde)
    {
        try {
            return $GLOBALS['injector']->getInstance('Horde_Core_Hooks')->
                callHook('authusername', 'horde', array($userId, $toHorde));
        } catch (Horde_Exception_HookNotSet $e) {
            return $userId;
        }
    }

    /**
     * Returns the currently logged in user, if there is one.
     *
     * @param string $format  The return format, defaults to the unique Horde
     *                        ID. Alternative formats:
     *   - bare: (string) Horde ID without any domain information.
     *           EXAMPLE: foo@example.com would be returned as 'foo'.
     *   - domain: (string) Domain of the Horde ID.
     *             EXAMPLE: foo@example.com would be returned as 'example.com'.
     *   - original: (string) The username used to originally login to Horde.
     *
     * @return mixed  The user ID or false if no user is logged in.
     */
    public function getAuth($format = null)
    {
        global $session;

        if (is_null($format) && !is_null($this->_cache['auth'])) {
            return $this->_cache['auth'];
        }

        if (!isset($session)) {
            return false;
        }

        if ($format == 'original') {
            return $session->exists('horde', 'auth/authId')
                ? $session->get('horde', 'auth/authId')
                : false;
        }

        $user = $session->get('horde', 'auth/userId');
        if (is_null($user)) {
            return false;
        }

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
            /* Specifically cache this result, since it generally is called
             * many times in a page. */
            $this->_cache['auth'] = $user;
            return $user;
        }
    }

    /**
     * Return whether the authentication backend requested a password change.
     *
     * @return boolean  Whether the backend requested a password change.
     */
    public function passwordChangeRequested()
    {
        return (bool)$GLOBALS['session']->get('horde', 'auth/change');
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
        global $session;

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

        $entry = $credentials;
        if (($base_app = $session->get('horde', 'auth/credentials')) &&
            ($session->get('horde', 'auth_app/' . $base_app) == $entry)) {
            $entry = true;
        }

        if (is_null($app)) {
            $app = $base_app;
        }

        /* The auth_app key contains application-specific authentication.
         * Session subkeys are the app names, values are an array containing
         * credentials. If the value is true, application does not require any
         * specific credentials. */
        $session->set('horde', 'auth_app/' . $app, $entry, $session::ENCRYPT);
        $session->set('horde', 'auth_app_init/' . $app, true);

        unset(
            $this->_cache['existing'][$app],
            $this->_cache['isauth'][$app]
        );
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
        global $session;

        $base_app = $session->get('horde', 'auth/credentials');
        if (is_null($base_app)) {
            return false;
        }

        if (is_null($app)) {
            $app = $base_app;
        }

        if (!$session->exists('horde', 'auth_app/' . $app)) {
            return ($base_app != $app)
                ? $this->_getAuthCredentials($base_app)
                : false;
        }

        return $session->get('horde', 'auth_app/' . $app);
    }

    /**
     * Returns information about the remote host.
     *
     * @since 2.17.0
     *
     * @return object  An object with the following properties:
     * <pre>
     *   - addr: (string) Remote IP address.
     *   - host: (string) Remote hostname (if resolvable; otherwise, this value
     *           is identical to 'addr').
     *   - proxy: (boolean) True if this user is connecting through a proxy.
     * </pre>
     */
    public function remoteHost()
    {
        global $injector;

        $out = new stdClass;

        $dns = $injector->getInstance('Net_DNS2_Resolver');
        $old_error = error_reporting(0);

        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $remote_path = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            $out->addr = $remote_path[0];
            $out->proxy = true;
        } else {
            $out->addr = $_SERVER['REMOTE_ADDR'];
            if (!empty($_SERVER['REMOTE_HOST'])) {
                $out->host = $_SERVER['REMOTE_HOST'];
            }
            $out->proxy = false;
        }

        if ($dns && !isset($out->host)) {
            $out->host = $out->addr;
            try {
                if ($response = $dns->query($out->addr, 'PTR')) {
                    foreach ($response->answer as $val) {
                        if (isset($val->ptrdname)) {
                            $out->host = $val->ptrdname;
                            break;
                        }
                    }
                }
            } catch (Net_DNS2_Exception $e) {}
        } elseif (!isset($out->host)) {
            $out->host = gethostbyaddr($out->addr);
        }
        error_reporting($old_error);

        return $out;
    }

    /**
     * Sets data in the session saying that authorization has succeeded,
     * note which userId was authorized, and note when the login took place.
     *
     * If a user name hook was defined in the configuration, it gets applied
     * to the $userId at this point.
     *
     * @param string $authId      The userId that has been authorized.
     * @param array $credentials  The credentials of the user.
     * @param array $options      Additional options:
     *   - app: (string) The app to set authentication credentials for.
     *          DEFAULT: 'horde'
     *   - change: (boolean) Whether to request that the user change their
     *             password.
     *             DEFAULT: No
     *   - language: (string) The preferred language.
     *               DEFAULT: null
     */
    public function setAuth($authId, $credentials, array $options = array())
    {
        global $browser, $injector, $session;

        $app = empty($options['app'])
            ? 'horde'
            : $options['app'];

        if ($this->getAuth() == $authId) {
            /* Store app credentials - base Horde session already exists. */
            $this->setAuthCredential($credentials, null, $app);
            return;
        }

        /* Initial authentication to Horde. */
        $session->set('horde', 'auth/authId', $authId);
        $session->set('horde', 'auth/browser', $browser->getAgentString());
        if (!empty($options['change'])) {
            $session->set('horde', 'auth/change', 1);
        }
        $session->set('horde', 'auth/credentials', $app);

        $remote = $this->remoteHost();
        $session->set('horde', 'auth/remoteAddr', $remote->addr);

        $session->set('horde', 'auth/timestamp', time());
        $session->set('horde', 'auth/userId', $this->convertUsername(trim($authId), true));

        $this->_cache['auth'] = null;
        $this->_cache['existing'] = $this->_cache['isauth'] = array();

        $this->setAuthCredential($credentials, null, $app);

        /* Reload preferences for the new user. */
        unset($GLOBALS['prefs']);
        $this->loadPrefs($this->getApp());

        $this->setLanguageEnvironment(isset($options['language']) ? $this->preferredLang($options['language']) : null, $app);
    }

    /**
     * Check existing auth for triggers that might invalidate it.
     *
     * @param string $app  Check authentication for this app too.
     *
     * @return boolean  Is existing auth valid?
     */
    public function checkExistingAuth($app = 'horde')
    {
        global $browser, $conf, $injector, $session;

        if (!empty($this->_cache['existing'][$app])) {
            return true;
        }

        /* Tasks that only need to run once. */
        if (empty($this->_cache['existing'])) {
            if (!empty($conf['auth']['checkip']) &&
                ($remoteaddr = $session->get('horde', 'auth/remoteAddr')) &&
                ($remoteob = $this->remoteHost()) &&
                ($remoteaddr != $remoteob->addr)) {
                $injector->getInstance('Horde_Core_Factory_Auth')->create()
                    ->setError(Horde_Core_Auth_Application::REASON_SESSIONIP);
                return false;
            }

            if (!empty($conf['auth']['checkbrowser']) &&
                ($session->get('horde', 'auth/browser') != $browser->getAgentString())) {
                $injector->getInstance('Horde_Core_Factory_Auth')->create()
                    ->setError(Horde_Core_Auth_Application::REASON_BROWSER);
                return false;
            }

            if (!empty($conf['session']['max_time']) &&
                (($conf['session']['max_time'] + $session->begin) < time())) {
                $injector->getInstance('Horde_Core_Factory_Auth')->create()
                    ->setError(Horde_Core_Auth_Application::REASON_SESSIONMAXTIME);
                return false;
            }
        }

        foreach (array_unique(array('horde', $app)) as $val) {
            if (!isset($this->_cache['existing'][$val])) {
                $auth = $injector->getInstance('Horde_Core_Factory_Auth')->create($val);
                if (!$auth->validateAuth()) {
                    if (!$auth->getError()) {
                        $auth->setError(Horde_Auth::REASON_SESSION);
                    }
                    return false;
                }
                $this->_cache['existing'][$val] = true;
            }
        }

        return true;
    }

    /**
     * Removes a user from the authentication backend and calls all
     * applications' removeUserData API methods.
     *
     * @param string $userId  The userId to delete.
     *
     * @throws Horde_Exception
     */
    public function removeUser($userId)
    {
        $GLOBALS['injector']
            ->getInstance('Horde_Core_Factory_Auth')
            ->create()
            ->removeUser($userId);
        $this->removeUserData($userId);
    }

    /**
     * Removes user's application data.
     *
     * @param string $user  The user ID to delete.
     * @param string $app   If set, only removes data from this application.
     *                      By default, removes data from all apps.
     *
     * @throws Horde_Exception
     */
    public function removeUserData($user, $app = null)
    {
        if (!$this->isAdmin() && ($user != $this->getAuth())) {
            throw new Horde_Exception(Horde_Core_Translation::t("You are not allowed to remove user data."));
        }

        $applist = empty($app)
            ? $this->listApps(array('notoolbar', 'hidden', 'active', 'admin', 'noadmin'))
            : array($app);
        $errApps = array();
        if (!empty($applist)) {
            $prefs_ob = $GLOBALS['injector']->getInstance('Horde_Core_Factory_Prefs')->create('horde', array(
                'user' => $user
            ));
        }

        foreach ($applist as $app) {
            try {
                $this->callAppMethod($app, 'removeUserData', array(
                    'args' => array($user)
                ));
            } catch (Exception $e) {
                Horde::log($e);
                $errApps[] = $app;
            }

            try {
                $prefs_ob->retrieve($app);
                $prefs_ob->remove();
            } catch (Horde_Exception $e) {
                Horde::log($e);
                $errApps[] = $app;
            }
        }

        if (count($errApps)) {
            throw new Horde_Exception(sprintf(Horde_Core_Translation::t("The following applications encountered errors removing user data: %s"), implode(', ', array_unique($errApps))));
        }
    }

    /**
     * Returns authentication metadata information.
     *
     * @since 2.12.0
     *
     * @return array  Authentication metadata:
     *   - authId: (string) The username used during the original auth.
     *   - browser: (string) The remote browser string.
     *   - change: (boolean) Is a password change requested?
     *   - credentials: (string) The 'auth_app' entry that contains the Horde
     *                 credentials.
     *   - remoteAddr: (string) The remote IP address of the user.
     *   - timestamp: (integer) The login time.
     *   - userId: (string) The unique Horde username.
     */
    public function getAuthInfo()
    {
        global $session;

        return $session->get('horde', 'auth/', $session::TYPE_ARRAY);
    }

    /**
     * Returns the list of applications currently authenticated to.
     *
     * @since 2.12.0
     *
     * @return array  List of authenticated applications.
     */
    public function getAuthApps()
    {
        global $session;

        return array_keys(
            $session->get('horde', 'auth_app/', $session::TYPE_ARRAY)
        );
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
        return ($charset = $this->nlsconfig->curr_charset)
            ? $charset
            : 'ISO-8859-1';
    }

    /**
     * Returns the charset to use for outgoing emails.
     *
     * @return string  The preferred charset for outgoing mails based on
     *                 the user's preferences and the current language.
     */
    public function getEmailCharset()
    {
        if (isset($GLOBALS['prefs']) &&
            ($charset = $GLOBALS['prefs']->getValue('sending_charset'))) {
            return $charset;
        }

        return ($charset = $this->nlsconfig->curr_emails)
            ? $charset
            : $this->getLanguageCharset();
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
        /* Check if we have a language set in the session */
        if ($GLOBALS['session']->exists('horde', 'language')) {
            return basename($GLOBALS['session']->get('horde', 'language'));
        }

        /* If language pref exists, we should use that. */
        if (isset($GLOBALS['prefs']) &&
            ($language = $GLOBALS['prefs']->getValue('language'))) {
            return basename($language);
        }

        /* Check if the user selected a language from the login screen */
        if (!empty($lang) && $this->nlsconfig->validLang($lang)) {
            return basename($lang);
        }

        /* Try browser-accepted languages. */
        if (!empty($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            /* The browser supplies a list, so return the first valid one. */
            $browser_langs = explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE']);
            foreach ($browser_langs as $lang) {
                /* Strip quality value for language */
                if (($pos = strpos($lang, ';')) !== false) {
                    $lang = substr($lang, 0, $pos);
                }

                $lang = $this->_mapLang(trim($lang));
                if ($this->nlsconfig->validLang($lang)) {
                    return basename($lang);
                }

                /* In case there's no full match, save our best guess. Try
                 * ll_LL, followed by just ll. */
                if (!isset($partial_lang)) {
                    $ll_LL = Horde_String::lower(substr($lang, 0, 2)) . '_' . Horde_String::upper(substr($lang, 0, 2));
                    if ($this->nlsconfig->validLang($ll_LL)) {
                        $partial_lang = $ll_LL;
                    } else {
                        $ll = $this->_mapLang(substr($lang, 0, 2));
                        if ($this->nlsconfig->validLang($ll))  {
                            $partial_lang = $ll;
                        }
                    }
                }
            }

            if (isset($partial_lang)) {
                return basename($partial_lang);
            }
        }

        /* Use site-wide default, if one is defined */
        return $this->nlsconfig->curr_default
            ? basename($this->nlsconfig->curr_default)
            /* No dice auto-detecting, default to US English. */
            : 'en_US';
    }

    /**
     * Sets the language.
     *
     * @param string $lang  The language abbreviation.
     *
     * @return string  The current language (since 2.5.0).
     *
     * @throws Horde_Exception
     */
    public function setLanguage($lang = null)
    {
        if (empty($lang) || !$this->nlsconfig->validLang($lang)) {
            $lang = $this->preferredLang();
        }

        $GLOBALS['session']->set('horde', 'language', $lang);

        $changed = false;
        if (isset($GLOBALS['language'])) {
            if ($GLOBALS['language'] == $lang) {
                return $lang;
            }
            $changed = true;
        }

        $GLOBALS['language'] = $lang;

        $lang_charset = $lang . '.UTF-8';
        if (setlocale(LC_ALL, $lang_charset)) {
            putenv('LC_ALL=' . $lang_charset);
            putenv('LANG=' . $lang_charset);
            putenv('LANGUAGE=' . $lang_charset);
        } else {
            $changed = false;
        }

        if ($changed) {
            $this->rebuild();

            $this->_cache['cfile'] = array();

            foreach ($this->listApps() as $app) {
                if ($this->isAuthenticated(array('app' => $app, 'notransparent' => true))) {
                    $this->callAppMethod($app, 'changeLanguage');
                }
            }
        }

        return $lang;
    }

    /**
     * Sets the language and reloads the whole NLS environment.
     *
     * When setting the language, the gettext catalogs have to be reloaded
     * too, charsets have to be updated etc. This method takes care of all
     * this.
     *
     * @param string $lang  The new language.
     * @param string $app   The application for reloading the gettext catalog.
     *                      Uses current application if null.
     */
    public function setLanguageEnvironment($lang = null, $app = null)
    {
        if (empty($app)) {
            $app = $this->getApp();
        }

        $old_lang = $this->setLanguage($lang);

        $this->setTextdomain(
            $app,
            $this->get('fileroot', $app) . '/locale'
        );

        if ($old_lang == $GLOBALS['language']) {
            return;
        }

        $GLOBALS['session']->remove('horde', 'nls/');
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

        /* The existence of this function depends on the platform. */
        if (function_exists('bind_textdomain_codeset')) {
            bind_textdomain_codeset($app, 'UTF-8');
        }
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

        return empty($this->nlsconfig->aliases[$trans_lang])
            ? $trans_lang
            : $this->nlsconfig->aliases[$trans_lang];
    }

    /**
     * Is the registry in 'test' mode?
     *
     * @since 2.12.0
     *
     * @return boolean  True if in testing mode.
     */
    public function isTest()
    {
        return !empty($this->_args['test']);
    }

}
