<?php
/**
 * The Horde_Test:: class provides functions used in the test scripts
 * used in the various applications (test.php).
 *
 * Copyright 1999-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @author  Jon Parise <jon@horde.org>
 * @author  Brent J. Nordquist <bjn@horde.org>
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package Horde_Test
 */

/* If gettext is not loaded, define a dummy _() function so that
 * including any file with gettext strings won't cause a fatal error,
 * causing test.php to return a blank page. */
if (!function_exists('_')) {
    function _($s) { return $s; }
}

class Horde_Test
{
    /**
     * The PHP version of the system.
     *
     * @var array
     */
    protected $_phpver;

    /**
     * Supported versions of PHP.
     *
     * @var array
     */
    protected $_supported = array(
        '5.2', '5.3'
    );

    /**
     * The module list
     * <pre>
     * KEY:   module name
     * VALUE: Either the description or an array with the following entries:
     *        'descrip' - (string) Module description
     *        'error' - (string) Error message
     *        'fatal' - (boolean) Is missing module fatal?
     *        'phpver' - (string) The PHP version above which to do the test
     * </pre>
     *
     * @var array
     */
    protected $_moduleList = array(
        'ctype' => array(
            'descrip' => 'Ctype Support',
            'error' => 'The ctype functions are required by the help system, the weather portal blocks, and a few Horde applications.'
        ),
        'dom' => array(
            'descrip' => 'DOM XML Support',
            'error' => 'Horde will not run without the dom extension. Don\'t compile PHP with <code>--disable-all/--disable-dom</code>, or enable the dom extension individually before continuing.',
            'fatal' => true
        ),
        'fileinfo' => array(
            'descrip' => 'MIME Magic Support (fileinfo)',
            'error' => 'The fileinfo PECL module is used to provide MIME Magic scanning on unknown data. See horde/docs/INSTALL for information on how to install PECL extensions.'
        ),
        'ftp' => array(
            'descrip' => 'FTP Support',
            'error' => 'FTP support is only required if you want to authenticate against an FTP server, upload your configuration files with FTP, or use an FTP server for file storage.'
        ),
        'gd' => array(
            'descrip' => 'GD Support',
            'error' => 'Horde will use the GD extension to perform manipulations on image data. You can also use either the ImageMagick software or Imagick extension to do these manipulations instead.'
        ),
        'gettext' => array(
            'descrip' => 'Gettext Support',
            'error' => 'Horde will not run without gettext support. Compile PHP with <code>--with-gettext</code> before continuing.',
            'fatal' => true
        ),
        'geoip' => array(
            'descrip' => 'GeoIP Support (PECL extension)',
            'error' => 'Horde can optionally use the GeoIP extension to provide faster country name lookups.'
        ),
        'hash' => array(
            'descrip' => 'Hash Support',
            'error' => 'Horde will not run without the hash extension. Don\'t compile PHP with <code>--disable-all/--disable-hash</code>, or enable the hash extension individually before continuing.',
            'fatal' => true
        ),
        'iconv' => array(
            'descrip' => 'Iconv Support',
            'error' => 'If you want to take full advantage of Horde\'s localization features and character set support, you will need the iconv extension.'
        ),
        'iconv_libiconv' => array(
            'descrip' => 'GNU Iconv Support',
            'error' => 'For best results make sure the iconv extension is linked against GNU libiconv.',
            'function' => '_checkIconvImplementation'
        ),
        'idn' => array(
            'descrip' => 'Internationalized Domain Names Support (PECL extension)',
            'error' => 'Horde requires the idn module to handle Internationalized Domain Names.'
        ),
        'imagick' => array(
            'descrip' => 'Imagick Library',
            'error' => 'Horde can make use of the Imagick Library, if it is installed on your system.  It is highly recommended to use either ImageMagick\'s convert utility or the Imagick php library for faster results.'
        ),
        'json' => array(
            'descrip' => 'JSON Support',
            'error' => 'Horde will not run without the json extension. Don\'t compile PHP with <code>--disable-all/--disable-json</code>, or enable the json extension individually before continuing.',
            'fatal' => true
        ),
        'ldap' => array(
            'descrip' => 'LDAP Support',
            'error' => 'LDAP support is only required if you want to use an LDAP server for anything like authentication, address books, or preference storage.'
        ),
        'lzf' => array(
            'descrip' => 'LZF Compression Support (PECL extension)',
            'error' => 'If the lzf PECL module is available, Horde can compress some cached data in your session to make your session size smaller.'
        ),
        'mbstring' => array(
            'descrip' => 'Mbstring Support',
            'error' => 'If you want to take full advantage of Horde\'s localization features and character set support, you will need the mbstring extension.'
        ),
        'mcrypt' => array(
            'descrip' => 'Mcrypt Support',
            'error' => 'Mcrypt is a general-purpose cryptography library which is broader and significantly more efficient (FASTER!) than PHP\'s own cryptographic code and will provider faster logins.'
        ),
        'memcache' => array(
            'descrip' => 'memcached Support (memcache) (PECL extension)',
            'error' => 'The memcache PECL module is only needed if you are using a memcached server for caching or sessions. See horde/docs/INSTALL for information on how to install PECL/PHP extensions.'
        ),
        'mysql' => array(
            'descrip' => 'MySQL Support',
            'error' => 'The MySQL extension is only required if you want to use a MySQL database server for data storage.'
        ),
        'openssl' => array(
            'descrip' => 'OpenSSL Support',
            'error' => 'The OpenSSL extension is required for any kind of S/MIME support.'
        ),
        'pcre' => array(
            'descrip' => 'PCRE Support',
            'error' => 'Horde will not run without the pcre extension. Don\'t compile PHP with <code>--disable-all/--without-pcre-regex</code>, or enable the pcre extension individually before continuing.',
            'fatal' => true
        ),
        'pdo' => array(
            'descrip' => 'PDO',
            'error' => 'The PDO extension is required if you plan on using a database backend other than mysql or mysqli with Horde_Db.',
        ),
        'pgsql' => array(
            'descrip' => 'PostgreSQL Support',
            'error' => 'The PostgreSQL extension is only required if you want to use a PostgreSQL database server for data storage.'
        ),
        'session' => array(
            'descrip' => 'Session Support',
            'fatal' => true
        ),
        'SimpleXML' => array(
            'descrip' => 'SimpleXML support',
            'error' => 'Horde will not run without the SimpleXML extension. Don\'t compile PHP with <code>--disable-all/--disable-simplexml</code>, or enable the SimpleXML extension individually before continuing.',
            'fatal' => true
        ),
        'tidy' => array(
            'descrip' => 'Tidy support',
            'error' => 'The tidy PHP extension is used to sanitize HTML data.'
        ),
        'xml' => array(
            'descrip' => 'XML Parser support',
            'error' => 'Horde will not run without the xml extension. Don\'t compile PHP with <code>--disable-all/--without-xml</code>, or enable the xml extension individually before continuing.',
            'fatal' => true
        ),
        'zlib' => array(
            'descrip' => 'Zlib Support',
            'error' => 'The zlib module is highly recommended for use with Horde.  It allows page compression and handling of ZIP and GZ data. Compile PHP with <code>--with-zlib</code> to activate.'
        )
    );

