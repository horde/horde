<?php
/**
 *
 */
class Kronolith_Resource
{
    /* ResponseType constants */
    const RESPONSETYPE_NONE = 0;
    const RESPONSETYPE_AUTO = 1;
    const RESPONSETYPE_ALWAYS_ACCEPT = 2;
    const RESPONSETYPE_ALWAYS_DECLINE = 3;
    const RESPONSETYPE_MANUAL = 4; // Send iTip - not sure how that would work without a user account for the resource.

    /**
     *
     */
    static public function factory($driver, $params)
    {

    }

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
        $calendar = 'resource_' . hash('md5', microtime());
        $resource->set('calendar', $calendar);
        $driver = Kronolith::getDriver('Resource');

        return $driver->save($resource);
    }

    /**
     * Return a list of resources that the current user has access to at the
     * specified permission level. Right now, all users have PERMS_READ, but
     * only system admins have PERMS_EDIT | PERMS_DELETE
     *
     * @return array of Kronolith_Resource objects
     */
    static public function listResources($perms = PERMS_READ, $params = array())
    {
        if (($perms & (PERMS_EDIT | PERMS_DELETE)) && !Horde_Auth::isAdmin()) {
            return array();
        }

        // Query kronolith_resource table for all(?) available resources?
        // maybe by 'type' or 'name'? type would be arbitrary?
        $driver = Kronolith::getDriver('Resource');
        return $driver->listResources($params);
    }

    /**
     * Determine if the provided calendar id represents a resource's calendar.
     *
     * @param $calendar
     * @return unknown_type
     */
    static public function isResourceCalendar($calendar)
    {
        if (strncmp($calendar, 'resource_', 9) === 0) {
            return true;
        }

        return false;
    }

    /**
     * Function to check availability and set response status for each resource
     * attached to the event.
     *
     * @return void
     */
    static public function checkResources($event)
    {
        foreach ($event->getResources() as $id => $resource) {

            /* Get the resource */
            $r = Kronolith::getDriver('Resource')->getResource($id);

            /* Determine if we have to calculate, or just auto-reply */
            $type = $r->getResponseType();
            switch($type) {
            case Kronolith_Resource::RESPONSETYPE_ALWAYS_ACCEPT:
                $r->addEvent($event);
                $event->addResource($r, Kronolith::RESPONSE_ACCEPTED);
                break;
            case Kronolith_Resource::RESPONSETYPE_AUTO:
                if ($r->isFree($event)) {
                    $r->addEvent($event);
                    $event->addResource($r, Kronolith::RESPONSE_ACCEPTED);
                } else {
                   $event->addResource($r, Kronolith::RESPONSE_DECLINED);
                }
                break;

            case Kronolith_Resource::RESPONSETYPE_ALWAYS_DECLINE:
                $event->addResource($r, Kronolith::RESPONSE_DECLINED);
                break;

            case Kronolith_Resource::RESPONSETYPE_NONE:
                $event->addResource($r, Kronolith::RESPONSE_NONE);
                break;

            case Kronolith_Resource::RESPONSETYPE_MANUAL:
                // Would be nice to be able to utilize iTips, but
                // no idea how that would work right now...resources are not
                // user accounts etc...for now, just set as NONE
                $event->addResource($r, Kronolith::RESONSE_NONE);
                break;
            }

        }
    }

}