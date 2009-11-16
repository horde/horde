<?php
/**
 * The Horde_Notification_Listener_Mobile:: class provides functionality for
 * displaying messages from the message stack on mobile devices.
 *
 * Copyright 2003-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @package Horde_Notification
 */
class Horde_Notification_Listener_Mobile extends Horde_Notification_Listener_Status
{
    /**
     * The Horde_Mobile:: object that status lines should be added to.
     *
     * @var Horde_Mobile
     */
    protected $_mobile;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->_handles = array(
            'horde.error'   => _("ERR"),
            'horde.success' => _("SUCCESS"),
            'horde.warning' => _("WARN"),
            'horde.message' => _("MSG")
        );
        $this->_name = 'status';
    }

    /**
     * Associate a Horde_Mobile:: object with the listener.
     *
     * @param Horde_Mobile  The Horde_Mobile:: object to send status lines to.
     */
    public function setMobileObject(&$mobile)
    {
        $this->_mobile = &$mobile;
    }

    /**
     * Outputs the status line if there are any messages on the 'mobile'
     * message stack.
     *
     * @param array &$messageStack  The stack of messages.
     * @param array $options        An array of options. Options: 'nospace'
     */
    public function notify(&$messageStack, $options = array())
    {
        if (!$this->_mobile) {
            $p = new Horde_Notification_Listener_Status();
            return $p->notify($messageStack, $options);
        }

        if (count($messageStack)) {
            while ($message = array_shift($messageStack)) {
                $this->getMessage($message);
            }
        }
    }

    /**
     * Outputs one message.
     *
     * @param array $message  One message hash from the stack.
     * @param array $options  An array of options (not used).
     */
    public function getMessage($message, $options = array())
    {
        if (!$this->_mobile) {
            $p = new Horde_Notification_Listener_Status();
            return $p->getMessage($message, $options);
        }

        $event = $this->getEvent($message);
        $this->_mobile->add(new Horde_Mobile_text($this->_handles[$message['type']] . ': ' . strip_tags($event->getMessage())));
    }

}
