<?php
/**
 * The IMP_Notification_Listener_StatusDimp:: class extends the
 * IMP_Notification_Listener_StatusImp:: class to return all dimp specific
 * messages instead of printing them.
 *
 * Copyright 2005-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Jan Schneider <jan@horde.org>
 * @package Horde_Notification
 */
class IMP_Notification_Listener_StatusDimp extends IMP_Notification_Listener_StatusImp
{
    /**
     * Handle every message of type dimp.*; otherwise delegate back to
     * the parent.
     *
     * @param string $type  The message type in question.
     *
     * @return boolean  Whether this listener handles the type.
     */
    public function handles($type)
    {
        return (substr($type, 0, 5) == 'dimp.') || parent::handles($type);
    }

    /**
     * Returns all status message if there are any on the 'status' message
     * stack.
     *
     * @param array &$messageStack  The stack of messages.
     * @param array $options        An array of options.
     */
    public function notify(&$messageStack, $options = array())
    {
        /* Don't capture notification messages if we are logging out are
         * accessing the options pages. */
        if (Auth::getAuth() && !strstr($_SERVER['PHP_SELF'], '/prefs.php')) {
            $options['store'] = true;
        }
        parent::notify($messageStack, $options);
    }

}
