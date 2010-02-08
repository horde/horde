<?php
/**
 * The Horde_Notification_Event:: class defines a single notification event.
 *
 * Copyright 2002-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Hans Lellelid <hans@velum.net>
 * @package Horde_Notification
 */
class Horde_Notification_Event
{
    /**
     * The message being passed.
     *
     * @var string
     */
    protected $_message = '';

    /**
     * If passed, sets the message for this event.
     *
     * @param string $message  The text message for this event.
     */
    public function __construct($message = null)
    {
        if (!is_null($message)) {
            $this->setMessage($message);
        }
    }

    /**
     * Sets the text message for this event.
     *
     * @param string $message  The text message to display.
     */
    public function setMessage($message)
    {
        $this->_message = $message;
    }

    /**
     * Gets the text message for this event.
     *
     * @return string  The text message to display.
     */
    public function getMessage()
    {
        return $this->_message;
    }

}
