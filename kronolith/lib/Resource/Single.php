<?php
/**
 * Kronolith_Resource implementation to represent a single resource.
 *
 * Copyright 2009 The Horde Project (http://www.horde.org/)
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
     * @param Kronolith_Event $event
     *
     * @return boolean
     */
    public function isFree($event)
    {
        /* Fetch events. */
        $busy = Kronolith::listEvents($event->start, $event->end, array($this->calendar));
        if ($busy instanceof PEAR_Error) {
            throw new Horde_Exception($busy->getMessage());
        }

        /* No events at all during time period for requested event */
        if (!count($busy)) {
            return true;
        }

        /* Check for conflicts, ignoring the conflict if it's for the
         * same event that is passed. */
        $uid = $event->getUID();
        foreach ($busy as $events) {
            foreach ($events as $e) {
                if (!($e->hasStatus(Kronolith::STATUS_CANCELLED) ||
                      $e->hasStatus(Kronolith::STATUS_FREE)) &&
                     $e->getUID() !== $uid) {

                     if (!($e->start->compareDateTime($event->end) >= 1 ||
                         $e->end->compareDateTime($event->start) <= -1)) {
                        // $e starts after $event ends  OR $e ends before $event->start

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
     * @return void
     */
    public function addEvent($event)
    {
        /* Get a driver for this resource's calendar */
        $driver = Kronolith::getDriver('Resource', $this->calendar);

        /* Make sure it's not already attached. */
        $uid = $event->getUID();
        $existing = $driver->getByUID($uid, array($this->calendar));
        if (!($existing instanceof PEAR_Error)) {
            /* Already attached, just update */
            $existing->fromiCalendar($event->toiCalendar(new Horde_iCalendar('2.0')));
            $existing->status = $event->status;
            $existing->save();
        } else {
            /* Create a new event */
            $e = $driver->getEvent();
            $e->setCalendar($this->calendar);
            $e->fromiCalendar($event->toiCalendar(new Horde_iCalendar('2.0')));
            $e->save();
        }
    }

    /**
     * Remove this event from resource's calendar
     *
     * @param $event
     * @return unknown_type
     */
    public function removeEvent($event)
    {
        $driver = Kronolith::getDriver('Resource', $this->calendar);
        $re = $driver->getByUID($event->getUID(), array($this->calendar));
        if ($re instanceof PEAR_Error) {
            throw new Horde_Exception ($re->getMessage());
        }

        $driver->deleteEvent($re->getId());
    }

    /**
     * Obtain the freebusy information for this resource.
     *
     * @return unknown_type
     */
    public function getFreeBusy($startstamp = null, $endstamp = null, $asObject = false)
    {
        $vfb = Kronolith_Freebusy::generate($this->calendar, $startstamp, $endstamp, $asObject);
        $vfb->removeAttribute('ORGANIZER');
        $vfb->setAttribute('ORGANIZER', $this->name);

        return $vfb;
    }

    public function setId($id)
    {
        if (!empty($this->_id)) {
            $this->_id = $id;
        } else {
            throw new Horde_Exception(_("Resource already exists. Cannot change the id."));
        }
    }
}