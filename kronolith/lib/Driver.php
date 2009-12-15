<?php
/**
 * Kronolith_Driver defines an API for implementing storage backends for
 * Kronolith.
 *
 * Copyright 1999-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @author  Jan Schneider <jan@horde.org>
 * @package Kronolith
 */
class Kronolith_Driver
{
    /**
     * A hash containing any parameters for the current driver.
     *
     * @var array
     */
    protected $_params = array();

    /**
     * The current calendar.
     *
     * @var string
     */
    public $calendar;

    /**
     * An error message to throw when something is wrong.
     *
     * @var string
     */
    private $_errormsg;

    /**
     * Constructor.
     *
     * Just stores the $params in our newly-created object. All other work is
     * done by {@link initialize()}.
     *
     * @param array $params  Any parameters needed for this driver.
     */
    public function __construct($params = array(), $errormsg = null)
    {
        $this->_params = $params;
        if ($errormsg === null) {
            $this->_errormsg = _("The Calendar backend is not currently available.");
        } else {
            $this->_errormsg = $errormsg;
        }
    }

    /**
     * Returns a configuration for this driver.
     *
     * @param string $param  A parameter name.
     *
     * @return mixed  The parameter value or null if not set.
     */
    public function getParam($param)
    {
        return isset($this->_params[$param]) ? $this->_params[$param] : null;
    }

    /**
     * Sets a configuration for this driver.
     *
     * @param string $param  A parameter name.
     * @param mixed $value   The parameter value.
     */
    public function setParam($param, $value)
    {
        $this->_params[$param] = $value;
    }

    public function open($calendar)
    {
        $this->calendar = $calendar;
    }

    /**
     * Renames a calendar.
     *
     * @param string $from  The current name of the calendar.
     * @param string $to    The new name of the calendar.
     *
     * @return mixed  True or a PEAR_Error on failure.
     */
    public function rename($from, $to)
    {
        return true;
    }

    /**
     * Searches a calendar.
     *
     * @param object $query  An object with the criteria to search for.
     * @param boolean $json  Store the results of the events' toJson() method?
     *
     * @return mixed  An array of Kronolith_Events or a PEAR_Error.
     */
    public function search($query, $json = false)
    {
        /* Our default implementation first gets <em>all</em> events in a
         * specific period, and then filters based on the actual values that
         * are filled in. Drivers can optimize this behavior if they have the
         * ability. */
        $results = array();

        $events = $this->listEvents($query->start, $query->end);
        if (is_a($events, 'PEAR_Error')) {
            return $events;
        }

        foreach ($events as $day => $day_events) {
            foreach ($day_events as $event) {
                if ((((!isset($query->start) ||
                       $event->end->compareDateTime($query->start) > 0) &&
                      (!isset($query->end) ||
                       $event->end->compareDateTime($query->end) < 0)) ||
                     ($event->recurs() &&
                      $event->end->compareDateTime($query->start) >= 0 &&
                      $event->start->compareDateTime($query->end) <= 0)) &&
                    (empty($query->title) ||
                     stristr($event->getTitle(), $query->title)) &&
                    (empty($query->location) ||
                     stristr($event->location, $query->location)) &&
                    (empty($query->description) ||
                     stristr($event->description, $query->description)) &&
                    (empty($query->creator) ||
                     stristr($event->creator, $query->creator))  &&
                    (!isset($query->status) ||
                     $event->status == $query->status)) {
                    Kronolith::addEvents($results, $event, $event->start, $event->end, false, $json, false);
                }
            }
        }

        return $results;
    }

    /**
     * Finds the next recurrence of $eventId that's after $afterDate.
     *
     * @param string $eventId        The ID of the event to fetch.
     * @param Horde_Date $afterDate  Return events after this date.
     *
     * @return Horde_Date|boolean  The date of the next recurrence or false if
     *                             the event does not recur after $afterDate.
     */
    public function nextRecurrence($eventId, $afterDate)
    {
        $event = $this->getEvent($eventId);
        if (is_a($event, 'PEAR_Error')) {
            return $event;
        }

        return $event->recurs() ? $event->recurrence->nextRecurrence($afterDate) : false;
    }

    /**
     * Returns the number of events in the current calendar.
     *
     * @return integer  The number of events.
     */
    public function countEvents()
    {
        $count = 0;
        foreach ($this->listEvents() as $dayevents) {
            $count += count($dayevents);
        }
        return $count;
    }

    /**
     * Attempts to return a concrete Kronolith_Driver instance based on
     * $driver.
     *
     * @param string $driver  The type of concrete Kronolith_Driver subclass
     *                        to return.
     *
     * @param array $params   A hash containing any additional configuration or
     *                        connection parameters a subclass might need.
     *
     * @return Kronolith_Driver  The newly created concrete Kronolith_Driver
     *                           instance, or a PEAR_Error on error.
     */
    public function factory($driver = null, $params = null)
    {
        $driver = basename($driver);
        $class = 'Kronolith_Driver_' . $driver;

        if (class_exists($class)) {
            $driver = new $class($params);
            $result = $driver->initialize();
            if (is_a($result, 'PEAR_Error')) {
                $driver = new Kronolith_Driver($params, sprintf(_("The Calendar backend is not currently available: %s"), $result->getMessage()));
            }
        } else {
            $driver = new Kronolith_Driver($params, sprintf(_("Unable to load the definition of %s."), $class));
        }

        return $driver;
    }

    /**
     * Stub to initiate a driver.
     */
    public function initialize()
    {
        return true;
    }

    /**
     * Stub to be overridden in the child class.
     */
    public function getEvent()
    {
        return PEAR::raiseError($this->_errormsg);
    }

    /**
     * Stub to be overridden in the child class.
     */
    public function getByUID($uid, $calendars = null, $getAll = false)
    {
        return PEAR::raiseError($this->_errormsg);
    }

    /**
     * Stub to be overridden in the child class.
     */
    public function listAlarms($date, $fullevent = false)
    {
        return PEAR::raiseError($this->_errormsg);
    }

    /**
     * Stub to be overridden in the child class.
     */
    public function listEvents()
    {
        return PEAR::raiseError($this->_errormsg);
    }

    /**
     * Stub to be overridden in the child class.
     */
    public function saveEvent()
    {
        return PEAR::raiseError($this->_errormsg);
    }

    /**
     * Stub for child class to override if it can implement.
     */
    public function exists()
    {
        return PEAR::raiseError('Not supported');
    }

    /**
     * Stub to be overridden in the child class.
     */
    public function move($eventId, $newCalendar)
    {
        return PEAR::raiseError('Not supported');
    }

    /**
     * Stub to be overridden in the child class.
     */
    public function delete($calendar)
    {
        return PEAR::raiseError('Not supported');
    }

    /**
     * Stub to be overridden in the child class.
     */
    public function deleteEvent($eventId)
    {

    }

    /**
     * Stub for child class to override if it can implement.
     */
    public function removeUserData($user)
    {
        return PEAR::raiseError(_("Removing user data is not supported with the current calendar storage backend."));
    }

    /**
     * Stub to be overridden in the child class if it can implement.
     */
    public function filterEventsByCalendar($uids, $calendar)
    {
        return PEAR::raiseError('Not supported');
    }
}
