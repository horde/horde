<?php
/**
 * Kronolith_Driver defines an API for implementing storage backends for
 * Kronolith.
 *
 * Copyright 1999-2010 The Horde Project (http://www.horde.org/)
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
     * The current calendar.
     *
     * @var string
     */
    public $calendar;

    /**
     * The HTML background color to be used for this event.
     *
     * @var string
     */
    public $backgroundColor = '#ddd';

    /**
     * The HTML foreground color to be used for this event.
     *
     * @var string
     */
    public $foregroundColor = '#000';

    /**
     * A hash containing any parameters for the current driver.
     *
     * @var array
     */
    protected $_params = array();

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

    /**
     * Sets all configuration parameters for this driver.
     *
     * @param string $params  A parameters hash.
     */
    public function setParams($params)
    {
        $this->_params = $params;
    }

    /**
     * Selects a calendar as the currently opened calendar.
     *
     * @param string $calendar  A calendar identifier.
     */
    public function open($calendar)
    {
        $this->calendar = $calendar;
    }

    /**
     * Returns the background color of the current calendar.
     *
     * @return string  The calendar color.
     */
    public function backgroundColor()
    {
        return '#dddddd';
    }

    /**
     * Returns the colors of the current calendar.
     *
     * @return array  The calendar background and foreground color.
     */
    public function colors()
    {
        $color = $this->backgroundColor();
        return array($color, Kronolith::foregroundColor($color));
    }

    /**
     * Searches a calendar.
     *
     * @param object $query  An object with the criteria to search for.
     * @param boolean $json  Store the results of the events' toJson() method?
     *
     * @return mixed  An array of Kronolith_Events.
     * @throws Kronolith_Exception
     */
    public function search($query, $json = false)
    {
        /* Our default implementation first gets <em>all</em> events in a
         * specific period, and then filters based on the actual values that
         * are filled in. Drivers can optimize this behavior if they have the
         * ability. */
        $results = array();

        $events = $this->listEvents($query->start, $query->end);
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
     * @throws Kronolith_Exception
     * @throws Horde_Exception_NotFound
     */
    public function nextRecurrence($eventId, $afterDate)
    {
        $event = $this->getEvent($eventId);
        return $event->recurs() ? $event->recurrence->nextRecurrence($afterDate) : false;
    }

    /**
     * Returns the number of events in the current calendar.
     *
     * @return integer  The number of events.
     * @throws Kronolith_Exception
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
     *                           instance.
     */
    static public function factory($driver = null, $params = null)
    {
        $driver = basename($driver);
        $class = 'Kronolith_Driver_' . $driver;

        if (class_exists($class)) {
            $driver = new $class($params);
            try {
                $driver->initialize();
            } catch (Exception $e) {
                $driver = new Kronolith_Driver($params, sprintf(_("The Calendar backend is not currently available: %s"), $e->getMessage()));
            }
        } else {
            $driver = new Kronolith_Driver($params, sprintf(_("Unable to load the definition of %s."), $class));
        }

        return $driver;
    }

    /**
     * Stub to initiate a driver.
     *
     * @throws Kronolith_Exception
     */
    public function initialize()
    {
        return true;
    }

    /**
     * Stub to be overridden in the child class.
     *
     * @throws Kronolith_Exception
     * @throws Horde_Exception_NotFound
     */
    public function getEvent()
    {
        throw new Kronolith_Exception($this->_errormsg);
    }

    /**
     * Stub to be overridden in the child class.
     *
     * @throws Kronolith_Exception
     * @throws Horde_Exception_NotFound
     */
    public function getByUID($uid, $calendars = null, $getAll = false)
    {
        throw new Kronolith_Exception($this->_errormsg);
    }

    /**
     * Stub to be overridden in the child class.
     *
     * @throws Kronolith_Exception
     */
    public function listAlarms($date, $fullevent = false)
    {
        throw new Kronolith_Exception($this->_errormsg);
    }

    /**
     * Stub to be overridden in the child class.
     *
     * @throws Kronolith_Exception
     */
    public function listEvents()
    {
        throw new Kronolith_Exception($this->_errormsg);
    }

    /**
     * Saves an event in the backend.
     *
     * If it is a new event, it is added, otherwise the event is updated.
     *
     * @param Kronolith_Event $event  The event to save.
     *
     * @return string  The event id.
     * @throws Horde_Mime_Exception
     * @throws Kronolith_Exception
     */
    public function saveEvent(Kronolith_Event $event)
    {
        if ($event->stored || $event->exists()) {
            return $this->_updateEvent($event);
        }
        return $this->_addEvent($event);
    }

    /**
     * Stub to be overridden in the child class.
     *
     * @throws Kronolith_Exception
     */
    protected function _addEvent(Kronolith_Event $event)
    {
        throw new Kronolith_Exception($this->_errormsg);
    }

    /**
     * Stub to be overridden in the child class.
     *
     * @throws Kronolith_Exception
     */
    protected function _updateEvent(Kronolith_Event $event)
    {
        throw new Kronolith_Exception($this->_errormsg);
    }

    /**
     * Stub for child class to override if it can implement.
     *
     * @throws Kronolith_Exception
     */
    public function exists()
    {
        throw new Kronolith_Exception('Not supported');
    }

    /**
     * Moves an event to a new calendar.
     *
     * @param string $eventId      The event to move.
     * @param string $newCalendar  The new calendar.
     *
     * @throws Kronolith_Exception
     * @throws Horde_Exception_NotFound
     */
    public function move($eventId, $newCalendar)
    {
        $event = $this->_move($eventId, $newCalendar);

        /* Log the moving of this item in the history log. */
        $uid = $event->uid;
        if ($uid) {
            $history = $GLOBALS['injector']->getInstance('Horde_History');
            try {
                $history->log('kronolith:' . $event->calendar . ':' . $uid, array('action' => 'delete'), true);
                $history->log('kronolith:' . $newCalendar . ':' . $uid, array('action' => 'add'), true);
            } catch (Exception $e) {
                Horde::logMessage($e, 'ERR');
            }
        }
    }

    /**
     * Stub to be overridden in the child class.
     *
     * @throws Kronolith_Exception
     */
    protected function _move($eventId, $newCalendar)
    {
        throw new Kronolith_Exception('Not supported');
    }

    /**
     * Stub to be overridden in the child class.
     *
     * @throws Kronolith_Exception
     */
    public function delete($calendar)
    {
        throw new Kronolith_Exception('Not supported');
    }

    /**
     * Stub to be overridden in the child class.
     */
    public function deleteEvent($eventId)
    {
    }

    /**
     * Stub to be overridden in the child class if it can implement.
     *
     * @throws Kronolith_Exception
     */
    public function filterEventsByCalendar($uids, $calendar)
    {
        throw new Kronolith_Exception('Not supported');
    }

    /**
     * Stub for child class to override if it can implement.
     *
     * @throws Kronolith_Exception
     */
    public function removeUserData($user)
    {
        throw new Kronolith_Exception(_("Removing user data is not supported with the current calendar storage backend."));
    }
}
