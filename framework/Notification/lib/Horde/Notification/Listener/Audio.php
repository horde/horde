<?php
/**
 * The Horde_Notification_Listener_Audio:: class provides functionality for
 * inserting embedded audio notifications from the stack into the page.
 *
 * Copyright 2005-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Jason M. Felice <jason.m.felice@gmail.com>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
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
