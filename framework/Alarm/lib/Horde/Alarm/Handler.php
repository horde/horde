<?php
/**
 * @package Alarm
 *
 * Copyright 2010-2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 */

/**
 * The Horde_Alarm_Handler class is an interface for all Horde_Alarm handlers
 * that notifies of active alarms.
 *
 * @author  Jan Schneider <jan@horde.org>
 * @package Alarm
 */
abstract class Horde_Alarm_Handler
{
    /**
     * The alarm object to that this handler is attached.
     *
     * Horde_Alarm
     */
    public $alarm;

    /**
     * Notifies about an alarm.
     *
     * @param array $alarm  An alarm hash.
     *
     * @throws Horde_Alarm_Exception
     */
    abstract public function notify(array $alarm);

    /**
     * Resets the internal status of the handler, so that alarm notifications
     * are sent again.
     *
     * @param array $alarm  An alarm hash.
     */
    public function reset(array $alarm)
    {
    }

    /**
     * Returns a human readable description of the handler.
     *
     * @return string
     */
    abstract public function getDescription();

    /**
     * Returns a hash of user-configurable parameters for the handler.
     *
     * The parameters are hashes with parameter names as keys and parameter
     * information as values. The parameter information is a hash with the
     * following keys:
     * - type: the parameter type as a preference type.
     * - desc: a parameter description.
     * - required: whether this parameter is required.
     *
     * @return array
     */
    public function getParameters()
    {
        return array();
    }
}
