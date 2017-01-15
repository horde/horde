<?php
/**
 * Utility class for dealing with Kronolith_Resource objects
 *
 * Copyright 2009-2017 Horde LLC (http://www.horde.org/)
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

   /**
    * Adds a new resource to storage
    *
    * @param array $info            The resource array.
    *   - name: (string)            The resource name.
    *   - desc: (string)            The resource description.
    *   - email: (string)           An email address for the resource, if
    *                               needed.
    *   - response_type: (integer)  The RESPONSETYPE_* constant.
    *   - group: (boolean)          Flag resource as a group.
    *   - members: (array)          An array of resource ids if this is a group.
    *
    * @return Kronolith_Resource_Single
    */
    public static function addResource(array $info)
    {
        global $injector, $registry;

        $kronolith_shares = $injector->getInstance('Kronolith_Shares');
        try {
            $share = $kronolith_shares->newShare(
                $registry->getAuth(),
                strval(new Horde_Support_Randomid()),
                $info['name']
            );
        } catch (Horde_Share_Exception $e) {
            throw new Kronolith_Exception($e);
        }

        $share->set('desc', $info['desc']);
        //$share->set('email', $info['email']);
        if (isset($info['response_type'])) {
            $share->set('response_type', $info['response_type']);
        }
        $share->set('calendar_type', Kronolith::SHARE_TYPE_RESOURCE);

        if (!empty($info['group'])) {
            $share->set('isgroup', true);
            $share->set('members', serialize($info['members']));
            $resource = new Kronolith_Resource_Group(array('share' => $share));
        } else {
            $resource = new Kronolith_Resource_Single(array('share' => $share));
        }

        $driver = Kronolith::getDriver('Resource');
        return $driver->save($resource);
    }


    /**
     * Handles checking for resource availability or cancellation.
     *
     * @param Kronolith_Event $event  The event whose resources we are saving.
     *
     * @return array  An array of accepted resource objects.
     */
    public static function checkResources($event)
    {
        $accepted_resources = array();

        // Don't waste time with resource acceptance if the status is cancelled,
        // the event will be removed from the resource calendar anyway.
        if ($event->status != Kronolith::STATUS_CANCELLED) {
            foreach (array_keys($event->getResources()) as $id) {
                /* Get the resource and protect against infinite recursion in
                 * case someone is silly enough to add a resource to it's own
                 * event.*/
                $resource = Kronolith::getDriver('Resource')->getResource($id);
                $rcal = $resource->get('calendar');
                if ($rcal == $event->calendar) {
                    continue;
                }
                Kronolith::getDriver('Resource')->open($rcal);

                /* Lock the resource and get the response */
                if ($resource->get('response_type') == Kronolith_Resource::RESPONSETYPE_AUTO) {
                    $haveLock = $resource->lock();
                    if (!$haveLock) {
                        throw new Kronolith_Exception(sprintf(_("The resource \"%s\" was locked. Please try again."), $resource->get('name')));
                    }
                } else {
                    $haveLock = false;
                }
                $response = $resource->getResponse($event);

                /* Remember accepted resources so we can add the event to their
                 * calendars. Otherwise, clear the lock. */
                if ($response == Kronolith::RESPONSE_ACCEPTED) {
                    $accepted_resources[] = $resource;
                } elseif ($haveLock) {
                    $resource->unlock();
                }

                if ($response == Kronolith::RESPONSE_DECLINED && $event->uid) {
                    $r_driver = Kronolith::getDriver('Resource');
                    $r_event = $r_driver->getByUID($event->uid, array($resource->get('calendar')));
                    $r_driver->deleteEvent($r_event, true, true);
                }

                /* Add the resource to the event */
                $event->addResource($resource, $response);
            }
        } else {
            // If event is cancelled, and actually exists, we need to mark it
            // as cancelled in resource calendar.
            foreach (array_keys($event->getResources()) as $id) {
                $resource = Kronolith::getDriver('Resource')->getResource($id);
                $rcal = $resource->get('calendar');
                if ($rcal == $event->calendar) {
                    continue;
                }
                try {
                    Kronolith::getDriver('Resource')->open($rcal);
                    $resource->addEvent($this);
                } catch (Exception $e) {
                }
            }
        }

        return $accepted_resources;
    }

    public static function getResource($id)
    {
        $r_share = $GLOBALS['kronolith_shares']->getShare($id);
        $resource = new Kronolith_Resource_Single(array('share' => $r_share));
    }

}
