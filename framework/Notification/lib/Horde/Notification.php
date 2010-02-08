<?php
/**
 * The Horde_Notification:: class provides a subject-observer pattern for
 * raising and showing messages of different types and to different
 * listeners.
 *
 * Copyright 2001-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Jan Schneider <jan@horde.org>
 * @package Horde_Notification
 */
class Horde_Notification
{
    /**
     * Horde_Notification instances.
     *
     * @var Horde_Notification
     */
    static protected $_instances = array();

    /**
     * Returns a reference to the global notification handler, only
     * creating it if it doesn't already exist.
     *
     * This method must be invoked as:
     *   $notification = Horde_Notification::singleton([$stack]);
     *
     * @param string $stack  The name of the message stack to use.
     *
     * return Horde_Notification_Handler  The Horde Notification handler.
     */
    static public function singleton($stack = 'horde_notification_stacks')
    {
        if (!isset(self::$_instances[$stack])) {
            $storage = new Horde_Notification_Storage_Session($stack);

            $handler = new Horde_Notification_Handler_Base($storage);
            $handler = new Horde_Notification_Handler_Decorator_Hordelog($handler);
            if (!empty($GLOBALS['conf']['alarms']['driver'])) {
                $handler = new Horde_Notification_Handler_Decorator_Alarm(
                    $handler, Horde_Alarm::factory()
                );
            }
            self::$_instances[$stack] = $handler;
        }

        return self::$_instances[$stack];
    }

}
