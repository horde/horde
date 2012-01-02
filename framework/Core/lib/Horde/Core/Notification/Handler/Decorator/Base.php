<?php
/**
 * Define the functions needed for a Decorator instance.
 *
 * Copyright 2001-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Notification
 */
class Horde_Core_Notification_Handler_Decorator_Base extends Horde_Notification_Handler_Decorator_Base
{
    /**
     * The application name of this Decorator.
     *
     * @var string
     */
    protected $_app = 'horde';

    /**
     * Event is being added to the Horde message stack.
     *
     * @param Horde_Notification_Event $event  Event object.
     * @param array $options                   Additional options (see
     *                                         Horde_Notification_Handler for
     *                                         details).
     */
    public function push(Horde_Notification_Event $event, $options)
    {
        $pushed = $GLOBALS['registry']->pushApp($this->_app, array(
            'check_perms' => true,
            'logintasks' => false
        ));

        parent::push($event, $options);

        if ($pushed) {
            $GLOBALS['registry']->popApp();
        }
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
        $error = null;

        $pushed = $GLOBALS['registry']->pushApp($this->_app, array(
            'check_perms' => true,
            'logintasks' => false
        ));

        try {
            parent::notify($handler, $listener);
        } catch (Exception $e) {
            $error = $e;
        }

        if ($pushed) {
            $GLOBALS['registry']->popApp();
        }

        if ($error) {
            throw $error;
        }
    }

}