    /**
     * PHP settings list.
     * <pre>
     * KEY:   setting name
     * VALUE: An array with the following entries:
     *        'error' - (string) Error Message
     *        'function' - (string) Reference to function to run. If function
     *                     returns non-empty value, error message will be
     *                     output.
     *        'setting' - (mixed) Either a boolean (whether setting should be
     *                    on or off) or 'value', which will simply output the
     *                    value of the setting.
     * </pre>
     *
     * @var array
     */
    protected $_settingsList = array(
        'allow_url_include' => array(
            'setting' => false,
            'error' => 'This is a security hazard. Horde will attempt to disable automatically, but it is best to manually disable also.'
        ),
        'magic_quotes_runtime' => array(
            'setting' => false,
            'error' => 'magic_quotes_runtime may cause problems with database inserts, etc. Horde will attempt to disable automatically, but it is best to manually disable also.'
        ),
        'magic_quotes_sybase' => array(
            'setting' => false,
            'error' => 'magic_quotes_sybase may cause problems with database inserts, etc. Horde will attempt to disable automatically, but it is best to manually disable also.'
        ),
        'memory_limit' => array(
            'setting' => 'value',
            'error' => 'If PHP\'s internal memory limit is not set high enough Horde will not be able to handle large data items. You should set the value of memory_limit in php.ini to a sufficiently high value - at least 64M is recommended.',
            'function' => '_checkMemoryLimit'
        ),
        'register_globals' => array(
            'setting' => false,
            'error' => 'Register globals has been deprecated in PHP 5. Horde will fatally exit if it is set. Turn it off.'
        ),
        'safe_mode' => array(
            'setting' => false,
            'error' => 'If safe_mode is enabled, Horde cannot set enviroment variables, which means Horde will be unable to translate the user interface into different languages.'
        ),
        'session.auto_start' => array(
            'setting' => false,
            'error' => 'Horde won\'t work with automatically started sessions, because it explicitly creates new session when necessary to protect against session fixations.'
        ),
        'session.gc_divisor' => array(
            'setting' => 'value',
            'error' => 'PHP automatically garbage collects old session information, as long as this setting (and session.gc_probability) are set to non-zero. It is recommended that this value be "10000" or higher (see docs/INSTALL).',
            'function' => '_checkGcDivisor'
        ),
        'session.gc_probability' => array(
            'setting' => 'value',
            'error' => 'PHP automatically garbage collects old session information, as long as this setting (and session.gc_divisor) are set to non-zero. It is recommended that this value be "1".',
            'function' => '_checkGcProbability'
        ),
        'session.use_trans_sid' => array(
            'setting' => false,
            'error' => 'Horde will work with session.use_trans_sid turned on, but you may see double session-ids in your URLs, and if the session name in php.ini differs from the session name configured in Horde, you may get two session ids and see other odd behavior. The URL-rewriting that use_trans_sid does also tends to break XHTML compliance. In short, you should really disable this.'
        ),
        'tidy.clean_output' => array(
            'setting' => false,
            'error' => 'This will break output of any dynamically created, non-HTML content. Horde will attempt to disable automatically, but it is best to manually disable also.'
        ),
        'zend_accelerator.compress_all' => array(
            'setting' => false,
            'error' => 'You should not enable output compression unconditionally because some browsers and scripts don\'t work well with output compression. Enable compression in Horde\'s configuration instead, so that we have full control over the conditions where to enable and disable it.'
        ),
        'zend.ze1_compatibility_mode' => array(
            'setting' => false,
            'error' => 'Unneeded, deprecated PHP 4 compatibility option. Horde will attempt to disable automatically, but it is best to manually disable also.'
        ),
        'zlib.output_compression' => array(
            'setting' => false,
            'error' => 'You should not enable output compression unconditionally because some browsers and scripts don\'t work well with output compression. Enable compression in Horde\'s configuration instead, so that we have full control over the conditions where to enable and disable it.'
        )
    );

