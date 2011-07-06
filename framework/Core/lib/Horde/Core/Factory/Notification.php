<?php
/**
 * @category Horde
 * @package  Core
 */
class Horde_Core_Factory_Notification extends Horde_Core_Factory_Injector
{
    protected $_notify;

    public function create(Horde_Injector $injector)
    {
        if (isset($this->_notify)) {
            return $this->_notify;
        }

        $this->_notify = new Horde_Notification_Handler(
            new Horde_Core_Notification_Storage_Session()
        );

        $this->_notify->addType('default', '*', 'Horde_Core_Notification_Event_Status');
        $this->_notify->addType('status', 'horde.*', 'Horde_Core_Notification_Event_Status');

        $this->_notify->addDecorator(new Horde_Notification_Handler_Decorator_Alarm($injector->getInstance('Horde_Core_Factory_Alarm'), $GLOBALS['registry']->getAuth()));
        $this->_notify->addDecorator(new Horde_Core_Notification_Handler_Decorator_Hordelog());

        try {
            foreach ($GLOBALS['registry']->listApps(null, false, Horde_Perms::READ) as $app) {
                if ($GLOBALS['registry']->isAuthenticated(array('app' => $app, 'notransparent' => true))) {
                    try {
                        $GLOBALS['registry']->callAppMethod($app, 'setupNotification', array('args' => array($this->_notify), 'noperms' => true));
                    } catch (Exception $e) {
                        continue;
                    }
                }
            }
        } catch (Horde_Exception $e) {
        }

        return $this->_notify;
    }
}
