<?php
/**
 * Copyright 2007-2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author    Jan Schneider <jan@horde.org>
 * @category  Horde
 * @license   http://www.horde.org/licenses/lgpl21 LGPL-2.1
 * @package   Alarm
 */

/**
 * The Horde_Alarm class provides an interface to deal with reminders, alarms
 * and notifications through a standardized API.
 *
 * @author    Jan Schneider <jan@horde.org>
 * @category  Horde
 * @copyright 2007-2017 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL-2.1
 * @package   Alarm
 */
abstract class Horde_Alarm
{
    /**
     * Logger.
     *
     * @var Horde_Log_Logger
     */
    protected $_logger;

    /**
     * Alarm loader callback.
     *
     * @var mixed
     */
    protected $_loader;

    /**
     * Hash containing connection parameters.
     *
     * @var array
     */
    protected $_params = array(
        'ttl' => 300
    );

    /**
     * All registered notification handlers.
     *
     * @var array
     */
    protected $_handlers = array();

    /**
     * Whether handler classes have been dynamically loaded already.
     *
     * @var boolean
     */
    protected $_handlersLoaded = false;

    /**
     * A list of errors, exceptions etc. that occured during notify() calls.
     *
     * @var array
     */
    protected $_errors = array();

    /**
     * Constructor.
     *
     * @param array $params  Configuration parameters:
     * <pre>
     * 'logger' - (Horde_Log_Logger) A logger instance.
     * 'ttl' - (integer) Time to live value, in seconds.
     * </pre>
     */
    public function __construct(array $params = array())
    {
        if (isset($params['logger'])) {
            $this->_logger = $params['logger'];
            unset($params['logger']);
        }
        if (isset($params['loader'])) {
            $this->_loader = $params['loader'];
            unset($params['loader']);
        }
        $this->_params = array_merge($this->_params, $params);
    }

    /**
     * Returns a list of alarms from the backend.
     *
     * @param string $user      Return alarms for this user, all users if
     *                          null, or global alarms if empty.
     * @param Horde_Date $time  The time when the alarms should be active.
     *                          Defaults to now.
     * @param boolean $load     Update active alarms from all applications?
     * @param boolean $preload  Preload alarms that go off within the next
     *                          ttl time span?
     *
     * @return array  A list of alarm hashes.
     * @throws Horde_Alarm_Exception
     */
    public function listAlarms($user = null, Horde_Date $time = null,
                               $load = false, $preload = true)
    {
        if (empty($time)) {
            $time = new Horde_Date(time());
        }
        if ($load && is_callable($this->_loader)) {
            call_user_func($this->_loader, $user, $preload);
        }

        $alarms = $this->_list($user, $time);

        foreach (array_keys($alarms) as $alarm) {
            if (isset($alarms[$alarm]['mail']['body'])) {
                $alarms[$alarm]['mail']['body'] = $this->_fromDriver($alarms[$alarm]['mail']['body']);
            }
        }
        return $alarms;
    }

    /**
     * Returns a list of alarms from the backend.
     *
     * @param Horde_Date $time  The time when the alarms should be active.
     * @param string $user      Return alarms for this user, all users if
     *                          null, or global alarms if empty.
     *
     * @return array  A list of alarm hashes.
     * @throws Horde_Alarm_Exception
     */
    abstract protected function _list($user, Horde_Date $time);

    /**
     * Returns a list of all global alarms from the backend.
     *
     * @return array  A list of alarm hashes.
     * @throws Horde_Alarm_Exception
     */
    public function globalAlarms()
    {
        $alarms = $this->_global();
        foreach (array_keys($alarms) as $alarm) {
            if (isset($alarms[$alarm]['mail']['body'])) {
                $alarms[$alarm]['mail']['body'] = $this->_fromDriver($alarms[$alarm]['mail']['body']);
            }
        }
        return $alarms;
    }

    /**
     * Returns a list of all global alarms from the backend.
     *
     * @return array  A list of alarm hashes.
     */
    abstract protected function _global();