    /**
     * PEAR modules list.
     * <pre>
     * KEY:   PEAR class name
     * VALUE: An array with the following entries:
     *        'depends' - (?) This module depends on another module.
     *        'error' - (string) Error message.
     *        'function' - (string) Reference to function to run if module is
     *                     found.
     *        'path' - (string) The path to the PEAR module. Only needed if
     *                 KEY is not autoloadable.
     *        'required' - (boolean) Is this PEAR module required?
     * </pre>
     *
     * @var array
     */
    protected $_pearList = array(
        'Auth_SASL' => array(
            'error' => 'Horde will work without the Auth_SASL class, but if you use Access Control Lists in IMP you should be aware that without this class passwords will be sent to the IMAP server in plain text when retrieving ACLs.'
        ),
        'Cache' => array(
            'error' => 'Cache is used by the Services_Weather module on the weather applet/block on the portal page.'
        ),
        'Crypt_Blowfish' => array(
            'error' => 'Crypt_Blowfish is required to store authentication credentials securely within the session data.',
            'required' => true
        ),
        'Date' => array(
            'path' => 'Date/Calc.php',
            'error' => 'Horde requires the Date_Calc class for Kronolith to calculate dates.'
        ),
        'HTTP_Request' => array(
            'error' => 'Parts of Horde (Jonah, the XML-RPC client/server) use the HTTP_Request library to retrieve URLs and do other HTTP requests.'
        ),
        'HTTP_WebDAV_Server' => array(
            'error' => 'The HTTP_WebDAV_Server is required if you want to use the WebDAV interface of Horde, e.g. to access calendars or tasklists with external clients.'
        ),
        'MDB2' => array(
            'error' => 'You will need MDB2 if you are using the SQL driver for Shares.',
        ),
        'Net_DNS2' => array(
            'error' => 'Net_DNS2 can speed up hostname lookups against broken DNS servers.'
        ),
        'Net_SMTP' => array(
            'error' => 'Make sure you are using the Net_SMTP module if you want "smtp" to work as a mailer option.'
        ),
        'Net_Socket' => array(
            'error' => 'Make sure you are using a version of PEAR which includes the Net_Socket class, or that you have installed the Net_Socket package seperately. See the INSTALL file for instructions on installing Net_Socket.'
        ),
        'Services_Weather' => array(
            'error' => 'Services_Weather is used by the weather applet/block on the portal page.'
        ),
        'XML_Serializer' => array(
            'error' => 'XML_Serializer is used by the Services_Weather module on the weather applet/block on the portal page.'
        )
    );

