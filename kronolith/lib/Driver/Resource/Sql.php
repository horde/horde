<?php
/**
 * The Kronolith_Driver_Resource class implements the Kronolith_Driver API for
 * storing resource calendars in a SQL backend.
 *
 * Copyright 1999-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author  Luc Saillard <luc.saillard@fr.alcove.com>
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @author  Jan Schneider <jan@horde.org>
 * @author  Michael J Rubinsky <mrubinsk@horde.org>
 * @package Kronolith
 */
class Kronolith_Driver_Resource_Sql extends Kronolith_Driver
{
    /**
     * The main event storage driver.
     *
     * @var Kronolith_Driver
     */
    protected $_driver;

    /**
     * The class name of the event object to instantiate.
     *
     * @var string
     */
    protected $_eventClass = 'Kronolith_Event_Resource_Sql';

    /**
     * Attempts to open a connection to the SQL server.
     *
     * @throws Kronolith_Exception
     */
    public function initialize()
    {
        if (empty($this->_params['db'])) {
            throw new InvalidArgumentException('Missing required Horde_Db_Adapter instance');
        }
        try {
            $this->_db = $this->_params['db'];
        } catch (Horde_Exception $e) {
            throw new Kronolith_Exception($e);
        }

        $this->_params = array_merge(array(
            'table' => 'kronolith_resources'
        ), $this->_params);

        $this->_driver = Kronolith::getDriver();
    }

