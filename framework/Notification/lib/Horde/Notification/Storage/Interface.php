<?php
/**
 * An interface describing a storage location for notification messages.
 *
 * @category Horde
 * @package  Notification
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Notification
 */

/**
 * An interface describing a storage location for notification messages.
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
interface Horde_Notification_Storage_Interface
{
    /**
     * Return the given stack from the notification store.
     *
     * @param string $key  The key for the data.
     *
     * @return mixed  The notification data stored for the given key.
     */
    public function get($key);

    /**
     * Set the given stack in the notification store.
     *
     * @param string $key   The key for the data.
     * @param mixed $value  The data.
     */
    public function set($key, $value);

    /**
     * Is the given stack present in the notification store?
     *
     * @param string $key  The key of the data.
     *
     * @return boolean  True if the element is set, false otherwise.
     */
    public function exists($key);

    /**
     * Unset the given stack in the notification store.
     *
     * @param string $key  The key of the data.
     */
    public function clear($key);

    /**
     * Store a new event.
     *
     * @param string $listener                 The event will be stored for
     *                                         this listener.
     * @param Horde_Notification_Event $event  The event to store.
     */
    public function push($listener, Horde_Notification_Event $event);

}
