<?php
/**
 * The Kronolith_Driver_Mock class provides a Kronolith dummy driver.
 *
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author  Gunnar Wrobel <wrobel@pardus.de>
 * @package Kronolith
 */
class Kronolith_Driver_Mock extends Kronolith_Driver
{

    /**
     * List all alarms.
     *
     * @param Horde_Date $date    The date to list alarms for
     * @param boolean $fullevent  Return the full event objects?
     *
     * @return array  An array of event ids, or Kronolith_Event objects
     * @throws Kronolith_Exception
     */
    public function listAlarms($date, $fullevent = false)
    {
        return array();
    }

    /**
     * Lists all events in the time range, optionally restricting results to
     * only events with alarms.
     *
     * @param Horde_Date $startInterval  Start of range date object.
     * @param Horde_Date $endInterval    End of range data object.
     * @param boolean $showRecurrence    Return every instance of a recurring
     *                                   event? If false, will only return
     *                                   recurring events once inside the
     *                                   $startDate - $endDate range.
     * @param boolean $hasAlarm          Only return events with alarms?
     * @param boolean $json              Store the results of the events'
     *                                   toJson() method?
     * @param boolean $coverDates        Whether to add the events to all days
     *                                   that they cover.
     *
     * @return array  Events in the given time range.
     * @throws Kronolith_Exception
     */
    public function listEvents($startDate = null, $endDate = null,
                               $showRecurrence = false, $hasAlarm = false,
                               $json = false, $coverDates = true)
    {
        return array();
    }
}
