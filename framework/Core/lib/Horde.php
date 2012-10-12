<?php
/**
 * Provides the base functionality shared by all Horde applications.
 *
 * Copyright 1999-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @author   Jon Parise <jon@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL
 * @package  Core
 */
class Horde
{
    /**
     * The access keys already used in this page.
     *
     * @var array
     */
    static protected $_used = array();

    /**
     * The labels already used in this page.
     *
     * @var array
     */
    static protected $_labels = array();

    /**
     * Are accesskeys supported on this system.
     *
     * @var boolean
     */
    static protected $_noAccessKey;

    /**
     * Whether the hook has already been loaded.
     *
     * @var array
     */
    static protected $_hooksLoaded = array();

    /**
     * The current buffer level.
     *
     * @var integer
     */
    static protected $_bufferLevel = 0;

    /**
     * Has content been sent at the base buffer level?
     *
     * @var boolean
     */
    static protected $_contentSent = false;

    /**
     * Shortcut to logging method.
     *
     * @see Horde_Core_Log_Logger
     */
    static public function log($event, $priority = null,
                               array $options = array())
    {
        /* Chicken/egg: wait until we have basic framework setup before we
         * start logging. */
        if (isset($GLOBALS['conf']) && isset($GLOBALS['injector'])) {
            if (!isset($options['trace'])) {
                $options['trace'] = 0;
            }
            $options['trace'] += 2;

            $GLOBALS['injector']->getInstance('Horde_Log_Logger')->log($event, $priority, $options);
        }
    }

    /**
     * Shortcut to logging method.
     *
     * @deprecated Use log() instead
     * @see log()
     */
    static public function logMessage($event, $priority = null,
                                      array $options = array())
    {
        if (!isset($options['trace'])) {
            $options['trace'] = 0;
        }
        $options['trace'] += 1;
        self::log($event, $priority, $options);
    }

    /**
     * Debug method.  Allows quick shortcut to produce debug output into a
     * temporary file.
     *
     * @param mixed $event        Item to log.
     * @param string $fname       Filename to log to. If empty, logs to
     *                            'horde_debug.txt' in the PHP temporary
     *                            directory.
     * @param boolean $backtrace  Include backtrace information?
     */
    static public function debug($event = null, $fname = null,
                                 $backtrace = true)
    {
        if (is_null($fname)) {
            $fname = self::getTempDir() . '/horde_debug.txt';
        }

        try {
            $logger = new Horde_Log_Logger(new Horde_Log_Handler_Stream($fname));
        } catch (Exception $e) {
            return;
        }

        $html_ini = ini_set('html_errors', 'Off');
        self::startBuffer();
        if (!is_null($event)) {
            echo "Variable information:\n";
            var_dump($event);
            echo "\n";
        }

        if (is_resource($event)) {
            echo "Stream contents:\n";
            rewind($event);
            fpassthru($event);
            echo "\n";
        }

        if ($backtrace) {
            echo "Backtrace:\n";
            echo strval(new Horde_Support_Backtrace());
        }

        $logger->log(self::endBuffer(), Horde_Log::DEBUG);
        ini_set('html_errors', $html_ini);
    }

    /**
     * Add a signature + timestamp to a query string and return the signed
     * query string.
     *
     * @param string $queryString  The query string to sign.
     * @param integer $now         The timestamp at which to sign. Leave blank
     *                             for generating signatures; specify when
     *                             testing.
     *
     * @return string  The signed query string.
     */
    static public function signQueryString($queryString, $now = null)
    {
        if (!isset($GLOBALS['conf']['secret_key'])) {
            return $queryString;
        }

        if (is_null($now)) {
            $now = time();
        }

        $queryString .= '&_t=' . $now . '&_h=';

        return $queryString . Horde_Url::uriB64Encode(hash_hmac('sha1', $queryString, $GLOBALS['conf']['secret_key'], true));
    }

    /**
     * Verify a signature and timestamp on a query string.
     *
     * @param string $data  The signed query string.
     * @param integer $now  The current time (can override for testing).
     *
     * @return boolean  Whether or not the string was valid.
     */
    static public function verifySignedQueryString($data, $now = null)
    {
        if (is_null($now)) {
            $now = time();
        }

        $pos = strrpos($data, '&_h=');
        if ($pos === false) {
            return false;
        }
        $pos += 4;

        $queryString = substr($data, 0, $pos);
        $hmac = substr($data, $pos);

        if ($hmac != Horde_Url::uriB64Encode(hash_hmac('sha1', $queryString, $GLOBALS['conf']['secret_key'], true))) {
            return false;
        }

        // String was not tampered with; now validate timestamp
        parse_str($queryString, $values);

        return !($values['_t'] + $GLOBALS['conf']['urls']['hmac_lifetime'] * 60 < $now);
    }

    /**
     * Returns the URL to various Horde services.
     *
     * @param string $type       The service to display.
     * <pre>
     * 'ajax'
     * 'cache'
     * 'download'
     * 'emailconfirm'
     * 'go'
     * 'help'
     * 'imple'
     * 'login'
     * 'logintasks'
     * 'logout'
     * 'pixel'
     * 'portal'
     * 'problem'
     * 'sidebar'
     * 'prefs'
     * </pre>
     * @param string $app        The name of the current Horde application.
     *
     * @return Horde_Url|boolean  The HTML to create the link.
     */
    static public function getServiceLink($type, $app = null)
    {
        $opts = array('app' => 'horde');

        switch ($type) {
        case 'ajax':
            $opts['noajax'] = true;
            return self::url('services/ajax.php/' . $app . '/', false, $opts);

        case 'cache':
            $opts['append_session'] = -1;
            return self::url('services/cache.php', false, $opts);

        case 'download':
            return self::url('services/download/', false, $opts)
                ->add('module', $app);

        case 'emailconfirm':
            $opts['noajax'] = true;
            return self::url('services/confirm.php', false, $opts);

        case 'go':
            $opts['noajax'] = true;
            return self::url('services/go.php', false, $opts);

        case 'help':
            return self::url('services/help/', false, $opts)
                ->add('module', $app);

        case 'imple':
            $opts['noajax'] = true;
            return self::url('services/imple.php', false, $opts);

        case 'login':
            $opts['noajax'] = true;
            return self::url('login.php', false, $opts);

        case 'logintasks':
            return self::url('services/logintasks.php', false, $opts)
                ->add('app', $app);

        case 'logout':
            return $GLOBALS['registry']->getLogoutUrl(array('reason' => Horde_Auth::REASON_LOGOUT));

        case 'pixel':
            return self::url('services/images/pixel.php', false, $opts);

        case 'prefs':
            if (!in_array($GLOBALS['conf']['prefs']['driver'], array('', 'none'))) {
                $url = self::url('services/prefs.php', false, $opts);
                if (!is_null($app)) {
                    $url->add('app', $app);
                }
                return $url;
            }
            break;

        case 'portal':
            if ($GLOBALS['session']->get('horde', 'mode') == 'smartmobile' && self::ajaxAvailable()) {
                return self::url('services/portal/mobile.php', false, $opts);
            } else {
                return self::url('services/portal/', false, $opts);
            }
            break;

        case 'problem':
            return self::url('services/problem.php', false, $opts)
                ->add('return_url', self::selfUrl(true, true, true));

        case 'sidebar':
            return self::url('services/sidebar.php', false, $opts);

        case 'twitter':
            return self::url('services/twitter/', true, $opts);
        }

        return false;
    }

