<?php
/**
 * Kronolith_Resource implementation to represent a group of similar resources.
 *
 * Copyright 2009 The Horde Project (http://www.horde.org/)
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
            return $this->_selectedResoruce->get($property);
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
        $resources = unserialize($this->get('members'));

        /* Iterate over all resources until one with no conflicts is found */
        $conflict = false;
        foreach ($resources as $resource_id) {
            $resource = $this->_driver->getResource($resource_id);
            $busy = Kronolith::listEvents($start, $end, array($resource->get('calendar')));
            if ($busy instanceof PEAR_Error) {
                throw new Horde_Exception($busy->getMessage());
            }

            /* No events at all during time period for requested event */
            if (!count($busy)) {
                $this->_selectedResource = $resource;
                return true;
            }

            /* Check for conflicts, ignoring the conflict if it's for the
             * same event that is passed. */
            if (!is_array($event)) {
                $uid = $event->getUID();
            } else {
                $uid = 0;
            }

            foreach ($busy as $events) {
                foreach ($events as $e) {
                    if (!($e->hasStatus(Kronolith::STATUS_CANCELLED) ||
                          $e->hasStatus(Kronolith::STATUS_FREE)) &&
                         $e->getUID() !== $uid) {

                         if (!($e->start->compareDateTime($end) >= 1 ||
                             $e->end->compareDateTime($start) <= -1)) {

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
        if (empty($this->_selectedResource)) {
            $this->isFree($event);
        }
//        /* Make sure it's not already attached. */
//        $uid = $event->getUID();
//        $existing = $driver->getByUID($uid, array($this->get('calendar')));
//        if (!($existing instanceof PEAR_Error)) {
//            /* Already attached, just update */
//            $existing->fromiCalendar($event->toiCalendar(new Horde_iCalendar('2.0')));
//            $existing->status = $event->status;
//            $existing->save();
//        } else {
//            /* Create a new event */
//            $e = $driver->getEvent();
//            $e->setCalendar($this->get('calendar'));
//            $e->fromiCalendar($event->toiCalendar(new Horde_iCalendar('2.0')));
//            $e->save();
//        }
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
        throw new Horde_Exception('Unsupported');
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