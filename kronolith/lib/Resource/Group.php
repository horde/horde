<?php
/**
 * Kronolith_Resource implementation to represent a group of similar resources.
 *
 * Copyright 2009-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Kronolith
 */
class Kronolith_Resource_Group extends Kronolith_Resource_Base
{
    /**
     *
     * @var Kronolith_Driver_Resource
     */
    private $_driver;

    /**
     * Local cache for event that accepts the invitation.
     * @TODO: probably want to cache this in the session since we will typically
     *        need to do this twice: once when adding the resource to the
     *        attendees form, and once when actually saving the event.
     *
     * @var Kronolith_Resource_Single
     */
    private $_selectedResource;

    /**
     * Const'r
     *
     * @param $params
     *
     * @return Kronolith_Resource
     */
    public function __construct($params)
    {
        $params['resource_type'] = 'Group';
        parent::__construct($params);
        $this->_driver = $this->getDriver();
    }

    /**
     * Override the get method to see if we have a selected resource. If so,
     * return the resource's property value, otherwise, return the group's
     * property value.
     *
     * @param string $property  The property to get.
     *
     * @return mixed  The requested property's value.
     */
    public function get($property)
    {
        if (empty($this->_selectedResource)) {
            return parent::get($property);
        } else {
            return $this->_selectedResource->get($property);
        }
    }

    public function getId()
    {
        if (!empty($this->_selectedResource)) {
            return $this->_selectedResource->getId();
        } else {
            return parent::getId();
        }
    }

    /**
     * Determine if the resource is free during the time period for the
     * supplied event.
     *
     * @param mixed $event  Either a Kronolith_Event object or an array
     *                      containing start and end times.
     *
     *
     * @return boolean
     * @throws Kronolith_Exception
     */
    public function isFree($event)
    {
        if (is_array($event)) {
            $start = $event['start'];
            $end = $event['end'];
        } else {
            $start = $event->start;
            $end = $event->end;
        }

        /* Get all resources that are included in this category */
        $resources = $this->get('members');

        /* Iterate over all resources until one with no conflicts is found */
        foreach ($resources as $resource_id) {
            $conflict = false;
            $resource = $this->_driver->getResource($resource_id);
            $busy = Kronolith::listEvents($start, $end, array($resource->get('calendar')));

            /* No events at all during time period for requested event */
            if (!count($busy)) {
                $this->_selectedResource = $resource;
                return true;
            }

            /* Check for conflicts, ignoring the conflict if it's for the
             * same event that is passed. */
            if (!is_array($event)) {
                $uid = $event->uid;
            } else {
                $uid = 0;
            }

            foreach ($busy as $events) {
                foreach ($events as $e) {
                    if (!($e->status == Kronolith::STATUS_CANCELLED ||
                          $e->status == Kronolith::STATUS_FREE) &&
                         $e->uid !== $uid) {

                         if (!($e->start->compareDateTime($end) >= 0) &&
                             !($e->end->compareDateTime($start) <= 0)) {

                            // Not free, continue to the next resource
                            $conflict = true;
                            break;
                         }
                    }
                }
            }

            if (!$conflict) {
                /* No conflict detected for this resource */
                $this->_selectedResource = $resource;
                return true;
            }
        }

        /* No resource found without conflicts */
        return false;
    }

    /**
     * Adds $event to an available member resource's calendar.
     *
     * @param $event
     *
     * @return void
     */
    public function addEvent($event)
    {
        throw new Horde_Exception('Events should be added to the Single resource object, not directly to the Group object.');
    }

    /**
     * Remove this event from resource's calendar
     *
     * @param $event
     * @return unknown_type
     */
    public function removeEvent($event)
    {
        throw new Horde_Exception('Unsupported');
    }

    /**
     * Obtain the freebusy information for this resource.
     *
     * @return unknown_type
     */
    public function getFreeBusy($startstamp = null, $endstamp = null, $asObject = false)
    {
        throw new Horde_Exception('Unsupported');
    }

    public function setId($id)
    {
        if (empty($this->_id)) {
            $this->_id = $id;
        } else {
            throw new Horde_Exception('Resource already exists. Cannot change the id.');
        }
    }

    /**
     * Group resources only make sense for RESPONSETYPE_AUTO
     *
     * @see lib/Resource/Kronolith_Resource_Base#getResponseType()
     */
    public function getResponseType()
    {
        return Kronolith_Resource::RESPONSETYPE_AUTO;
    }

}