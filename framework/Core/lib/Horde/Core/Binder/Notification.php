<?php
class Horde_Core_Binder_Notification implements Horde_Injector_Binder
{
    public function create(Horde_Injector $injector)
    {
        $notify = Horde_Notification::singleton();

        $notify->addType('default', '*', 'Horde_Core_Notification_Status');
        $notify->addType('status', 'horde.*', 'Horde_Core_Notification_Status');

        $notify->addDecorator(new Horde_Notification_Handler_Decorator_Alarm(Horde_Alarm::factory(), Horde_Auth::getAuth()));
        $notify->addDecorator(new Horde_Notification_Handler_Decorator_Hordelog());

        return $notify;
    }

    public function equals(Horde_Injector_Binder $binder)
    {
        return false;
    }

}
