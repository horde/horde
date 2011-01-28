<?php
/**
 * The Horde_Notification_Listener:: class provides functionality for
 * displaying messages from the message stack as a status line.
 *
 * Copyright 2001-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @package  Notification
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
     * Key is the type, value is the default class name of the Event type to
     * use.
     *
     * @var array
     */
    protected $_handles = array();

    /**
     * Does this listener handle a certain type of message?
     *
     * @param string $type  The message type in question.
     *
     * @return mixed  False if this listener does not handle, the default
     *                event class if it does handle the type.
     */
    public function handles($type)
    {
        if (isset($this->_handles[$type])) {
            return $this->_handles[$type];
        }

        /* Search for '*' entries. */
        foreach (array_keys($this->_handles) as $key) {
            if ((substr($key, -1) == '*') &&
                (strpos($type, substr($key, 0, -1)) === 0)) {
                return $this->_handles[$key];
            }
        }

        return false;
    }

    /**
     * Adds message type handler.
     *
     * @param string $type   The type identifier.
     * @param string $class  A classname.
     */
    public function addType($type, $class)
    {
        $this->_handles[$type] = $class;
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
     * @param array $events   The list of events to handle.
     * @param array $options  An array of options.
     */
    abstract public function notify($events, $options = array());

}
