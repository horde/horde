<?php
/**
 * Copyright 2010-2017 Horde LLC (http://www.horde.org/)
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
 * Null Alarm driver.
 *
 * @author    Jan Schneider <jan@horde.org>
 * @category  Horde
 * @copyright 2010-2017 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL-2.1
 * @package   Alarm
 */
class Horde_Alarm_Null extends Horde_Alarm
{
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
        return array();
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
     * @param string $user  The alarm's user
     *
     * @return array  An alarm hash.
     * @throws Horde_Alarm_Exception
     */
    protected function _get($id, $user)
    {
        throw new Horde_Alarm_Exception('Alarm not found');
    }

    /**
     * Adds an alarm hash to the backend.
     *
     * @param array $alarm  An alarm hash.
     *
     * @throws Horde_Alarm_Exception
     */
    protected function _add(array $alarm)
    {
    }

    /**
     * Updates an alarm hash in the backend.
     *
     * @param array $alarm         An alarm hash.
     * @param boolean $keepsnooze  Whether to keep the snooze value unchanged.
     *
     * @throws Horde_Alarm_Exception
     */
    protected function _update(array $alarm, $keepsnooze = false)
    {
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
    protected function _exists($id, $user, $instanceid = null)
    {
        return false;
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
        return false;
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
