<?php
/**
 * The Horde_Util:: class provides generally useful methods.
 *
 * Copyright 1999-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @author   Jon Parise <jon@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @package  Util
 */
class Horde_Util
{
    /**
     * A list of random patterns to use for overwriting purposes.
     * See http://www.cs.auckland.ac.nz/~pgut001/pubs/secure_del.html.
     * We save the random overwrites for efficiency reasons.
     *
     * @var array
     */
    static public $patterns = array(
        "\x55", "\xaa", "\x92\x49\x24", "\x49\x24\x92", "\x24\x92\x49",
        "\x00", "\x11", "\x22", "\x33", "\x44", "\x55", "\x66", "\x77",
        "\x88", "\x99", "\xaa", "\xbb", "\xcc", "\xdd", "\xee", "\xff",
        "\x92\x49\x24", "\x49\x24\x92", "\x24\x92\x49", "\x6d\xb6\xdb",
        "\xb6\xdb\x6d", "\xdb\x6d\xb6"
    );

    /**
     * Temp directory locations.
     *
     * @var array
     */
    static public $tmpLocations = array(
        '/tmp', '/var/tmp', 'c:\WUTemp', 'c:\temp', 'c:\windows\temp',
        'c:\winnt\temp'
    );

    /**
     * TODO
     */
    static protected $_magicquotes = null;

    /**
     * TODO
     */
    static protected $_shutdowndata = array(
        'dirs' => array(),
        'files' => array(),
        'secure' => array()
    );

    /**
     * TODO
     */
    static protected $_shutdownreg = false;

    /**
     * Cache for extensionExists().
     *
     * @var array
     */
    static protected $_cache = array();

    /**
     * Returns an object's clone.
     *
     * @param object &$obj  The object to clone.
     *
     * @return object  The cloned object.
     */
    static public function &cloneObject(&$obj)
    {
        if (!is_object($obj)) {
            $bt = debug_backtrace();
            if (isset($bt[1])) {
                $caller = $bt[1]['function'];
                if (isset($bt[1]['class'])) {
                    $caller = $bt[1]['class'].$bt[1]['type'].$caller;
                }
            } else {
                $caller = 'main';
            }

            $caller .= ' on line ' . $bt[0]['line'] . ' of ' . $bt[0]['file'];

            $ret = $obj;
            return $ret;
        }

        $ret = clone($obj);
        return $ret;
    }

    /**
     * Checks to see if a value has been set by the script and not by GET,
     * POST, or cookie input. The value being checked MUST be in the global
     * scope.
     *
     * @param string $varname  The variable name to check.
     * @param mixed $default   Default value if the variable isn't present
     *                         or was specified by the user. Defaults to null.
     *
     * @return mixed  $default if the var is in user input or not present,
     *                the variable value otherwise.
     */
    static public function nonInputVar($varname, $default = null)
    {
        return (isset($_GET[$varname]) || isset($_POST[$varname]) || isset($_COOKIE[$varname]))
            ? $default
            : (isset($GLOBALS[$varname]) ? $GLOBALS[$varname] : $default);
    }

    /**
     * Adds a name=value pair to the end of an URL, taking care of whether
     * there are existing parameters and whether to use ?, & or &amp; as the
     * glue.  All data will be urlencoded.
     *
     * @deprecated
     *
     * @param Horde_Url|string $url  The URL to modify.
     * @param mixed $parameter       Either the name value -or- an array of
     *                               name/value pairs.
     * @param string $value          If specified, the value part ($parameter
     *                               is then assumed to just be the parameter
     *                               name).
     * @param boolean $encode        Encode the argument separator?
     *
     * @return Horde_Url  The modified URL.
     */
    static public function addParameter($url, $parameter, $value = null,
                                        $encode = true)
    {
        if (empty($parameter)) {
            return $url;
        }

        if ($url instanceof Horde_Url) {
            $url = $url->copy()->add($parameter, $value);
            if (is_null($url->raw)) {
                $url->setRaw(!$encode);
            }
            return $url;
        }

        $url = new Horde_Url($url);
        if (is_null($url->raw) && count($url->parameters)) {
            $url->setRaw(!$encode);
        }
        return $url->add($parameter, $value);
    }

