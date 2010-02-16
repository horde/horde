<?php
/**
 * The Horde_Notification_Listener_Status:: class provides functionality for
 * displaying messages from the message stack as a status line.
 *
 * Copyright 2001-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Jan Schneider <jan@horde.org>
 * @package Horde_Notification
 */
class Horde_Notification_Listener_Status extends Horde_Notification_Listener
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->_handles['status'] = 'Horde_Notification_Event_Status';
        $this->_name = 'status';
    }

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
        if (!count($events)) {
            return;
        }

        if (!empty($options['mobile'])) {
            foreach ($events as $event) {
                $options['mobile']->add(new Horde_Mobile_Text(strip_tags($event)));
            }
            return;
        }

        echo '<ul class="notices">';

        foreach ($events as $event) {
            echo '<li>' . $event . '</li>';
        }

        echo '</ul>';
    }

}
