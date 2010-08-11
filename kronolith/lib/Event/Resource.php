<?php
/**
 * Copyright 1999-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Luc Saillard <luc.saillard@fr.alcove.com>
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @author  Jan Schneider <jan@horde.org>
 * @author  Michael J. Rubinsky <mrubinsk@horde.org>
 *
 * @package Kronolith
 */
class Kronolith_Event_Resource extends Kronolith_Event
{
    /**
     * The type of the calender this event exists on.
     *
     * @var string
     */
    public $calendarType = 'resource';

    /**
     * @var array
     */
    private $_properties = array();

    /**
     * Imports a backend specific event object.
     *
     * @param array $event  Backend specific event object that this object
     *                      will represent.
     */
    public function fromDriver($SQLEvent)
    {
        $driver = $this->getDriver();

        $this->allday = (bool)$SQLEvent['event_allday'];
        if (!$this->allday && $driver->getParam('utc')) {
            $tz_local = date_default_timezone_get();
            $this->start = new Horde_Date($SQLEvent['event_start'], 'UTC');
            $this->start->setTimezone($tz_local);
            $this->end = new Horde_Date($SQLEvent['event_end'], 'UTC');
            $this->end->setTimezone($tz_local);
        } else {
            $this->start = new Horde_Date($SQLEvent['event_start']);
            $this->end = new Horde_Date($SQLEvent['event_end']);
        }

        $this->durMin = ($this->end->timestamp() - $this->start->timestamp()) / 60;

        $this->title = $driver->convertFromDriver($SQLEvent['event_title']);
        $this->id = $SQLEvent['event_id'];
        $this->uid = $SQLEvent['event_uid'];
        $this->creator = $SQLEvent['event_creator_id'];

        if (!empty($SQLEvent['event_recurtype'])) {
            $this->recurrence = new Horde_Date_Recurrence($this->start);
            $this->recurrence->setRecurType((int)$SQLEvent['event_recurtype']);
            $this->recurrence->setRecurInterval((int)$SQLEvent['event_recurinterval']);
            if (isset($SQLEvent['event_recurenddate'])) {
                if ($driver->getParam('utc')) {
                    $recur_end = new Horde_Date($SQLEvent['event_recurenddate'], 'UTC');
                    if ($recur_end->min == 0) {
                        /* Old recurrence end date format. */
                        $recur_end = new Horde_Date($SQLEvent['event_recurenddate']);
                        $recur_end->hour = 23;
                        $recur_end->min = 59;
                        $recur_end->sec = 59;
                    } else {
                        $recur_end->setTimezone(date_default_timezone_get());
                    }
                } else {
                    $recur_end = new Horde_Date($SQLEvent['event_recurenddate']);
                    $recur_end->hour = 23;
                    $recur_end->min = 59;
                    $recur_end->sec = 59;
                }
                $this->recurrence->setRecurEnd($recur_end);
            }
            if (isset($SQLEvent['event_recurcount'])) {
                $this->recurrence->setRecurCount((int)$SQLEvent['event_recurcount']);
            }
            if (isset($SQLEvent['event_recurdays'])) {
                $this->recurrence->recurData = (int)$SQLEvent['event_recurdays'];
            }
            if (!empty($SQLEvent['event_exceptions'])) {
                $this->recurrence->exceptions = explode(',', $SQLEvent['event_exceptions']);
            }
        }

        if (isset($SQLEvent['event_location'])) {
            $this->location = $driver->convertFromDriver($SQLEvent['event_location']);
        }
        if (isset($SQLEvent['event_private'])) {
            $this->private = (bool)($SQLEvent['event_private']);
        }
        if (isset($SQLEvent['event_status'])) {
            $this->status = (int)$SQLEvent['event_status'];
        }
        if (isset($SQLEvent['event_attendees'])) {
            $this->attendees = array_change_key_case($driver->convertFromDriver(unserialize($SQLEvent['event_attendees'])));
        }
        if (isset($SQLEvent['event_resources'])) {
            $this->_resources = array_change_key_case($driver->convertFromDriver(unserialize($SQLEvent['event_resources'])));
        }
        if (isset($SQLEvent['event_description'])) {
            $this->description = $driver->convertFromDriver($SQLEvent['event_description']);
        }
        if (isset($SQLEvent['event_alarm'])) {
            $this->alarm = (int)$SQLEvent['event_alarm'];
        }
        if (isset($SQLEvent['event_alarm_methods'])) {
            $this->methods = $driver->convertFromDriver(unserialize($SQLEvent['event_alarm_methods']));
        }
        $this->initialized = true;
        $this->stored = true;
    }