    /**
     * Removes name=value pairs from a URL.
     *
     * @deprecated
     *
     * @param Horde_Url|string $url  The URL to modify.
     * @param mixed $remove          Either a single parameter to remove or an
     *                               array of parameters to remove.
     *
     * @return Horde_Url  The modified URL.
     */
    static public function removeParameter($url, $remove)
    {
        if ($url instanceof Horde_Url) {
            return $url->copy()->remove($remove);
        }

        $horde_url = new Horde_Url($url);
        return $horde_url->remove($remove);
    }

    /**
     * Returns a hidden form input containing the session name and id.
     *
     * @param boolean $append_session  0 = only if needed, 1 = always.
     *
     * @return string  The hidden form input, if needed/requested.
     */
    static public function formInput($append_session = 0)
    {
        return (($append_session == 1) || !isset($_COOKIE[session_name()]))
            ? '<input type="hidden" name="' . htmlspecialchars(session_name()) . '" value="' . htmlspecialchars(session_id()) . "\" />\n"
            : '';
    }

    /**
     * Prints a hidden form input containing the session name and id.
     *
     * @param boolean $append_session  0 = only if needed, 1 = always.
     */
    static public function pformInput($append_session = 0)
    {
        echo self::formInput($append_session);
    }

    /**
     * If magic_quotes_gpc is in use, run stripslashes() on $var.
     *
     * @param string &$var  The string to un-quote, if necessary.
     *
     * @return string  $var, minus any magic quotes.
     */
    static public function dispelMagicQuotes(&$var)
    {
        if (is_null(self::$_magicquotes)) {
            self::$_magicquotes = get_magic_quotes_gpc();
        }

        if (self::$_magicquotes) {
            if (!is_array($var)) {
                $var = stripslashes($var);
            } else {
                array_walk($var, array(__CLASS__, 'dispelMagicQuotes'));
            }
        }

        return $var;
    }

    /**
     * Gets a form variable from GET or POST data, stripped of magic quotes if
     * necessary. If the variable is somehow set in both the GET data and the
     * POST data, the value from the POST data will be returned and the GET
     * value will be ignored.
     *
     * @param string $var      The name of the form variable to look for.
     * @param string $default  The value to return if the variable is not
     *                         there.
     *
     * @return string  The cleaned form variable, or $default.
     */
    static public function getFormData($var, $default = null)
    {
        return (($val = self::getPost($var)) !== null)
            ? $val
            : self::getGet($var, $default);
    }

    /**
     * Gets a form variable from GET data, stripped of magic quotes if
     * necessary. This function will NOT return a POST variable.
     *
     * @param string $var      The name of the form variable to look for.
     * @param string $default  The value to return if the variable is not
     *                         there.
     *
     * @return string  The cleaned form variable, or $default.
     */
    static public function getGet($var, $default = null)
    {
        return (isset($_GET[$var]))
            ? self::dispelMagicQuotes($_GET[$var])
            : $default;
    }

    /**
     * Gets a form variable from POST data, stripped of magic quotes if
     * necessary. This function will NOT return a GET variable.
     *
     * @param string $var      The name of the form variable to look for.
     * @param string $default  The value to return if the variable is not
     *                         there.
     *
     * @return string  The cleaned form variable, or $default.
     */
    static public function getPost($var, $default = null)
    {
        return (isset($_POST[$var]))
            ? self::dispelMagicQuotes($_POST[$var])
            : $default;
    }

