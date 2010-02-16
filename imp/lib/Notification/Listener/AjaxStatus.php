<?php
/**
 * The Ajax status class provides a method to display Growler messages using
 * the DimpCore javascript notification framework.
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package IMP
 */
class IMP_Notification_Listener_AjaxStatus extends Horde_Notification_Listener_Status
{
    /**
     * Outputs the status line if there are any messages on the 'status'
     * message stack.
     *
     * @param array $events   The list of events to handle.
     * @param array $options  An array of options:
     * <pre>
     * 'mobile' - (Horde_Mobile) The mobile object to send status lines to.
     * </pre>
     */
    public function notify($events, $options = array())
    {
        Horde::addInlineScript(array(
            'var ajax_dc = window.DimpCore || parent.DimpCore',
            'if (ajax_dc) { ajax_dc.showNotifications(' . Horde_Serialize::serialize($events, Horde_Serialize::JSON) . ') }'
        ), 'dom');
    }

}
