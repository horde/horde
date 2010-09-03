<?php
/**
 * The Horde:: class provides the functionality shared by all Horde
 * applications.
 *
 * Copyright 1999-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @author   Jon Parise <jon@horde.org>
 * @category Horde
 * @package  Core
 */
class Horde
{
    /**
     * Has compression been started?
     *
     * @var boolean
     */
    static protected $_compressStart = false;

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
     * Inline script cache.
     *
     * @var array
     */
    static protected $_inlineScript = array();

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
     * META tag cache.
     *
     * @var array
     */
    static protected $_metaTags = array();

    /**
     * Shortcut to logging method.
     *
     * @see Horde_Core_Log_Logger
     */
    static public function logMessage($event, $priority = null,
                                      array $options = array())
    {
        $options['trace'] = 2;
        $GLOBALS['injector']->getInstance('Horde_Log_Logger')->log($event, $priority, $options);
    }

    /**
     * Aborts with a fatal error, displaying debug information to the user.
     *
     * @param mixed $error   Either a string or an object with a getMessage()
     *                       method (e.g. PEAR_Error, Exception).
     * @param integer $file  The file in which the error occured.
     * @param integer $line  The line on which the error occured.
     * @param boolean $log   Log this message via logMessage()?
     */
    static public function fatal($error, $file = null, $line = null,
                                 $log = true)
    {
        $admin = $GLOBALS['registry']->isAdmin();
        $cli = Horde_Cli::runningFromCLI();

        $errortext = '<h1>' . _("A fatal error has occurred") . '</h1>';

        if (($error instanceof PEAR_Error) ||
            (is_object($error) && method_exists($error, 'getMessage'))) {
            $errortext .= '<h3>' . htmlspecialchars($error->getMessage()) . '</h3>';
        } elseif (is_string($error)) {
            $errortext .= '<h3>' . htmlspecialchars($error) . '</h3>';
        }

        if ($admin) {
            if ($error instanceof Exception) {
                $trace = $error;
            } else {
                $trace = debug_backtrace();
            }
            $backtrace = new Horde_Support_Backtrace($trace);
            $errortext .= '<div id="backtrace"><pre>' . (string)$backtrace . '</pre></div>';
            if (is_object($error)) {
                $errortext .= '<h3>' . _("Details") . '</h3>';
                $errortext .= '<h4>' . _("The full error message is logged in Horde's log file, and is shown below only to administrators. Non-administrative users will not see error details.") . '</h4>';
                $errortext .= '<div id="details"><pre>' . htmlspecialchars(print_r($error, true)) . '</pre></div>';
            }
        } elseif ($log) {
            $errortext .= '<h3>' . _("Details have been logged for the administrator.") . '</h3>';
        }

        // Log the error via logMessage() if requested.
        if ($log) {
            try {
                self::logMessage($error, 'EMERG');
            } catch (Exception $e) {}
        }

        if ($cli) {
            echo html_entity_decode(strip_tags(str_replace(array('<br />', '<p>', '</p>', '<h1>', '</h1>', '<h3>', '</h3>'), "\n", $errortext)));
        } else {
            echo <<< HTML
<html>
<head><title>Horde :: Fatal Error</title></head>
<body style="background:#fff; color:#000">$errortext</body>
</html>
HTML;
        }
        exit(1);
    }

    /**
     * Log deprecated errors.
     *
     * @param integer $errno     See set_error_handler().
     * @param string $errstr     See set_error_handler().
     * @param string $errfile    See set_error_handler().
     * @param integer $errline   See set_error_handler().
     * @param array $errcontext  See set_error_handler().
     */
    static public function logDeprecated($errno, $errstr, $errfile, $errline,
                                         $errcontext)
    {
        if (class_exists('Horde_Log')) {
            try {
                self::logMessage(new ErrorException($errstr, 0, $errno, $errfile, $errline), 'DEBUG');
            } catch (Exception $e) {}
        }
    }

    /**
     * Adds the javascript code to the output (if output has already started)
     * or to the list of script files to include via includeScriptFiles().
     *
     * As long as one script file is added, 'prototype.js' will be
     * automatically added.
     *
     * @param string $file    The full javascript file name.
     * @param string $app     The application name. Defaults to the current
     *                        application.
     * @param array $options  Additional options:
     * <pre>
     * 'external' - (boolean) Treat $file as an external URL.
     *              DEFAULT: $file is located in the app's js/ directory.
     * 'full' - (boolean) Output a full URL
     *          DEFAULT: false
     * </pre>
     *
     * @throws Horde_Exception
     */
    static public function addScriptFile($file, $app = null,
                                         $options = array())
    {
        $hsf = $GLOBALS['injector']->getInstance('Horde_Script_Files');
        if (empty($options['external'])) {
            $hsf->add($file, $app, !empty($options['full']));
        } else {
            $hsf->addExternal($file, $app);
        }
    }

