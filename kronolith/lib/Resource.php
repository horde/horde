<?php
/**
 * Utility class for dealing with Kronolith_Resource objects
 *
 * Copyright 2009-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
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
    * @param Kronolith_Resource_Base $resource
    *
    * @return unknown_type
    */
    static public function addResource(Kronolith_Resource_Base $resource)
    {
        // Create a new calendar id.
        $calendar = uniqid(mt_rand());
        $resource->set('calendar', $calendar);
        $driver = Kronolith::getDriver('Resource');

        return $driver->save($resource);
    }

}
