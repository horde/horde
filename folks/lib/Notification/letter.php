<?php
/**
 * Folks Notification Class.
 *
 * Copyright Obala d.o.o. (www.obala.si)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Duck <duck@obala.net>
 * @package Folks
 */
class Folks_Notification_letter extends Folks_Notification {

    /**
     * Returns method human name
     */
    public function getName()
    {
        return $GLOBALS['registry']->get('name', 'letter');
    }

    /**
     * Checks if a driver is available for a certain notification type
     *
     * @param string $type Notification type
     *
     * @return boolean
     */
    public function isAvailable($type)
    {
        if ($type == 'friends') {
            return $GLOBALS['registry']->hasMethod('users/getFriends');
        }

        return true;
    }

    /**
     * Notify user
     *
     * @param mixed  $user        User or array of users to send notification to
     * @param string $subject     Subject of message
     * @param string $body        Body of message
     * @param array  $attachments Attached files
     *
     * @return true on succes, PEAR_Error on failure
     */
    public function notify($user, $subject, $body, $attachments = array())
    {
        if (empty($user)) {
            return true;
        }

        return $GLOBALS['registry']->callByPackage(
            'letter', 'sendMessage', array($user,
                                           array('title' => $subject,
                                                 'content' => $body,
                                                 'attachments' => $attachments)));
    }

    /**
     * Notify user's friends
     *
     * @param mixed  $user        User or array of users to send notification to
     * @param string $subject     Subject of message
     * @param string $body        Body of message
     * @param array  $attachments Attached files
     *
     * @return true on succes, PEAR_Error on failure
     */
    public function notifyFriends($user, $subject, $body, $attachments = array())
    {
        $friends = $GLOBALS['registry']->call('users/getFriends');
        return $this->notify($friends, $subject, $body, $attachments);
    }
}
