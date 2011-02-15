<?php
/**
 * Define the functions needed for a Decorator instance.
 *
 * Copyright 2001-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @package  Notification
 */
class Horde_Notification_Handler_Decorator_Base
{
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
    }

    /**
     * Listeners are handling their messages.
     *
     * @param array $options  An array containing display options for the
     *                        listeners (see Horde_Notification_Handler for
     *                        details).
     *
     * @throws Horde_Notification_Exception
     */
    public function notify($options)
    {
    }

}