    /**
     * Required configuration files.
     * <pre>
     * KEY:   file path
     * VALUE: The error message to use (null to use default message)
     * </pre>
     *
     * @var array
     */
    protected $_fileList = array(
        'config/conf.php' => null,
        'config/mime_drivers.php' => null,
        'config/nls.php' => null,
        'config/prefs.php' => null,
        'config/registry.php' => null
    );

    /**
     * Inter-Horde application dependencies.
     * <pre>
     * KEY:   app name
     * VALUE: An array with the following entries:
     *        'error' - (string) Error message.
     *        'version' - (string) Minimum version required of the app.
     * </pre>
     *
     * @var array
     */
    protected $_appList = array();

    /**
     * Constructor.
     */
    public function __construct()
    {
        /* Store the PHP version information. */
        $this->_phpver = $this->_splitPhpVersion(PHP_VERSION);

        /* We want to be as verbose as possible here. */
        error_reporting(E_ALL);

        /* Set character encoding. */
        header('Content-type: text/html; charset=UTF-8');
        header('Vary: Accept-Language');
    }

    /**
     * Parse PHP version.
     *
     * @param string $version  A PHP-style version string (X.X.X).
     *
     * @param array  The parsed string.
     *               Keys: 'major', 'minor', 'subminor', 'class'
     */
    protected function _splitPhpVersion($version)
    {
        /* First pick off major version, and lower-case the rest. */
        if ((strlen($version) >= 3) && ($version[1] == '.')) {
            $phpver['major'] = substr($version, 0, 3);
            $version = substr(strtolower($version), 3);
        } else {
            $phpver['major'] = $version;
            $phpver['class'] = 'unknown';
            return $phpver;
        }

        if ($version[0] == '.') {
            $version = substr($version, 1);
        }

        /* Next, determine if this is 4.0b or 4.0rc; if so, there is no
           minor, the rest is the subminor, and class is set to beta. */
        $s = strspn($version, '0123456789');
        if ($s == 0) {
            $phpver['subminor'] = $version;
            $phpver['class'] = 'beta';
            return $phpver;
        }

        /* Otherwise, this is non-beta;  the numeric part is the minor,
           the rest is either a classification (dev, cvs) or a subminor
           version (rc<x>, pl<x>). */
        $phpver['minor'] = substr($version, 0, $s);
        if ((strlen($version) > $s) &&
            (($version[$s] == '.') || ($version[$s] == '-'))) {
            ++$s;
        }
        $phpver['subminor'] = substr($version, $s);
        if (($phpver['subminor'] == 'cvs') ||
            ($phpver['subminor'] == 'dev') ||
            (substr($phpver['subminor'], 0, 2) == 'rc')) {
            unset($phpver['subminor']);
            $phpver['class'] = 'dev';
        } else {
            if (!$phpver['subminor']) {
                unset($phpver['subminor']);
            }
            $phpver['class'] = 'release';
        }

        return $phpver;
    }

