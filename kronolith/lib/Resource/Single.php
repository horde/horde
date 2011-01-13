<?php
/**
 * Kronolith_Resource implementation to represent a single resource.
 *
 * Copyright 2009-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Kronolith
 */
class Kronolith_Resource_Single extends Kronolith_Resource_Base
{
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

        /* Fetch events. */
        $busy = Kronolith::listEvents($start, $end, array($this->get('calendar')));

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
     * @param $event
     *
     * @throws Kronolith_Exception
     */
    public function addEvent($event)
    {
        /* Get a driver for this resource's calendar */
        $driver = $this->getDriver();

        /* Make sure it's not already attached. */
        $uid = $event->uid;
        try {
            $existing = $driver->getByUID($uid, array($this->get('calendar')));
            /* Already attached, just update */
            $this->_copyEvent($event, $existing);
            $result = $existing->save();
        } catch (Horde_Exception_NotFound $ex) {
            /* Create a new event */
            $e = $driver->getEvent();
            $this->_copyEvent($event, $e);
            $result = $e->save();
        }
    }

    /**
     * Remove this event from resource's calendar
     *
     * @param $event
     *
     * @throws Kronolith_Exception
     * @throws Horde_Exception_NotFound
     */
    public function removeEvent($event)
    {
        $driver = Kronolith::getDriver('Resource', $this->get('calendar'));
        $re = $driver->getByUID($event->uid, array($this->get('calendar')));
        $driver->deleteEvent($re->id);
    }

    /**
     * Obtain the freebusy information for this resource.
     *
     * @return unknown_type
     */
    public function getFreeBusy($startstamp = null, $endstamp = null, $asObject = false)
    {
        $vfb = Kronolith_Freebusy::generate('resource_' . $this->get('calendar'), $startstamp, $endstamp, $asObject);
        $vfb->removeAttribute('ORGANIZER');
        $vfb->setAttribute('ORGANIZER', $this->get('name'));

        return $vfb;
    }

    public function setId($id)
    {
        if (empty($this->_id)) {
            $this->_id = $id;
        } else {
            throw new Horde_Exception('Resource already exists. Cannot change the id.');
        }
    }

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
    private function _copyEvent($from, &$to)
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
        $to->start = $from->start;
        $to->end = $from->end;
        $to->durMin = $from->durMin;
        $to->allday = $from->allday;
        $to->recurrence = $from->recurrence;
        $to->initialized = true;
    }

}