    /**
     * Returns an alarm hash from the backend.
     *
     * @param string $id    The alarm's unique id.
     * @param string $user  The alarm's user
     *
     * @return array  An alarm hash. Contains the following:
     * <pre>
     * id: Unique alarm id.
     * user: The alarm's user. Empty if a global alarm.
     * start: The alarm start as a Horde_Date.
     * end: The alarm end as a Horde_Date.
     * methods: The notification methods for this alarm.
     * params: The paramters for the notification methods.
     * title: The alarm title.
     * text: An optional alarm description.
     * snooze: The snooze time (next time) of the alarm as a Horde_Date.
     * internal: Holds internally used data.
     * instanceid: Holds an instance identifier for recurring alarms.
     *             (@since 2.2.0)
     * </pre>
     * @throws Horde_Alarm_Exception
     */
    public function get($id, $user)
    {
        $alarm = $this->_get($id, $user);

        if (isset($alarm['mail']['body'])) {
            $alarm['mail']['body'] = $this->_fromDriver($alarm['mail']['body']);
        }

        return $alarm;
    }

    /**
     * Returns an alarm hash from the backend.
     *
     * @param string $id    The alarm's unique id.
     * @param string $user  The alarm's user
     *
     * @return array  An alarm hash.
     * @throws Horde_Alarm_Exception
     */
    abstract protected function _get($id, $user);

    /**
     * Stores an alarm hash in the backend.
     *
     * The alarm will be added if it doesn't exist, and updated otherwise.
     *
     * @param array $alarm   An alarm hash. See self::get() for format.
     * @param boolean $keep  Whether to keep the snooze value and notification
     *                       status unchanged. If true, the alarm will get
     *                       "un-snoozed", and notifications (like mails) are
     *                       sent again.
     *
     * @throws Horde_Alarm_Exception
     */
    public function set(array $alarm, $keep = false)
    {
        if (isset($alarm['mail']['body'])) {
            $alarm['mail']['body'] = $this->_toDriver($alarm['mail']['body']);
        }

        // If this is a recurring alarm and we have a new instanceid,
        // remove the previous entry regardless of the value of $keep.
        // Otherwise, the alarm will never be reset. @since 2.2.0
        if (!empty($alarm['instanceid']) &&
            !$this->exists($alarm['id'], isset($alarm['user']) ? $alarm['user'] : '', !empty($alarm['instanceid']) ? $alarm['instanceid'] : null)) {
            $this->delete($alarm['id'], isset($alarm['user']) ? $alarm['user'] : '');
        }

        if ($this->exists($alarm['id'], isset($alarm['user']) ? $alarm['user'] : '')) {
            $this->_update($alarm, $keep);
            if (!$keep) {
                foreach ($this->_handlers as &$handler) {
                    $handler->reset($alarm);
                }
            }
        } else {
            $this->_add($alarm);
        }
    }

    /**
     * Adds an alarm hash to the backend.
     *
     * @param array $alarm  An alarm hash.
     *
     * @throws Horde_Alarm_Exception
     */
    abstract protected function _add(array $alarm);

    /**
     * Updates an alarm hash in the backend.
     *
     * @param array $alarm         An alarm hash.
     * @param boolean $keepsnooze  Whether to keep the snooze value unchanged.
     *
     * @throws Horde_Alarm_Exception
     */
    abstract protected function _update(array $alarm, $keepsnooze = false);

    /**
     * Updates internal alarm properties, i.e. properties not determined by
     * the application setting the alarm.
     *
     * @param string $id       The alarm's unique id.
     * @param string $user     The alarm's user
     * @param array $internal  A hash with the internal data.
     *
     * @throws Horde_Alarm_Exception
     */
    abstract public function internal($id, $user, array $internal);