    /**
     * Returns a response object with added notification information.
     *
     * @param mixed $data      The 'response' data.
     * @param boolean $notify  If true, adds notification info to object.
     *
     * @return object  The Horde JSON response.  It has the following
     *                 properties:
     *   - msgs: (array) [OPTIONAL] List of notification messages.
     *   - response: (mixed) The response data for the request.
     */
    static public function prepareResponse($data = null, $notify = false)
    {
        $response = new stdClass();
        $response->response = $data;

        if ($notify) {
            $stack = $GLOBALS['notification']->notify(array('listeners' => 'status', 'raw' => true));
            if (!empty($stack)) {
                $response->msgs = $stack;
            }
        }

        return $response;
    }

    /**
     * Send response data to browser.
     *
     * @param mixed $data  The data to serialize and send to the browser.
     * @param string $ct   The content-type to send the data with.  Either
     *                     'json', 'js-json', 'html', 'plain', and 'xml'.
     */
    static public function sendHTTPResponse($data, $ct)
    {
        // Output headers and encoded response.
        switch ($ct) {
        case 'json':
        case 'js-json':
            /* JSON responses are a structured object which always
             * includes the response in a member named 'response', and an
             * additional array of messages in 'msgs' which may be updates
             * for the server or notification messages.
             *
             * Make sure no null bytes sneak into the JSON output stream.
             * Null bytes cause IE to stop reading from the input stream,
             * causing malformed JSON data and a failed request.  These
             * bytes don't seem to break any other browser, but might as
             * well remove them anyway.
             *
             * Finally, add prototypejs security delimiters to returned
             * JSON. */
            $s_data = str_replace("\00", '', self::escapeJson($data));

            if ($ct == 'json') {
                header('Content-Type: application/json');
                echo $s_data;
            } else {
                header('Content-Type: text/html; charset=UTF-8');
                echo htmlspecialchars($s_data);
            }
            break;

        case 'html':
        case 'plain':
        case 'xml':
            $s_data = is_string($data) ? $data : $data->response;
            header('Content-Type: text/' . $ct . '; charset=UTF-8');
            echo $s_data;
            break;

        default:
            echo $data;
        }

        exit;
    }

    /**
     * Do necessary escaping to output JSON.
     *
     * @param mixed $data     The data to JSON-ify.
     * @param array $options  Additional options:
     *   - nodelimit: (boolean) Don't add security delimiters?
     *                DEFAULT: false
     *   - urlencode: (boolean) URL encode the json string
     *                DEFAULT: false
     *
     * @return string  The escaped string.
     */
    static public function escapeJson($data, array $options = array())
    {
        $json = Horde_Serialize::serialize($data, Horde_Serialize::JSON);
        if (empty($options['nodelimit'])) {
            $json = '/*-secure-' . $json . '*/';
        }

        return empty($options['urlencode'])
            ? $json
            : '\'' . rawurlencode($json) . '\'';
    }