    /**
     * Check the list of PHP modules.
     *
     * @return string  The HTML output.
     */
    public function phpModuleCheck()
    {
        $output = '';

        foreach ($this->_moduleList as $key => $val) {
            $error_msg = $mod_test = $status_out = $fatal = null;
            $test_function = null;
            $entry = array();

            if (is_array($val)) {
                $descrip = $val['descrip'];
                $fatal = !empty($val['fatal']);
                if (isset($val['phpver']) &&
                    (version_compare(PHP_VERSION, $val['phpver']) == -1)) {
                    $mod_test = true;
                    $status_out = 'N/A';
                }
                if (isset($val['error'])) {
                    $error_msg = $val['error'];
                }
                if (isset($val['function'])) {
                    $test_function = $val['function'];
                }
            } else {
                $descrip = $val;
            }

            if (is_null($status_out)) {
                if (!is_null($test_function)) {
                    $mod_test = call_user_func(array($this, $test_function));
                } else {
                    $mod_test = extension_loaded($key);
                }

                $status_out = $this->_status($mod_test, $fatal);
            }

            $entry[] = $descrip;
            $entry[] = $status_out;

            if (!is_null($error_msg) && !$mod_test) {
                $entry[] = $error_msg;
                if (!$fatal) {
                    $entry[] = 1;
                }
            }

            $output .= $this->_outputLine($entry);

            if ($fatal && !$mod_test) {
                echo $output;
                exit;
            }
        }

        return $output;
    }

    /**
     * Additional check for iconv module implementation.
     *
     * @return string  Returns error string on error.
     */
    protected function _checkIconvImplementation()
    {
        return extension_loaded('iconv') &&
               in_array(ICONV_IMPL, array('libiconv', 'glibc'));
    }

    /**
     * Checks the list of PHP settings.
     *
     * @params array $settings  The list of settings to check.
     *
     * @return string  The HTML output.
     */
    public function phpSettingCheck($settings = null)
    {
        $output = '';

        if (is_null($settings)) {
            $settings = $this->_settingsList;
        }

        foreach ($settings as $key => $val) {
            $entry = array();
            if (is_bool($val['setting'])) {
                $result = (ini_get($key) == $val['setting']);
                $entry[] = $key . ' ' . (($val['setting'] === true) ? 'enabled' : 'disabled');
                $entry[] = $this->_status($result);
                if (!$result &&
                    (!isset($val['function']) ||
                     call_user_func(array($this, $val['function'])))) {
                    $entry[] = $val['error'];
                }
            } elseif ($val['setting'] == 'value') {
                $entry[] = $key . ' value';
                $entry[] = ini_get($key);
                if (!empty($val['error']) &&
                    (!isset($val['function']) ||
                     call_user_func(array($this, $val['function'])))) {
                    $entry[] = $val['error'];
                    $entry[] = 1;
                }
            }
            $output .= $this->_outputLine($entry);
        }

        return $output;
    }

    /**
     * Check the list of PEAR modules.
     *
     * @return string  The HTML output.
     */
    public function pearModuleCheck()
    {
        $output = '';

        /* Turn tracking of errors on. */
        ini_set('track_errors', 1);

        /* Print the include_path. */
        $output .= $this->_outputLine(array("<strong>PEAR Search Path (PHP's include_path)</strong>", '&nbsp;<tt>' . ini_get('include_path') . '</tt>'));

        /* Check for PEAR in general. */
        $entry = array();
        $entry[] = 'PEAR';
        $entry[] = $this->_status(!isset($php_errormsg));
        if (isset($php_errormsg)) {
            $entry[] = 'Check your PHP include_path setting to make sure it has the PEAR library directory.';
            $output .= $this->_outputLine($entry);
            ini_restore('track_errors');
            return $output;
        }
        $output .= $this->_outputLine($entry);

        /* Go through module list. */
        $succeeded = array();
        foreach ($this->_pearList as $key => $val) {
            $entry = array();

            /* If this module depends on another module that we
             * haven't succesfully found, fail the test. */
            if (!empty($val['depends']) && empty($succeeded[$val['depends']])) {
                $result = false;
            } elseif (empty($val['path'])) {
                $result = @class_exists($key);
            } else {
                $result = @include_once $val['path'];
            }
            $error_msg = $val['error'];
            if ($result && isset($val['function'])) {
                $func_output = call_user_func(array($this, $val['function']));
                if ($func_output) {
                    $result = false;
                    $error_msg = $func_output;
                }
            }
            $entry[] = $key;
            $entry[] = $this->_status($result, !empty($val['required']));

            if ($result) {
                $succeeded[$key] = true;
            } else {
                if (!empty($val['required'])) {
                    $error_msg .= ' THIS IS A REQUIRED MODULE!';
                }
                $entry[] = $error_msg;
                if (empty($val['required'])) {
                    $entry[] = 1;
                }
            }

            $output .= $this->_outputLine($entry);
        }

        /* Restore previous value of 'track_errors'. */
        ini_restore('track_errors');

        return $output;
    }

