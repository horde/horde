<?php
/**
 * Base class for dealing with Kronolith_Resource objects. Handles basic
 * creation/deletion/listing by delegating to the underlying Kronolith_Driver
 * object.
 *
 * For now, assume SQL driver only. Could probably easily extend this to use
 * different backend drivers if/when support is added to those drivers for
 * resources.
 *
 * Copyright 2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Kronolith
 */
class Kronolith_Resource
{
    static protected $_driver;

    /**
     *
     * @return array of Kronolith_Resource objects
     */
    static public function listResources($params = array())
    {
        // Query kronolith_resource table for all(?) available resources?
        // maybe by 'type' or 'name'? type would be arbitrary?
        if (empty(self::$_driver)) {
            self::$_driver = Kronolith::getDriver('Sql');
        }

        $resources = self::$_driver->listResources($params);
        $return = array();
        foreach ($resources as $resource) {
            $return[] = new Kronolith_Resource_Single($resource);
        }

        return $return;
    }

    /**
     * Adds a new resource to storage
     *
     * @param Kronolith_Resource $resource
     * @return unknown_type
     */
    static public function addResource($resource)
    {
        // Create a new calendar id.
        $calendar = 'resource_' . hash('md5', microtime());
        $resource->calendar = $calendar;

        if (empty(self::$_driver)) {
            self::$_driver = Kronolith::getDriver('Sql');
        }

        return self::$_driver->saveResource($resource);
    }

    /**
     * Removes a resource from storage
     *
     * @param Kronolith_Resource $resource
     * @return boolean
     * @throws Horde_Exception
     */
    static public function removeResource($resource)
    {

    }

    static public function getResource($id)
    {
        $driver = Kronolith::getDriver('Sql');
        return new Kronolith_Resource_Single($driver->getResource($id));
    }

    static public function isResourceCalendar($calendar)
    {
        if (strncmp($calendar, 'resource_', 9) === 0) {
            return true;
        }
    }

}