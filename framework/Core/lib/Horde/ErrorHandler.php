<?php
/**
 * Horde_ErrorHandler: simple error_handler implementation for
 * handling PHP errors, generating backtraces for them, etc.
 *
 * @TODO Split dump() off into a Horde_Log backend, and make this more
 * general-purpose. Also make it configurable whether or not to honor
 * suppression of errors with @.
 *
 * @category Horde
 * @package  Core
 */
class Horde_ErrorHandler
{
    /**
     * Mapping of error codes to error code names.
     *
     * @var array
     */
    public static $errorTypes = array(
        1 => 'ERROR',
        2 => 'WARNING',
        4 => 'PARSE',
        8 => 'NOTICE',
        16 => 'CORE_ERROR',
        32 => 'CORE_WARNING',
        64 => 'COMPILE_ERROR',
        128 => 'COMPILE_WARNING',
        256 => 'USER_ERROR',
        512 => 'USER_WARNING',
        1024 => 'USER_NOTICE',
        2047 => 'ALL',
        2048 => 'STRICT',
        4096 => 'RECOVERABLE_ERROR',
    );

    /**
     * error_reporting mask
     *
     * @var integer
     */
    protected static $_mask = E_ALL;

    /**
     * Array of errors that have been caught.
     *
     * @var array
     */
    protected static $_errors = array();

    /**
     * Configurable function to run on shutdown.
     *
     * @var callable
     */
    protected static $_shutdownFunc;

    /**
     * Set the error handler and shutdown functions.
     *
     * @param TODO
     */
    public static function register($shutdownFunc = null)
    {
        set_error_handler(array(__CLASS__, 'handleError'));

        if (is_null($shutdownFunc)) {
            $shutdownFunc = array(__CLASS__, 'dump');
        }

        self::$_shutdownFunc = $shutdownFunc;
    }

    /**
     * Call the shutdown func, passing in accumulated errors.
     */
    public function __destruct()
    {
        if (self::$_errors) {
            call_user_func(self::$_shutdownFunc, self::$_errors);
        }
    }

    /**
     * Process and handle/store an error.
     *
     * @param integer $errno    TODO
     * @param string $errstr    TODO
     * @param string $errfile   TODO
     * @param integer $errline  TODO
     */
    public static function handleError($errno, $errstr, $errfile, $errline)
    {
        // Was the error suppressed?
        if (!error_reporting()) {
            // @TODO
            // ...
        }

        // Check the mask.
        if ($errno & self::$_mask) {
            self::$_errors[] = array(
                'no' => $errno,
                'str' => self::_cleanErrorString($errstr),
                'file' => $errfile,
                'line' => $errline,
                'trace' => self::_errorBacktrace(),
            );
        }
    }

    /**
     * Include the context of the error in the debug
     * information. Takes more (and could be much more) memory.
     *
     * @param integer $errno    TODO
     * @param string $errstr    TODO
     * @param string $errfile   TODO
     * @param integer $errline  TODO
     * @param TODO $errcontext  TODO
     */
    public static function handleErrorWithContext($errno, $errstr, $errfile,
                                                  $errline, $errcontext)
    {
        self::$_errors[] = array(
            'no' => $errno,
            'str' => self::_cleanErrorString($errstr),
            'file' => $errfile,
            'line' => $errline,
            'context' => $errcontext,
            'trace' => self::_errorBacktrace(),
        );
    }

    /**
     * Remove function documentation links from an error string.
     *
     * @param string $errstr  TODO
     *
     * @return string  TODO
     */
    protected static function _cleanErrorString($errstr)
    {
        return preg_replace("%\s\[<a href='function\.[\d\w-_]+'>function\.[\d\w-_]+</a>\]%", '', $errstr);
    }

    /**
     * Generate an exception-like backtrace from the debug_backtrace()
     * function for errors.
     *
     * @return array  TODO
     */
    protected static function _errorBacktrace()
    {
        // Skip two levels of backtrace
        $skip = 2;

        $backtrace = debug_backtrace();
        $trace = array();
        for ($i = $skip, $i_max = count($backtrace); $i < $i_max; $i++) {
            $frame = $backtrace[$i];
            $trace[$i - $skip] = array(
                'file' => isset($frame['file']) ? $frame['file'] : null,
                'line' => isset($frame['line']) ? $frame['line'] : null,
                'function' => isset($frame['function']) ? $frame['function'] : null,
                'class' => isset($frame['class']) ? $frame['class'] : null,
                'type' => isset($frame['type']) ? $frame['type'] : null,
                'args' => isset($frame['args']) ? $frame['args'] : null,
            );
        }

        return $trace;
    }

    /**
     * On text/html pages, if the user is an administrator, show all
     * errors that occurred during the request.
     *
     * @param array $errors  Accumulated errors.
     */
    public static function dump($errors)
    {
        if (!$GLOBALS['registry']->isAdmin()) {
            return;
        }

        $dump = false;
        foreach (headers_list() as $header) {
            if (strpos($header, 'Content-type: text/html') !== false) {
                $dump = true;
                break;
            }
        }

        if ($dump) {
            foreach ($errors as $error) {
                echo '<p>' . htmlspecialchars($error['file']) . ':' . htmlspecialchars($error['line']) . ': ' . htmlspecialchars($error['str']) . '</p>';
            }
        }
    }

}