    /**
     * Additional check for 'session.gc_divisor'.
     *
     * @return boolean  Returns true if error string should be displayed.
     */
    protected function _checkMemoryLimit()
    {
        $memlimit = trim(ini_get('memory_limit'));
        switch (strtolower(substr($memlimit, -1))) {
        case 'g':
            $memlimit *= 1024;
            // Fall-through

        case 'm':
            $memlimit *= 1024;
            // Fall-through

        case 'k':
            $memlimit *= 1024;
            // Fall-through
        }

        return ($memlimit < 67108864);
    }

    /**
     * Additional check for 'session.gc_divisor'.
     *
     * @return boolean  Returns true if error string should be displayed.
     */
    protected function _checkGcDivisor()
    {
        return (ini_get('session.gc_divisor') < 10000);
    }

    /**
     * Additional check for 'session.gc_probability'.
     *
     * @return boolean  Returns true if error string should be displayed.
     */
    protected function _checkGcProbability()
    {
        return !(ini_get('session.gc_probability') &&
                 ini_get('session.gc_divisor'));
    }

    /**
     * Check the list of required files
     *
     * @return string  The HTML output.
     */
    public function requiredFileCheck()
    {
        $output = '';
        $filedir = $GLOBALS['registry']->get('fileroot');

        foreach ($this->_fileList as $key => $val) {
            $entry = array();
            $result = file_exists($filedir . '/' . $key);

            $entry[] = $key;
            $entry[] = $this->_status($result);

            if (!$result) {
                if (empty($val)) {
                    $text = 'The file <code>' . $key . '</code> appears to be missing.';
                    if ($key == 'config/conf.php') {
                        $text .= ' You need to login to Horde as an administrator and create the initial configuration file.';
                    } else {
                        $text .= ' You probably just forgot to copy <code>' . $key . '.dist</code> over. While you do that, take a look at the settings and make sure they are appropriate for your site.';
                    }

                    $entry[] = $text;
                } else {
                    $entry[] = $val;
                }
            }

            $output .= $this->_outputLine($entry);
        }

        return $output;
    }

    /**
     * Check the list of required Horde applications.
     *
     * @return string  The HTML output.
     */
    public function requiredAppCheck()
    {
        $output = '';

        $horde_apps = $GLOBALS['registry']->listApps(null, true, null);

        foreach ($this->_appList as $key => $val) {
            $entry = array();
            $entry[] = $key;

            if (!isset($horde_apps[$key])) {
                $entry[] = $this->_status(false, false);
                $entry[] = $val['error'];
                $entry[] = 1;
            } else {
                /* Strip '-git', and H# (ver) from version string. */
                $origver = $GLOBALS['registry']->getVersion($key);
                $appver = preg_replace('/(H\d) \((.*)\)/', '$2', str_replace('-git', '', $origver));
                if (version_compare($val['version'], $appver) === 1) {
                    $entry[] = $this->_status(false, false) . ' (Have version: ' . $origver . '; Need version: ' . $val['version'] . ')';
                    $entry[] = $val['error'];
                    $entry[] = 1;
                } else {
                    $entry[] = $this->_status(true) . ' (Version: ' . $origver . ')';
                }
            }
            $output .= $this->_outputLine($entry);
        }

        return $output;
    }

