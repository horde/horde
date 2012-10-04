<?php
/**
 * Kronolith_Resource implementation to represent a single resource.
 *
 * Copyright 2009-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author Michael J Rubinsky <mrubinsk@horde.org>
 * @package Kronolith
 */
class Kronolith_Resource_Single extends Kronolith_Resource_Base
{
    /**
     * Determine if the resource is free during the time period for the
     * supplied event.
     *
     * @param Kronolith_Event $event  The event to check availability for.
     *
     * @return boolean
     * @throws Kronolith_Exception
     */
    public function isFree(Kronolith_Event $event)
    {
        if (is_array($event)) {
            $start = $event['start'];
            $end = $event['end'];
        } else {
            $start = $event->start;
            $end = $event->end;
        }

        /* Fetch Events */
        $busy = Kronolith::getDriver('Resource', $this->get('calendar'))
            ->listEvents($start, $end, array('show_recurrence' => true));

        /* No events at all during time period for requested event */
        if (!count($busy)) {
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

                     // Comparing to zero allows the events to start at the same
                     // the previous event ends.
                     if (!($e->start->compareDateTime($end) >= 0) &&
                         !($e->end->compareDateTime($start) <= 0)) {

                        return false;
                     }
                }
            }
        }

        return true;
    }

    /**
     * Adds $event to this resource's calendar or updates the current entry
     * of the event in the calendar.
     *
     * @param Kronolith_Event $event  The event to add to the resource. Note
     *                                this is the base driver event.
     *
     * @throws Kronolith_Exception
     */
    public function addEvent(Kronolith_Event $event)
    {
        // Get a Kronolith_Driver_Resource object.
        $resource_driver = $this->getDriver();
        $uid = $event->uid;
        // Ensure it's not already attached.
        try {
            $resource_event = $resource_driver->getByUID($uid, array($this->get('calendar')));
            $this->_copyEvent($event, $resource_event);
            $resource_event->save();
        } catch (Horde_Exception_NotFound $ex) {
            // New event
            $resource_event = $resource_driver->getEvent();
            $this->_copyEvent($event, $resource_event);
            $resource_event->save();
        }
    }

    /**
     * Remove this event from resource's calendar
     *
     * @param Kronolith_Event $event  The event to remove.
     *
     * @throws Kronolith_Exception
     * @throws Horde_Exception_NotFound
     */
    public function removeEvent(Kronolith_Event $event)
    {
        $resource_driver = $this->getDriver();
        $resource_event = $resource_driver->getByUID($event->uid, array($this->get('calendar')));
        $resource_driver->deleteEvent($resource_event->id);
    }

    /**
     * Obtain the freebusy information for this resource.
     *
     * @return mixed string|Horde_Icalendar_Vfreebusy  The Freebusy object or
     *                                                 the iCalendar text.
     */
    public function getFreeBusy($startstamp = null, $endstamp = null, $asObject = false, $json = false)
    {
        $vfb = Kronolith_Freebusy::generate($this->get('calendar'), $startstamp, $endstamp, true);
        $vfb->removeAttribute('ORGANIZER');
        $vfb->setAttribute('ORGANIZER', $this->get('name'));

        if ($json) {
            return Kronolith_Freebusy::toJson($vfb);
        } elseif (!$asObject) {
            return $vfb->exportvCalendar();
        }

        return $vfb;
    }

    /**
     * Sets the current resource's id. Must not be an existing resource.
     *
     * @param integer $id  The id for this resource
     *
     * @throws Kronolith_Exception
     */
    public function setId($id)
    {
        if (empty($this->_id)) {
            $this->_id = $id;
        } else {
            throw new Kronolith_Exception('Resource already exists. Cannot change the id.');
        }
    }

    /**
     * Get ResponseType for this resource.
     *
     * @return integer  The response type for this resource. A
     *                  Kronolith_Resource::RESPONSE_TYPE_* constant.
     */
    public function getResponseType()
    {
        return $this->get('response_type');
    }

    /**
     * Utility function to copy select event properties from $from to $to in
     * order to add an event to the resource calendar.
     *
     * @param Kronolith_Event $from
     * @param Kronolith_Event $to
     *
     * @return void
     */
    private function _copyEvent(Kronolith_Event $from, Kronolith_Event &$to)
    {
        $to->uid = $from->uid;
        $to->title = $from->title;
        $to->location = $from->location;
        $to->status = $from->status;
        $to->description = $from->description;
        $to->url = $from->url;
        $to->tags = $from->tags;
        $to->geoLocation = $from->geoLocation;
        $to->first = $from ->first;
        $to->last = $from->last;
        $to->start = clone $from->start;
        $to->end = clone $from->end;
        $to->durMin = $from->durMin;
        $to->allday = $from->allday;
        $to->recurrence = clone $from->recurrence;
        $to->initialized = true;
        $to->timezone = $from->timezone;
    }

}