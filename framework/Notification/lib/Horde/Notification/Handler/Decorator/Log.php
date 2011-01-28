<?php
/**
 * The Log Decorator logs error events when they are pushed on the stack.
 *
 * Copyright 2001-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @package  Notification
 */
class Horde_Notification_Handler_Decorator_Log
extends Horde_Notification_Handler_Decorator_Base
{
    /**
     * The log handler.
     *
     * @var object
     */
    private $_logger;

    /**
     * Constructor.
     *
     * @param object $logger  The log handler. The provided instance is
     *                        required to implement the debug() function. You
     *                        should be able to use a common Logger here (PEAR
     *                        Log, Horde_Log_Logger, or Zend_Log).
     */
    public function __construct($logger)
    {
        $this->_logger  = $logger;
    }

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
        $this->_logger->debug($event->message);
    }

}
