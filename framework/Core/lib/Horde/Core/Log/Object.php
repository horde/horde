<?php
/**
 * Copyright 2013-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category  Horde
 * @copyright 2013-2015 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL-2.1
 * @package   Core
 */

/**
 * A loggable event, with the display controlled by Horde configuration.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2013-2015 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL-2.1
 * @package   Core
 * @since     2.5.0
 *
 * @property-read Horde_Support_Backtrace $backtrace  Backtrace object.
 * @property boolean $logged  Has this object been logged?
 * @property string $message  Log message.
 * @property integer $priority  Log priority.
 * @property integer $timestamp  Log timestamp.
 */
class Horde_Core_Log_Object
{
    /**
     * Exception backtrace.
     *
     * @var Horde_Support_Backtrace
     */
    protected $_backtrace;

    /**
     * Original exception.
     *
     * @var Exception
     */
    protected $_exception;

    /**
     * Has this object been logged?
     *
     * @var boolean
     */
    protected $_logged = false;

    /**
     * Log message.
     *
     * @var string
     */
    protected $_message = '';

    /**
     * Log priority.
     *
     * @var integer
     */
    protected $_priority = Horde_Log::INFO;

    /**
     * Log timestamp.
     *
     * @var integer
     */
    protected $_timestamp;

    /**
     * Constructor.
     *
     * @param mixed $event     Either a string (log string), an array
     *                         (containing 'level', 'message', and 'timestamp'
     *                         entries) or an object with a getMessage()
     *                         method (e.g. PEAR_Error, Exception).
     * @param mixed $priority  The priority of the message. Integers
     *                         correspond to Horde_Log constants. String
     *                         values are auto translated to Horde_Log
     *                         constants.
     * @param array $options   Additional options:
     *   - file: (string) The filename to use in the log message.
     *   - line: (integer) The file line to use in the log message.
     *   - notracelog: (boolean) If true, don't output backtrace.
     *   - trace: (integer) The trace level of the original log location.
     */
    public function __construct($event, $priority = null,
                                array $options = array())
    {
        $text = null;
        $timestamp = time();

        if (is_array($event)) {
            if (isset($event['level'])) {
                $priority = $event['level'];
            }

            if (isset($event['message'])) {
                $this->message  = $event['message'];
            }

            if (isset($event['timestamp'])) {
                $timestamp = $event['timestamp'];
            }
        } elseif ($event instanceof Exception) {
            $this->_exception = $event;

            if (is_null($priority)) {
                $priority = Horde_Log::ERR;
            }

            if ($event instanceof Horde_Exception) {
                $this->_logged = $event->logged;
                if ($loglevel = $event->getLogLevel()) {
                    $priority = $loglevel;
                }
            }

            $text = $event->getMessage();
            if (!empty($event->details)) {
                $text .= ' ' . $event->details;
            }
            $trace = array(
                'file' => $event->getFile(),
                'line' => $event->getLine()
            );

            if (empty($options['notracelog']) &&
                class_exists('Horde_Support_Backtrace')) {
                $this->_backtrace = new Horde_Support_Backtrace($event);
            }
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
            $trace_count = count($trace);
            $frame = isset($options['trace'])
                ? min($trace_count, $options['trace'])
                : 0;
            while ($frame < $trace_count) {
                if (isset($trace[$frame]['class'])) {
                    if (!in_array($trace[$frame]['class'], array('Horde_Log_Logger', 'Horde_Core_Log_Logger'))) {
                        break;
                    }
                } elseif (isset($trace[$frame]['function']) &&
                          !in_array($trace[$frame]['function'], array('call_user_func', 'call_user_func_array'))) {
                    break;
                }
                ++$frame;
            }
            $trace = $trace[$frame];
        }

        if (!is_null($priority)) {
            $this->priority = $priority;
        }

        if (!is_null($text)) {
            $app = isset($GLOBALS['registry'])
                ? $GLOBALS['registry']->getApp()
                : 'horde';

            $this->_message = ($app ? '[' . $app . '] ' : '') .
                $text .
                ' [pid ' . getmypid();

            if (isset($options['file']) || isset($trace['file'])) {
                $file = isset($options['file'])
                    ? $options['file']
                    : $trace['file'];
                $line = isset($options['line'])
                    ? $options['line']
                    : $trace['line'];

                $this->_message .= ' on line ' . $line . ' of "' . $file . '"]';
            } else {
                $this->_message .= ']';
            }
        }

        $this->_timestamp = $timestamp;
    }

    /**
     */
    public function __get($name)
    {
        switch ($name) {
        case 'backtrace':
        case 'logged':
        case 'message':
        case 'priority':
        case 'timestamp':
            $varname = '_' . $name;
            return $this->$varname;
        }
    }

    /**
     */
    public function __set($name, $value)
    {
        switch ($name) {
        case 'logged':
            if ($value && $this->_exception) {
                $this->_exception->logged = true;
            }
            $this->_logged = $value;
            break;

        case 'message':
            $this->_message = strval($value);
            break;

        case 'priority':
            if (is_integer($value)) {
                $this->_priority = $value;
            } elseif (defined('Horde_Log::' . $value)) {
                $this->_priority = constant('Horde_Log::' . $value);
            }
            break;

        case 'timestamp':
            $this->_timestamp = intval($value);
            break;
        }
    }

    /**
     * Formats the object for use with Horde_Log_Logger#out().
     *
     * @return array  Array containting log output information.
     */
    public function toArray()
    {
        global $conf;

        $out = array(
            'level' => $this->priority,
            'message' => $this->message
        );

        if (!empty($conf['log']['time_format'])) {
            $out['timestamp'] = date($conf['log']['time_format'], $this->timestamp);
        }

        return $out;
    }

}
