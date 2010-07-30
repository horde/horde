<?php
/**
 * Utility class for dealing with Kronolith_Resource objects
 *
 * Copyright 2009-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Michael J. Rubinsky <mrubinsk@horde.org>
 *
 * @package Kronolith
 */
class Kronolith_Resource
{
    /* ResponseType constants */
    const RESPONSETYPE_NONE = 0;
    const RESPONSETYPE_AUTO = 1;
    const RESPONSETYPE_ALWAYS_ACCEPT = 2;
    const RESPONSETYPE_ALWAYS_DECLINE = 3;
    const RESPONSETYPE_MANUAL = 4;

    /* Resource Type constants */
    const TYPE_SINGLE = 'Single';
    const TYPE_GROUP = 'Group';

   /**
    * Adds a new resource to storage
    *
    * @param Kronolith_Resource $resource
    *
    * @return unknown_type
    */
    static public function addResource($resource)
    {
        // Create a new calendar id.
        $calendar = 'resource_' . uniqid(mt_rand());
        $resource->set('calendar', $calendar);
        $driver = Kronolith::getDriver('Resource');

        return $driver->save($resource);
    }

    /**
     * Determine if the provided calendar id represents a resource's calendar.
     *
     * @param string $calendar  The calendar identifier to check.
     *
     * @return boolean
     */
    static public function isResourceCalendar($calendar)
    {
        if (strncmp($calendar, 'resource_', 9) === 0) {
            return true;
        }

        return false;
    }

}