    /**
     * Returns whether an alarm with the given id exists already.
     *
     * @param string $id          The alarm's unique id.
     * @param string $user        The alarm's user
     * @param string $instanceid  An optional instanceid to check for.
     *                            @since 2.2.0
     *
     * @return boolean  True if the specified alarm exists.
     */
    public function exists($id, $user, $instanceid = null)
    {
        try {
            return $this->_exists($id, $user, $instanceid);
        } catch (Horde_Alarm_Exception $e) {
            return false;
        }
    }

    /**
     * Returns whether an alarm with the given id exists already.
     *
     * @param string $id          The alarm's unique id.
     * @param string $user        The alarm's user
     * @param string $instanceid  An optional instanceid to match.
     *
     * @return boolean  True if the specified alarm exists.
     * @throws Horde_Alarm_Exception
     */
    abstract protected function _exists($id, $user, $instanceid = null);

    /**
     * Delays (snoozes) an alarm for a certain period.
     *
     * @param string $id        The alarm's unique id.
     * @param string $user      The notified user.
     * @param integer $minutes  The delay in minutes. A negative value
     *                          dismisses the alarm completely.
     *
     * @throws Horde_Alarm_Exception
     */
    public function snooze($id, $user, $minutes)
    {
        if (empty($user)) {
            throw new Horde_Alarm_Exception('This alarm cannot be snoozed.');
        }

        $alarm = $this->get($id, $user);

        if ($alarm) {
            if ($minutes > 0) {
                $alarm['snooze'] = new Horde_Date(time());
                $alarm['snooze']->min += $minutes;
                $this->_snooze($id, $user, $alarm['snooze']);
                return;
            }

            $this->_dismiss($id, $user);
        }
    }

    /**
     * Delays (snoozes) an alarm for a certain period.
     *
     * @param string $id          The alarm's unique id.
     * @param string $user        The alarm's user
     * @param Horde_Date $snooze  The snooze time.
     *
     * @throws Horde_Alarm_Exception
     */
    abstract protected function _snooze($id, $user, Horde_Date $snooze);

    /**
     * Returns whether an alarm is snoozed.
     *
     * @param string $id        The alarm's unique id.
     * @param string $user      The alarm's user
     * @param Horde_Date $time  The time when the alarm may be snoozed.
     *                          Defaults to now.
     *
     * @return boolean  True if the alarm is snoozed.
     *
     * @throws Horde_Alarm_Exception
     */
    public function isSnoozed($id, $user, Horde_Date $time = null)
    {
        if (is_null($time)) {
            $time = new Horde_Date(time());
        }
        return (bool)$this->_isSnoozed($id, $user, $time);
    }

    /**
     * Returns whether an alarm is snoozed.
     *
     * @param string $id        The alarm's unique id.
     * @param string $user      The alarm's user
     * @param Horde_Date $time  The time when the alarm may be snoozed.
     *
     * @return boolean  True if the alarm is snoozed.
     * @throws Horde_Alarm_Exception
     */
    abstract protected function _isSnoozed($id, $user, Horde_Date $time);

    /**
     * Dismisses an alarm.
     *
     * @param string $id          The alarm's unique id.
     * @param string $user        The alarm's user
     *
     * @throws Horde_Alarm_Exception
     */
    abstract protected function _dismiss($id, $user);

    /**
     * Deletes an alarm from the backend.
     *
     * @param string $id    The alarm's unique id.
     * @param string $user  The alarm's user. All users' alarms if null.
     *
     * @throws Horde_Alarm_Exception
     */
    function delete($id, $user = null)
    {
        $this->_delete($id, $user);
    }

    /**
     * Deletes an alarm from the backend.
     *
     * @param string $id    The alarm's unique id.
     * @param string $user  The alarm's user. All users' alarms if null.
     *
     * @throws Horde_Alarm_Exception
     */
    abstract protected function _delete($id, $user = null);

