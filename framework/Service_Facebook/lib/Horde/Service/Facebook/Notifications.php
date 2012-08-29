<?php
/**
 * Notifications methods for Horde_Service_Faceboook
 *
 * Copyright 2009-2012 Horde LLC (http://www.horde.org/)
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @package Service_Facebook
 */
class Horde_Service_Facebook_Notifications extends Horde_Service_Facebook_Base
{
    /**
     * Returns the outstanding notifications for the session user.
     *
     * @return array An assoc array of notification count objects for
     *               'messages', 'pokes' and 'shares', a uid list of
     *               'friend_requests', a gid list of 'group_invites',
     *               and an eid list of 'event_invites'
     * @throws Horde_Service_Facebook_Exception
     */
    public function get()
    {
        // Session key is *required*
        if (!$this->_facebook->auth->getSessionKey()) {
            throw new Horde_Service_Facebook_Exception(
                'session_key is required',
                Horde_Service_Facebook_ErrorCodes::API_EC_SESSION_REQUIRED);
        }

        return $this->_facebook->callMethod('facebook.notifications.get');
    }

}