    /**
     * Is the current HTTP connection considered secure?
     * @TODO Move this to the request classes!
     *
     * @return boolean
     */
    static public function isConnectionSecure()
    {
        if ($GLOBALS['browser']->usingSSLConnection()) {
            return true;
        }

        if (!empty($GLOBALS['conf']['safe_ips'])) {
            if (reset($GLOBALS['conf']['safe_ips']) == '*') {
                return true;
            }

            /* $_SERVER['HTTP_X_FORWARDED_FOR'] is user data and not
             * reliable. We don't consult it for safe IPs. We also have to
             * assume that if it is present, the user is coming through a proxy
             * server. If so, we don't count any non-SSL connection as safe, no
             * matter the source IP. */
            if (!isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                $remote_addr = $_SERVER['REMOTE_ADDR'];
                foreach ($GLOBALS['conf']['safe_ips'] as $safe_ip) {
                    $safe_ip = preg_replace('/(\.0)*$/', '', $safe_ip);
                    if (strpos($remote_addr, $safe_ip) === 0) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     * Throws an exception if not using a secure connection.
     *
     * @throws Horde_Exception
     */
    static public function requireSecureConnection()
    {
        if (!self::isConnectionSecure()) {
            throw new Horde_Exception(Horde_Core_Translation::t("The encryption features require a secure web connection."));
        }
    }

    /**
     * Loads global and vhost specific configuration files.
     *
     * @param string $config_file      The name of the configuration file.
     * @param string|array $var_names  The name(s) of the variable(s) that
     *                                 is/are defined in the configuration
     *                                 file.
     * @param string $app              The application. Defaults to the current
     *                                 application.
     * @param boolean $show_output     If true, the contents of the requested
     *                                 config file are simply output instead of
     *                                 loaded into a variable.
     *
     * @return mixed  The value of $var_names, in a compact()'ed array if
     *                $var_names is an array.
     * @throws Horde_Exception
     */
    static public function loadConfiguration($config_file, $var_names = null,
                                             $app = null, $show_output = false)
    {
        global $registry;

        if (is_null($app)) {
            $app = $registry->getApp();
        }

        // Track if we've included some version (main or vhosted) of
        // the config file.
        $filelist = array();
        $was_included = false;

        // Load global configuration file.
        $config_dir = (($app == 'horde') && defined('HORDE_BASE'))
            ? HORDE_BASE . '/config/'
            : $registry->get('fileroot', $app) . '/config/';
        $base_path = $file = $config_dir . $config_file;
        if (file_exists($file)) {
            $filelist[$file] = 1;
        }

        // Load global configuration stanzas in .d directory
        $directory = preg_replace('/\.php$/', '.d', $base_path);
        if (file_exists($directory) &&
            is_dir($directory) &&
            ($sub_files = glob("$directory/*.php"))) {
            foreach ($sub_files as $val) {
                $filelist[$val] = 0;
            }
        }

        // Load local version of configuration file
        $file = substr($file, 0, strrpos($file, '.')) . '.local'
            . substr($file, strrpos($file, '.'));
        if (file_exists($file)) {
            $filelist[$file] = 0;
        }

        // Load vhost configuration file. The vhost conf.php for Horde is added
        // later though, because the vhost configuration variable is not
        // available at this point.
        $vhost_added = false;
        if (!empty($GLOBALS['conf']['vhosts'])) {
            $file = $config_dir . substr($config_file, 0, -4) . '-' . $GLOBALS['conf']['server']['name'] . '.php';

            if (file_exists($file)) {
                $filelist[$file] = 0;
            }
            $vhost_added = true;
        }

        /* We need to use a while-loop here because we modify $filelist inside
         * the loop. */
        while (list($file, $log_check) = each($filelist)) {
            /* If we are not exporting variables located in the configuration
             * file, or we are not capturing the output, then there is no
             * need to load the configuration file more than once. */
            self::startBuffer();
            $success = (is_null($var_names) && !$show_output)
                ? include_once $file
                : include $file;
            $output = self::endBuffer();

            if (!$success) {
                throw new Horde_Exception(sprintf('Failed to import configuration file "%s".', $file));
            }

            if (!empty($output) && !$show_output) {
                /* Horde 3 -> 4 conversion checking. This is the only place
                 * to catch PEAR_LOG errors. */
                if ($log_check &&
                    isset($conf['log']['priority']) &&
                    (strpos($conf['log']['priority'], 'PEAR_LOG_') !== false)) {
                    $conf['log']['priority'] = 'INFO';
                    self::logMessage('Logging priority is using the old PEAR_LOG constant', 'INFO');
                } else {
                    throw new Horde_Exception(sprintf('Failed to import configuration file "%s": ', $file) . strip_tags($output));
                }
            }

            // Load vhost conf.php for Horde now if necessary.
            if (!$vhost_added &&
                $app == 'horde' &&
                $config_file == 'conf.php') {
                if (!empty($conf['vhosts'])) {
                    $file = $config_dir . 'conf-' . $conf['server']['name'] . '.php';
                    if (file_exists($file)) {
                        $filelist[$file] = 0;
                    }
                }
                $vhost_added = true;
            }

            $was_included = true;
        }

        // Return an error if neither main or vhosted versions of the config
        // file exist.
        if (!$was_included) {
            throw new Horde_Exception(sprintf('Failed to import configuration file "%s".', $base_path));
        }

        if (isset($output) && $show_output) {
            echo $output;
        }

        Horde::logMessage('Load config file (' . $config_file . '; app: ' . $app . ')', 'DEBUG');

        if (is_null($var_names)) {
            return;
        } elseif (is_array($var_names)) {
            return compact($var_names);
        } elseif (isset($$var_names)) {
            return $$var_names;
        }

        return array();
    }

    /**
     * Returns the driver parameters for the specified backend.
     *
     * @param mixed $backend  The backend system (e.g. 'prefs', 'categories',
     *                        'contacts') being used.
     *                        The used configuration array will be
     *                        $conf[$backend]. If an array gets passed, it will
     *                        be $conf[$key1][$key2].
     * @param string $type    The type of driver. If null, will not merge with
     *                        base config.
     *
     * @return array  The connection parameters.
     */
    static public function getDriverConfig($backend, $type = 'sql')
    {
        global $conf;

        if (!is_null($type)) {
            $type = Horde_String::lower($type);
        }

        if (is_array($backend)) {
            $c = Horde_Array::getElement($conf, $backend);
        } elseif (isset($conf[$backend])) {
            $c = $conf[$backend];
        } else {
            $c = null;
        }

        if (!is_null($c) && isset($c['params'])) {
            $c['params']['umask'] = $conf['umask'];
            return (!is_null($type) && isset($conf[$type]))
                ? array_merge($conf[$type], $c['params'])
                : $c['params'];
        }

        return (!is_null($type) && isset($conf[$type]))
            ? $conf[$type]
            : array();
    }

    /**
     * Checks if all necessary parameters for a driver configuration
     * are set and throws a fatal error with a detailed explanation
     * how to fix this, if something is missing.
     *
     * @param array $params     The configuration array with all parameters.
     * @param string $driver    The key name (in the configuration array) of
     *                          the driver.
     * @param array $fields     An array with mandatory parameter names for
     *                          this driver.
     * @param string $name      The clear text name of the driver. If not
     *                          specified, the application name will be used.
     * @param string $file      The configuration file that should contain
     *                          these settings.
     * @param string $variable  The name of the configuration variable.
     *
     * @throws Horde_Exception
     */
    static public function assertDriverConfig($params, $driver, $fields,
                                              $name = null,
                                              $file = 'conf.php',
                                              $variable = '$conf')
    {
        global $registry;

        // Don't generate a fatal error if we fail during or before
        // Registry instantiation.
        if (is_null($name)) {
            $name = isset($registry) ? $registry->getApp() : '[unknown]';
        }
        $fileroot = isset($registry) ? $registry->get('fileroot') : '';

        if (!is_array($params) || !count($params)) {
            throw new Horde_Exception(
                sprintf(Horde_Core_Translation::t("No configuration information specified for %s."), $name) . "\n\n" .
                sprintf(Horde_Core_Translation::t("The file %s should contain some %s settings."),
                    $fileroot . '/config/' . $file,
                    sprintf("%s['%s']['params']", $variable, $driver)));
        }

        foreach ($fields as $field) {
            if (!isset($params[$field])) {
                throw new Horde_Exception(
                    sprintf(Horde_Core_Translation::t("Required \"%s\" not specified in %s configuration."), $field, $name) . "\n\n" .
                    sprintf(Horde_Core_Translation::t("The file %s should contain a %s setting."),
                        $fileroot . '/config/' . $file,
                        sprintf("%s['%s']['params']['%s']", $variable, $driver, $field)));
            }
        }
    }

    /**
     * Returns a session-id-ified version of $uri.
     * If a full URL is requested, all parameter separators get converted to
     * "&", otherwise to "&amp;".
     *
     * @param mixed $uri      The URI to be modified (either a string or any
     *                        object with a __toString() function).
     * @param boolean $full   Generate a full (http://server/path/) URL.
     * @param mixed $opts     Additional options. If a string/integer, it is
     *                        taken to be the 'append_session' option.  If an
     *                        array, one of the following:
     *   - app: (string) Use this app for the webroot.
     *          DEFAULT: current application
     *   - append_session: (integer) 0 = only if needed [DEFAULT], 1 = always,
     *                     -1 = never.
     *   - force_ssl: (boolean) Ignore $conf['use_ssl'] and force creation of
     *                a SSL URL?
     *                DEFAULT: false
     *
     * @return Horde_Url  The URL with the session id appended (if needed).
     */
    static public function url($uri, $full = false, $opts = array())
    {
        if (is_array($opts)) {
            $append_session = isset($opts['append_session'])
                ? $opts['append_session']
                : 0;
            if (!empty($opts['force_ssl'])) {
                $full = true;
            }
        } else {
            $append_session = $opts;
            $opts = array();
        }

        $puri = parse_url($uri);
        $url = '';
        $schemeRegexp = '|^([a-zA-Z][a-zA-Z0-9+.-]{0,19})://|';
        $webroot = ltrim($GLOBALS['registry']->get('webroot', empty($opts['app']) ? null : $opts['app']), '/');

        if ($full &&
            !isset($puri['scheme']) &&
            !preg_match($schemeRegexp, $webroot) ) {
            /* Store connection parameters in local variables. */
            $server_name = $GLOBALS['conf']['server']['name'];
            $server_port = isset($GLOBALS['conf']['server']['port']) ? $GLOBALS['conf']['server']['port'] : '';

            $protocol = 'http';
            switch ($GLOBALS['conf']['use_ssl']) {
            case 1:
                $protocol = 'https';
                break;

            case 2:
                if ($GLOBALS['browser']->usingSSLConnection()) {
                    $protocol = 'https';
                }
                break;

            case 3:
                $server_port = '';
                if (!empty($opts['force_ssl'])) {
                    $protocol = 'https';
                }
                break;
            }

            /* If using a non-standard port, add to the URL. */
            if (!empty($server_port) &&
                ((($protocol == 'http') && ($server_port != 80)) ||
                 (($protocol == 'https') && ($server_port != 443)))) {
                $server_name .= ':' . $server_port;
            }

            $url = $protocol . '://' . $server_name;
        } elseif (isset($puri['scheme'])) {
            $url = $puri['scheme'] . '://' . $puri['host'];

            /* If using a non-standard port, add to the URL. */
            if (isset($puri['port']) &&
                ((($puri['scheme'] == 'http') && ($puri['port'] != 80)) ||
                 (($puri['scheme'] == 'https') && ($puri['port'] != 443)))) {
                $url .= ':' . $puri['port'];
            }
        }

        if (isset($puri['path']) &&
            (substr($puri['path'], 0, 1) == '/') &&
            (!preg_match($schemeRegexp, $webroot) ||
             (preg_match($schemeRegexp, $webroot) && isset($puri['scheme'])))) {
            $url .= $puri['path'];
        } elseif (isset($puri['path']) && preg_match($schemeRegexp, $webroot)) {
            if (substr($puri['path'], 0, 1) == '/') {
                $pwebroot = parse_url($webroot);
                $url = $pwebroot['scheme'] . '://' . $pwebroot['host']
                    . $puri['path'];
            } else {
                $url = $webroot . '/' . $puri['path'];
            }
        } else {
            $url .= '/' . ($webroot ? $webroot . '/' : '') . (isset($puri['path']) ? $puri['path'] : '');
        }

        if (isset($puri['query'])) {
            $url .= '?' . $puri['query'];
        }

        $ob = new Horde_Url($url, $full);

        if (empty($GLOBALS['conf']['session']['use_only_cookies']) &&
            (($append_session == 1) ||
             (($append_session == 0) && !isset($_COOKIE[session_name()])))) {
            $ob->add(session_name(), session_id());
        }

        return $ob;
    }

    /**
     * Returns an external link passed through the dereferrer to strip session
     * IDs from the referrer.
     *
     * @param string $url   The external URL to link to.
     * @param boolean $tag  If true, a complete <a> tag is returned, only the
     *                      url otherwise.
     *
     * @return string  The link to the dereferrer script.
     */
    static public function externalUrl($url, $tag = false)
    {
        if (!isset($_GET[session_name()]) ||
            Horde_String::substr($url, 0, 1) == '#' ||
            Horde_String::substr($url, 0, 7) == 'mailto:') {
            $ext = $url;
        } else {
            $ext = $GLOBALS['registry']->getServiceLink('go', 'horde');

            /* We must make sure there are no &amp's in the URL. */
            $url = preg_replace(array('/(=?.*?)&amp;(.*?=)/', '/(=?.*?)&amp;(.*?=)/'), '$1&$2', $url);
            $ext .= '?' . self::signQueryString('url=' . urlencode($url));
        }

        if ($tag) {
            $ext = self::link($ext, $url, '', '_blank');
        }

        return $ext;
    }

    /**
     * Returns an anchor tag with the relevant parameters
     *
     * @param Horde_Url|string $url  The full URL to be linked to.
     * @param string $title          The link title/description.
     * @param string $class          The CSS class of the link.
     * @param string $target         The window target to point to.
     * @param string $onclick        JavaScript action for the 'onclick' event.
     * @param string $title2         The link title (tooltip) (deprecated - just
     *                               use $title).
     * @param string $accesskey      The access key to use.
     * @param array $attributes      Any other name/value pairs to add to the
     *                               <a> tag.
     * @param boolean $escape        Whether to escape special characters in the
     *                               title attribute.
     *
     * @return string  The full <a href> tag.
     */
    static public function link($url = '', $title = '', $class = '',
                                $target = '', $onclick = '', $title2 = '',
                                $accesskey = '', $attributes = array(),
                                $escape = true)
    {
        if (!($url instanceof Horde_Url)) {
            $url = new Horde_Url($url);
        }

        if (!empty($title2)) {
            $title = $title2;
        }
        if (!empty($onclick)) {
            $attributes['onclick'] = $onclick;
        }
        if (!empty($class)) {
            $attributes['class'] = $class;
        }
        if (!empty($target)) {
            $attributes['target'] = $target;
        }
        if (!empty($accesskey)) {
            $attributes['accesskey'] = $accesskey;
        }
        if (!empty($title)) {
            if ($escape) {
                $title = str_replace(
                    array("\r", "\n"), '',
                    htmlspecialchars(nl2br(htmlspecialchars($title))));
                /* Remove double encoded entities. */
                $title = preg_replace('/&amp;([a-z]+|(#\d+));/i', '&\\1;', $title);
            }
            $attributes['title.raw'] = $title;
        }

        return $url->link($attributes);
    }

    /**
     * Uses DOM Tooltips to display the 'title' attribute for link() calls.
     *
     * @param string $url        The full URL to be linked to
     * @param string $status     The JavaScript mouse-over string
     * @param string $class      The CSS class of the link
     * @param string $target     The window target to point to.
     * @param string $onclick    JavaScript action for the 'onclick' event.
     * @param string $title      The link title (tooltip). Most not contain
     *                           HTML data other than &lt;br&gt;, which will
     *                           be converted to a linebreak.
     * @param string $accesskey  The access key to use.
     * @param array  $attributes Any other name/value pairs to add to the
     *                           &lt;a&gt; tag.
     *
     * @return string  The full <a href> tag.
     */
    static public function linkTooltip($url, $status = '', $class = '',
                                       $target = '', $onclick = '',
                                       $title = '', $accesskey = '',
                                       $attributes = array())
    {
        if (strlen($title)) {
            $attributes['nicetitle'] = Horde_Serialize::serialize(explode("\n", preg_replace('/<br\s*\/?\s*>/', "\n", $title)), Horde_Serialize::JSON);
            $title = null;
            $GLOBALS['injector']->getInstance('Horde_PageOutput')->addScriptFile('tooltips.js', 'horde');
        }

        return self::link($url, $title, $class, $target, $onclick, null, $accesskey, $attributes, false);
    }

    /**
     * Returns an anchor sequence with the relevant parameters for a widget
     * with accesskey and text.
     *
     * @param array $opts  A hash with widget options:
     *                     - url: (string) The full URL to be linked to.
     *                     - title: (string) The link title/description.
     *                     - nocheck: (boolean, optional) Don't check if the
     *                                access key already has been used.
     *                                Defaults to false (= check).
     *                     Any other options will be passed as attributes to
     *                     the link tag.
     *
     * @return string  The full <a href>Title</a> sequence.
     */
    static public function widget($params)
    {
        $params = array_merge(
            array(
                'class' => '',
                'target' => '',
                'onclick' => '',
                'nocheck' => false),
            $params
        );

        $url = new Horde_Url($params['url']);
        $title = $params['title'];
        $params['accesskey'] = self::getAccessKey($title, $params['nocheck']);

        unset($params['url'], $params['title'], $params['nocheck']);

        return $url->link($params)
            . self::highlightAccessKey($title, $params['accesskey'])
            . '</a>';
    }

    /**
     * Returns a session-id-ified version of $SCRIPT_NAME resp. $PHP_SELF.
     *
     * @param boolean $script_params Include script parameters like
     *                               QUERY_STRING and PATH_INFO?
     * @param boolean $nocache       Include a cache-buster parameter in the
     *                               URL?
     * @param boolean $full          Return a full URL?
     * @param boolean $force_ssl     Ignore $conf['use_ssl'] and force creation
     *                               of a SSL URL?
     *
     * @return Horde_Url  The requested URL.
     */
    static public function selfUrl($script_params = false, $nocache = true,
                                   $full = false, $force_ssl = false)
    {
        if (!strncmp(PHP_SAPI, 'cgi', 3)) {
            // When using CGI PHP, SCRIPT_NAME may contain the path to
            // the PHP binary instead of the script being run; use
            // PHP_SELF instead.
            $url = $_SERVER['PHP_SELF'];
        } else {
            $url = isset($_SERVER['SCRIPT_NAME']) ?
                $_SERVER['SCRIPT_NAME'] :
                $_SERVER['PHP_SELF'];
        }
        if (isset($_SERVER['REQUEST_URI'])) {
            $url = Horde_String::common($_SERVER['REQUEST_URI'], $url);
        }
        if (substr($url, -9) == 'index.php') {
            $url = substr($url, 0, -9);
        }

        if ($script_params) {
            if ($pathInfo = Horde_Util::getPathInfo()) {
                if (substr($url, -1) == '/') {
                    $pathInfo = substr($pathInfo, 1);
                }
                $url .= $pathInfo;
            }
            if (!empty($_SERVER['QUERY_STRING'])) {
                $url .= '?' . $_SERVER['QUERY_STRING'];
            }
        }

        $url = self::url($url, $full, array('force_ssl' => $force_ssl));

        return ($nocache && $GLOBALS['browser']->hasQuirk('cache_same_url'))
            ? $url->unique()
            : $url;
    }

    /**
     * Constructs a correctly-pathed tag to an image.
     *
     * @param mixed $src   The image file (either a string or a
     *                     Horde_Themes_Image object).
     * @param string $alt  Text describing the image.
     * @param mixed $attr  Any additional attributes for the image tag. Can
     *                     be a pre-built string or an array of key/value
     *                     pairs that will be assembled and html-encoded.
     *
     * @return string  The full image tag.
     */
    static public function img($src, $alt = '', $attr = '')
    {
        /* If browser does not support images, simply return the ALT text. */
        if (!$GLOBALS['browser']->hasFeature('images')) {
            return htmlspecialchars($alt);
        }

        /* If no directory has been specified, get it from the registry. */
        if (!($src instanceof Horde_Themes_Image) && (substr($src, 0, 1) != '/')) {
            $src = Horde_Themes::img($src);
        }

        /* Build all of the tag attributes. */
        $attributes = array('alt' => $alt);
        if (is_array($attr)) {
            $attributes = array_merge($attributes, $attr);
        }

        $img = '<img';
        foreach ($attributes as $attribute => $value) {
            $img .= ' ' . $attribute . '="' . @htmlspecialchars($value) . '"';
        }

        /* If the user supplied a pre-built string of attributes, add that. */
        if (is_string($attr) && !empty($attr)) {
            $img .= ' ' . $attr;
        }

        /* Return the closed image tag. */
        return $img . ' src="' . (empty($GLOBALS['conf']['nobase64_img']) ? self::base64ImgData($src) : $src) . '" />';
    }

    /**
     * Same as img(), but returns a full source url for the image.
     * Useful for when the image may be part of embedded Horde content on an
     * external site.
     *
     * @see img()
     */
    static public function fullSrcImg($src, $options = array())
    {
        /* If browser does not support images, simply return the ALT text. */
        if (!$GLOBALS['browser']->hasFeature('images')) {
            return '';
        }

        /* If no directory has been specified, get it from the registry. */
        if (!($src instanceof Horde_Themes_Image) && (substr($src, 0, 1) != '/')) {
            $src = Horde_Themes::img($src, $options);
        }

        /* If we can send as data, no need to get the full path */
        if (!empty($GLOBALS['conf']['nobase64_img'])) {
            $src = self::base64ImgData($src);
        }
        if (substr($src, 0, 10) != 'data:image') {
            $src = self::url($src, true, array('append_session' => -1));
        }

        $img = '<img';
        if (!empty($options['attr'])) {
            /* Build all of the tag attributes. */
            if (is_array($options['attr'])) {
                foreach ($options['attr'] as $attribute => $value) {
                    $img .= ' ' . $attribute . '="' . htmlspecialchars($value) . '"';
                }
            }

            /* If the user supplied a pre-built string of attributes, add
             * that. */
            if (is_string($options['attr'])) {
                $img .= ' ' . $options['attr'];
            }
        }

        /* Return the closed image tag. */
        return $img . ' src="' . $src . '" />';
    }

    /**
     * Generate RFC 2397-compliant image data strings.
     *
     * @param mixed $in       URI or Horde_Themes_Image object containing
     *                        image data.
     * @param integer $limit  Sets a hard size limit for image data; if
     *                        exceeded, will not string encode.
     *
     * @return string  The string to use in the image 'src' attribute; either
     *                 the image data if the browser supports, or the URI
     *                 if not.
     */
    static public function base64ImgData($in, $limit = null)
    {
        $dataurl = $GLOBALS['browser']->hasFeature('dataurl');
        if (!$dataurl) {
            return $in;
        }

        if (!is_null($limit) &&
            (is_bool($dataurl) || ($limit < $dataurl))) {
            $dataurl = $limit;
        }

        /* Only encode image files if they are below the dataurl limit. */
        if (!($in instanceof Horde_Themes_Image)) {
            $in = Horde_Themes_Image::fromUri($in);
        }
        if (!file_exists($in->fs)) {
            return $in->uri;
        }

        /* Delete approx. 50 chars from the limit to account for the various
         * data/base64 header text.  Multiply by 0.75 to determine the
         * base64 encoded size. */
        return (($dataurl === true) ||
                (filesize($in->fs) <= (($dataurl * 0.75) - 50)))
            ? 'data:' . Horde_Mime_Magic::extToMime(substr($in->uri, strrpos($in->uri, '.') + 1)) . ';base64,' . base64_encode(file_get_contents($in->fs))
            : $in->uri;
    }

    /**
     * Determines the location of the system temporary directory. If a specific
     * configuration cannot be found, it defaults to /tmp.
     *
     * @return string  A directory name that can be used for temp files.
     *                 Returns false if one could not be found.
     */
    static public function getTempDir()
    {
        global $conf;

        /* If one has been specifically set, then use that */
        if (!empty($conf['tmpdir'])) {
            $tmp = $conf['tmpdir'];
        }

        /* Next, try sys_get_temp_dir(). */
        if (empty($tmp)) {
            $tmp = sys_get_temp_dir();
        }

        /* If it is still empty, we have failed, so return false;
         * otherwise return the directory determined. */
        return empty($tmp)
            ? false
            : $tmp;
    }

    /**
     * Creates a temporary filename for the lifetime of the script, and
     * (optionally) registers it to be deleted at request shutdown.
     *
     * @param string $prefix           Prefix to make the temporary name more
     *                                 recognizable.
     * @param boolean $delete          Delete the file at the end of the
     *                                 request?
     * @param string $dir              Directory to create the temporary file
     *                                 in.
     * @param boolean $secure          If deleting file, should we securely
     *                                 delete the file?
     * @param boolean $session_remove  Delete this file when session is
     *                                 destroyed?
     *
     * @return string   Returns the full path-name to the temporary file or
     *                  false if a temporary file could not be created.
     */
    static public function getTempFile($prefix = 'Horde', $delete = true,
                                       $dir = '', $secure = false,
                                       $session_remove = false)
    {
        if (empty($dir) || !is_dir($dir)) {
            $dir = self::getTempDir();
        }
        $tmpfile = Horde_Util::getTempFile($prefix, $delete, $dir, $secure);
        if ($session_remove) {
            $gcfiles = $GLOBALS['session']->get('horde', 'gc_tempfiles', Horde_Session::TYPE_ARRAY);
            $gcfiles[] = $tmpfile;
            $GLOBALS['session']->set('horde', 'gc_tempfiles', $gcfiles);
        }

        return $tmpfile;
    }

    /**
     * Returns the Web server being used.
     * PHP string list built from the PHP 'configure' script.
     *
     * @return string  A web server identification string.
     * @see php_sapi_name()
     */
    static public function webServerID()
    {
        switch (PHP_SAPI) {
        case 'apache':
            return 'apache1';

        case 'apache2filter':
        case 'apache2handler':
            return 'apache2';

        default:
            return PHP_SAPI;
        }
    }

    /**
     * Returns an un-used access key from the label given.
     *
     * @param string $label     The label to choose an access key from.
     * @param boolean $nocheck  Don't check if the access key already has been
     *                          used?
     *
     * @return string  A single lower case character access key or empty
     *                 string if none can be found
     */
    static public function getAccessKey($label, $nocheck = false,
                                        $shutdown = false)
    {
        /* Shutdown call for translators? */
        if ($shutdown) {
            if (!count(self::$_labels)) {
                return;
            }
            $script = basename($_SERVER['PHP_SELF']);
            $labels = array_keys(self::$_labels);
            sort($labels);
            $used = array_keys(self::$_used);
            sort($used);
            $remaining = str_replace($used, array(), 'abcdefghijklmnopqrstuvwxyz');
            self::logMessage('Access key information for ' . $script);
            self::logMessage('Used labels: ' . implode(',', $labels));
            self::logMessage('Used keys: ' . implode('', $used));
            self::logMessage('Free keys: ' . $remaining);
            return;
        }

        /* Use access keys at all? */
        if (!isset(self::$_noAccessKey)) {
            self::$_noAccessKey = !$GLOBALS['browser']->hasFeature('accesskey') || !$GLOBALS['prefs']->getValue('widget_accesskey');
        }

        if (self::$_noAccessKey || !preg_match('/_([A-Za-z])/', $label, $match)) {
            return '';
        }
        $key = $match[1];

        /* Has this key already been used? */
        if (isset(self::$_used[strtolower($key)]) &&
            !($nocheck && isset(self::$_labels[$label]))) {
            return '';
        }

        /* Save key and label. */
        self::$_used[strtolower($key)] = true;
        self::$_labels[$label] = true;

        return $key;
    }

    /**
     * Strips an access key from a label.
     * For multibyte charset strings the access key gets removed completely,
     * otherwise only the underscore gets removed.
     *
     * @param string $label  The label containing an access key.
     *
     * @return string  The label with the access key being stripped.
     */
    static public function stripAccessKey($label)
    {
        return preg_replace('/_([A-Za-z])/', $GLOBALS['registry']->nlsconfig->curr_multibyte && preg_match('/[\x80-\xff]/', $label) ? '' : '\1', $label);
    }

    /**
     * Highlights an access key in a label.
     *
     * @param string $label      The label to highlight the access key in.
     * @param string $accessKey  The access key to highlight.
     *
     * @return string  The HTML version of the label with the access key
     *                 highlighted.
     */
    static public function highlightAccessKey($label, $accessKey)
    {
        $stripped_label = self::stripAccessKey($label);

        if (empty($accessKey)) {
            return $stripped_label;
        }

        if ($GLOBALS['registry']->nlsconfig->curr_multibyte) {
            /* Prefix parenthesis with the UTF-8 representation of the LRO
             * (Left-to-Right-Override) Unicode codepoint U+202D. */
            return $stripped_label . "\xe2\x80\xad" .
                '(<span class="accessKey">' . strtoupper($accessKey) .
                '</span>' . ')';
        }

        return str_replace('_' . $accessKey, '<span class="accessKey">' . $accessKey . '</span>', $label);
    }

    /**
     * Returns the appropriate "accesskey" and "title" attributes for an HTML
     * tag and the given label.
     *
     * @param string $label          The title of an HTML element
     * @param boolean $nocheck       Don't check if the access key already has
     *                               been used?
     * @param boolean $return_array  Return attributes as a hash?
     *
     * @return string  The title, and if appropriate, the accesskey attributes
     *                 for the element.
     */
    static public function getAccessKeyAndTitle($label, $nocheck = false,
                                                $return_array = false)
    {
        $ak = self::getAccessKey($label, $nocheck);
        $attributes = array('title' => self::stripAccessKey($label));
        if (!empty($ak)) {
            $attributes['title'] .= sprintf(Horde_Core_Translation::t(" (Accesskey %s)"), strtoupper($ak));
            $attributes['accesskey'] = $ak;
        }

        if ($return_array) {
            return $attributes;
        }

        $html = '';
        foreach ($attributes as $attribute => $value) {
            $html .= sprintf(' %s="%s"', $attribute, $value);
        }
        return $html;
    }

    /**
     * Returns a label element including an access key for usage in conjuction
     * with a form field. User preferences regarding access keys are respected.
     *
     * @param string $for    The form field's id attribute.
     * @param string $label  The label text.
     * @param string $ak     The access key to use. If null a new access key
     *                       will be generated.
     *
     * @return string  The html code for the label element.
     */
    static public function label($for, $label, $ak = null)
    {
        if (is_null($ak)) {
            $ak = self::getAccessKey($label, 1);
        }
        $label = self::highlightAccessKey($label, $ak);

        return sprintf('<label for="%s"%s>%s</label>',
                       $for,
                       !empty($ak) ? ' accesskey="' . $ak . '"' : '',
                       $label);
    }

    /**
     * Call a Horde hook, handling all of the necessary lookups and parsing
     * of the hook code.
     *
     * WARNING: Throwing exceptions is expensive, so use callHook() with care
     * and cache the results if you going to use the results more than once.
     *
     * @param string $hook  The function to call.
     * @param array  $args  An array of any arguments to pass to the hook
     *                      function.
     * @param string $app   The hook application.
     *
     * @return mixed  The results of the hook.
     * @throws Horde_Exception  Thrown on error from hook code.
     * @throws Horde_Exception_HookNotSet  Thrown if hook is not active.
     */
    static public function callHook($hook, $args = array(), $app = 'horde')
    {
        if (!self::hookExists($hook, $app)) {
            throw new Horde_Exception_HookNotSet();
        }

        $hook_class = $app . '_Hooks';
        $hook_ob = new $hook_class;
        try {
            self::logMessage(sprintf('Hook %s in application %s called.', $hook, $app), 'DEBUG');
            return call_user_func_array(array($hook_ob, $hook), $args);
        } catch (Horde_Exception $e) {
            self::logMessage($e, 'ERR');
            throw $e;
        }
    }

    /**
     * Returns whether a hook exists.
     *
     * Use this if you have to call a hook many times and expect the hook to
     * not exist.
     *
     * @param string $hook  The function to call.
     * @param string $app   The hook application.
     *
     * @return boolean  True if the hook exists.
     */
    static public function hookExists($hook, $app = 'horde')
    {
        $hook_class = $app . '_Hooks';

        if (!isset(self::$_hooksLoaded[$app])) {
            self::$_hooksLoaded[$app] = false;
            if (!class_exists($hook_class, false)) {
                try {
                    self::loadConfiguration('hooks.php', null, $app);
                    self::$_hooksLoaded[$app] = array();
                } catch (Horde_Exception $e) {}
            }
        }

        if (self::$_hooksLoaded[$app] === false) {
            return false;
        }

        if (!isset(self::$_hooksLoaded[$app][$hook])) {
            self::$_hooksLoaded[$app][$hook] =
                class_exists($hook_class, false) &&
                ($hook_ob = new $hook_class) &&
                method_exists($hook_ob, $hook);
        }

        return self::$_hooksLoaded[$app][$hook];
    }

    /**
     * Utility function to send redirect headers to browser, handling any
     * browser quirks.
     *
     * @param string $url  The URL to redirect to.
     */
    static public function redirect($url)
    {
        if ($GLOBALS['browser']->isBrowser('msie') &&
            ($GLOBALS['conf']['use_ssl'] == 3) &&
            (strlen($url) < 160)) {
            header('Refresh: 0; URL=' . $url);
        } else {
            header('Location: ' . $url);
        }
        exit;
    }

    /**
     * Print inline javascript to output buffer after wrapping with necessary
     * javascript tags.
     *
     * @param array $script  The script to output.
     *
     * @return string  The script with the necessary HTML javascript tags
     *                 appended.
     */
    static public function wrapInlineScript($script)
    {
        return '<script type="text/javascript">//<![CDATA[' . "\n" . implode('', $script) . "\n//]]></script>\n";
    }

    /**
     * Creates a URL for cached data.
     *
     * @param string $type   The cache type ('app', 'css', 'js').
     * @param array $params  Optional parameters:
     *   - app: REQUIRED for $type == 'app'. Identifies the application to
     *          call the 'cacheOutput' API call, which is passed in the
     *          value of the entire $params array (which may include parameters
     *          other than those listed here). The return from cacheOutput
     *          should be a 2-element array: 'data' (the cached data) and
     *          'type' (the content-type of the data).
     *   - cid: REQUIRED for $type == 'css' || 'js'. The cacheid of the
     *          data (stored in Horde_Cache).
     *   - nocache: If true, sets the cache limiter to 'nocache' instead of
     *              the default 'public'.
     *
     * @return Horde_Url  The URL to the cache page.
     */
    static public function getCacheUrl($type, $params = array())
    {
        $url = $GLOBALS['registry']
            ->getserviceLink('cache', 'horde')
            ->add('cache', $type);
        foreach ($params as $key => $val) {
            $url .= '/' . $key . '=' . rawurlencode(strval($val));
        }

        return self::url($url, true, array('append_session' => -1));
    }

    /**
     * Output the javascript needed to call the popup JS function.
     *
     * @param string|Horde_Url $url  The page to load.
     * @param array $options         Additional options:
     *   - height: (integer) The height of the popup window.
     *             DEFAULT: 650px
     *   - menu: (boolean) Show the browser menu in the popup window?
     *           DEFAULT: false
     *   - onload: (string) A JS function to call after the popup window is
     *             fully loaded.
     *             DEFAULT: None
     *   - params: (array) Additional parameters to pass to the URL.
     *             DEFAULT: None
     *   - urlencode: (boolean) URL encode the json string?
     *                DEFAULT: No
     *   - width: (integer) The width of the popup window.
     *            DEFAULT: 700 px
     *
     * @return string  The javascript needed to call the popup code.
     */
    static public function popupJs($url, $options = array())
    {
        $GLOBALS['page_output']->addScriptPackage('Popup');

        $params = new stdClass;

        if (!$url instanceof Horde_Url) {
            $url = new Horde_Url($url);
        }
        $params->url = $url->url;

        if (!empty($url->parameters)) {
            if (!isset($options['params'])) {
                $options['params'] = array();
            }
            $options['params'] = array_merge($url->parameters, $options['params']);
        }

        if (!empty($options['menu'])) {
            $params->menu = 1;
        }
        foreach (array('height', 'onload', 'params', 'width') as $key) {
            if (!empty($options[$key])) {
                $params->$key = $options[$key];
            }
        }

        return 'void(HordePopup.popup(' . self::escapeJson($params, array('nodelimit' => true, 'urlencode' => !empty($options['urlencode']))) . '));';
    }

    /**
     * Start buffering output.
     */
    static public function startBuffer()
    {
        if (!self::$_bufferLevel) {
            self::$_contentSent = self::contentSent();
        }

        ++self::$_bufferLevel;
        ob_start();
    }

    /**
     * End buffering output.
     *
     * @return string  The buffered output.
     */
    static public function endBuffer()
    {
        if (self::$_bufferLevel) {
            --self::$_bufferLevel;
            return ob_get_clean();
        }

        return '';
    }

    /**
     * Has any content been sent to the browser?
     *
     * @return boolean  True if content has been sent.
     */
    static public function contentSent()
    {
        return ((self::$_bufferLevel && self::$_contentSent) ||
                (!self::$_bufferLevel && (ob_get_length() || headers_sent())));
    }

    /**
     * Returns the sidebar for the current application.
     *
     * @param string $app  The application to generate the menu for. Defaults
     *                     to the current app.
     *
     * @return Horve_View_Sidebar  The sidebar.
     */
    static public function sidebar($app = null)
    {
        global $registry;

        if (empty($app)) {
            $app = $registry->getApp();
        }

        $menu = new Horde_Menu();
        $registry->callAppMethod($app, 'menu', array(
            'args' => array($menu)
        ));
        $sidebar = $menu->render();
        $registry->callAppMethod($app, 'sidebar', array(
            'args' => array($sidebar)
        ));

        return $sidebar;
    }

    /**
     * Process a permission denied error, running a user-defined hook if
     * necessary.
     *
     * @param string $app    Application name.
     * @param string $perm   Permission name.
     * @param string $error  An error message to output via the notification
     *                       system.
     */
    static public function permissionDeniedError($app, $perm, $error = null)
    {
        try {
            self::callHook('perms_denied', array($app, $perm));
        } catch (Horde_Exception_HookNotSet $e) {}

        if (!is_null($error)) {
            $GLOBALS['notification']->push($error, 'horde.warning');
        }
    }

    /**
     * Initialize a HordeMap.
     *
     */
    static public function initMap(array $params = array())
    {
        global $conf, $page_output;

        if (empty($params['providers'])) {
            $params['providers'] = $conf['maps']['providers'];
        }
        if (empty($params['geocoder'])) {
            $params['geocoder'] = $conf['maps']['geocoder'];
        }

        // Language specific file needed?
        $language = str_replace('_', '-', $GLOBALS['language']);
        if (!file_exists($GLOBALS['registry']->get('jsfs', 'horde') . '/map/lang/' . $language . '.js')) {
            $language = 'en-US';
        }
        $params['conf'] = array(
            'language' => $language
        );

        $params['driver'] = 'Horde';
        foreach ($params['providers'] as $layer) {
            switch ($layer) {
            case 'Google':
                $params['conf']['apikeys']['google'] = $conf['api']['googlemaps'];
                break;
            case 'Yahoo':
                $params['conf']['apikeys']['yahoo'] = $conf['api']['yahoomaps'];
                break;
            case 'Cloudmade':
                $params['conf']['apikeys']['cloudmade'] = $conf['api']['cloudmade'];
                break;
            case 'Mytopo':
                $params['conf']['apikeys']['mytopo'] = $conf['api']['mytopo'];
                break;
            }
        }

        if (!empty($params['geocoder'])) {
            switch ($params['geocoder']) {
            case 'Google':
                $params['conf']['apikeys']['google'] = $conf['api']['googlemaps'];
                break;
            case 'Yahoo':
                $params['conf']['apikeys']['yahoo'] = $conf['api']['yahoomaps'];
                break;
            case 'Cloudmade':
                $params['conf']['apikeys']['cloudmade'] = $conf['api']['cloudmade'];
                break;
            }
        }
        $params['jsuri'] = $GLOBALS['registry']->get('jsuri', 'horde') . '/map/';

        $page_output->addScriptFile('map/map.js', 'horde');
        $page_output->addInlineScript(array(
            'HordeMap.initialize(' . Horde_Serialize::serialize($params, HORDE_SERIALIZE::JSON) . ');'
        ));
    }

}
