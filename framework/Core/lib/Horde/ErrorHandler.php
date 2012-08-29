<?php
/**
 * Provides methods used to handle error reporting.
 *
 * Copyright 2012 Horde LLC (http://www.horde.org/)
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
    static public function fatal($error)
    {
        global $registry;

        try {
            Horde::logMessage($error, 'EMERG');
        } catch (Exception $e) {}

        if (is_object($error)) {
            switch (get_class($error)) {
            case 'Horde_Exception_AuthenticationFailure':
                if ($registry->isAuthenticated(array('app' => $error->application, 'notransparent' => true)) &&
                    $registry->clearAuthApp($error->applicaton)) {
                    break;
                }

                if (Horde_Cli::runningFromCLI()) {
                    $cli = new Horde_Cli();
                    $cli->fatal($error);
                }

                $params = array(
                    'app' => $error->application,
                    'reason' => $error->getCode()
                );

                switch ($error->getCode()) {
                case Horde_Auth::REASON_MESSAGE:
                    $params['msg'] = $error->getMessage();
                    break;
                }

                header('Location: ' . $registry->getLogoutUrl($params));
                exit;
            }
        }

        header('Content-type: text/html; charset=UTF-8');
        try {
            $admin = $registry->isAdmin();
            $cli = Horde_Cli::runningFromCLI();

            $errortext = '<h1>' . Horde_Core_Translation::t("A fatal error has occurred") . '</h1>';

            if (($error instanceof PEAR_Error) ||
                (is_object($error) && method_exists($error, 'getMessage'))) {
                $errortext .= '<h3>' . htmlspecialchars($error->getMessage()) . '</h3>';
            } elseif (is_string($error)) {
                $errortext .= '<h3>' . htmlspecialchars($error) . '</h3>';
            }

            if ($admin || $cli) {
                $trace = ($error instanceof Exception)
                    ? $error
                    : debug_backtrace();
                $errortext .= '<div id="backtrace"><pre>' .
                    strval(new Horde_Support_Backtrace($trace)) .
                    '</pre></div>';
                if (is_object($error)) {
                    $errortext .= '<h3>' . Horde_Core_Translation::t("Details") . '</h3>';
                    $errortext .= '<h4>' . Horde_Core_Translation::t("The full error message is logged in Horde's log file, and is shown below only to administrators. Non-administrative users will not see error details.") . '</h4>';
                    $errortext .= '<div id="details"><pre>' . htmlspecialchars(print_r($error, true)) . '</pre></div>';
                }
            } else {
                $errortext .= '<h3>' . Horde_Core_Translation::t("Details have been logged for the administrator.") . '</h3>';
            }
        } catch (Exception $e) {
            die($e);
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
     * PHP legacy error handling (non-Exceptions).
     *
     * @param integer $errno     See set_error_handler().
     * @param string $errstr     See set_error_handler().
     * @param string $errfile    See set_error_handler().
     * @param integer $errline   See set_error_handler().
     * @param array $errcontext  See set_error_handler().
     */
    static public function errorHandler($errno, $errstr, $errfile, $errline,
                                        $errcontext)
    {
        // Calls prefixed with '@'.
        if (error_reporting() == 0) {
            // Must return false to populate $php_errormsg (as of PHP 5.2).
            return false;
        }

        if (!class_exists('Horde_Log')) {
            return;
        }

        try {
            switch ($errno) {
            case E_WARNING:
                $priority = Horde_Log::WARN;
                break;

            case E_NOTICE:
                $priority = Horde_Log::NOTICE;
                break;

            default:
                $priority = Horde_Log::DEBUG;
                break;
            }

            Horde::logMessage(new ErrorException('PHP ERROR: ' . $errstr, 0, $errno, $errfile, $errline), $priority);
        } catch (Exception $e) {}
    }

}
