<?php
/**
 * The Kronolith_Driver_Resource class implements the Kronolith_Driver API for
 * storing resource calendars in a SQL backend.
 *
 * Copyright 1999-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Luc Saillard <luc.saillard@fr.alcove.com>
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @author  Jan Schneider <jan@horde.org>
 * @author  Michael J Rubinsky <mrubinsk@horde.org>
 * @package Kronolith
 */
class Kronolith_Driver_Resource extends Kronolith_Driver_Sql
{
    /**
     * The class name of the event object to instantiate.
     *
     * @var string
     */
    protected $_eventClass = 'Kronolith_Event_Resource';

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
    public function deleteEvent($event, $silent = false)
    {
        $delete_event = $this->getEvent($event);

        $uid = $delete_event->uid;
        $driver = Kronolith::getDriver();
        $events = $driver->getByUID($uid, null, true);
        foreach ($events as $e) {
            $resources = $e->getResources();
            if (count($resources)) {
                // found the right entry
                $r = $this->getResource($this->getResourceIdByCalendar($delete_event->calendar));
                $e->removeResource($r);
                $e->save();
            }
        }
        $this->open($delete_event->calendar);
        parent::deleteEvent($event, $silent);
    }

    /**
     * Save or update a Kronolith_Resource
     *
     * @param Kronolith_Resource $resource
     *
     * @return Kronolith_Resource object
     * @throws Kronolith_Exception
     */
    public function save($resource)
    {
        if ($resource->getId()) {
            $query = 'UPDATE kronolith_resources SET resource_name = ?, '
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
                $result = $this->_db->update($query, $values);
            } catch (Horde_Db_Exception $e) {
                throw new Kronolith_Exception($e);
            }
        } else {
            $query = 'INSERT INTO kronolith_resources '
                . '(resource_name, resource_calendar, '
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
                $result = $this->_db->insert($query, $values);
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
    public function delete($resource)
    {
        if (!($resource instanceof Kronolith_Resource_Base) || !$resource->getId()) {
            throw new Kronolith_Exception(_("Resource not valid."));
        }

        $query = 'DELETE FROM ' . $this->_params['table'] . ' WHERE calendar_id = ?';
        try {
            $this->_db->delete($query, array($resource->get('calendar')));
            $query = 'DELETE FROM kronolith_resources WHERE resource_id = ?';
            $this->_db->delete($query, array($resource->getId()));
        } catch (Horde_Db_Exception $e) {
            throw new Kronolith_Exception($e);
        }
    }

    /**
     * Obtain a Kronolith_Resource by the resource's id
     *
     * @param int $id  The key for the Kronolith_Resource
     *
     * @return Kronolith_Resource_Base
     * @throws Kronolith_Exception
     */
    public function getResource($id)
    {
        $query = 'SELECT resource_id, resource_name, resource_calendar, '
            . 'resource_description, resource_response_type, resource_type, '
            . 'resource_members, resource_email FROM kronolith_resources '
            . 'WHERE resource_id = ?';

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
        $query = 'SELECT resource_id FROM kronolith_resources WHERE resource_calendar = ?';
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
     * Return a list of Kronolith_Resources
     *
     * Right now, all users have Horde_Perms::READ, but only system admins have
     * Horde_Perms::EDIT | Horde_Perms::DELETE
     *
     * @param integer $perms  A Horde_Perms::* constant.
     * @param array $filter   A hash of field/values to filter on.
     *
     * @return an array of Kronolith_Resource objects.
     * @throws Kronolith_Exception
     */
    public function listResources($perms = Horde_Perms::READ, $filter = array())
    {
        if (($perms & (Horde_Perms::EDIT | Horde_Perms::DELETE)) &&
            !$GLOBALS['registry']->isAdmin()) {
            return array();
        }

        $query = 'SELECT resource_id, resource_name, resource_calendar, resource_description, resource_response_type, resource_type, resource_members, resource_email FROM kronolith_resources';
        if (count($filter)) {
            $clause = ' WHERE ';
            $i = 0;
            $c = count($filter);
            foreach ($filter as $field => $value) {
                $clause .= 'resource_' . $field . ' = ?' . (($i++ < ($c - 1)) ? ' AND ' : '');
            }
            $query .= $clause;
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
     * Obtain the group id for each group the speciied resource is a member of.
     *
     * @param integer $resource_id  The resource id to check for.
     *
     * @return array of group ids.
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
     * Convert from driver keys and charset to Kronolith keys and charset.
     *
     * @param array $params  The key/values to convert.
     *
     * @return An array of converted values.
     */
    protected function _fromDriver($params)
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
    protected function _updateTags($event)
    {
        // noop
    }

    /**
     * Helper function to add tags from a newly creted event to the tagger.
     *
     * @param Kronolith_Event $event  The event to save tags to storage for.
     */
    protected function _addTags($event)
    {
        // noop
    }

    protected function _handleNotifications($event, $action)
    {
        // noop
    }

}
