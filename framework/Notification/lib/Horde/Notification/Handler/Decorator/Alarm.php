<?php
/**
 * The Alarm Decorator notifies the alarm system to push its notifications on
 * the stack.
 *
 * Copyright 2001-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Notification
 */
class Horde_Notification_Handler_Decorator_Alarm
extends Horde_Notification_Handler_Decorator_Base
{
    /**
     * A Horde_Alarm instance.
     *
     * @var Horde_Core_Factory_Alarm
     */
    protected $_alarm;

    /**
     * The current user.
     *
     * @var string
     */
    protected $_user;

    /**
     * Initialize the notification system, set up any needed session
     * variables, etc.
     *
     * @param object $alarm  An alarm factory that implements create().
     * @param string $user   The current username.
     */
    public function __construct($alarm, $user)
    {
        $this->_alarm = $alarm;
        $this->_user = $user;
    }

    /**
     * Listeners are handling their messages.
     *
     * @param Horde_Notification_Handler $handler    The base handler object.
     * @param Horde_Notification_Listener $listener  The Listener object that
     *                                               is handling its messages.
     *
     * @throws Horde_Notification_Exception
     */
    public function notify(Horde_Notification_Handler $handler,
                           Horde_Notification_Listener $listener)
    {
        if ($listener instanceof Horde_Notification_Listener_Status) {
            try {
                // TODO: Use $handler
                $this->_alarm->create()->notify($this->_user);
            } catch (Horde_Alarm_Exception $e) {
                throw new Horde_Notification_Exception($e);
            }
        }
    }

}
