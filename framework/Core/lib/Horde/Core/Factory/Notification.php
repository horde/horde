<?php
/**
 * @category Horde
 * @package  Core
 */
class Horde_Core_Factory_Notification
{
    public function create(Horde_Injector $injector)
    {
        $notify = Horde_Notification::singleton();

        $notify->addType('default', '*', 'Horde_Core_Notification_Status');
        $notify->addType('status', 'horde.*', 'Horde_Core_Notification_Status');

        $notify->addDecorator(new Horde_Notification_Handler_Decorator_Alarm($injector->getInstance('Horde_Alarm'), $GLOBALS['registry']->getAuth()));
        $notify->addDecorator(new Horde_Core_Notification_Hordelog());

        return $notify;
    }
}