    /**
     * Determines the location of the system temporary directory.
     *
     * @return string  A directory name which can be used for temp files.
     *                 Returns false if one could not be found.
     */
    static public function getTempDir()
    {
        $tmp = false;

        // Try sys_get_temp_dir() - only available in PHP 5.2.1+.
        if (function_exists('sys_get_temp_dir')) {
            $tmp = sys_get_temp_dir();
        }

        // First, try PHP's upload_tmp_dir directive.
        if (!$tmp) {
            $tmp = ini_get('upload_tmp_dir');

            // Otherwise, try to determine the TMPDIR environment
            // variable.
            if (!$tmp) {
                $tmp = getenv('TMPDIR');

                // If we still cannot determine a value, then cycle through a
                // list of preset possibilities.
                if (!$tmp) {
                    foreach (self::$tmpLocations as $tmp_check) {
                        if (@is_dir($tmp_check)) {
                            return $tmp_check;
                        }
                    }
                }
            }
        }

        return $tmp ? $tmp : false;
    }

    /**
     * Creates a temporary filename for the lifetime of the script, and
     * (optionally) registers it to be deleted at request shutdown.
     *
     * @param string $prefix   Prefix to make the temporary name more
     *                         recognizable.
     * @param boolean $delete  Delete the file at the end of the request?
     * @param string $dir      Directory to create the temporary file in.
     * @param boolean $secure  If deleting the file, should we securely delete
     *                         the file by overwriting it with random data?
     *
     * @return string   Returns the full path-name to the temporary file.
     *                  Returns false if a temp file could not be created.
     */
    static public function getTempFile($prefix = '', $delete = true, $dir = '',
                                       $secure = false)
    {
        $tempDir = (empty($dir) || !is_dir($dir))
            ? null
            : $dir;

        $tempFile = tempnam($tempDir, $prefix);

        // If the file was created, then register it for deletion and return.
        if (empty($tempFile)) {
            return false;
        }

        if ($delete) {
            self::deleteAtShutdown($tempFile, true, $secure);
        }

        return $tempFile;
    }

    /**
     * Creates a temporary filename with a specific extension for the lifetime
     * of the script, and (optionally) registers it to be deleted at request
     * shutdown.
     *
     * @param string $extension  The file extension to use.
     * @param string $prefix     Prefix to make the temporary name more
     *                           recognizable.
     * @param boolean $delete    Delete the file at the end of the request?
     * @param string $dir        Directory to create the temporary file in.
     * @param boolean $secure    If deleting file, should we securely delete
     *                           the file by overwriting it with random data?
     *
     * @return string   Returns the full path-name to the temporary file.
     *                  Returns false if a temporary file could not be created.
     */
    static public function getTempFileWithExtension($extension = '.tmp',
                                                    $prefix = '',
                                                    $delete = true, $dir = '',
                                                    $secure = false)
    {
        $tempDir = (empty($dir) || !is_dir($dir))
            ? self::getTempDir()
            : $dir;

        if (empty($tempDir)) {
            return false;
        }

        $windows = substr(PHP_OS, 0, 3) == 'WIN';
        $tries = 1;
        do {
            // Get a known, unique temporary file name.
            $sysFileName = tempnam($tempDir, $prefix);
            if ($sysFileName === false) {
                return false;
            }

            // tack on the extension
            $tmpFileName = $sysFileName . $extension;
            if ($sysFileName == $tmpFileName) {
                return $sysFileName;
            }

            // Move or point the created temporary file to the full filename
            // with extension. These calls fail if the new name already
            // exists.
            $fileCreated = ($windows ? @rename($sysFileName, $tmpFileName) : @link($sysFileName, $tmpFileName));
            if ($fileCreated) {
                if (!$windows) {
                    unlink($sysFileName);
                }

                if ($delete) {
                    self::deleteAtShutdown($tmpFileName, true, $secure);
                }

                return $tmpFileName;
            }

            unlink($sysFileName);
        } while (++$tries <= 5);

        return false;
    }

