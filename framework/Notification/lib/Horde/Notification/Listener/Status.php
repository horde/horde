<?php
/**
 * The Horde_Notification_Listener_Status:: class provides functionality for
 * displaying messages from the message stack as a status line.
 *
 * Copyright 2001-2009 The Horde Project (http://www.horde.org/)
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
        $this->_handles = array(
            'horde.error' => array('alerts/error.png', _("Error")),
            'horde.success' => array('alerts/success.png', _("Success")),
            'horde.warning' => array('alerts/warning.png', _("Warning")),
            'horde.message' => array('alerts/message.png', _("Message")),
            'horde.alarm' => array('alerts/alarm.png', _("Alarm"))
        );
    }

    /**
     * Outputs the status line if there are any messages on the 'status'
     * message stack.
     *
     * @param array &$messageStack  The stack of messages.
     * @param array $options        An array of options.
     */
    public function notify(&$messageStack, $options = array())
    {
        if (count($messageStack)) {
            echo '<ul class="notices">';
            while ($message = array_shift($messageStack)) {
                $message = $this->getMessage($message);
                $message = preg_replace('/^<p class="notice">(.*)<\/p>$/', '<li>$1</li>', $message);
                echo $message;
            }
            echo '</ul>';
        }
    }

    /**
     * Outputs one message.
     *
     * @param array $message  One message hash from the stack.
     */
    public function getMessage($message)
    {
        $event = $this->getEvent($message);
        $text = $event->getMessage();

        if (!in_array('content.raw', $this->getFlags($message))) {
            $text = htmlspecialchars($text);
        }

        return '<li>' . Horde::img($this->_handles[$message['type']][0], $this->_handles[$message['type']][1], '', $GLOBALS['registry']->getImageDir('horde')) . $text . '</li>';
    }

}
