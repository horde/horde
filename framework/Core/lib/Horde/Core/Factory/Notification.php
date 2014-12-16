<?php
/**
 * @category Horde
 * @package  Core
 */
class Horde_Core_Factory_Notification extends Horde_Core_Factory_Injector
{
    /**
     * Singleton instance.
     *
     * @var Horde_Notification_Handler
     */
    protected $_notify;

    /**
     */
    public function create(Horde_Injector $injector)
    {
        global $registry;

        if (isset($this->_notify)) {
            return $this->_notify;
        }

        $this->_notify = new Horde_Core_Notification_Handler(
            new Horde_Core_Notification_Storage_Session()
        );

        $this->_notify->addType('default', '*', 'Horde_Core_Notification_Event_Status');
        $this->_notify->addType('status', 'horde.*', 'Horde_Core_Notification_Event_Status');

        $this->_notify->addDecorator(new Horde_Notification_Handler_Decorator_Alarm($injector->getInstance('Horde_Core_Factory_Alarm'), $registry->getAuth()));
        $this->_notify->addDecorator(new Horde_Core_Notification_Handler_Decorator_Hordelog());

        return $this->_notify;
    }

    /**
     * @deprecated
     */
    public function addApplicationHandlers()
    {
        return $this->_notify->attachAllAppHandlers();
    }

}