    /**
     * Outputs the necessary script tags, honoring configuration choices as
     * to script caching.
     *
     * @throws Horde_Exception
     */
    static public function includeScriptFiles()
    {
        global $conf;

        $driver = empty($conf['cachejs'])
            ? 'none'
            : $conf['cachejsparams']['driver'];
        $hsf = $GLOBALS['injector']->getInstance('Horde_Script_Files');

        if ($driver == 'none') {
            $hsf->includeFiles();
            return;
        }

        $js_tocache = $js_force = $js_external = array();

        $s_list = $hsf->listFiles();
        if (empty($s_list)) {
            return;
        }

        if ($driver == 'horde_cache') {
            $cache = $GLOBALS['injector']->getInstance('Horde_Cache');
            $cache_lifetime = empty($conf['cachejsparams']['lifetime'])
                ? 0
                : $conf['cachejsparams']['lifetime'];
        }

        switch ($conf['cachejsparams']['compress']) {
        case 'closure':
            $jsmin_params = array(
                'closure' => $conf['cachejsparams']['closurepath'],
                'java' => $conf['cachejsparams']['javapath']
            );
            break;

        case 'yui':
            $jsmin_params = array(
                'java' => $conf['cachejsparams']['javapath'],
                'yui' => $conf['cachejsparams']['yuipath']
            );
            break;

        default:
            $jsmin_params = array();
            break;
        }

        /* Output prototype.js separately from the other files. */
        $js_force[] = array(
            $s_list['horde'][0]['p'] . $s_list['horde'][0]['f'],
            'mtime' => array(filemtime($s_list['horde'][0]['p'] . $s_list['horde'][0]['f']))
        );
        unset($s_list['horde'][0]);

        foreach ($s_list as $files) {
            foreach ($files as $file) {
                if ($file['d'] && ($file['f'][0] != '/') && empty($file['e'])) {
                    $js_tocache[] = $file['p'] . $file['f'];
                    $js_tocache['mtime'][] = filemtime($file['p'] . $file['f']);
                } elseif (!empty($file['e'])) {
                    $js_external[] = $file['u'];
                } else {
                    $js_force[] = array(
                        $file['p'] . $file['f'],
                        'mtime' => array(filemtime($file['p'] . $file['f']))
                    );
                }
            }
        }

        foreach (array_merge($js_force, array($js_tocache)) as $files) {
            if (!count($files)) {
                continue;
            }

            $mtime = max($files['mtime']);
            unset($files['mtime']);

            $sig_files = $files;
            sort($sig_files);
            $sig = hash('md5', serialize($sig_files) . $mtime);

            switch ($driver) {
            case 'filesystem':
                $js_filename = '/static/' . $sig . '.js';
                $js_path = $GLOBALS['registry']->get('fileroot', 'horde') . $js_filename;
                $js_url = $GLOBALS['registry']->get('webroot', 'horde') . $js_filename;
                $exists = file_exists($js_path);
                break;

            case 'horde_cache':
                // Do lifetime checking here, not on cache display page.
                $exists = $cache->exists($sig, $cache_lifetime);
                $js_url = self::getCacheUrl('js', array('cid' => $sig));
                break;
            }

            if (!$exists) {
                $out = '';
                foreach ($files as $val) {
                    $js_text = file_get_contents($val);

                    if ($conf['cachejsparams']['compress'] == 'none') {
                        $out .= $js_text . "\n";
                    } else {
                        /* Separate JS files with a newline since some
                         * compressors may strip trailing terminators. */
                        try {
                            $out .= $GLOBALS['injector']->getInstance('Horde_Text_Filter')->filter($js_text, 'JavascriptMinify', $jsmin_params) . "\n";
                        } catch (Horde_Exception $e) {
                            $out .= $js_text . "\n";
                        }
                    }
                }

                switch ($driver) {
                case 'filesystem':
                    if (!file_put_contents($js_path, $out)) {
                        throw new Horde_Exception('Could not write cached JS file to disk.');
                    }
                    break;

                case 'horde_cache':
                    $cache->set($sig, $out);
                    break;
                }
            }

            $hsf->outputTag($js_url);
        }

        $hsf->clear();

        foreach ($js_external as $val) {
            $hsf->outputTag($val);
        }
    }

    /**
     * Get a token for protecting a form.
     *
     * @param string $slug  Slug name.
     *
     * @return string  Token string.
     */
    static public function getRequestToken($slug)
    {
        $token = Horde_Token::generateId($slug);
        $_SESSION['horde_form_secrets'][$token] = time();
        return $token;
    }

