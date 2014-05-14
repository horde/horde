<?php
/**
 * Copyright 2001-2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category  Horde
 * @copyright 2001-2014 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Core
 */
/**
 * Define the functions needed for a Decorator instance.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2001-2014 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Core
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
     * @todo Declare as final.
     *
     * @param Horde_Notification_Event $event  Event object.
     * @param array $options                   Additional options (see
     *                                         Horde_Notification_Handler for
     *                                         details).
     */
    public function push(Horde_Notification_Event $event, $options)
    {
        global $registry;

        try {
            $pushed = $registry->pushApp($this->_app, array(
                'check_perms' => true,
                'logintasks' => false
            ));
        } catch (Exception $e) {
            return;
        }

        $this->_push($event, $options);

        if ($pushed) {
            $registry->popApp();
        }
    }

    /**
     * @see   push()
     * @since 2.12.0
     */
    protected function _push(Horde_Notification_Event $event, $options)
    {
        parent::push($event, $options);
    }

    /**
     * Listeners are handling their messages.
     *
     * @todo Declare as final.
     *
     * @param Horde_Notification_Handler $handler    The base handler object.
     * @param Horde_Notification_Listener $listener  The Listener object that
     *                                               is handling its messages.
     *
     * @throws Horde_Notification_Exception
     */
    public function notify(
        Horde_Notification_Handler $handler,
        Horde_Notification_Listener $listener
    )
    {
        global $registry;

        $error = null;

        try {
            $pushed = $registry->pushApp($this->_app, array(
                'check_perms' => true,
                'logintasks' => false
            ));
        } catch (Exception $e) {
            return;
        }

        try {
            $this->_notify($handler, $listener);
        } catch (Exception $e) {
            $error = $e;
        }

        if ($pushed) {
            $registry->popApp();
        }

        if ($error) {
            throw $error;
        }
    }

    /**
     * @see   notify()
     * @since 2.12.0
     */
    protected function _notify(
        Horde_Notification_Handler $handler,
        Horde_Notification_Listener $listener
    )
    {
        parent::notify($handler, $listener);
    }

}
