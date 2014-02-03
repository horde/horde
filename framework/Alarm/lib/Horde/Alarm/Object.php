<?php
/**
 * Copyright 2010-2014 Horde LLC (http://www.horde.org/)
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
 * The Horde_Alarm_Object class is a Horde_Alarm storage implementation using
 * an object instance.
 *
 * @author    Jan Schneider <jan@horde.org>
 * @category  Horde
 * @copyright 2010-2014 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL-2.1
 * @package   Alarm
 */
class Horde_Alarm_Object extends Horde_Alarm
{
    protected $_alarms = array();

    /**
     * Returns a certain alarm.
     *
     * @param string $id    The alarm's unique id.
     * @param string $user  The alarm's user.
     *
     * @return array  An alarm hash.
     */
    protected function &_findAlarm($id, $user)
    {
        foreach ($this->_alarms as &$alarm) {
            if ($alarm['id'] == $id && $alarm['user'] === $user) {
                return $alarm;
            }
        }
        $result = null;
        return $result;
    }

    /**
     * Sorts a number of alarms in chronological order.
     */
    protected function _sortAlarms($a, $b)
    {
        $cmp = $a['start']->compareDateTime($b['start']);
        if ($cmp) {
            return $cmp;
        }
        if (empty($a['end'])) {
            return -1;
        }
        if (empty($b['end'])) {
            return 1;
        }
        return $a['end']->compareDateTime($b['end']);
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
    protected function _list($user, Horde_Date $time)
    {
        $alarms = array();
        foreach ($this->_alarms as $alarm) {
            if (empty($alarm['dismissed']) &&
                ((empty($alarm['snooze']) && $alarm['start']->compareDateTime($time) <= 0) ||
                 $alarm['snooze']->compareDateTime($time) <= 0) &&
                (empty($alarm['end']) || $alarm['end']->compareDateTime($time) >= 0) &&
                (is_null($user) || empty($alarm['uid']) || $alarm['uid'] = $user)) {
                $alarms[] = $alarm;
            }
        }
        usort($alarms, array($this, '_sortAlarms'));
        return $alarms;
    }

    /**
     * Returns a list of all global alarms from the backend.
     *
     * @return array  A list of alarm hashes.
     */
    protected function _global()
    {
        return array();
    }

    /**
     * Returns an alarm hash from the backend.
     *
     * @param string $id    The alarm's unique id.
     * @param string $user  The alarm's user.
     *
     * @return array  An alarm hash.
     * @throws Horde_Alarm_Exception
     */
    protected function _get($id, $user)
    {
        $alarm = $this->_findAlarm($id, $user);
        if (!$alarm) {
            throw new Horde_Alarm_Exception('Alarm not found');
        }
        return $alarm;
    }

    /**
     * Adds an alarm hash to the backend.
     *
     * @param array $alarm  An alarm hash.
     */
    protected function _add(array $alarm)
    {
        $alarm = array_merge(
            array('user' => '',
                  'end' => null,
                  'text' => null,
                  'snooze' => null,
                  'internal' => null),
            $alarm);
        $this->_alarms[] = $alarm;
    }

    /**
     * Updates an alarm hash in the backend.
     *
     * @param array $alarm         An alarm hash.
     * @param boolean $keepsnooze  Whether to keep the snooze value unchanged.
     */
    protected function _update(array $alarm, $keepsnooze = false)
    {
        $user = isset($alarm['user']) ? $alarm['user'] : null;
        $al = &$this->_findAlarm($alarm['id'], $user);
        foreach (array('start', 'end', 'methods', 'params', 'title', 'text') as $property) {
            $al[$property] = isset($alarm[$property]) ? $alarm[$property] : null;
        }
        if (!$keepsnooze) {
            $al['snooze'] = null;
        }
    }

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
    public function internal($id, $user, array $internal)
    {
        $alarm = &$this->_findAlarm($id, $user);
        $alarm['internal'] = $internal;
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
    protected function _exists($id, $user)
    {
        return (bool)$this->_findAlarm($id, $user);
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
    protected function _snooze($id, $user, Horde_Date $snooze)
    {
        $alarm = &$this->_findAlarm($id, $user);
        $alarm['snooze'] = $snooze;
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
    protected function _isSnoozed($id, $user, Horde_Date $time)
    {
        $alarm = $this->_findAlarm($id, $user);
        return !empty($alarm['dismissed']) ||
            (isset($alarm['snooze']) &&
             $alarm['snooze']->compareDateTime($time) >= 0);
    }

    /**
     * Dismisses an alarm.
     *
     * @param string $id          The alarm's unique id.
     * @param string $user        The alarm's user
     *
     * @throws Horde_Alarm_Exception
     */
    protected function _dismiss($id, $user)
    {
        $alarm = &$this->_findAlarm($id, $user);
        $alarm['dismissed'] = true;
    }

    /**
     * Deletes an alarm from the backend.
     *
     * @param string $id    The alarm's unique id.
     * @param string $user  The alarm's user. All users' alarms if null.
     *
     * @throws Horde_Alarm_Exception
     */
    protected function _delete($id, $user = null)
    {
        $newAlarms = array();
        foreach ($this->_alarms as &$alarm) {
            if ($alarm['id'] != $id ||
                (!is_null($user) && $alarm['user'] != $user)) {
                $newAlarms[] = $alarm;
            }
        }
        $this->_alarms = $newAlarms;
    }

    /**
     * Garbage collects old alarms in the backend.
     *
     * @throws Horde_Alarm_Exception
     */
    protected function _gc()
    {
    }

    /**
     * Attempts to initialize the backend.
     *
     * @throws Horde_Alarm_Exception
     */
    public function initialize()
    {
    }

    /**
     * Converts a value from the driver's charset.
     *
     * @param mixed $value  Value to convert.
     *
     * @return mixed  Converted value.
     */
    protected function _fromDriver($value)
    {
        return $value;
    }

    /**
     * Converts a value to the driver's charset.
     *
     * @param mixed $value  Value to convert.
     *
     * @return mixed  Converted value.
     */
    protected function _toDriver($value)
    {
        return $value;
    }

}
