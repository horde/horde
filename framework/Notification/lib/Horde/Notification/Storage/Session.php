<?php
/**
 * A class that stores notifications in the session.
 *
 * @category Horde
 * @package  Notification
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Notification
 */

/**
 * A class that stores notifications in the session.
 *
 * Copyright 2009-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category Horde
 * @package  Notification
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Notification
 */
class Horde_Notification_Storage_Session
implements Horde_Notification_Storage_Interface
{
    /**
     * The stack name.
     *
     * @var string
     */
    protected $_stack;

    /**
     * Constructor.
     *
     * @param string $stack  The name of the notification stack.
     */
    public function __construct($stack)
    {
        $this->_stack = $stack;

        /* Make sure the message stack is registered in the session. */
        if (!isset($_SESSION[$this->_stack])) {
            $_SESSION[$this->_stack] = array();
        }
    }

    /**
     * Return the given stack by reference from the notification store.
     *
     * @param string $key  The key for the data.
     *
     * @return mixed  The notification data stored for the given key.
     */
    public function &get($key)
    {
        return $_SESSION[$this->_stack][$key];
    }

    /**
     * Set the given stack in the notification store.
     *
     * @param string $key   The key for the data.
     * @param mixed $value  The data.
     */
    public function set($key, $value)
    {
        $_SESSION[$this->_stack][$key] = $value;
    }

    /**
     * Is the given stack present in the notification store?
     *
     * @param string $key  The key of the data.
     *
     * @return boolean  True if the element is set, false otherwise.
     */
    public function exists($key)
    {
        return isset($_SESSION[$this->_stack][$key]);
    }

    /**
     * Unset the given stack in the notification store.
     *
     * @param string $key  The key of the data.
     */
    public function clear($key)
    {
        unset($_SESSION[$this->_stack][$key]);
    }

    /**
     * Store a new event for the given listener stack.
     *
     * @param string $listener                    The event will be stored for
     *                                            this listener.
     * @param Horde_Notification_Event $event  The event to store.
     */
    public function push($listener, Horde_Notification_Event $event)
    {
        /* No need to serialize() ourselves - PHP's session handling does
         * this automatically. */
        $_SESSION[$this->_stack][$listener][] = $event;
    }

}
