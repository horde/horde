<?php
/**
 * The Kronolith_Driver_Resource class implements the Kronolith_Driver API for
 * storing resource calendars.
 *
 * Copyright 1999-2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author  Michael J Rubinsky <mrubinsk@horde.org>
 * @package Kronolith
 */
class Kronolith_Driver_Resource extends Kronolith_Driver
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
    protected $_eventClass = 'Kronolith_Event_Resource';

    /**
     *
     */
    public function initialize()
    {
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
        $json = !empty($options['json']);
        $options['json'] = false;
        $events = $this->_driver->listEvents($startDate, $endDate, $options);
        $results = array();

        foreach ($events as $period_key => $period) {
            foreach ($period as $event_id => $event) {
                $resource_event = $this->_buildResourceEvent($event);
                $results[$period_key][$event_id] = $json
                    ? $resource_event->toJson()
                    : $resource_event;
            }
        }

        return $results;
    }

    protected function _buildResourceEvent($driver_event)
    {
        $resource_event = new $this->_eventClass($this);
        $resource_event->fromDriver($driver_event->toProperties(true));
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
       $event->fromDriver($driver_event->toProperties(true));
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
     * @param string|Kronolith_Event_Resource $eventId  The ID of the event
     *                                                      to delete.
     * @param boolean $silent  Don't send notifications, used when deleting
     *                         events in bulk from maintenance tasks.
     * @param boolean $keep_bound  If true, does not remove the resource from
     *                             the bound event. @since 4.2.2
     *
     * @throws Kronolith_Exception
     * @throws Horde_Exception_NotFound
     */
    public function deleteEvent($eventId, $silent = false, $keep_bound = false)
    {
        if ($eventId instanceof Kronolith_Event_Resource) {
            $delete_event = $eventId;
            $eventId = $delete_event->id;
        } else {
            $delete_event = $this->getEvent($eventId);
        }

        if ($keep_bound) {
            return;
        }

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
        $this->_driver->open($this->calendar);
        $this->_driver->deleteEvent($delete_event, $silent);
    }

    /**
     * Save or update a Kronolith_Resource
     *
     * @param Kronolith_Resource_Base $resource
     *
     * @return Kronolith_Resource object
     * @throws Kronolith_Exception, Horde_Exception_PermissionDenied
     */
    public function save(Kronolith_Resource_Base $resource)
    {
        // @todo
        if (!$GLOBALS['registry']->isAdmin() &&
            !$GLOBALS['injector']->getInstance('Horde_Core_Perms')->hasAppPermission('resource_management')) {
            throw new Horde_Exception_PermissionDenied();
        }
        $resource->share()->save();

        return $resource;
    }

    /**
     * Removes a resource from storage, along with any events in the resource's
     * calendar.
     *
     * @param Kronolith_Resource_Base $resource  The kronolith resource to remove
     *
     * @throws Kronolith_Exception, Horde_Exception_PermissionDenied
     */
    public function delete($resource)
    {
        // @todo
        if (!$GLOBALS['registry']->isAdmin() &&
            !$GLOBALS['injector']->getInstance('Horde_Core_Perms')->hasAppPermission('resource_management')) {
            throw new Horde_Exception_PermissionDenied();
        }

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

        $this->_deleteResourceCalendar($resource->get('calendar'));

        try {
            $GLOBALS['injector']->getInstance('Kronolith_Shares')
                ->removeShare($resource->share());
        } catch (Horde_Share_Exception $e) {
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
        try {
            $share = $GLOBALS['injector']->getInstance('Kronolith_Shares')
                ->getShareById($id);
        } catch (Horde_Share_Exception $e) {
            throw new Kronolith_Exception($e);
        }

        $class = 'Kronolith_Resource_' . ($share->get('isgroup')
            ? 'Group'
            : 'Single');

        return new $class(array('share' => $share));
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
        try {
            $share = $GLOBALS['injector']->getInstance('Kronolith_Shares')
                ->getShare($calendar);
        } catch (Horde_Share_Exception $e) {
            throw new Kronolith_Exception($e);
        }

        return $share->getId();
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
        try {
            $share = $GLOBALS['injector']->getInstance('Kronolith_Shares')
                ->getShare($calendar);
        } catch (Horde_Share_Exception $e) {
            return false;
        }

        return $share->get('calendar_type') == Kronolith::SHARE_TYPE_RESOURCE;
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
    public function listResources(
        $perms = Horde_Perms::READ, array $filter = array(), $orderby = null
    )
    {
        global $injector, $registry;

        $attributes = array_merge(
            array('calendar_type' => Kronolith::SHARE_TYPE_RESOURCE),
            $filter
        );
        $shares = $injector->getInstance('Kronolith_Shares')->listShares(
            $registry->getAuth(),
            array('perm' => $perms, 'attributes' => $attributes)
        );
        $return = array();
        foreach ($shares as $share) {
            $class = 'Kronolith_Resource_'
                . ($share->get('isgroup') ? 'Group' : 'Single');
            $return[$share->getName()] = new $class(array('share' => $share));
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
        $groups = $this->listResources(Horde_Perms::READ, array('isgroup' => 1));
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
        return $this->_driver->convertFromDriver($value);
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
        return $this->_driver->convertToDriver($value);
    }

    /**
     * Delete the resource calendar
     *
     * @param string $calendar  The calendar id.
     */
    public function _deleteResourceCalendar($calendar)
    {
        $this->open($calendar);
        $events = $this->listEvents(null, null, array('cover_dates' => false));
        foreach ($events as $dayevents) {
            foreach ($dayevents as $event) {
                $this->deleteEvent($event, true);
            }
        }
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

    protected function _handleNotifications(Kronolith_Event $event, $action)
    {
        // noop
    }
}
