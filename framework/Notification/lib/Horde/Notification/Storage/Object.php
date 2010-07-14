<?php
/**
 * A class that stores notifications in an object.
 *
 * @category Horde
 * @package  Notification
 * @author   Jan Schneider <jan@horde.org>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Notification
 */

/**
 * A class that stores notifications in an object.
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category Horde
 * @package  Notification
 * @author   Jan Schneider <jan@horde.org>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Notification
 */
class Horde_Notification_Storage_Object
implements Horde_Notification_Storage_Interface
{
    /**
     * Holds the notifications pushed into this storage object.
     *
     * @var array
     */
    public $notifications = array();

    /**
     * Return the given stack by reference from the notification store.
     *
     * @param string $key  The key for the data.
     *
     * @return mixed  The notification data stored for the given key.
     */
    public function &get($key)
    {
        return $this->notifications[$key];
    }

    /**
     * Set the given stack in the notification store.
     *
     * @param string $key   The key for the data.
     * @param mixed $value  The data.
     */
    public function set($key, $value)
    {
        $this->notifications[$key] = $value;
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
        return isset($this->notifications[$key]);
    }

    /**
     * Unset the given stack in the notification store.
     *
     * @param string $key  The key of the data.
     */
    public function clear($key)
    {
        unset($this->notifications[$key]);
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
        $this->notifications[$listener][] = $event;
    }

}
