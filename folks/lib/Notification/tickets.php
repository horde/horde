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
class Folks_Notification_tickets extends Folks_Notification {

    /**
     * Returns method human name
     */
    public function getName()
    {
        return $GLOBALS['registry']->get('name', 'whups');
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
        if (!$GLOBALS['registry']->hasInterface('tickets') ||
                $type == 'admins') {
            return false;
        }

        return false;
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
        global $registry;

        $info = array_merge($this->_params['ticket_params'],
                            array('summary' => $subject,
                                    'comment' => $body,
                                    'user_email' => $this->_getUserFromAddr()));

        $ticket_id = $registry->call('tickets/addTicket', array($info));

        if (empty($attachments) ||
            !$registry->hasMethod('tickets/addAttachment')) {
            return $result;
        }

        foreach ($attachments as $attachment) {
            $result = $registry->call(
                'tickets/addAttachment',
                        array('ticket_id' => $ticket_id,
                                'name' => $attachment['name'],
                                'data' => file_get_contents($attachment['file'])));
        }

        return true;
    }
}