    /**
     * Creates a temporary directory in the system's temporary directory.
     *
     * @param boolean $delete   Delete the temporary directory at the end of
     *                          the request?
     * @param string $temp_dir  Use this temporary directory as the directory
     *                          where the temporary directory will be created.
     *
     * @return string  The pathname to the new temporary directory.
     *                 Returns false if directory not created.
     */
    static public function createTempDir($delete = true, $temp_dir = null)
    {
        if (is_null($temp_dir)) {
            $temp_dir = self::getTempDir();
        }

        if (empty($temp_dir)) {
            return false;
        }

        /* Get the first 8 characters of a random string to use as a temporary
           directory name. */
        do {
            $new_dir = $temp_dir . '/' . substr(base_convert(uniqid(mt_rand()), 10, 36), 0, 8);
        } while (file_exists($new_dir));

        $old_umask = umask(0000);
        if (!mkdir($new_dir, 0700)) {
            $new_dir = false;
        } elseif ($delete) {
            self::deleteAtShutdown($new_dir);
        }
        umask($old_umask);

        return $new_dir;
    }

    /**
     * Returns the canonical path of the string.  Like PHP's built-in
     * realpath() except the directory need not exist on the local server.
     *
     * Algorithim loosely based on code from the Perl File::Spec::Unix module
     * (version 1.5).
     *
     * @param string $path  A file path.
     *
     * @return string  The canonicalized file path.
     */
    static public function realPath($path)
    {
        /* Standardize on UNIX directory separators. */
        if (!strncasecmp(PHP_OS, 'WIN', 3)) {
            $path = str_replace('\\', '/', $path);
        }

        /* xx////xx -> xx/xx
         * xx/././xx -> xx/xx */
        $path = preg_replace(array("|/+|", "@(/\.)+(/|\Z(?!\n))@"), array('/', '/'), $path);

        /* ./xx -> xx */
        if ($path != './') {
            $path = preg_replace("|^(\./)+|", '', $path);
        }

        /* /../../xx -> xx */
        $path = preg_replace("|^/(\.\./?)+|", '/', $path);

        /* xx/ -> xx */
        if ($path != '/') {
            $path = preg_replace("|/\Z(?!\n)|", '', $path);
        }

        /* /xx/.. -> / */
        while (strpos($path, '/..') !== false) {
            $path = preg_replace("|/[^/]+/\.\.|", '', $path);
        }

        return empty($path) ? '/' : $path;
    }

    /**
     * Removes given elements at request shutdown.
     *
     * If called with a filename will delete that file at request shutdown; if
     * called with a directory will remove that directory and all files in that
     * directory at request shutdown.
     *
     * If called with no arguments, return all elements to be deleted (this
     * should only be done by Horde_Util::_deleteAtShutdown()).
     *
     * The first time it is called, it initializes the array and registers
     * Horde_Util::_deleteAtShutdown() as a shutdown function - no need to do
     * so manually.
     *
     * The second parameter allows the unregistering of previously registered
     * elements.
     *
     * @param string $filename   The filename to be deleted at the end of the
     *                           request.
     * @param boolean $register  If true, then register the element for
     *                           deletion, otherwise, unregister it.
     * @param boolean $secure    If deleting file, should we securely delete
     *                           the file?
     */
    static public function deleteAtShutdown($filename, $register = true,
                                            $secure = false)
    {
        /* Initialization of variables and shutdown functions. */
        if (!self::$_shutdownreg) {
            register_shutdown_function(array(__CLASS__, 'shutdown'));
            self::$_shutdownreg = true;
        }

        $ptr = &self::$_shutdowndata;
        if ($register) {
            if (@is_dir($filename)) {
                $ptr['dirs'][$filename] = true;
            } else {
                $ptr['files'][$filename] = true;
            }

            if ($secure) {
                $ptr['secure'][$filename] = true;
            }
        } else {
            unset($ptr['dirs'][$filename], $ptr['files'][$filename], $ptr['secure'][$filename]);
        }
    }

