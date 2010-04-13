<?php
/**
 * The Hordelog Decorator logs error events via Horde::logMessage().
 *
 * Copyright 2001-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @package  Core
 */
class Horde_Core_Notification_Hordelog
extends Horde_Notification_Handler_Decorator_Base
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
        Horde::logMessage($event->message, 'DEBUG');
    }

}