    /**
     * Prepares this event to be saved to the backend.
     */
    public function toDriver()
    {
        $driver = $this->getDriver();

        /* Basic fields. */
        $this->_properties['event_creator_id'] = $driver->convertToDriver($this->creator);
        $this->_properties['event_title'] = $driver->convertToDriver($this->title);
        $this->_properties['event_description'] = $driver->convertToDriver($this->description);
        $this->_properties['event_location'] = $driver->convertToDriver($this->location);
        $this->_properties['event_private'] = (int)$this->private;
        $this->_properties['event_status'] = $this->status;
        $this->_properties['event_attendees'] = serialize($driver->convertToDriver($this->attendees));
        $this->_properties['event_resources'] = serialize($driver->convertToDriver($this->getResources()));
        $this->_properties['event_modified'] = $_SERVER['REQUEST_TIME'];

        if ($this->isAllDay()) {
            $this->_properties['event_start'] = $this->start->strftime('%Y-%m-%d %H:%M:%S');
            $this->_properties['event_end'] = $this->end->strftime('%Y-%m-%d %H:%M:%S');
            $this->_properties['event_allday'] = 1;
        } else {
            if ($driver->getParam('utc')) {
                $start = clone $this->start;
                $end = clone $this->end;
                $start->setTimezone('UTC');
                $end->setTimezone('UTC');
            } else {
                $start = $this->start;
                $end = $this->end;
            }
            $this->_properties['event_start'] = $start->strftime('%Y-%m-%d %H:%M:%S');
            $this->_properties['event_end'] = $end->strftime('%Y-%m-%d %H:%M:%S');
            $this->_properties['event_allday'] = 0;
        }

        /* Alarm. */
        $this->_properties['event_alarm'] = (int)$this->alarm;

        /* Alarm Notification Methods. */
        $this->_properties['event_alarm_methods'] = serialize($driver->convertToDriver($this->methods));

        /* Recurrence. */
        if (!$this->recurs()) {
            $this->_properties['event_recurtype'] = 0;
        } else {
            $recur = $this->recurrence->getRecurType();
            if ($this->recurrence->hasRecurEnd()) {
                if ($driver->getParam('utc')) {
                    $recur_end = clone $this->recurrence->recurEnd;
                    $recur_end->setTimezone('UTC');
                } else {
                    $recur_end = $this->recurrence->recurEnd;
                }
            } else {
                $recur_end = new Horde_Date(array('year' => 9999, 'month' => 12, 'mday' => 31, 'hour' => 23, 'min' => 59, 'sec' => 59));
            }

            $this->_properties['event_recurtype'] = $recur;
            $this->_properties['event_recurinterval'] = $this->recurrence->getRecurInterval();
            $this->_properties['event_recurenddate'] = $recur_end->format('Y-m-d H:i:s');
            $this->_properties['event_recurcount'] = $this->recurrence->getRecurCount();

            switch ($recur) {
            case Horde_Date_Recurrence::RECUR_WEEKLY:
                $this->_properties['event_recurdays'] = $this->recurrence->getRecurOnDays();
                break;
            }
            $this->_properties['event_exceptions'] = implode(',', $this->recurrence->getExceptions());
        }
    }

    public function getProperties()
    {
        return $this->_properties;
    }

    /**
     * Returns a reference to a driver that's valid for this event.
     *
     * @return Kronolith_Driver  A driver that this event can use to save
     *                           itself, etc.
     */
    public function getDriver()
    {
        return Kronolith::getDriver('Resource', $this->calendar);
    }

    /**
     * Encapsulates permissions checking. For now, admins, and ONLY admins have
     * any permissions to a resource's events.
     *
     * @param integer $permission  The permission to check for.
     * @param string $user         The user to check permissions for.
     *
     * @return boolean
     */
    public function hasPermission($permission, $user = null)
    {
        return $GLOBALS['registry']->isAdmin();
    }

}
