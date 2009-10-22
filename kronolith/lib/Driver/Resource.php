<?php
/**
 * The Kronolith_Driver_Resource class implements the Kronolith_Driver API for
 * storing resource calendars in a SQL backend.
 *
 * Copyright 1999-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Luc Saillard <luc.saillard@fr.alcove.com>
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @author  Jan Schneider <jan@horde.org>
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
     */
    public function deleteEvent($event, $silent = false)
    {
        /* Since this is the Kronolith_Resource's version of the event, if we
         * delete it, we must also make sure to remove it from the event that
         * it is attached to. Not sure if there is a better way to do this...
         */
        $delete_event = $this->getEvent($event);
        $uid = $delete_event->getUID();
        $driver = Kronolith::getDriver();
        $events = $driver->getByUID($uid, null, true);
        foreach ($events as $e) {
            $resources = $e->getResources();
            if (count($resources)) {
                // found the right entry
                $r = $this->getResource($this->getResourceIdByCalendar($delete_event->getCalendar()));
                $e->removeResource($r);
                $e->save();
            }
        }

        parent::deleteEvent($event, $silent);
    }

    /**
     * Save or update a Kronolith_Resource
     *
     * @param Kronolith_Resource $resource
     *
     * @return Kronolith_Resource object
     * @throws Horde_Exception
     */
    public function save($resource)
    {
        if ($resource->getId()) {
            $query = 'UPDATE kronolith_resources SET resource_name = ?, resource_calendar = ? , resource_description = ?, resource_response_type = ?, resource_type = ?, resource_members = ? WHERE resource_id = ?';
            $values = array($this->convertToDriver($resource->get('name')), $resource->get('calendar'), $this->convertToDriver($resource->get('description')), $resource->get('response_type'), $resource->get('type'), serialize($resource->get('members')), $resource->getId());
            $result = $this->_write_db->query($query, $values);
            if ($result instanceof PEAR_Error) {
                Horde::logMessage($result, __FILE__, __LINE__, PEAR_LOG_ERR);
                throw new Horde_Exception($result);
            }
        } else {
            $query = 'INSERT INTO kronolith_resources (resource_id, resource_name, resource_calendar, resource_description, resource_response_type, resource_type, resource_members)';
            $cols_values = ' VALUES (?, ?, ?, ?, ?, ?, ?)';
            $id = $this->_db->nextId('kronolith_resources');
            $values = array($id, $this->convertToDriver($resource->get('name')), $resource->get('calendar'), $this->convertToDriver($resource->get('description')), $resource->get('response_type'), $resource->get('type'), serialize($resource->get('members')));
            $result = $this->_write_db->query($query . $cols_values, $values);
            if ($result instanceof PEAR_Error) {
                Horde::logMessage($result, __FILE__, __LINE__, PEAR_LOG_ERR);
                throw new Horde_Exception($result);
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
     * @return boolean
     * @throws Horde_Exception
     */
    public function delete($resource)
    {
        if (!($resource instanceof Kronolith_Resource_Base) || !$resource->getId()) {
            throw new Horde_Exception(_("Resource not valid."));
        }

        $query = 'DELETE FROM ' . $this->_params['table'] . ' WHERE calendar_id = ?';
        $result = $this->_write_db->query($query, array($resource->get('calendar')));
        if ($result instanceof PEAR_Error) {
            Horde::logMessage($result, __FILE__, __LINE__, PEAR_LOG_ERR);
            throw new Horde_Exception($result);
        }
        $query = 'DELETE FROM kronolith_resources WHERE resource_id = ?';
        $result = $this->_write_db->query($query, array($resource->getId()));
        if ($result instanceof PEAR_Error) {
            Horde::logMessage($result, __FILE__, __LINE__, PEAR_LOG_ERR);
            throw new Horde_Exception($result);
        }

        return true;
    }

    /**
     * Obtain a Kronolith_Resource by the resource's id
     *
     * @param int $id  The key for the Kronolith_Resource
     *
     * @return Kronolith_Resource_Single || Kronolith_Resource_Group
     * @throws Horde_Exception
     */
    public function getResource($id)
    {
        $query = 'SELECT resource_id, resource_name, resource_calendar, resource_description, resource_response_type, resource_type, resource_members FROM kronolith_resources WHERE resource_id = ?';
        $results = $this->_db->getRow($query, array($id), DB_FETCHMODE_ASSOC);
        if ($results instanceof PEAR_Error) {
            Horde::logMessage($result, __FILE__, __LINE__, PEAR_LOG_ERR);
            throw new Horde_Exception($results);
        }
        if (empty($results)) {
            throw new Horde_Exception('Resource not found');
        }

        $class = 'Kronolith_Resource_' . $results['resource_type'];
        if (!class_exists($class)) {
            throw new Horde_Exception(sprintf(_("Could not load the class definition for %s"), $class));
        }

        return new $class($this->_fromDriver($results));
    }

    /**
     * Obtain the resource id associated with the given calendar uid.
     *
     * @param string $calendar  The calendar's uid
     *
     * @return int  The Kronolith_Resource id
     * @throws Horde_Exception
     */
    public function getResourceIdByCalendar($calendar)
    {
        $query = 'SELECT resource_id FROM kronolith_resources WHERE resource_calendar = ?';
        $results = $this->_db->getOne($query, array($calendar));
        if ($results instanceof PEAR_Error) {
            Horde::logMessage($result, __FILE__, __LINE__, PEAR_LOG_ERR);
            throw new Horde_Exception($results);
        }
        if (empty($results)) {
            throw new Horde_Exception('Resource not found');
        }

        return $results;
    }

    /**
     * Return a list of Kronolith_Resources
     *
     * Right now, all users have PERMS_READ, but only system admins have
     * PERMS_EDIT | PERMS_DELETE
     *
     * @param int $perms     A PERMS_* constant.
     * @param array $filter  A hash of field/values to filter on.
     *
     * @return an array of Kronolith_Resource objects.
     */
    public function listResources($perms = PERMS_READ, $filter = array())
    {
        if (($perms & (PERMS_EDIT | PERMS_DELETE)) && !Horde_Auth::isAdmin()) {
            return array();
        }

        $query = 'SELECT resource_id, resource_name, resource_calendar, resource_description, resource_response_type, resource_type, resource_members FROM kronolith_resources';
        if (count($filter)) {
            $clause = ' WHERE ';
            $i = 0;
            $c = count($filter);
            foreach ($filter as $field => $value) {
                $clause .= 'resource_' . $field . ' = ?' . (($i++ < ($c - 1)) ? ' AND ' : '');
            }
            $query .= $clause;
        }

        $results = $this->_db->getAssoc($query, true, $filter, DB_FETCHMODE_ASSOC, false);
        if ($results instanceof PEAR_Error) {
            Horde::logMessage($results, __FILE__, __LINE__, PEAR_LOG_ERR);
            throw new Horde_Exception($results);
        }

        $return = array();
        foreach ($results as $key => $result) {
            $class = 'Kronolith_Resource_' . $result['resource_type'];
            $return[$key] = new $class($this->_fromDriver(array_merge(array('resource_id' => $key), $result)));
        }

        return $return;
    }

    /**
     * Obtain the group id for each group the speciied resource is a member of.
     *
     * @param integer $resource_id  The resource id to check for.
     *
     * @return array of group ids.
     */
    public function getGroupMemberships($resource_id)
    {
        $groups = $this->listResources(PERMS_READ, array('type' => Kronolith_Resource::TYPE_GROUP));
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
     * Remove all events owned by the specified user in all calendars.
     *
     * @todo Refactor: move to Kronolith::
     *
     * @param string $user  The user name to delete events for.
     *
     * @param mixed  True | PEAR_Error
     */
    public function removeUserData($user)
    {
        return PEAR::raiseError(_("Removing user data is not supported with the current calendar storage backend."));
    }

}
