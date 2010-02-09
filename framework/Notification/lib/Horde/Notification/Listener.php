<?php
/**
 * The Horde_Notification_Listener:: class provides functionality for
 * displaying messages from the message stack as a status line.
 *
 * Copyright 2001-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @package Horde_Notification
 */
abstract class Horde_Notification_Listener
{
    /**
     * The base type of this listener.
     *
     * @var string
     */
    protected $_name;

    /**
     * Array of message types that this listener handles.
     *
     * @var array
     */
    protected $_handles = array();

    /**
     * Does this listener handle a certain type of message?
     *
     * @param string $type  The message type in question.
     *
     * @return boolean  Whether this listener handles the type.
     */
    public function handles($type)
    {
        return isset($this->_handles[$type]);
    }

    /**
     * Return a unique identifier for this listener.
     *
     * @return string  Unique id.
     */
    public function getName()
    {
        return $this->_name;
    }

    /**
     * Outputs the status line, sends emails, pages, etc., if there
     * are any messages on this listener's message stack.
     *
     * @param array &$messageStack  The stack of messages.
     * @param array $options        An array of options.
     */
    abstract public function notify(&$messageStacks, $options = array());

    /**
     * Processes one message from the message stack.
     *
     * @param Horde_Notification_Event $event  One event object from the
     *                                         stack.
     * @param array $options                   An array of options.
     *
     * @return mixed  The formatted message.
     */
    abstract public function getMessage($event, $options = array());

}
