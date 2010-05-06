<?php
/**
 * @package Horde_Alarm
 *
 * Copyright 2007-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 */

/**
 * The Horde_Alarm:: class provides an interface to deal with reminders,
 * alarms and notifications through a standardized API.
 *
 * @author  Jan Schneider <jan@horde.org>
 * @package Horde_Alarm
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
     * Hash containing connection parameters.
     *
     * @var array
     */
    protected $_params = array(
        'ttl' => 300
    );

    /**
     * Attempts to return a concrete instance based on $driver.
     *
     * @param string $driver  The type of concrete subclass to
     *                        return. The class name is based on the storage
     *                        driver ($driver). The code is dynamically
     *                        included.
     * @param array $params   A hash containing any additional configuration
     *                        or connection parameters a subclass might need.
     *
     * @return Horde_Alarm  The newly created concrete instance.
     * @throws Horde_Alarm_Exception
     */
    static public function factory($driver, array $params = array())
    {
        $driver = ucfirst(basename($driver));
        $class = __CLASS__ . '_' . $driver;

        if (!class_exists($class)) {
            throw new Horde_Alarm_Exception('Could not find driver.');
        }

        $alarm = new $class($params);
        $alarm->initialize();
        $alarm->gc();

        return $alarm;
    }

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

        $this->_params = array_merge($this->_params, $params);
    }

    /**
     * Retrieves active alarms from all applications and stores them in the
     * backend.
     *
     * The applications will only be called once in the configured time span,
     * by default 5 minutes.
     *
     * @param string $user      Retrieve alarms for this user, or for all users
     *                          if null.
     * @param boolean $preload  Preload alarms that go off within the next
     *                          ttl time span?
     */
    public function load($user = null, $preload = true)
    {
        if (isset($_SESSION['horde']['alarm']['loaded']) &&
            (time() - $_SESSION['horde']['alarm']['loaded']) < $this->_params['ttl']) {
            return;
        }

        foreach ($GLOBALS['registry']->listApps(null, false, Horde_Perms::READ) as $app) {
            if (!$GLOBALS['registry']->hasMethod('listAlarms', $app)) {
                continue;
            }

            /* Preload alarms that happen in the next ttl seconds. */
            if ($preload) {
                try {
                    $alarms = $GLOBALS['registry']->callByPackage($app, 'listAlarms', array(time() + $this->_params['ttl'], $user), array('noperms' => true));
                } catch (Horde_Exception $e) {
                    continue;
                }
            } else {
                $alarms = array();
            }

            /* Load current alarms if no preloading requested or if this
             * is the first call in this session. */
            if (!$preload ||
                !isset($_SESSION['horde']['alarm']['loaded'])) {
                try {
                    $app_alarms = $GLOBALS['registry']->callByPackage($app, 'listAlarms', array(time(), $user), array('noperms' => true));
                } catch (Horde_Exception $e) {
                    if ($this->_logger) {
                        $this->_logger->log($e, 'ERR');
                    }
                    $app_alarms = array();
                }
                $alarms = array_merge($alarms, $app_alarms);
            }

            foreach ($alarms as $alarm) {
                $this->set($alarm, true);
            }
        }

        $_SESSION['horde']['alarm']['loaded'] = time();
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
        if ($load) {
            $this->load($user, $preload);
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
     * @param array $alarm         An alarm hash. See self::get() for format.
     * @param boolean $keepsnooze  Whether to keep the snooze value unchanged.
     *
     * @throws Horde_Alarm_Exception
     */
    public function set(array $alarm, $keepsnooze = false)
    {
        if (isset($alarm['mail']['body'])) {
            $alarm['mail']['body'] = $this->_toDriver($alarm['mail']['body']);
        }

        if ($this->exists($alarm['id'], isset($alarm['user']) ? $alarm['user'] : '')) {
            $this->_update($alarm, $keepsnooze);
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
    abstract protected function _internal($id, $user, array $internal);

    /**
     * Returns whether an alarm with the given id exists already.
     *
     * @param string $id    The alarm's unique id.
     * @param string $user  The alarm's user
     *
     * @return boolean  True if the specified alarm exists.
     */
    public function exists($id, $user)
    {
        try {
            return $this->_exists($id, $user);
        } catch (Horde_Alarm_Exception $e) {
            return false;
        }
    }

    /**
     * Returns whether an alarm with the given id exists already.
     *
     * @param string $id    The alarm's unique id.
     * @param string $user  The alarm's user
     *
     * @return boolean  True if the specified alarm exists.
     * @throws Horde_Alarm_Exception
     */
    abstract protected function _exists($id, $user);

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
     * @throws Horde_Alarm_Exception
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

        $methods = array_keys($this->notificationMethods());
        foreach ($alarms as $alarm) {
            foreach ($alarm['methods'] as $alarm_method) {
                if (in_array($alarm_method, $methods) &&
                    !in_array($alarm_method, $exclude)) {
                    try {
                        $this->{'_' . $alarm_method}($alarm);
                    } catch (Horde_Alarm_Exception $e) {
                        if ($this->_logger) {
                            $this->_logger->log($e, 'ERR');
                        }
                    }
                }
            }
        }
    }

    /**
     * Notifies about an alarm through Horde_Notification.
     *
     * @param array $alarm  An alarm hash.
     */
    protected function _notify(array $alarm)
    {
        static $sound_played;

        $GLOBALS['notification']->push($alarm['title'], 'horde.alarm', array('alarm' => $alarm));
        if (!empty($alarm['params']['notify']['sound']) &&
            !isset($sound_played[$alarm['params']['notify']['sound']])) {
            $GLOBALS['notification']->attach('audio');
            $GLOBALS['notification']->push($alarm['params']['notify']['sound'], 'audio');
            $sound_played[$alarm['params']['notify']['sound']] = true;
        }
    }

    /**
     * Notifies about an alarm by email.
     *
     * @param array $alarm  An alarm hash.
     *
     * @throws Horde_Mime_Exception
     * @throws Horde_Alarm_Exception
     */
    protected function _mail(array $alarm)
    {
        if (!empty($alarm['internal']['mail']['sent'])) {
            return;
        }

        if (empty($alarm['params']['mail']['email'])) {
            if (empty($alarm['user'])) {
                return;
            }
            $identity = $GLOBALS['injector']->getInstance('Horde_Prefs_Identity')->getIdentity($alarm['user']);
            $email = $identity->getDefaultFromAddress(true);
        } else {
            $email = $alarm['params']['mail']['email'];
        }

        $mail = new Horde_Mime_Mail(array(
            'subject' => $alarm['title'],
            'body' => empty($alarm['params']['mail']['body']) ? $alarm['text'] : $alarm['params']['mail']['body'],
            'to' => $email,
            'from' => $email,
            'charset' => Horde_Nls::getCharset()
        ));
        $mail->addHeader('Auto-Submitted', 'auto-generated');
        $mail->addHeader('X-Horde-Alarm', $alarm['title'], Horde_Nls::getCharset());
        $mail->send($GLOBALS['injector']->getInstance('Mail'));

        $alarm['internal']['mail']['sent'] = true;
        $this->_internal($alarm['id'], $alarm['user'], $alarm['internal']);
    }

    /**
     * Notifies about an alarm with an SMS through the sms/send API method.
     *
     * @param array $alarm  An alarm hash.
     */
    protected function _sms(array $alarm)
    {
    }

    /**
     * Returns a list of available notification methods and method parameters.
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
    public function notificationMethods()
    {
        static $methods;

        if (!isset($methods)) {
            $methods = array(
                'notify' => array(
                    '__desc' => _("Inline"),
                    'sound' => array(
                        'type' => 'sound',
                        'desc' => _("Play a sound?"),
                        'required' => false
                    )
                ),
                'mail' => array(
                    '__desc' => _("Email"),
                    'email' => array(
                        'type' => 'text',
                        'desc' => _("Email address (optional)"),
                        'required' => false
                    )
                )
            );
            /*
            if ($GLOBALS['registry']->hasMethod('sms/send')) {
                $methods['sms'] = array(
                    'phone' => array('type' => 'text',
                                     'desc' => _("Cell phone number"),
                                     'required' => true));
            }
            */
        }

        return $methods;
    }

    /**
     * Garbage collects old alarms in the backend.
     *
     * @throws Horde_Alarm_Exception
     */
    public function gc()
    {
        /* A 1% chance we will run garbage collection during a call. */
        if (rand(0, 99) == 0) {
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
