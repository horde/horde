<?php
/**
 * Copyright 1999-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Luc Saillard <luc.saillard@fr.alcove.com>
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @author  Jan Schneider <jan@horde.org>
 * @package Kronolith
 */
class Kronolith_Event_Sql extends Kronolith_Event
{
    /**
     * The type of the calender this event exists on.
     *
     * @var string
     */
    public $calendarType = 'internal';

    /**
     * Const'r
     *
     * @param Kronolith_Driver $driver  The backend driver that this event is
     *                                  stored in.
     * @param mixed $eventObject        Backend specific event object
     *                                  that this will represent.
     */
    public function __construct($driver, $eventObject = null)
    {
        /* Set default alarm value. */
        if (!isset($alarm) && isset($GLOBALS['prefs'])) {
            $this->alarm = $GLOBALS['prefs']->getValue('default_alarm');
        }

        parent::__construct($driver, $eventObject);

        if (!empty($this->calendar) &&
            isset($GLOBALS['all_calendars'][$this->calendar])) {
            $this->_backgroundColor = $GLOBALS['all_calendars'][$this->calendar]->background();
            $this->_foregroundColor = $GLOBALS['all_calendars'][$this->calendar]->foreground();
        }
    }

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
        if (isset($SQLEvent['event_url'])) {
            $this->url = $SQLEvent['event_url'];
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
        if (isset($SQLEvent['event_baseid'])) {
            $this->baseid = $SQLEvent['event_baseid'];
        }
        if (isset($SQLEvent['event_exceptionoriginaldate'])) {
            $this->exceptionoriginaldate = new Horde_Date($SQLEvent['event_exceptionoriginaldate']);
        }

        $this->initialized = true;
        $this->stored = true;
    }

    /**
     * Prepares this event to be saved to the backend.
     */
    public function toProperties()
    {
        $driver = $this->getDriver();
        $properties = array();

        /* Basic fields. */
        $properties['event_creator_id'] = $driver->convertToDriver($this->creator);
        $properties['event_title'] = $driver->convertToDriver($this->title);
        $properties['event_description'] = $driver->convertToDriver($this->description);
        $properties['event_location'] = $driver->convertToDriver($this->location);
        $properties['event_url'] = (string)$this->url;
        $properties['event_private'] = (int)$this->private;
        $properties['event_status'] = $this->status;
        $properties['event_attendees'] = serialize($driver->convertToDriver($this->attendees));
        $properties['event_resources'] = serialize($driver->convertToDriver($this->getResources()));
        $properties['event_modified'] = $_SERVER['REQUEST_TIME'];

        if ($this->isAllDay()) {
            $properties['event_start'] = $this->start->strftime('%Y-%m-%d %H:%M:%S');
            $properties['event_end'] = $this->end->strftime('%Y-%m-%d %H:%M:%S');
            $properties['event_allday'] = 1;
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
            $properties['event_start'] = $start->strftime('%Y-%m-%d %H:%M:%S');
            $properties['event_end'] = $end->strftime('%Y-%m-%d %H:%M:%S');
            $properties['event_allday'] = 0;
        }

        /* Alarm. */
        $properties['event_alarm'] = (int)$this->alarm;

        /* Alarm Notification Methods. */
        $properties['event_alarm_methods'] = serialize($driver->convertToDriver($this->methods));

        /* Recurrence. */
        if (!$this->recurs()) {
            $properties['event_recurtype'] = 0;
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

            $properties['event_recurtype'] = $recur;
            $properties['event_recurinterval'] = $this->recurrence->getRecurInterval();
            $properties['event_recurenddate'] = $recur_end->format('Y-m-d H:i:s');
            $properties['event_recurcount'] = $this->recurrence->getRecurCount();

            switch ($recur) {
            case Horde_Date_Recurrence::RECUR_WEEKLY:
                $properties['event_recurdays'] = $this->recurrence->getRecurOnDays();
                break;
            }
            $properties['event_exceptions'] = implode(',', $this->recurrence->getExceptions());
        }

        /* Exception information */
        if (!empty($this->baseid)) {
            $properties['event_baseid'] = $this->baseid;
            $properties['event_exceptionoriginaldate'] = $this->exceptionoriginaldate;
        }

        return $properties;
    }

}
