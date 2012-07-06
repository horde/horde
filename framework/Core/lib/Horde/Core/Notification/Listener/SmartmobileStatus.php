<?php
/**
 * Provides a method to display notification messages in Smartmobile views
 * (using the mobile jquery framework).
 *
 * Copyright 2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Core
 */
class Horde_Core_Notification_Listener_SmartmobileStatus extends Horde_Notification_Listener_Status
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
        if (empty($events)) {
            return;
        }

        // TODO: Need to add delay to allow browser to correctly populate
        // location of original page, or else closing notification reloads
        // previous page (Ticket #11103).
        $GLOBALS['injector']->getInstance('Horde_PageOutput')->addInlineScript(array(
            '$(function() {HordeMobile.showNotifications(' .
            Horde_Serialize::serialize($events, Horde_Serialize::JSON) .
            ');});'
        ));
    }

}
