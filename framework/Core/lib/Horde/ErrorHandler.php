<?php
/**
 * Provides methods used to handle error reporting.
 *
 * Copyright 2012-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL-2.1
 * @package  Core
 */
class Horde_ErrorHandler
{
    /**
     * Aborts with a fatal error, displaying debug information to the user.
     *
     * @param mixed $error  Either a string or an object with a getMessage()
     *                      method (e.g. PEAR_Error, Exception).
     */
    public static function fatal($error)
    {
        global $registry;

        if (is_object($error)) {
            switch (get_class($error)) {
            case 'Horde_Exception_AuthenticationFailure':
                $auth_app = !$registry->clearAuthApp($error->application);

                if ($auth_app &&
                    $registry->isAuthenticated(array('app' => $error->application, 'notransparent' => true))) {
                    break;
                }

                try {
                    Horde::log($error, 'NOTICE');
                } catch (Exception $e) {}

                if (Horde_Cli::runningFromCLI()) {
                    $cli = new Horde_Cli();
                    $cli->fatal($error);
                }

                $params = array();

                if ($registry->getAuth()) {
                    $params['app'] = $error->application;
                }

                switch ($error->getCode()) {
                case Horde_Auth::REASON_MESSAGE:
                    $params['msg'] = $error->getMessage();
                    $params['reason'] = $error->getCode();
                    break;
                }

                $logout_url = $registry->getLogoutUrl($params);

                /* Clear authentication here. Otherwise, there might be
                 * issues on the login page since we would otherwise need
                 * to do session token checking (which might not be
                 * available, so logout won't happen, etc...) */
                if ($auth_app && array_key_exists('app', $params)) {
                    $registry->clearAuth();
                }

                $logout_url->redirect();
            }
        }

        try {
            Horde::log($error, 'EMERG');
        } catch (Exception $e) {}

        try {
            $cli = Horde_Cli::runningFromCLI();
        } catch (Exception $e) {
            die($e);
        }

        if ($cli) {
            $cli = new Horde_Cli();
            $cli->fatal($error);
        }

        if (!headers_sent()) {
            header('Content-type: text/html; charset=UTF-8');
        }
        echo <<< HTML
<html>
<head><title>Horde :: Fatal Error</title></head>
<body style="background:#fff; color:#000">
HTML;

        ob_start();
        try {
            $admin = (isset($registry) && $registry->isAdmin());

            echo '<h1>' . Horde_Core_Translation::t("A fatal error has occurred") . '</h1>';

            if (is_object($error) && method_exists($error, 'getMessage')) {
                echo '<h3>' . htmlspecialchars($error->getMessage()) . '</h3>';
            } elseif (is_string($error)) {
                echo '<h3>' . htmlspecialchars($error) . '</h3>';
            }

            if ($admin) {
                $trace = ($error instanceof Exception)
                    ? $error
                    : debug_backtrace();
                echo '<div id="backtrace"><pre>' .
                    strval(new Horde_Support_Backtrace($trace)) .
                    '</pre></div>';
                if (is_object($error)) {
                    echo '<h3>' . Horde_Core_Translation::t("Details") . '</h3>';
                    echo '<h4>' . Horde_Core_Translation::t("The full error message is logged in Horde's log file, and is shown below only to administrators. Non-administrative users will not see error details.") . '</h4>';
                    ob_flush();
                    flush();
                    //echo '<div id="details"><pre>' . htmlspecialchars(print_r($error, true)) . '</pre></div>';
                }
            } else {
                echo '<h3>' . Horde_Core_Translation::t("Details have been logged for the administrator.") . '</h3>';
            }
        } catch (Exception $e) {
            die($e);
        }

        ob_end_flush();
        echo '</body></html>';
        exit(1);
    }

    /**
     * PHP legacy error handling (non-Exceptions).
     *
     * @param integer $errno     See set_error_handler().
     * @param string $errstr     See set_error_handler().
     * @param string $errfile    See set_error_handler().
     * @param integer $errline   See set_error_handler().
     * @param array $errcontext  See set_error_handler().
     */
    public static function errorHandler($errno, $errstr, $errfile, $errline,
                                        $errcontext)
    {
        $er = error_reporting();

        // Calls prefixed with '@'.
        if ($er == 0) {
            // Must return false to populate $php_errormsg (as of PHP 5.2).
            return false;
        }

        if (!($er & $errno) || !class_exists('Horde_Log')) {
            return;
        }

        $options = array();

        try {
            switch ($errno) {
            case E_WARNING:
            case E_USER_WARNING:
            case E_RECOVERABLE_ERROR:
                $priority = Horde_Log::WARN;
                break;

            case E_NOTICE:
            case E_USER_NOTICE:
                $priority = Horde_Log::NOTICE;
                break;

            case E_STRICT:
                $options['notracelog'] = true;
                $priority = Horde_Log::DEBUG;
                break;

            default:
                $priority = Horde_Log::DEBUG;
                break;
            }

            Horde::log(new ErrorException('PHP ERROR: ' . $errstr, 0, $errno, $errfile, $errline), $priority, $options);
        } catch (Exception $e) {}
    }

    /**
     * Catch fatal errors.
     */
    public static function catchFatalError()
    {
        $error = error_get_last();
        if ($error['type'] == E_ERROR) {
            self::fatal(new ErrorException(
                $error['message'],
                0,
                $error['type'],
                $error['file'],
                $error['line']
            ));
        }
    }

}