    /**
     * Selects a calendar as the currently opened calendar.
     *
     * @param string $calendar  A calendar identifier.
     */
    public function open($calendar)
    {
        $this->calendar = $calendar;
        $this->_driver->open($calendar);
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
    public function listEvents(Horde_Date $startDate = null,
                               Horde_Date $endDate = null,
                               array $options = array())
    {
        $events = $this->_driver->listEvents($startDate, $endDate, $options);
        $results = array();

        foreach ($events as $period_key => $period) {
            foreach ($period as $event_id => $event) {
                $results[$period_key][$event_id] = $this->_buildResourceEvent($event);
            }
        }

        return $results;
    }

    protected function _buildResourceEvent($driver_event)
    {
        $resource_event = new $this->_eventClass($this);
        $resource_event->fromDriver($driver_event->toProperties());
        $resource_event->id = $driver_event->id;
        $resource_event->uid = $driver_event->uid;
        $resource_event->calendar = $this->calendar;

        return $resource_event;
    }

    /**
     * Get an event or events with the given UID value.
     *
     * @param string $uid       The UID to match
     * @param array $calendars  A restricted array of calendar ids to search
     * @param boolean $getAll   Return all matching events?
     *
     * @return Kronolith_Event
     * @throws Kronolith_Exception
     * @throws Horde_Exception_NotFound
     */
    public function getByUID($uid, $calendars = null, $getAll = false)
    {
       $event = new $this->_eventClass($this);
       $driver_event = $this->_driver->getByUID($uid, $calendars, $getAll);
       $event->fromDriver($driver_event->toProperties());
       $event->id = $driver_event->id;
       $event->uid = $driver_event->uid;
       $event->calendar = $this->calendar;
       return $event;
    }

    /**
     * @throws Kronolith_Exception
     * @throws Horde_Exception_NotFound
     */
    public function getEvent($eventId = null)
    {
        if (!strlen($eventId)) {
            $event = new $this->_eventClass($this);
            $event->calendar = $this->calendar;

            return $event;
        }

        $driver_event = $this->_driver->getEvent($eventId);
        $event = $this->_buildResourceEvent($driver_event);

        return $event;
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
        return $this->_driver->saveEvent($event);
    }

    /**
     * Delete an event.
     *
     * Since this is the Kronolith_Resource's version of the event, if we
     * delete it, we must also make sure to remove it from the event that
     * it is attached to. Not sure if there is a better way to do this...
     *
     * @see lib/Driver/Kronolith_Driver_Sql#deleteEvent($eventId, $silent)
     * @throws Kronolith_Exception
     * @throws Horde_Exception_NotFound
     */
    public function deleteEvent($eventId, $silent = false)
    {
        $resource_event = new $this->_eventClass($this);
        $this->_driver->open($this->calendar);
        if ($eventId instanceof Kronolith_Event) {
            $deleteEvent = $eventId;
            $eventId = $deleteEvent->id;
        } else {
            $delete_event = $this->_driver->getEvent($eventId);
        }
        $resource_event->fromDriver($delete_event->toProperties());
        $resource_event->id = $eventId;
        $resource_event->uid = $delete_event->uid;
        $resource_event->calendar = $this->calendar;

        $uid = $delete_event->uid;
        $events = $this->_driver->getByUID($uid, null, true);
        foreach ($events as $e) {
            $resources = $e->getResources();
            if (count($resources)) {
                $r = $this->getResource($this->getResourceIdByCalendar($delete_event->calendar));
                $e->removeResource($r);
                $e->save();
            }
        }

        $this->_driver->deleteEvent($resource_event, $silent);
    }

    /**
     * Save or update a Kronolith_Resource
     *
     * @param Kronolith_Resource_Base $resource
     *
     * @return Kronolith_Resource object
     * @throws Kronolith_Exception
     */
    public function save(Kronolith_Resource_Base $resource)
    {
        if ($resource->getId()) {
            $query = 'UPDATE ' . $this->_params['table'] . ' SET resource_name = ?, '
                . 'resource_calendar = ? , resource_description = ?, '
                . 'resource_response_type = ?, resource_type = ?, '
                . 'resource_members = ?, resource_email = ? WHERE resource_id = ?';

            $values = array($this->convertToDriver($resource->get('name')),
                            $resource->get('calendar'),
                            $this->convertToDriver($resource->get('description')),
                            $resource->get('response_type'),
                            $resource->get('type'),
                            serialize($resource->get('members')),
                            $resource->get('email'),
                            $resource->getId());

            try {
                $this->_db->update($query, $values);
            } catch (Horde_Db_Exception $e) {
                throw new Kronolith_Exception($e);
            }
        } else {
            $query = 'INSERT INTO ' . $this->_params['table']
                . ' (resource_name, resource_calendar, '
                .  'resource_description, resource_response_type, '
                . ' resource_type, resource_members, resource_email)'
                . ' VALUES (?, ?, ?, ?, ?, ?, ?)';
            $values = array($this->convertToDriver($resource->get('name')),
                            $resource->get('calendar'),
                            $this->convertToDriver($resource->get('description')),
                            $resource->get('response_type'),
                            $resource->get('type'),
                            serialize($resource->get('members')),
                            $resource->get('email'));
            try {
                $id = $this->_db->insert($query, $values);
            } catch (Horde_Db_Exception $e) {
                throw new Kronolith_Exception($e);
            }
            $resource->setId($id);
        }

        return $resource;
    }

    /**
     * Removes a resource from storage, along with any events in the resource's
     * calendar.
     *
     * @param Kronolith_Resource $resource  The kronolith resource to remove
     *
     * @throws Kronolith_Exception
     */
    public function delete(Kronolith_Resource_Base $resource)
    {
        if (!$resource->getId()) {
            throw new Kronolith_Exception(_("Resource not valid."));
        }

        // Get group memberships and remove from group.
        $groups = $this->getGroupMemberships($resource->getId());
        foreach ($groups as $id) {
            $rg = $this->getResource($id);
            $members = $rg->get('members');
            unset($members[array_search($resource->getId(), $members)]);
            $rg->set('members', $members);
            $rg->save();
        }

        $query = 'DELETE FROM ' . $this->_params['table'] . ' WHERE calendar_id = ?';
        try {
            $this->_db->delete($query, array($resource->get('calendar')));
            $query = 'DELETE FROM ' . $this->_params['table'] . ' WHERE resource_id = ?';
            $this->_db->delete($query, array($resource->getId()));
        } catch (Horde_Db_Exception $e) {
            throw new Kronolith_Exception($e);
        }
    }

    /**
     * Obtain a Kronolith_Resource by the resource's id
     *
     * @param integer $id  The key for the Kronolith_Resource
     *
     * @return Kronolith_Resource_Base
     * @throws Kronolith_Exception
     */
    public function getResource($id)
    {
        $query = 'SELECT resource_id, resource_name, resource_calendar, '
            . 'resource_description, resource_response_type, resource_type, '
            . 'resource_members, resource_email FROM ' . $this->_params['table']
            . ' WHERE resource_id = ?';

        try {
            $results = $this->_db->selectOne($query, array($id));
        } catch (Horde_Db_Exception $e) {
            throw new Kronolith_Exception($e);
        }
        if (!count($results)) {
            throw new Kronolith_Exception('Resource not found');
        }

        $class = 'Kronolith_Resource_' . $results['resource_type'];
        if (!class_exists($class)) {
            throw new Kronolith_Exception('Could not load the class definition for ' . $class);
        }

        return new $class($this->_fromDriver($results));
    }

    /**
     * Obtain the resource id associated with the given calendar uid.
     *
     * @param string $calendar  The calendar's uid.
     *
     * @return integer  The Kronolith_Resource id.
     * @throws Kronolith_Exception
     */
    public function getResourceIdByCalendar($calendar)
    {
        $query = 'SELECT resource_id FROM ' . $this->_params['table']
            . ' WHERE resource_calendar = ?';
        try {
            $result = $this->_db->selectValue($query, array($calendar));
        } catch (Horde_Db_Exception $e) {
            throw new Kronolith_Exception($e);
        }
        if (empty($result)) {
            throw new Kronolith_Exception('Resource not found');
        }

        return $result;
    }

    /**
     * Determine if the provided calendar id represents a resource's calendar.
     *
     * @param string $calendar  The calendar identifier to check.
     *
     * @return boolean
     */
    public function isResourceCalendar($calendar)
    {
        $query = 'SELECT count(*) FROM ' . $this->_params['table']
            . ' WHERE resource_calendar = ?';
        try {
            return $this->_db->selectValue($query, array($calendar)) > 0;
        } catch (Horde_Db_Exception $e) {
            throw new Kronolith_Exception($e);
        }
    }

    /**
     * Return a list of Kronolith_Resources
     *
     * Right now, all users have Horde_Perms::READ, but only system admins have
     * Horde_Perms::EDIT | Horde_Perms::DELETE
     *
     * @param integer $perms   A Horde_Perms::* constant.
     * @param array $filter    A hash of field/values to filter on.
     * @param string $orderby  Field to order results by. Null for no ordering.
     *
     * @return an array of Kronolith_Resource objects.
     * @throws Kronolith_Exception
     */
    public function listResources($perms = Horde_Perms::READ, array $filter = array(), $orderby = null)
    {
        if (($perms & (Horde_Perms::EDIT | Horde_Perms::DELETE)) &&
            !$GLOBALS['registry']->isAdmin()) {
            return array();
        }

        $query = 'SELECT resource_id, resource_name, resource_calendar, resource_description,'
            . ' resource_response_type, resource_type, resource_members, resource_email FROM '
            . $this->_params['table'];
        if (count($filter)) {
            $clause = ' WHERE ';
            $i = 0;
            $c = count($filter);
            foreach (array_keys($filter) as $field) {
                $clause .= 'resource_' . $field . ' = ?' . (($i++ < ($c - 1)) ? ' AND ' : '');
            }
            $query .= $clause;
        }

        if (!empty($orderby)) {
            $query .= ' ORDER BY resource_' . $orderby;
        }

        try {
            $results = $this->_db->selectAll($query, $filter);
        } catch (Horde_Db_Exception $e) {
            throw new Kronolith_Exception($e);
        }
        $return = array();
        foreach ($results as $row) {
            $class = 'Kronolith_Resource_' . $row['resource_type'];
            $return[$row['resource_id']] = new $class($this->_fromDriver(array_merge(array('resource_id' => $row['resource_id']), $row)));
        }

        return $return;
    }

    /**
     * Obtain the group id for each group the specified resource is a member of.
     *
     * @param integer $resource_id  The resource id to check for.
     *
     * @return array  An array of group ids.
     * @throws Kronolith_Exception
     */
    public function getGroupMemberships($resource_id)
    {
        $groups = $this->listResources(Horde_Perms::READ, array('type' => Kronolith_Resource::TYPE_GROUP));
        $in = array();
        foreach ($groups as $group) {
            $members = $group->get('members');
            if (array_search($resource_id, $members) !== false) {
                $in[] = $group->getId();
            }
        }

        return $in;
    }

    /**
     * Converts a value from the driver's charset to the default
     * charset.
     *
     * @param mixed $value  A value to convert.
     *
     * @return mixed  The converted value.
     */
    public function convertFromDriver($value)
    {
        return Horde_String::convertCharset($value, $this->_params['charset'], 'UTF-8');
    }

    /**
     * Converts a value from the default charset to the driver's
     * charset.
     *
     * @param mixed $value  A value to convert.
     *
     * @return mixed  The converted value.
     */
    public function convertToDriver($value)
    {
        return Horde_String::convertCharset($value, 'UTF-8', $this->_params['charset']);
    }

    /**
     * Convert from driver keys and charset to Kronolith keys and charset.
     *
     * @param array $params  The key/values to convert.
     *
     * @return array  An array of converted values.
     */
    protected function _fromDriver(array $params)
    {
        $return = array();
        foreach ($params as $field => $value) {
            if ($field == 'resource_name' || $field == 'resource_description') {
                $value = $this->convertFromDriver($value);
            } elseif ($field == 'resource_members') {
                $value = @unserialize($value);
            }

            $return[str_replace('resource_', '', $field)] = $value;
        }

        return $return;
    }

    /**
     * Helper function to update an existing event's tags to tagger storage.
     *
     * @param Kronolith_Event $event  The event to update
     */
    protected function _updateTags(Kronolith_Event $event)
    {
        // noop
    }

    /**
     * Helper function to add tags from a newly creted event to the tagger.
     *
     * @param Kronolith_Event $event  The event to save tags to storage for.
     */
    protected function _addTags(Kronolith_Event $event)
    {
        // noop
    }

    protected function _handleNotifications($event, $action)
    {
        // noop
    }

}
