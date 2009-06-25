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
     * The notified message stack.
     *
     * @var array
     */
    protected $_notifiedStack = array();

    /**
     * Constructor.
     */
    public function __construct()
    {
        $image_dir = $GLOBALS['registry']->getImageDir('horde');

        $this->_handles = array(
            'horde.error' => array($image_dir . '/alerts/error.png', _("Error")),
            'horde.success' => array($image_dir . '/alerts/success.png', _("Success")),
            'horde.warning' => array($image_dir . '/alerts/warning.png', _("Warning")),
            'horde.message' => array($image_dir . '/alerts/message.png', _("Message")),
            'horde.alarm' => array($image_dir . '/alerts/alarm.png', _("Alarm"))
        );
        $this->_name = 'status';
    }

    /**
     * Outputs the status line if there are any messages on the 'status'
     * message stack.
     *
     * @param array &$messageStack  The stack of messages.
     * @param array $options        An array of options.
     * <pre>
     * 'store' - (boolean) If false, outputs message stack to page. If true,
     *                     stores the message stack for subsequent retrieval
     *                     via getStack(). DEFAULT: false
     * </pre>
     */
    public function notify(&$messageStack, $options = array())
    {
        if (!count($messageStack)) {
            return;
        }

        $store = !empty($options['store']);

        if (!$store) {
            echo '<ul class="notices">';
        }

        while ($message = array_shift($messageStack)) {
            $message = $this->getMessage($message, array('data' => $store));
            if ($store) {
                $this->_notifiedStack[] = $message;
            } else {
                echo preg_replace('/^<p class="notice">(.*)<\/p>$/', '<li>$1</li>', $message);
            }
        }

        if (!$store) {
            echo '</ul>';
        }
    }

    /**
     * Outputs one message.
     *
     * @param array $message  One message hash from the stack.
     * @param array $options  An array of options.
     * <pre>
     * 'data' - (boolean) If false, outputs HTML code. If true, outputs an
     *                    array of message information. DEFAULT: false
     * </pre>
     *
     * @return mixed  TODO
     */
    public function getMessage($message, $options = array())
    {
        $event = $this->getEvent($message);
        $text = $event->getMessage();

        if (!in_array('content.raw', $this->getFlags($message))) {
            $text = htmlspecialchars($text, ENT_COMPAT, NLS::getCharset());
        }

        return empty($options['data'])
            ? '<li>' . Horde::img($this->_handles[$message['type']][0], $this->_handles[$message['type']][1], '', '') . $text . '</li>'
            : array('message' => $text, 'type' => $message['type']);
    }

    /**
     * Returns all status messages stored via the 'store' option to notify().
     *
     * @param boolean $clear  Clear the entries off the internal stack?
     *
     * @return array  An array of data items.
     */
    public function getStack($clear = true)
    {
        $info = $this->_notifiedStack;
        if ($clear) {
            $this->_notifiedStack = array();
        }
        return $info;
    }

}
