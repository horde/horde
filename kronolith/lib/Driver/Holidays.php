<?php
/**
 * The Kronolith_Driver_Holidays implements support for the PEAR package
 * Date_Holidays.
 *
 * Copyright 2006-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @see     http://pear.php.net/packages/Date_Holidays
 * @author  Stephan Hohmann <webmaster@dasourcerer.net>
 * @package Kronolith
 */
class Kronolith_Driver_Holidays extends Kronolith_Driver
{
    public function listAlarms($date, $fullevent = false)
    {
        return array();
    }

    /**
     * Lists all events in the time range, optionally restricting results to
     * only events with alarms.
     *
     * @param Horde_Date $startDate  The start of range date.
     * @param Horde_Date $endDate    The end of date range.
     * @param array $options         Additional options:
     *   - show_recurrence: (boolean) Return every instance of a recurring
     *                       event?
     *                      DEFAULT: false (Only return recurring events once
     *                      inside $startDate - $endDate range)
     *   - has_alarm:       (boolean) Only return events with alarms.
     *                      DEFAULT: false (Return all events)
     *   - json:            (boolean) Store the results of the event's toJson()
     *                      method?
     *                      DEFAULT: false
     *   - cover_dates:     (boolean) Add the events to all days that they
     *                      cover?
     *                      DEFAULT: true
     *   - hide_exceptions: (boolean) Hide events that represent exceptions to
     *                      a recurring event.
     *                      DEFAULT: false (Do not hide exception events)
     *   - fetch_tags:      (boolean) Fetch tags for all events.
     *                      DEFAULT: false (Do not fetch event tags)
     *
     * @throws Kronolith_Exception
     */
    protected function _listEvents(Horde_Date $startDate = null,
                                   Horde_Date $endDate = null,
                                   array $options = array())
    {
        if (!class_exists('Date_Holidays')) {
            Horde::logMessage('Support for Date_Holidays has been enabled but the package seems to be missing.', 'ERR');
            return array();
        }

        if (is_null($startDate) && !is_null($endDate)) {
            $startDate = clone $endDate;
            $startDate->year--;
        }
        if (is_null($endDate) && !is_null($startDate)) {
            $endDate = clone $startDate;
            $endDate->year++;
        }
        if ($options['has_alarm'] || is_null($startDate) || is_null($endDate)) {
            return array();
        }

        $startDate = clone $startDate;
        $startDate->hour = $startDate->min = $startDate->sec = 0;
        $endDate = clone $endDate;
        $endDate->hour = 23;
        $endDate->min = $endDate->sec = 59;

        Date_Holidays::staticSetProperty('DIE_ON_MISSING_LOCALE', false);

        $results = array();
        for ($year = $startDate->year; $year <= $endDate->year; $year++) {
            $dh = Date_Holidays::factory($this->calendar, $year, $this->_params['language']);
            if (Date_Holidays::isError($dh)) {
                Horde::logMessage(sprintf('Factory was unable to produce driver object for driver %s in year %s with locale %s',
                                          $this->calendar, $year, $this->_params['language']), 'ERR');
                continue;
            }
            $dh->addTranslation($this->_params['language']);
            $events = $this->_getEvents($dh, $startDate, $endDate);
            foreach ($events as $event) {
                Kronolith::addEvents($results, $event, $startDate, $endDate,
                                     $options['show_recurrence'],
                                     $options['json'],
                                     $options['cover_dates']);
            }
        }

        return $results;
    }

    /**
     * @throws Kronolith_Exception
     * @throws Horde_Exception_NotFound
     */
    public function getEvent($eventId = null)
    {
        if (!$eventId) {
            $date = new Date();
            return new Kronolith_Event_Holidays($this, new Date_Holidays_Holiday(null, null, $date, null));
        }

        list($id, $date) = explode('-', $eventId, 2);
        $year = substr($date, 0, 4);

        $dh = Date_Holidays::factory($this->calendar, $year, $this->_params['language']);
        if (Date_Holidays::isError($dh)) {
            Horde::logMessage(sprintf('Factory was unable to produce driver object for driver %s in year %s with locale %s',
                                      $this->calendar, $year, $this->_params['language']), 'ERR');
            return false;
        }
        $dh->addTranslation($this->_params['language']);

        $event = $dh->getHoliday($id);
        if ($event instanceof PEAR_Error) {
            throw new Horde_Exception_NotFound($event);
        }

        return new Kronolith_Event_Holidays($this, $event);
    }

    private function _getEvents($dh, $startDate, $endDate)
    {
        $events = array();
        for ($date = new Horde_Date($startDate);
             $date->compareDate($endDate) <= 0;
             $date->mday++) {
            $holidays = $dh->getHolidayForDate($date->format('Y-m-d'), null, true);
            if (Date_Holidays::isError($holidays)) {
                Horde::logMessage(sprintf('Unable to retrieve list of holidays from %s to %s',
                                          (string)$startDate, (string)$endDate), __FILE__, __LINE__);
                continue;
            }

            if (is_null($holidays)) {
                continue;
            }

            foreach ($holidays as $holiday) {
                $event = new Kronolith_Event_Holidays($this, $holiday);
                $events[] = $event;
            }
        }
        return $events;
    }

}
