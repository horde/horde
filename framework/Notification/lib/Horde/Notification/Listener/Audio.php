<?php
/**
 * The Horde_Notification_Listener_Audio:: class provides functionality for
 * inserting embedded audio notifications from the stack into the page.
 *
 * Copyright 2005-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Jason M. Felice <jason.m.felice@gmail.com>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Notification
 */
class Horde_Notification_Listener_Audio extends Horde_Notification_Listener
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->_handles['audio'] = 'Horde_Notification_Event';
        $this->_name = 'audio';
    }

    /**
     * Outputs the embedded audio code if there are any messages on the
     * 'audio' message stack.
     *
     * @param array $events   The list of events to handle.
     * @param array $options  An array of options (not used).
     */
    public function notify($events, $options = array())
    {
        foreach ($events as $event) {
            echo '<embed src="' . htmlspecialchars(strval($event)) . '" width="0" height="0" autostart="true" />';
        }
    }

}
