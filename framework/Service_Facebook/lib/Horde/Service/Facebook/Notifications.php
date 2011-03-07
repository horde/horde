<?php
/**
 * Notifications methods for Horde_Service_Faceboook
 *
 * Copyright 2009-2011 The Horde Project (http://www.horde.org)
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
     * @throws Horde_Service_Facebook_Exception
     * @return array An assoc array of notification count objects for
     *               'messages', 'pokes' and 'shares', a uid list of
     *               'friend_requests', a gid list of 'group_invites',
     *               and an eid list of 'event_invites'
     */
    public function &get()
    {
        // Session key is *required*
        if (!$skey = $this->_facebook->auth->getSessionKey()) {
            throw new Horde_Service_Facebook_Exception('session_key is required',
                                               Horde_Service_Facebook_ErrorCodes::API_EC_SESSION_REQUIRED);
        }
        return $this->_facebook->callMethod('facebook.notifications.get',
            array('session_key' => $skey));
    }

    /**
     * Sends a notification to the specified users.
     *
     * @param mixed $to_ids         Either an array of uids or a string
     *                              delimited list of uids.
     * @param string $notification  A FBML string for the notification.
     * @param string $type          Either 'user_to_user' or 'app_to_user'
     *
     * @throws Horde_Service_Facebook_Exception
     *
     * @return string A comma separated list of successful recipients
     */
    public function &send($to_ids, $notification, $type)
    {
        // Session key is *required*
        if (!$this->_facebook->auth->getSessionKey()) {
            throw new Horde_Service_Facebook_Exception('session_key is required',
                                               Horde_Service_Facebook_ErrorCodes::API_EC_SESSION_REQUIRED);
        }

        return $this->_facebook->callMethod('facebook.notifications.send',
            array('to_ids' => $to_ids,
                  'notification' => $notification,
                  'type' => $type,
                  'session_key' => $this->_facebook->auth->getSessionKey()));
    }

    /**
     * Sends an email to the specified user of the application.
     *
     * @param string $recipients comma-separated ids of the recipients
     * @param string $subject    subject of the email
     * @param string $text       (plain text) body of the email
     * @param string $fbml       fbml markup for an html version of the email
     *
     * @throws Horde_Service_Facebook_Exception
     * @return string  A comma separated list of successful recipients
     * @error
     *    API_EC_PARAM_USER_ID_LIST
     */
    public function &sendEmail($recipients, $subject, $text, $fbml)
    {
        // Session key is *required*
        if (!$this->_facebook->auth->getSessionKey()) {
            throw new Horde_Service_Facebook_Exception('session_key is required',
                                               Horde_Service_Facebook_ErrorCodes::API_EC_SESSION_REQUIRED);
        }
        return $this->_facebook->callMethod('facebook.notifications.sendEmail',
            array('recipients' => $recipients,
                  'subject' => $subject,
                  'text' => $text,
                  'fbml' => $fbml,
                  'session_key' => $this->_facebook->auth->getSessionKey()));
    }

}