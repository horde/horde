<?php
/**
 * This class extends the base Horde_Log_Logger class to ensure that the log
 * entries are consistently generated across the applications and framework
 * libraries.
 *
 * Copyright 2010-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @package  Core
 */
class Horde_Core_Log_Logger extends Horde_Log_Logger
{
    /**
     * Logs a message to the global Horde log backend.
     *
     * @param mixed $event     Either a string (log string), an array
     *                         (containing 'level', 'message', and 'timestamp'
     *                         entries) or an object with a getMessage()
     *                         method (e.g. PEAR_Error, Exception,
     *                         ErrorException).
     * @param mixed $priority  The priority of the message. Integers
     *                         correspond to Horde_Log constants. String
     *                         values are auto translated to Horde_Log
     *                         constants.
     * @param array $options   Additional options:
     * <pre>
     * 'file' - (string) The filename to use in the log message.
     * 'line' - (integer) The file line to use in the log message.
     * 'trace' - (integer) The trace level of the original log location.
     * </pre>
     */
    public function log($event, $priority = null, $options = array())
    {
        /* If an array is passed in, assume that the caller knew what they
         * were doing and pass it directly to the log backend. */
        if (is_array($event)) {
            return parent::log($event, constant('Horde_Log::' . $priority));
        }

        if ($event instanceof Exception) {
            if (is_null($priority)) {
                $priority = Horde_Log::ERR;
            }
            $text = $event->getMessage();
            $trace = array(
                'file' => $event->getFile(),
                'line' => $event->getLine()
            );
        } else {
            if ($event instanceof PEAR_Error) {
                if (is_null($priority)) {
                    $priority = Horde_Log::ERR;
                }
                $userinfo = $event->getUserInfo();
                $text = $event->getMessage();
                if (!empty($userinfo)) {
                    if (is_array($userinfo)) {
                        $userinfo = @implode(', ', $userinfo);
                    }
                    $text .= ': ' . $userinfo;
                }
            } elseif (is_object($event)) {
                $text = strval($event);
                if (!is_string($text)) {
                    $text = is_callable(array($event, 'getMessage'))
                        ? $event->getMessage()
                        : '';
                }
            } else {
                $text = $event;
            }

            $trace = debug_backtrace();
            if (isset($options['trace'])) {
                $frame = $options['trace'] - 1;
            } elseif (count($trace) > 1 &&
                      $trace[1]['class'] == 'Horde_Log_Logger' &&
                      $trace[1]['function'] == '__call') {
                $frame = 2;
            } else {
                $frame = 0;
            }
            $trace = $trace[$frame];
        }

        if (is_null($priority)) {
            $priority = Horde_Log::INFO;
        } elseif (is_string($priority)) {
            $priority = defined('Horde_Log::' . $priority)
                ? constant('Horde_Log::' . $priority)
                : Horde_Log::INFO;
        }

        $file = isset($options['file'])
            ? $options['file']
            : $trace['file'];
        $line = isset($options['line'])
            ? $options['line']
            : $trace['line'];

        $app = isset($GLOBALS['registry'])
            ? $GLOBALS['registry']->getApp()
            : 'horde';

        $message = (empty($GLOBALS['conf']['log']['ident']) ? '' : $GLOBALS['conf']['log']['ident'] . ' ') .
            ($app ? '[' . $app . '] ' : '') .
            $text .
            ' [pid ' . getmypid() . ' on line ' . $line . ' of "' . $file . '"]';

        /* Make sure to log in the system's locale and timezone. */
        // TODO: Needed?
        $locale = setlocale(LC_TIME, 0);
        setlocale(LC_TIME, 'C');
        $tz = getenv('TZ');
        @putenv('TZ');

        $eventob = array(
            'level' => $priority,
            'message' => $message,
        );

        if (!empty($GLOBALS['conf']['log']['time_format'])) {
            $eventob['timestamp'] = date($GLOBALS['conf']['log']['time_format']);
        }

        parent::log($eventob);

        /* If logging an exception, log the backtrace too. */
        if (($event instanceof Exception) &&
            class_exists('Horde_Support_Backtrace')) {
            parent::log((string)new Horde_Support_Backtrace($event), Horde_Log::DEBUG);
        }

        /* Restore original locale and timezone. */
        // TODO: Needed?
        setlocale(LC_TIME, $locale);
        if ($tz) {
            @putenv('TZ=' . $tz);
        }
    }

}
