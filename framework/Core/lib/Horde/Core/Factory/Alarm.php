<?php
/**
 * A Horde_Injector:: based Horde_Alarm:: factory.
 *
 * PHP version 5
 *
 * @category Horde
 * @package  Core
 * @author   Michael J. Rubinsky <mrubinsk@horde.org>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Core
 */

/**
 * A Horde_Injector:: based Horde_Core_Ajax_Application:: factory.
 *
 * Copyright 2010-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category Horde
 * @package  Core
 * @author   Michael J. Rubinsky <mrubinsk@horde.org>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Core
 */
class Horde_Core_Factory_Alarm extends Horde_Core_Factory_Base
{
    /**
     * A Horde_Alarm instance.
     *
     * @var Horde_Alarm
     */
    protected $_alarm;

    /**
     * TTL value.
     *
     * @var integer
     */
    protected $_ttl;

    /**
     * Return a Horde_Alarm instance.
     *
     * @return Horde_Alarm
     * @throws Horde_Exception
     */
    public function create()
    {
        if (isset($this->_alarm)) {
            return $this->_alarm;
        }

        $driver = empty($GLOBALS['conf']['alarms']['driver'])
            ? 'Null'
            : $GLOBALS['conf']['alarms']['driver'];
        $params = Horde::getDriverConfig('alarms', $driver);

        if (strcasecmp($driver, 'Sql') === 0) {
            $params['db'] = $this->_injector->getInstance('Horde_Db_Adapter');
        }

        $params['logger'] = $this->_injector->getInstance('Horde_Log_Logger');
        $params['loader'] = array($this, 'load');

        $this->_ttl = isset($params['ttl'])
            ? $params['ttl']
            : 300;

        $class = $this->_getDriverName($driver, 'Horde_Alarm');
        $this->_alarm = new $class($params);
        $this->_alarm->initialize();
        $this->_alarm->gc();

        /* Add those handlers that need configuration and can't be auto-loaded
         * through Horde_Alarms::handlers(). */
        $this->_alarm->addHandler('notify', new Horde_Core_Alarm_Handler_Notify());

        $handler_params = array(
            'js_notify' => array($this->_injector->getInstance('Horde_PageOutput'), 'addInlineScript'),
            'icon' => (string)Horde_Themes::img('alerts/alarm.png')
        );
        $this->_alarm->addHandler('desktop', new Horde_Alarm_Handler_Desktop($handler_params));

        $handler_params = array(
            'identity' => $this->_injector->getInstance('Horde_Core_Factory_Identity'),
            'mail' => $this->_injector->getInstance('Horde_Mail'),
        );
        $this->_alarm->addHandler('mail', new Horde_Alarm_Handler_Mail($handler_params));

        return $this->_alarm;
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
        global $registry, $session;

        if ($this->_ttl &&
            $session->exists('horde', 'alarm_loaded') &&
            ((time() - $session->get('horde', 'alarm_loaded')) < $this->_ttl)) {
            return;
        }

        /* Cache alarm handler application method existence. */
        $cache = $session->get('horde', 'factory_alarm');

        if (is_null($cache)) {
            $save = array();
            $changed = ($registry->getAuth() !== false);

            try {
                $apps = $registry->listApps(null, false, Horde_Perms::READ);
            } catch (Horde_Exception $e) {
                $apps = array();
            }
        } else {
            $apps = $cache;
            $changed = false;
        }

        foreach ($apps as $app) {
            if ($changed) {
                if (!$registry->hasFeature('alarmHandler')) {
                    continue;
                }
                $save[] = $app;
            }

            /* Preload alarms that happen in the next ttl seconds. */
            $time = $preload
                ? time() + $this->_ttl
                : time();

            try {
                foreach ($registry->callAppMethod($app, 'listAlarms', array('args' => array($time, $user), 'noperms' => true)) as $alarm) {
                    $this->_alarm->set($alarm, true);
                }
            } catch (Horde_Exception $e) {}
        }

        if ($changed) {
            $session->set('horde', 'factory_alarm', $save);
        }

        $session->set('horde', 'alarm_loaded', time());
    }

}