    /**
     * Deletes registered files at request shutdown.
     *
     * This function should never be called manually; it is registered as a
     * shutdown function by Horde_Util::deleteAtShutdown() and called
     * automatically at the end of the request.
     *
     * Contains code from gpg_functions.php.
     * Copyright 2002-2003 Braverock Ventures
     */
    static public function shutdown()
    {
        $ptr = &self::$_shutdowndata;

        foreach ($ptr['files'] as $file => $val) {
            /* Delete files */
            if ($val && file_exists($file)) {
                /* Should we securely delete the file by overwriting the data
                   with a random string? */
                if (isset($ptr['secure'][$file])) {
                    $filesize = filesize($file);
                    $fp = fopen($file, 'r+');
                    foreach (self::$patterns as $pattern) {
                        $pattern = substr(str_repeat($pattern, floor($filesize / strlen($pattern)) + 1), 0, $filesize);
                        fwrite($fp, $pattern);
                        fseek($fp, 0);
                    }
                    fclose($fp);
                }
                @unlink($file);
            }
        }

        foreach ($ptr['dirs'] as $dir => $val) {
            /* Delete directories */
            if ($val && file_exists($dir)) {
                /* Make sure directory is empty. */
                $dir_class = dir($dir);
                while (false !== ($entry = $dir_class->read())) {
                    if ($entry != '.' && $entry != '..') {
                        @unlink($dir . '/' . $entry);
                    }
                }
                $dir_class->close();
                @rmdir($dir);
            }
        }
    }

    /**
     * Caches the result of extension_loaded() calls.
     *
     * @param string $ext  The extension name.
     *
     * @return boolean  Is the extension loaded?
     */
    static public function extensionExists($ext)
    {
        if (!isset(self::$_cache[$ext])) {
            self::$_cache[$ext] = extension_loaded($ext);
        }

        return self::$_cache[$ext];
    }

    /**
     * Tries to load a PHP extension, behaving correctly for all operating
     * systems.
     *
     * @param string $ext  The extension to load.
     *
     * @return boolean  True if the extension is now loaded, false if not.
     *                  True can mean that the extension was already loaded,
     *                  OR was loaded dynamically.
     */
    static public function loadExtension($ext)
    {
        /* If $ext is already loaded, our work is done. */
        if (self::extensionExists($ext)) {
            return true;
        }

        /* See if we can call dl() at all, by the current ini settings. */
        if ((ini_get('enable_dl') != 1) || (ini_get('safe_mode') == 1)) {
            return false;
        }

        if (!strncasecmp(PHP_OS, 'WIN', 3)) {
            $suffix = 'dll';
        } else {
            switch (PHP_OS) {
            case 'HP-UX':
                $suffix = 'sl';
                break;

            case 'AIX':
                $suffix = 'a';
                break;

            case 'OSX':
                $suffix = 'bundle';
                break;

            default:
                $suffix = 'so';
            }
        }

        return @dl($ext . '.' . $suffix) || @dl('php_' . $ext . '.' . $suffix);
    }

    /**
     * Utility function to obtain PATH_INFO information.
     *
     * @return string  The PATH_INFO string.
     */
    static public function getPathInfo()
    {
        if (isset($_SERVER['PATH_INFO']) &&
            (strpos($_SERVER['SERVER_SOFTWARE'], 'lighttpd') === false)) {
            return $_SERVER['PATH_INFO'];
        } elseif (isset($_SERVER['REQUEST_URI']) &&
                  isset($_SERVER['SCRIPT_NAME'])) {
            if (basename($_SERVER['SCRIPT_NAME']) == 'index.php') {
                $search = array(dirname($_SERVER['SCRIPT_NAME']) . '/',
                                'index.php');
            } else {
                $search = array($_SERVER['SCRIPT_NAME']);
            }
            if (!empty($_SERVER['QUERY_STRING'])) {
                $search[] = '?' . $_SERVER['QUERY_STRING'];
            }
            return str_replace($search, '', $_SERVER['REQUEST_URI']);
        }

        return '';
    }

}
