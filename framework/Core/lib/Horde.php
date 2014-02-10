<?php
/**
 * Provides the base functionality shared by all Horde applications.
 *
 * Copyright 1999-2014 Horde LLC (http://www.horde.org/)
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
     * The access keys already used in this page.
     *
     * @var array
     */
    static protected $_used = array();

    /**
     * Shortcut to logging method.
     *
     * @see Horde_Core_Log_Logger
     */
    static public function log($event, $priority = null,
                               array $options = array())
    {
        $options['trace'] = isset($options['trace'])
            ? ($options['trace'] + 1)
            : 1;
        $log_ob = new Horde_Core_Log_Object($event, $priority, $options);

        /* Chicken/egg: we must wait until we have basic framework setup
         * before we can start logging. Otherwise, queue entries. */
        if (isset($GLOBALS['injector']) &&
            Horde_Core_Factory_Logger::available()) {
            $GLOBALS['injector']->getInstance('Horde_Log_Logger')->logObject($log_ob);
        } else {
            Horde_Core_Factory_Logger::queue($log_ob);
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
        $options['trace'] = isset($options['trace'])
            ? ($options['trace'] + 1)
            : 1;
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
     * @param mixed $queryString  The query string (or Horde_Url object)
     *                            to sign.
     * @param integer $now        The timestamp at which to sign. Leave blank
     *                            for generating signatures; specify when
     *                            testing.
     *
     * @return mixed  The signed query string (or Horde_Url object).
     */
    static public function signQueryString($queryString, $now = null)
    {
        if (!isset($GLOBALS['conf']['secret_key'])) {
            return $queryString;
        }

        if (is_null($now)) {
            $now = time();
        }

        if ($queryString instanceof Horde_Url) {
            $queryString->setRaw(true)->add(array('_t' => $now, '_h' => ''));
            $parse_url = parse_url($queryString);
            $queryString->add('_h', Horde_Url::uriB64Encode(hash_hmac('sha1', $parse_url['query'] . '=', $GLOBALS['conf']['secret_key'], true)));
            return $queryString;
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
        if (isset($puri['fragment'])) {
            $url .= '#' . $puri['fragment'];
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
            $ext = self::signQueryString($GLOBALS['registry']->getServiceLink('go', 'horde')->add('url', $url));
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
     * @param array $params  A hash with widget options (other options will be
     *                       passed as attributes to the link tag):
     *   - url: (string) The full URL to be linked to.
     *   - title: (string) The link title/description.
     *   - nocheck: (boolean, optional) Don't check if the accesskey already
     *              already has been used.
     *              Defaults to false (= check).
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

        $url = ($params['url'] instanceof Horde_Url)
            ? $params['url']
            : new Horde_Url($params['url']);
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
     *                               (Deprecated: use Horde::selfUrlParams()
     *                               instead.)
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
     * Create a self URL of the current page, building the parameter list from
     * the current Horde_Variables object (or via another Variables object
     * passed as an optional argument) rather than the original request data.
     *
     * @since 2.3.0
     *
     * @param array $opts  Additional options:
     *   - force_ssl: (boolean) Force creation of an SSL URL?
     *                DEFAULT: false
     *   - full: (boolean) Return a full URL?
     *           DEFAULT: false
     *   - nocache: (boolean) Include a cache-buster parameter in the URL?
     *              DEFAULT: true
     *   - vars: (Horde_Variables) Use this Horde_Variables object instead of
     *           the Horde global object.
     *           DEFAULT: Use the Horde global object.
     *
     * @return Horde_Url  The self URL.
     */
    static public function selfUrlParams(array $opts = array())
    {
        $vars = isset($opts['vars'])
            ? $opts['vars']
            : $GLOBALS['injector']->getInstance('Horde_Variables');

        $url = self::selfUrl(
            false,
            (!array_key_exists('nocache', $opts) || empty($opts['nocache'])),
            !empty($opts['full']),
            !empty($opts['force_ssl'])
        )->add(iterator_to_array($vars));

        if (!isset($opts['vars'])) {
            $url->remove(array_keys($_COOKIE));
        }

        return $url;
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
            self::log('Access key information for ' . $script);
            self::log('Used labels: ' . implode(',', $labels));
            self::log('Used keys: ' . implode('', $used));
            self::log('Free keys: ' . $remaining);
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
     *                DEFAULT: false
     *   - width: (integer) The width of the popup window.
     *            DEFAULT: 700 px
     *
     * @return string  The javascript needed to call the popup code.
     */
    static public function popupJs($url, $options = array())
    {
        $GLOBALS['page_output']->addScriptPackage('Horde_Core_Script_Package_Popup');

        $params = new stdClass;

        if (!$url instanceof Horde_Url) {
            $url = new Horde_Url($url);
        }
        $params->url = $url->url;

        if (!empty($url->parameters)) {
            if (!isset($options['params'])) {
                $options['params'] = array();
            }
            foreach (array_merge($url->parameters, $options['params']) as $key => $val) {
                $options['params'][$key] = addcslashes($val, '"');
            }
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
            $GLOBALS['injector']->getInstance('Horde_Core_Hooks')
                ->callHook('perms_denied', 'horde', array($app, $perm));
        } catch (Horde_Exception_HookNotSet $e) {}

        if (!is_null($error)) {
            $GLOBALS['notification']->push($error, 'horde.warning');
        }
    }

    /**
     * Handle deprecated methods (located in Horde_Deprecated).
     */
    static public function __callStatic($name, $arguments)
    {
        return call_user_func_array(
            array('Horde_Deprecated', $name),
            $arguments
        );
    }

}
