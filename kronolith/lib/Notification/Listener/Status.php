<?php
/**
 * The Kronolith_Notification_Listener_Status:: class extends the
 * Horde_Notification_Listener_Status:: class to return all kronolith messages
 * instead of printing them.
 *
 * Copyright 2005-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Jan Schneider <jan@horde.org>
 * @package Horde_Notification
 */
class Kronolith_Notification_Listener_Status extends Horde_Notification_Listener_Status
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
        return (substr($type, 0, 10) == 'kronolith.') || parent::handles($type);
    }

}
