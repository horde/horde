<?php
/**
 * The Hordelog Decorator logs error events via Horde::log().
 *
 * Copyright 2001-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @package  Core
 */
class Horde_Core_Notification_Handler_Decorator_Hordelog
extends Horde_Notification_Handler_Decorator_Base
{
    /**
     */
    protected function _push(Horde_Notification_Event $event, $options)
    {
        Horde::log($event->message, 'DEBUG');
    }

}