    /**
     * Obtain information on the PHP version.
     *
     * @return object stdClass  TODO
     */
    public function getPhpVersionInformation()
    {
        $output = new stdClass;
        $vers_check = true;

        $testscript = Horde::selfUrl(true);
        $output->phpinfo = $testscript->copy()->add('mode', 'phpinfo');
        $output->extensions = $testscript->copy()->add('mode', 'extensions');
        $output->version = PHP_VERSION;
        $output->major = $this->_phpver['major'];
        if (isset($this->_phpver['minor'])) {
            $output->minor = $this->_phpver['minor'];
        }
        if (isset($this->_phpver['subminor'])) {
            $output->subminor = $this->_phpver['subminor'];
        }
        $output->class = $this->_phpver['class'];

        $output->status_color = 'red';
        if ($output->major < '5.2') {
            $output->status = 'This version of PHP is not supported. You need to upgrade to a more recent version.';
            $vers_check = false;
        } elseif (in_array($output->major, $this->_supported)) {
            $output->status = 'You are running a supported version of PHP.';
            $output->status_color = 'green';
        } else {
            $output->status = 'This version of PHP has not been fully tested with this version of Horde.';
            $output->status_color = 'orange';
        }

        if (!$vers_check) {
            $output->version_check = 'Horde requires PHP 5.2.0 or greater.';
        }

        return $output;
    }

    /**
     * Output the results of a status check.
     *
     * @param boolean $bool      The result of the status check.
     * @param boolean $required  Whether the checked item is required.
     *
     * @return string  The HTML of the result of the status check.
     */
    protected function _status($bool, $required = true)
    {
        if ($bool) {
            return '<strong style="color:green">Yes</strong>';
        } elseif ($required) {
            return '<strong style="color:red">No</strong>';
        }

        return '<strong style="color:orange">No</strong>';
    }

    /**
     * Internal output function.
     *
     * @param array $entry  Array with the following values:
     * <pre>
     * 1st value: Header
     * 2nd value: Test Result
     * 3rd value: Error message (if present)
     * 4th value: Error level (if present): 0 = error, 1 = warning
     * </pre>
     *
     * @return string  HTML output.
     */
    protected function _outputLine($entry)
    {
        $output = '<li>' . array_shift($entry) . ': ' . array_shift($entry);
        if (!empty($entry)) {
            $msg = array_shift($entry);
            $output .= '<br /><strong style="color:' . (empty($entry) || !array_shift($entry) ? 'red' : 'orange') . '">' . $msg . "</strong>\n";
        }

        return $output . "</li>\n";
    }

    /**
     * Any application specific tests that need to be done.
     *
     * @return string  HTML output.
     */
    public function appTests()
    {
        /* File upload information. */
        $upload_check = $this->phpSettingCheck(array(
            'file_uploads' => array(
                'error' => 'file_uploads must be enabled for some features like sending emails with IMP.',
                'setting' => true
            )
        ));
        $upload_tmp_dir = ($dir = ini_get('upload_tmp_dir'))
            ? '<li>upload_tmp_dir: <strong style="color:"' . (is_writable($dir) ? 'green' : 'red') . '">' . $dir . '</strong></li>'
            : '';

        $ret = '<h1>File Uploads</h1><ul>' .
            $upload_check .
            $upload_tmp_dir .
            '<li>upload_max_filesize: ' . ini_get('upload_max_filesize') . '</li>'.
            '<li>post_max_size: ' . ini_get('post_max_size') . '<br />' .
            'This value should be several times the expect largest upload size (notwithstanding any upload limits present in an application). Any upload that exceeds this size will cause any state information sent along with the uploaded data to be lost. This is a PHP limitation and can not be worked around.'.
            '</li></ul>';

        /* Determine if 'static' is writable by the web user. */
        $ret .= '<h1>Local File Permissions</h1><ul>' .
            '<li>Is <tt>' . htmlspecialchars(HORDE_BASE) . '/static</tt> writable by the web server user? ';
        $ret .= is_writable(HORDE_BASE . '/static')
            ? '<strong style="color:green">Yes</strong>'
            : "<strong style=\"color:red\">No</strong><br /><strong style=\"color:orange\">If caching javascript and CSS files by storing them in static files (HIGHLY RECOMMENDED), this directory must be writable as the user the web server runs as.</strong>";
        return $ret . '</li></ul>';
    }

}