    /**
     * Notifies the user about any active alarms.
     *
     * @param string $user      Notify this user, all users if null, or guest
     *                          users if empty.
     * @param boolean $load     Update active alarms from all applications?
     * @param boolean $preload  Preload alarms that go off within the next
     *                          ttl time span?
     * @param array $exclude    Don't notify with these methods.
     *
     * @throws Horde_Alarm_Exception if loading of alarms fails, but not if
     *                               notifying of individual alarms fails.
     */
    public function notify($user = null, $load = true, $preload = true,
                           array $exclude = array())
    {
        try {
            $alarms = $this->listAlarms($user, null, $load, $preload);
        } catch (Horde_Alarm_Exception $e) {
            if ($this->_logger) {
                $this->_logger->log($e, 'ERR');
            }
            throw $e;
        }

        if (empty($alarms)) {
            return;
        }

        $handlers = $this->handlers();
        foreach ($alarms as $alarm) {
            foreach ($alarm['methods'] as $alarm_method) {
                if (isset($handlers[$alarm_method]) &&
                    !in_array($alarm_method, $exclude)) {
                    try {
                        $handlers[$alarm_method]->notify($alarm);
                    } catch (Horde_Alarm_Exception $e) {
                        $this->_errors[] = $e;
                    }
                }
            }
        }
    }

    /**
     * Registers a notification handler.
     *
     * @param string $name                  A handler name.
     * @param Horde_Alarm_Handler $handler  A notification handler.
     */
    public function addHandler($name, Horde_Alarm_Handler $handler)
    {
        $this->_handlers[$name] = $handler;
        $handler->alarm = $this;
    }

    /**
     * Returns a list of available notification handlers and parameters.
     *
     * The returned list is a hash with method names as the keys and
     * optionally associated parameters as values. The parameters are hashes
     * again with parameter names as keys and parameter information as
     * values. The parameter information is hash with the following keys:
     * 'desc' contains a parameter description; 'required' specifies whether
     * this parameter is required.
     *
     * @return array  List of methods and parameters.
     */
    public function handlers()
    {
        if (!$this->_handlersLoaded) {
            foreach (new DirectoryIterator(__DIR__ . '/Alarm/Handler') as $file) {
                if (!$file->isFile() || substr($file->getFilename(), -4) != '.php') {
                    continue;
                }
                $handler = Horde_String::lower($file->getBasename('.php'));
                if (isset($this->_handlers[$handler])) {
                    continue;
                }
                require_once $file->getPathname();
                $class = 'Horde_Alarm_Handler_' . $file->getBasename('.php');
                if (class_exists($class, false)) {
                    $this->addHandler($handler, new $class());
                }
            }
            $this->_handlerLoaded = true;
        }

        return $this->_handlers;
    }

    /**
     * Returns a list of errors, exceptions etc. that occured during notify()
     * calls.
     *
     * @since Horde_Alarm 2.1.0
     *
     * @return array  Error list.
     */
    public function getErrors()
    {
        return $this->_errors;
    }

    /**
     * Garbage collects old alarms in the backend.
     *
     * @param boolean $force  Force garbace collection? If false, GC happens
     *                        with a 1% chance.
     *
     * @throws Horde_Alarm_Exception
     */
    public function gc($force = false)
    {
        /* A 1% chance we will run garbage collection during a call. */
        if ($force || rand(0, 99) == 0) {
            $this->_gc();
        }
    }

    /**
     * Garbage collects old alarms in the backend.
     *
     * @throws Horde_Alarm_Exception
     */
    abstract protected function _gc();

    /**
     * Attempts to initialize the backend.
     *
     * @throws Horde_Alarm_Exception
     */
    abstract public function initialize();

    /**
     * Converts a value from the driver's charset.
     *
     * @param mixed $value  Value to convert.
     *
     * @return mixed  Converted value.
     */
    abstract protected function _fromDriver($value);

    /**
     * Converts a value to the driver's charset.
     *
     * @param mixed $value  Value to convert.
     *
     * @return mixed  Converted value.
     */
    abstract protected function _toDriver($value);

}
