<?php
/**
 * The IMP_Notification_Listener_StatusMobile:: class extends the
 * Horde_Notification_Listener_Mobile:: class to display IMAP alert
 * notifications.
 *
 * Copyright 1999-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package Horde_Notification
 */
class IMP_Notification_Listener_StatusMobile extends Horde_Notification_Listener_Mobile
{
    /**
     * Returns all status message if there are any on the 'status' message
     * stack.
     *
     * @param array &$messageStack  The stack of messages.
     * @param array $options        An array of options.
     */
    public function notify(&$messageStack, $options = array())
    {
        /* Display IMAP alerts. */
        foreach ($GLOBALS['imp_imap']->ob()->alerts() as $alert) {
            $GLOBALS['notification']->push($alert, 'horde.warning');
        }

        parent::notify($messageStack, $options);
    }

}
