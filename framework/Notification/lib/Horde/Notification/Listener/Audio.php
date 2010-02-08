<?php
/**
 * The Horde_Notification_Listener_Audio:: class provides functionality for
 * inserting embedded audio notifications from the stack into the page.
 *
 * Copyright 2005-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Jason M. Felice <jason.m.felice@gmail.com>
 * @package Horde_Notification
 */
class Horde_Notification_Listener_Audio extends Horde_Notification_Listener
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->_handles = array(
            'audio' => ''
        );
        $this->_name = 'audio';
    }

    /**
     * Outputs the embedded audio code if there are any messages on the
     * 'audio' message stack.
     *
     * @param array &$messageStack  The stack of messages.
     * @param array $options        An array of options (not used).
     */
    public function notify(&$messageStack, $options = array())
    {
        if (count($messageStack)) {
            while ($message = array_shift($messageStack)) {
                echo $this->getMessage($message);
            }
        }
    }

    /**
     * Outputs one message.
     *
     * @param array $message  One message hash from the stack.
     * @param array $options  An array of options (not used).
     *
     * @return text  The message representation.
     */
    public function getMessage($message, $options = array())
    {
        $event = $this->getEvent($message);
        return '<embed src="' . htmlspecialchars($event->getMessage()) .
               '" width="0" height="0" autostart="true" />';
    }

}
