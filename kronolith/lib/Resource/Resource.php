<?php
/**
 * Kronolith resources
 *
 */
class Kronolith_Resource
{
    protected $_params = array();

    public function __construct($params = array())
    {
        $this->_params = $params;
    }

    /**
     *
     *  Properties:
     * name        - Display name of resource.
     * calendar_id - The calendar associated with this resource.
     * category    - The category of this resource...an arbitrary label used
     *               to group multiple resources for the resource_group implementation
     * properties  - any other properties this resource may have?
     *               (max capacity of room, size of TV, whatever...)
     *               probably just for display, not sure how this would work
     *               if we wanted to be able to search since we are implementing these generically.
     *               Don't think we want a datatree-style attirbutes table for this.
     */
    public function __get($property)
    {
        return $this->_params[$property];
    }


    /**
     *
     * @param $startTime
     * @param $endTime
     * @return unknown_type
     */
    public function isFree($startTime, $endTime)
    {
        //Should this take an event also, instead?
    }

    /**
     * Adds $event to this resource's calendar - thus blocking the time
     * for any other event.
     *
     * @param $event
     * @return unknown_type
     */
    public function attachToEvent($event)
    {

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
     * @param $params
     * @return unknown_type
     */
    static public function addResource($params)
    {

    }

}