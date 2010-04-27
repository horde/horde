<?php
/**
 * The Ajax status class provides a method to display Growler messages using
 * the KronolithCore javascript notification framework.
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package Kronolith
 */
class Kronolith_Notification_Listener_AjaxStatus extends Horde_Notification_Listener_Status
{
    /**
     * Outputs the status line if there are any messages on the 'status'
     * message stack.
     *
     * @param array $events   The list of events to handle.
     * @param array $options  An array of options. Not used.
     */
    public function notify($events, $options = array())
    {
        Horde::addInlineScript(array(
            'if (window.KronolithCore || parent.KronolithCore) { (window.KronolithCore || parent.KronolithCore).showNotifications(' . Horde_Serialize::serialize($events, Horde_Serialize::JSON) . ') }'
        ), 'dom');
    }

}