    /**
     * Check if a token for a form is valid.
     *
     * @param string $slug   Slug name.
     * @param string $token  Token to check.
     *
     * @throws Horde_Exception
     */
    static public function checkRequestToken($slug, $token)
    {
        if (empty($_SESSION['horde_form_secrets'][$token])) {
            throw new Horde_Exception(_("We cannot verify that this request was really sent by you. It could be a malicious request. If you intended to perform this action, you can retry it now."));
        }

        if (($_SESSION['horde_form_secrets'][$token] + $GLOBALS['conf']['urls']['token_lifetime'] * 60) < time()) {
            throw new Horde_Exception(sprintf(_("This request cannot be completed because the link you followed or the form you submitted was only valid for %s minutes. Please try again now."), $GLOBALS['conf']['urls']['token_lifetime']));
        }
    }

    /**
     * Add a signature + timestamp to a query string and return the signed query
     * string.
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
     * 'prefsapi
     * 'problem'
     * 'sidebar'
     * 'options'
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
            return self::url('services/ajax.php/' . $app . '/', false, $opts)
                ->remove('ajaxui');

        case 'cache':
            $opts['append_session'] = -1;
            return self::url('services/cache.php', false, $opts);

        case 'download':
            return self::url('services/download/', false, $opts)
                ->add('module', $app);

        case 'emailconfirm':
            return self::url('services/confirm.php', false, $opts);

        case 'go':
            return self::url('services/go.php', false, $opts)
                ->remove('ajaxui');

        case 'help':
            return self::url('services/help/', false, $opts)
                ->add('module', $app);

        case 'imple':
            return self::url('services/imple.php', false, $opts)
                ->remove('ajaxui');

        case 'login':
            return self::url('login.php', false, $opts);

        case 'logintasks':
            return self::url('services/logintasks.php', false, $opts)
                ->add('app', $app);

        case 'logout':
            return $GLOBALS['registry']->getLogoutUrl(array('reason' => Horde_Auth::REASON_LOGOUT));

        case 'options':
        case 'prefsapi':
            if (!in_array($GLOBALS['conf']['prefs']['driver'], array('', 'none'))) {
                $url = self::url(($type == 'options') ? 'services/prefs.php' : 'services/prefs/', false, $opts);
                if (!is_null($app)) {
                    $url->add('app', $app);
                }
                return $url;
            }
            break;

        case 'problem':
            return self::url('services/problem.php', false, $opts)
                ->add('return_url', urlencode(self::selfUrl(true, true, true)));

        case 'sidebar':
            return self::url('services/sidebar.php', false, $opts);
        }

        return false;
    }

    /**
     * Returns a stdClass response object with added notification information.
     *
     * @param mixed $data      The 'response' data.
     * @param boolean $notify  If true, adds notification info to object.
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
        $charset = $GLOBALS['registry']->getCharset();

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
            $s_data = Horde_String::convertCharset(str_replace("\00", '', self::escapeJson($data, array('charset' => $charset))), $charset, 'UTF-8');

            if ($ct == 'json') {
                header('Content-Type: application/json');
                echo $s_data;
            } else {
                header('Content-Type: text/html; charset=' . $charset);
                echo htmlspecialchars($s_data);
            }
            break;

        case 'html':
        case 'plain':
        case 'xml':
            $s_data = is_string($data) ? $data : $data->response;
            header('Content-Type: text/' . $ct . '; charset=' . $charset);
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
     * <pre>
     * 'charset' - (string) The charset of $data.
     *             DEFAULT: Horde_Registry::getCharset()
     * 'nodelimit' - (boolean) Don't add security delimiters?
     *               DEFAULT: false
     * 'urlencode' - (boolean) URL encode the json string
     *               DEFAULT: false
     * </pre>
     *
     * @return string  The escaped string.
     */
    static public function escapeJson($data, array $options = array())
    {
        $json = Horde_Serialize::serialize($data, Horde_Serialize::JSON, empty($options['charset']) ? $GLOBALS['registry']->getCharset() : $options['charset']);
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
            throw new Horde_Exception(_("The encryption features require a secure web connection."));
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
        $file = $config_dir . $config_file;
        if (file_exists($file)) {
            $filelist[$file] = 1;
        }

        // Load global configuration stanzas in .d directory
        $directory = preg_replace('/\.php$/', '.d', $config_dir . $config_file);
        if (file_exists($directory) &&
            is_dir($directory) &&
            ($sub_files = glob("$directory/*.php"))) {
            foreach ($sub_files as $val) {
                $filelist[$val] = 0;
            }
        }

        // Load vhost configuration file.
        if (!empty($conf['vhosts']) || !empty($GLOBALS['conf']['vhosts'])) {
            $server_name = isset($GLOBALS['conf'])
                ? $GLOBALS['conf']['server']['name']
                : $conf['server']['name'];
            $file = $config_dir . substr($config_file, 0, -4) . '-' . $server_name . '.php';

            if (file_exists($file)) {
                $filelist[$file] = 0;
            }
        }

        foreach ($filelist as $file => $log_check) {
            /* If we are not exporting variables located in the configuration
             * file, or we are not capturing the output, then there is no
             * need to load the configuration file more than once. */
            self::startBuffer();
            $success = (is_null($var_names) && !$show_output)
                ? include_once $file
                : include $file;
            $output = self::endBuffer();

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

            if (!$success) {
                throw new Horde_Exception(sprintf('Failed to import configuration file "%s".', $file));
            }

            $was_included = true;
        }

        // Return an error if neither main or vhosted versions of the config
        // file exist.
        if (!$was_included) {
            throw new Horde_Exception(sprintf('Failed to import configuration file "%s".', $config_dir . $config_file));
        }

        if (isset($output) && $show_output) {
            echo $output;
        }

        if (is_null($var_names)) {
            return;
        } elseif (is_array($var_names)) {
            return compact($var_names);
        } elseif (isset($$var_names)) {
            return $$var_names;
        } else {
            return array();
        }
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
                sprintf(_("No configuration information specified for %s."), $name) . "\n\n" .
                sprintf(_("The file %s should contain some %s settings."),
                    $fileroot . '/config/' . $file,
                    sprintf("%s['%s']['params']", $variable, $driver)));
        }

        foreach ($fields as $field) {
            if (!isset($params[$field])) {
                throw new Horde_Exception(
                    sprintf(_("Required \"%s\" not specified in %s configuration."), $field, $name) . "\n\n" .
                    sprintf(_("The file %s should contain a %s setting."),
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
     * <pre>
     * 'app' - (string) Use this app for the webroot.
     *         DEFAULT: current application
     * 'append_session' - (integer) 0 = only if needed [DEFAULT], 1 = always,
     *                    -1 = never.
     * 'force_ssl' - (boolean) Ignore $conf['use_ssl'] and force creation of a
     *               SSL URL?
     *               DEFAULT: false
     * </pre>
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

        if ($full && !isset($puri['scheme'])) {
            /* Store connection parameters in local variables. */
            $server_name = $GLOBALS['conf']['server']['name'];
            $server_port = $GLOBALS['conf']['server']['port'];

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

            /* If using non-standard ports, add the port to the URL. */
            if (!empty($server_port) &&
                ((($protocol == 'http') && ($server_port != 80)) ||
                 (($protocol == 'https') && ($server_port != 443)))) {
                $server_name .= ':' . $server_port;
            }

            $url = $protocol . '://' . $server_name;
        }

        if (isset($puri['path']) && $puri['path'][0] == '/') {
            $url .= $puri['path'];
        } else {
            $webroot = $GLOBALS['registry']->get('webroot', empty($opts['app']) ? null : $opts['app']);
            $url .= '/' . ltrim($webroot, '/') . '/' . (isset($puri['path']) ? $puri['path'] : '');
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

        if (($append_session >= 0) &&
            Horde_Util::getFormData('ajaxui')) {
            $ob->add('ajaxui', 1);
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
            $ext = self::getServiceLink('go', 'horde');

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
     * Returns a URL to be used for downloading, that takes into account any
     * special browser quirks (i.e. IE's broken filename handling).
     *
     * @param string $filename  The filename of the download data.
     * @param array $params     Any additional parameters needed.
     * @param string $url       The URL to alter. If none passed in, will use
     *                          the file 'view.php' located in the current
     *                          app's base directory.
     *
     * @return Horde_Url  The download URL.
     */
    static public function downloadUrl($filename, $params = array(),
                                       $url = null)
    {
        global $browser, $registry;

        $horde_url = false;

        if (is_null($url)) {
            $url = self::getServiceLink('download', $registry->getApp());
            $horde_url = true;
        }

        /* Add parameters. */
        if (!is_null($params)) {
            $url->add($params);
        }

        /* If we are using the default Horde download link, add the
         * filename to the end of the URL. Although not necessary for
         * many browsers, this should allow every browser to download
         * correctly. */
        if ($horde_url) {
            $url->add('fn', '/' . rawurlencode($filename));
        } elseif ($browser->hasQuirk('break_disposition_filename')) {
            /* Some browsers will only obtain the filename correctly
             * if the extension is the last argument in the query
             * string and rest of the filename appears in the
             * PATH_INFO element. */
            $url = (string)$url;
            $filename = rawurlencode($filename);

            /* Get the webserver ID. */
            $server = self::webServerID();

            /* Get the name and extension of the file.  Apache 2 does
             * NOT support PATH_INFO information being passed to the
             * PHP module by default, so disable that
             * functionality. */
            if (($server != 'apache2')) {
                if (($pos = strrpos($filename, '.'))) {
                    $name = '/' . preg_replace('/\./', '%2E', substr($filename, 0, $pos));
                    $ext = substr($filename, $pos);
                } else {
                    $name = '/' . $filename;
                    $ext = '';
                }

                /* Enter the PATH_INFO information. */
                if (($pos = strpos($url, '?'))) {
                    $url = substr($url, 0, $pos) . $name . substr($url, $pos);
                } else {
                    $url .= $name;
                }
            }
            $url = new Horde_Url($url);

            /* Append the extension, if it exists. */
            if (($server == 'apache2') || !empty($ext)) {
                $url->add('fn_ext', '/' . $filename);
            }
        }

        return $url;
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
                $charset = $GLOBALS['registry']->getCharset();
                $old_error = error_reporting(0);
                $title = str_replace(
                    array("\r", "\n"), '',
                    htmlspecialchars(
                        nl2br(htmlspecialchars($title, ENT_QUOTES, $charset)),
                        ENT_QUOTES, $charset));

                error_reporting($old_error);

                /* Remove double encoded entities. */
                $title = preg_replace('/&amp;([a-z]+|(#\d+));/i', '&\\1;', $title);
            }
            $attributes['title.raw'] = $title;
        }

        return $url->link($attributes);
    }

    /**
     * Uses DOM Tooltips to display the 'title' attribute for
     * link() calls.
     *
     * @param string $url        The full URL to be linked to
     * @param string $status     The JavaScript mouse-over string
     * @param string $class      The CSS class of the link
     * @param string $target     The window target to point to.
     * @param string $onclick    JavaScript action for the 'onclick' event.
     * @param string $title      The link title (tooltip).
     * @param string $accesskey  The access key to use.
     * @param array  $attributes Any other name/value pairs to add to the <a>
     *                           tag.
     *
     * @return string  The full <a href> tag.
     */
    static public function linkTooltip($url, $status = '', $class = '',
                                       $target = '', $onclick = '',
                                       $title = '', $accesskey = '',
                                       $attributes = array())
    {
        if (!empty($title)) {
            $charset = $GLOBALS['registry']->getCharset();
            $old_error = error_reporting(0);
            $title = '&lt;pre&gt;' . preg_replace(array('/\n/', '/((?<!<br)\s{1,}(?<!\/>))/em', '/<br \/><br \/>/', '/<br \/>/'), array('', 'str_repeat("&nbsp;", strlen("$1"))', '&lt;br /&gt; &lt;br /&gt;', '&lt;br /&gt;'), nl2br(htmlspecialchars(htmlspecialchars($title, ENT_QUOTES, $charset), ENT_QUOTES, $charset))) . '&lt;/pre&gt;';
            error_reporting($old_error);

            self::addScriptFile('tooltips.js', 'horde');
        }

        return self::link($url, $title, $class, $target, $onclick, null, $accesskey, $attributes, false);
    }

    /**
     * Returns an anchor sequence with the relevant parameters for a widget
     * with accesskey and text.
     *
     * @param string  $url      The full URL to be linked to.
     * @param string  $title    The link title/description.
     * @param string  $class    The CSS class of the link
     * @param string  $target   The window target to point to.
     * @param string  $onclick  JavaScript action for the 'onclick' event.
     * @param string  $title2   The link title (tooltip) (deprecated - just use
     *                          $title).
     * @param boolean $nocheck  Don't check if the access key already has been
     *                          used. Defaults to false (= check).
     *
     * @return string  The full <a href>Title</a> sequence.
     */
    static public function widget($url, $title = '', $class = 'widget',
                                  $target = '', $onclick = '', $title2 = '',
                                  $nocheck = false)
    {
        if (!empty($title2)) {
            $title = $title2;
        }

        $ak = self::getAccessKey($title, $nocheck);

        return self::link($url, '', $class, $target, $onclick, '', $ak) . self::highlightAccessKey($title, $ak) . '</a>';
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
        if (substr($url, -9) == 'index.php') {
            $url = substr($url, 0, -9);
        }

        if ($script_params) {
            if ($pathInfo = Horde_Util::getPathInfo()) {
                $url .= (substr($url, -1) != '/' ?  '/' : '')  . $pathInfo;
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
     * Constructs a correctly-pathed link to an image.
     *
     * @param mixed $src    The image file (either a string or a
     *                      Horde_Themes_Image object).
     * @param string $alt   Text describing the image.
     * @param mixed  $attr  Any additional attributes for the image tag. Can be
     *                      a pre-built string or an array of key/value pairs
     *                      that will be assembled and html-encoded.
     *
     * @return string  The full image tag.
     */
    static public function img($src, $alt = '', $attr = '')
    {
        $charset = $GLOBALS['registry']->getCharset();

        /* If browser does not support images, simply return the ALT text. */
        if (!$GLOBALS['browser']->hasFeature('images')) {
            return @htmlspecialchars($alt, ENT_COMPAT, $charset);
        }

        /* If no directory has been specified, get it from the registry. */
        if (!($src instanceof Horde_Themes_Image) && ($src[0] != '/')) {
            $src = Horde_Themes::img($src);
        }

        /* Build all of the tag attributes. */
        $attributes = array('alt' => $alt);
        if (is_array($attr)) {
            $attributes = array_merge($attributes, $attr);
        }

        $img = '<img';
        $old_error = error_reporting(0);
        foreach ($attributes as $attribute => $value) {
            $img .= ' ' . $attribute . '="' . htmlspecialchars($value, ENT_COMPAT, $charset) . '"';
        }
        error_reporting($old_error);

        /* If the user supplied a pre-built string of attributes, add that. */
        if (is_string($attr) && !empty($attr)) {
            $img .= ' ' . $attr;
        }

        /* Return the closed image tag. */
        return $img . ' src="' . self::base64ImgData($src) . '" />';
    }

    /**
     * Same as self::img(), but returns a full source url for the image.
     * Useful for when the image may be part of embedded Horde content on an
     * external site. Basically a stop-gap measure until Horde_View etc...
     *
     * @see self::img()
     */
    static public function fullSrcImg($src, $options = array())
    {
        $charset = $GLOBALS['registry']->getCharset();

        /* If browser does not support images, simply return the ALT text. */
        if (!$GLOBALS['browser']->hasFeature('images')) {
            return @htmlspecialchars($alt, ENT_COMPAT, $charset);
        }

        /* If no directory has been specified, get it from the registry. */
        if (!($src instanceof Horde_Themes_Image) && ($src[0] != '/')) {
            $src = Horde_Themes::img($src, $options);
        }

        /* If we can send as data, no need to get the full path */
        $src = self::base64ImgData($src);
        if (substr($src, 0, 10) != 'data:image') {
            $src = self::url($src, true, array('append_session' => -1));
        }

        $img = '<img';
        if (!empty($options['attr'])) {
            /* Build all of the tag attributes. */
            if (is_array($options['attr'])) {
                $old_error = error_reporting(0);
                foreach ($options['attr'] as $attribute => $value) {
                    $img .= ' ' . $attribute . '="' . htmlspecialchars($value, ENT_COMPAT, $charset) . '"';
                }
                error_reporting($old_error);
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
    public function base64ImgData($in, $limit = null)
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
            ? 'data:image/' . substr($in->uri, strrpos($in->uri, '.') + 1) . ';base64,' . base64_encode(file_get_contents($in->fs))
            : $in->uri;
    }

    /**
     * Determines the location of the system temporary directory. If a specific
     * setting cannot be found, it defaults to /tmp.
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

        /* Next, try Horde_Util::getTempDir(). */
        if (empty($tmp)) {
            $tmp = Horde_Util::getTempDir();
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
     * @param string $prefix   Prefix to make the temporary name more
     *                         recognizable.
     * @param boolean $delete  Delete the file at the end of the request?
     * @param string $dir      Directory to create the temporary file in.
     * @param boolean $secure  If deleting file, should we securely delete the
     *                         file?
     *
     * @return string   Returns the full path-name to the temporary file or
     *                  false if a temporary file could not be created.
     */
    static public function getTempFile($prefix = 'Horde', $delete = true,
                                       $dir = '', $secure = false)
    {
        if (empty($dir) || !is_dir($dir)) {
            $dir = self::getTempDir();
        }

        return Horde_Util::getTempFile($prefix, $delete, $dir, $secure);
    }

    /**
     * Starts output compression, if requested.
     */
    static public function compressOutput()
    {
        if (self::$_compressStart) {
            return;
        }

        /* Compress output if requested and possible. */
        if ($GLOBALS['conf']['compress_pages'] &&
            !$GLOBALS['browser']->hasQuirk('buggy_compression') &&
            !(bool)ini_get('zlib.output_compression') &&
            !(bool)ini_get('zend_accelerator.compress_all') &&
            ini_get('output_handler') != 'ob_gzhandler') {
            if (ob_get_level()) {
                ob_end_clean();
            }
            ob_start('ob_gzhandler');
        }

        self::$_compressStart = true;
    }

    /**
     * Determines if output compression can be used.
     *
     * @return boolean  True if output compression can be used, false if not.
     */
    static public function allowOutputCompression()
    {
        return !$GLOBALS['browser']->hasQuirk('buggy_compression') &&
               (ini_get('zlib.output_compression') == '') &&
               (ini_get('zend_accelerator.compress_all') == '') &&
               (ini_get('output_handler') != 'ob_gzhandler');
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
        $multibyte = isset($GLOBALS['registry']->nlsconfig['multibyte'][$GLOBALS['registry']->getCharset(true)]);
        return preg_replace('/_([A-Za-z])/',
                            $multibyte && preg_match('/[\x80-\xff]/', $label) ? '' : '\1',
                            $label);
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

        if (isset($GLOBALS['registry']->nlsconfig['multibyte'][$GLOBALS['registry']->getCharset(true)])) {
            /* Prefix parenthesis with the UTF-8 representation of the LRO
             * (Left-to-Right-Override) Unicode codepoint U+202D. */
            $prefix = ($GLOBALS['registry']->getCharset() == 'UTF-8')
                ? "\xe2\x80\xad"
                : '';
            return $stripped_label . $prefix . '(<span class="accessKey">'
                . strtoupper($accessKey) . '</span>' . ')';
        }

        return str_replace('_' . $accessKey, '<span class="accessKey">' . $accessKey . '</span>', $label);
    }

    /**
     * Returns the appropriate "accesskey" and "title" attributes for an HTML
     * tag and the given label.
     *
     * @param string $label     The title of an HTML element
     * @param boolean $nocheck  Don't check if the access key already has been
     *                          used?
     *
     * @return string  The title, and if appropriate, the accesskey attributes
     *                 for the element.
     */
    static public function getAccessKeyAndTitle($label, $nocheck = false)
    {
        $ak = self::getAccessKey($label, $nocheck);
        $attributes = 'title="' . self::stripAccessKey($label);
        if (!empty($ak)) {
            $attributes .= sprintf(_(" (Accesskey %s)"), strtoupper($ak));
            $attributes .= '" accesskey="' . $ak;
        }

        return $attributes . '"';
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
        $error = false;
        $hook_class = $app . '_Hooks';

        if (!class_exists($hook_class)) {
            try {
                self::loadConfiguration('hooks.php', null, $app);
            } catch (Horde_Exception $e) {
                $error = true;
            }
        }

        if (class_exists($hook_class)) {
            $hook_ob = new $hook_class;
            $error = !method_exists($hook_ob, $hook);
        } else {
            $error = true;
        }

        if ($error) {
            $error = sprintf('Hook %s in application %s not called.', $hook, $app);
            self::logMessage($error, 'DEBUG');
            throw new Horde_Exception_HookNotSet($error);
        }

        try {
            self::logMessage(sprintf('Hook %s in application %s called.', $hook, $app), 'DEBUG');
            return call_user_func_array(array($hook_ob, $hook), $args);
        } catch (Horde_Exception $e) {
            self::logMessage($e, 'ERR');
            throw $e;
        }
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
     * Add inline javascript to the output buffer.
     *
     * @param mixed $script    The script text to add (can be stored in an
     *                         array also).
     * @param string $onload   Load the script after the page has loaded?
     *                         Either 'dom' (on dom:loaded), 'load'.
     * @param boolean $top     Add script to top of stack?
     */
    static public function addInlineScript($script, $onload = null,
                                           $top = false)
    {
        if (is_array($script)) {
            $script = implode(';', $script);
        }

        $script = trim($script);
        if (empty($script)) {
            return;
        }

        if (is_null($onload)) {
            $onload = 'none';
        }

        $script = trim($script, ';') . ';';

        if ($top && isset(self::$_inlineScript[$onload])) {
            array_unshift(self::$_inlineScript[$onload], $script);
        } else {
            self::$_inlineScript[$onload][] = $script;
        }

        // If headers have already been sent, we need to output a
        // <script> tag directly.
        if (self::contentSent()) {
            self::outputInlineScript();
        }
    }

    /**
     * Add inline javascript variable definitions to the output buffer.
     *
     * @param array $data  Keys are the variable names, values are the data to
     *                     JSON encode.  If the key begins with a '-', the
     *                     data will be added to the output as-is.
     */
    static public function addInlineJsVars($data)
    {
        $charset = $GLOBALS['registry']->getCharset();
        $out = array();

        foreach ($data as $key => $val) {
            if ($key[0] == '-') {
                $key = substr($key, 1);
            } else {
                $val = Horde_Serialize::serialize($val, Horde_Serialize::JSON, $charset);
            }

            $out[] = $key . '=' . $val;
        }

        self::addInlineScript($out);
    }

    /**
     * Print pending inline javascript to the output buffer.
     *
     * @param boolean $nowrap  Don't wrap inline script.
     */
    static public function outputInlineScript($nowrap = false)
    {
        if (empty(self::$_inlineScript)) {
            return;
        }

        $script = array();

        foreach (self::$_inlineScript as $key => $val) {
            $val = implode('', $val);

            switch ($key) {
            case 'dom':
                self::addScriptFile('prototype.js', 'horde');
                $val = 'document.observe("dom:loaded", function() {' . $val . '});';
                break;

            case 'load':
                self::addScriptFile('prototype.js', 'horde');
                $val = 'Event.observe(window, "load", function() {' . $val . '});';
                break;
            }

            $script[] = $val;
        }

        echo $nowrap
            ? implode('', $script)
            : self::wrapInlineScript($script);

        self::$_inlineScript = array();
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
     * <pre>
     * RESERVED PARAMETERS:
     * 'app' - REQUIRED for $type == 'app'. Identifies the application to
     *         call the 'cacheOutput' API call, which is passed in the
     *         value of the entire $params array (which may include parameters
     *         other than those listed here). The return from cacheOutput
     *         should be a 2-element array: 'data' (the cached data) and
     *         'type' (the content-type of the data).
     * 'cid' - REQUIRED for $type == 'css' || 'js'. The cacheid of the
     *         data (stored in Horde_Cache).
     * 'nocache' - If true, sets the cache limiter to 'nocache' instead of
     *             the default 'public'.
     * </pre>
     *
     * @return string  The URL to the cache page.
     */
    static public function getCacheUrl($type, $params = array())
    {
        $url = self::getserviceLink('cache', 'horde')->add('cache', $type);
        foreach ($params as $key => $val) {
            $url .= '/' . $key . '=' . rawurlencode(strval($val));
        }

        return self::url($url);
    }

    /**
     * Output the javascript needed to call the popup JS function.
     *
     * @param string|Horde_Url $url  The page to load.
     * @param array $options         Additional options:
     * <pre>
     * 'height' - (integer) The height of the popup window.
     *            DEFAULT: 650 px
     * 'menu' - (boolean) Show the browser menu in the popup window?
     *          DEFAULT: false
     * 'onload' - (string) A JS function to call after the popup window is
     *            fully loaded.
     *            DEFAULT: None
     * 'params' - (array) Additional parameters to pass to the URL.
     *            DEFAULT: None
     * 'urlencode' - (boolean) URL encode the json string?
     *               DEFAULT: No
     * 'width' - (integer) The width of the popup window.
     *           DEFAULT: 700 px
     * </pre>
     *
     * @return string  The javascript needed to call the popup code.
     */
    static public function popupJs($url, $options = array())
    {
        self::addScriptFile('popup.js', 'horde');

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

        if (!empty($options['height'])) {
            $params->height = $options['height'];
        }
        if (!empty($options['menu'])) {
            $params->menu = 1;
        }
        if (!empty($options['onload'])) {
            $params->onload = $options['onload'];
        }
        if (!empty($options['params'])) {
            $params->params = http_build_query(array_map('rawurlencode', $options['params']));
        }
        if (!empty($options['width'])) {
            $params->width = $options['width'];
        }

        return 'void(Horde.popup(' . self::escapeJson($params, array('nodelimit' => true, 'urlencode' => !empty($options['urlencode']))) . '));';
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
     * Adds a META http-equiv tag to the page output.
     *
     * @param string $type     The http-equiv type value.
     * @param string $content  The content of the META tag.
     */
    static public function addMetaTag($type, $content)
    {
        self::$_metaTags[$type] = $content;
    }

    /**
     * Adds a META refresh tag.
     *
     * @param integer $time  Refresh time.
     * @param string $url    Refresh URL
     */
    static public function metaRefresh($time, $url)
    {
        if (!empty($time) && !empty($url)) {
            self::addMetaTag('refresh', $time . ';url=' . $url);
        }
    }

    /**
     * Adds a META tag to disable DNS prefetching.
     * See Horde Bug #8836.
     */
    static public function noDnsPrefetch()
    {
        self::addMetaTag('x-dns-prefetch-control', 'off');
    }

    /**
     * Output META tags to page.
     */
    static public function outputMetaTags()
    {
        foreach (self::$_metaTags as $key => $val) {
            echo '<meta http-equiv="' . $key . '" content="' . $val . "\" />\n";
        }

        self::$_metaTags = array();
    }

    /**
     * Is an AJAX view supported/available on the current browser?
     *
     * return boolean  True if the AJAX view can be displayed.
     */
    static public function ajaxAvailable()
    {
        global $browser;

        return $browser->hasFeature('xmlhttpreq') &&
            (!$browser->isBrowser('msie') || $browser->getMajor() >= 7) &&
            (!$browser->hasFeature('issafari') || $browser->getMajor() >= 2);
    }

}
