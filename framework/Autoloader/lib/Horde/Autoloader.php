<?php
/**
 * Horde Autoloader.
 *
 * @category Horde
 * @package  Horde_Autoloader
 * @license  http://www.gnu.org/copyleft/lesser.html
 */
class Horde_Autoloader
{
    /**
     * Patterns that match classes we can load.
     *
     * @var array
     */
    protected static $_classPatterns = array();

    /**
     * The include path cache.
     *
     * @var array
     */
    protected static $_includeCache = null;

    /**
     * Callbacks.
     *
     * @var array
     */
    protected static $_callbacks = array();

    /**
     * Autoload implementation automatically registered with
     * spl_autoload_register.
     *
     * We ignore E_WARNINGS when trying to include files so that if our
     * autoloader doesn't find a file, we pass on to the next autoloader (if
     * any) or to the PHP class not found error. We don't want to suppress all
     * errors, though, or else we'll end up silencing parse errors or
     * redefined class name errors, making debugging especially difficult.
     *
     * @param string $class  Class name to load (or interface).
     */
    public static function loadClass($class)
    {
        /* Search in class patterns first. */
        foreach (self::$_classPatterns as $classPattern) {
            list($pattern, $replace) = $classPattern;

            if (!is_null($replace) &&
                preg_match($pattern, $class, $matches, PREG_OFFSET_CAPTURE)) {
                if (strcasecmp($matches[0][0], $class) === 0) {
                    $relativePath = $replace . '/' . $class;
                } else {
                    $relativePath = str_replace(array('\\', '_'), '/', substr($class, 0, $matches[0][1])) .
                        $replace .
                        str_replace(array('\\', '_'), '/', substr($class, $matches[0][1] + strlen($matches[0][0])));
                }

                if (self::_loadClass($class, $relativePath)) {
                    return true;
                }
            }
        }

        /* Do a final search in the include path. */
        $relativePath = str_replace(array('\\', '_'), '/', $class);
        return self::_loadClass($class, $relativePath);
    }

    /**
     * Try to load the class from each path in the include_path.
     *
     * @param string $class         The loaded classname.
     * @param string $relativePath  TODO
     */
    protected static function _loadClass($class, $relativePath)
    {
        $result = false;

        if (substr($relativePath, 0, 1) == '/') {
            $result = self::_loadClassUsingAbsolutePath($relativePath);
        } else {
            if (is_null(self::$_includeCache)) {
                self::_cacheIncludePath();
            }

            foreach (self::$_includeCache as $includePath) {
                if (self::_loadClassUsingAbsolutePath($includePath . DIRECTORY_SEPARATOR . $relativePath)) {
                    $result = true;
                    break;
                }
            }
        }

        if (!$result) {
            return false;
        }

        $class = strtolower($class);
        if (isset(self::$_callbacks[$class])) {
            call_user_func(self::$_callbacks[$class]);
        }

        return true;
    }

    /**
     * Include a PHP file using an absolute path, suppressing E_WARNING and
     * E_DEPRECATED errors, but letting fatal/parse errors come through.
     *
     * @param string $absolutePath  TODO
     *
     * @TODO Remove E_DEPRECATED masking
     */
    protected static function _loadClassUsingAbsolutePath($absolutePath)
    {
        $err_mask = E_ALL ^ E_WARNING;
        if (defined('E_DEPRECATED')) {
            $err_mask = $err_mask ^ E_DEPRECATED;
        }
        $oldErrorReporting = error_reporting($err_mask);
        $included = include $absolutePath . '.php';
        error_reporting($oldErrorReporting);
        return $included;
    }

    /**
     * Add a new path to the include_path we're loading from.
     *
     * @param string $path      The directory to add.
     * @param boolean $prepend  Add to the beginning of the stack?
     *
     * @return string  The new include_path.
     */
    public static function addClassPath($path, $prepend = true)
    {
        if ($path == '.') {
            throw new Exception('"." is not allowed in the include_path. Use an absolute path instead.');
        }
        $path = realpath($path);

        if (is_null(self::$_includeCache)) {
            self::_cacheIncludePath();
        }

        if (in_array($path, self::$_includeCache)) {
            // The path is already present in our stack; don't re-add it.
            return implode(PATH_SEPARATOR, self::$_includeCache);
        }

        if ($prepend) {
            array_unshift(self::$_includeCache, $path);
        } else {
            self::$_includeCache[] = $path;
        }

        $include_path = implode(PATH_SEPARATOR, self::$_includeCache);
        set_include_path($include_path);

        return $include_path;
    }

    /**
     * Add a new class pattern.
     *
     * @param string $pattern  The class pattern to add.
     * @param string $replace  The substitution pattern. All '_' and '\'
     *                         strings in a classname will be converted to
     *                         directory separators.  If the entire pattern
     *                         is matched, the matched text will be appended
     *                         to the replacement string (allows for a single
     *                         base class file to live within the include
     *                         directory).
     */
    public static function addClassPattern($pattern, $replace = null)
    {
        if (strlen($replace)) {
            $replace = rtrim($replace, '/') . '/';
        }
        self::$_classPatterns[] = array($pattern, $replace);
    }

    /**
     * Add a callback to run when a class is loaded through loadClass().
     *
     * @param string $class    The classname.
     * @param mixed $callback  The callback to run when the class is loaded.
     */
    public static function addCallback($class, $callback)
    {
        self::$_callbacks[strtolower($class)] = $callback;
    }

    /**
     * TODO
     */
    protected static function _cacheIncludePath()
    {
        self::$_includeCache = array();
        foreach (explode(PATH_SEPARATOR, get_include_path()) as $path) {
            if ($path == '.') { continue; }
            $path = realpath($path);
            if ($path) {
                self::$_includeCache[] = $path;
            }
        }
    }

}

/* Register the autoloader in a way to play well with as many configurations
 * as possible. */
if (function_exists('spl_autoload_register')) {
    spl_autoload_register(array('Horde_Autoloader', 'loadClass'));
    if (function_exists('__autoload')) {
        spl_autoload_register('__autoload');
    }
} elseif (!function_exists('__autoload')) {
    function __autoload($class)
    {
        return Horde_Autoloader::loadClass($class);
    }
}
