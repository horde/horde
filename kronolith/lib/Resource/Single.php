<?php
/**
 * Kronolith_Resource implementation to represent a single resource.
 *
 */
class Kronolith_Resource_Single extends Kronolith_Resource_Base
{
    /**
     * Should this take an event, or a time range?
     *
     * @param $startTime
     * @param $endTime
     * @return unknown_type
     */
    public function isFree($startTime, $endTime)
    {

    }

    /**
     * Adds $event to this resource's calendar - thus blocking the time
     * for any other event.
     *
     * @param $event
     *
     * @return unknown_type
     * @throws Horde_Exception
     */
    public function attachToEvent($event)
    {
        /* Get a driver for this resource's calendar */
        $driver = Kronolith::getDriver(null, $this->calendar_id);
        /* Make sure it's not already attached. */
        $uid = $event->getUID();
        $existing = $driver->getByUID($uid, array($this->calendar_id));
        if (!($existing instanceof PEAR_Error)) {
            throw new Horde_Exception(_("Already Exists"));
        }

        /* Create a new event */
        $e = $driver->getEvent();
        $e->setCalendar($this->calendar_id);
        $e->fromiCalendar($event->toiCalendar($iCal = new Horde_iCalendar('2.0')));
        $e->save();
    }

    /**
     * Remove this event from resource's calendar
     *
     * @param $event
     * @return unknown_type
     */
    public function detachFromEvent($event)
    {

    }

    /**
     * Obtain the freebusy information for this resource.  Takes into account
     * if this is a group of resources or not. (Returns the cumulative FB info
     * for all the resources in the group.
     * @return unknown_type
     */
    public function getFreeBusy()
    {

    }

}