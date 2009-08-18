<?php
/**
 *
 */
class Kronolith_Resource
{
    /**
     *
     * @return unknown_type
     */
    static public function listResources($params)
    {
        // Query kronolith_resource table for all(?) available resources?
        // maybe by 'type' or 'name'? type would be arbitrary?
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
        $calendar = hash('md5', microtime());
        $resource->calendar_id = $calendar;

        $driver = Kronolith::getDriver('Sql');
        return $driver->saveResource($resource);
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

}