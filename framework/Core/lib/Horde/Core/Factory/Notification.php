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
        global $registry, $session;

        if (isset($this->_notify)) {
            return $this->_notify;
        }

        $auth = $registry->getAuth();

        $this->_notify = new Horde_Notification_Handler(
            new Horde_Core_Notification_Storage_Session()
        );

        $this->_notify->addType('default', '*', 'Horde_Core_Notification_Event_Status');
        $this->_notify->addType('status', 'horde.*', 'Horde_Core_Notification_Event_Status');

        $this->_notify->addDecorator(new Horde_Notification_Handler_Decorator_Alarm($injector->getInstance('Horde_Core_Factory_Alarm'), $auth));
        $this->_notify->addDecorator(new Horde_Core_Notification_Handler_Decorator_Hordelog());

        /* Cache notification handler application method existence. */
        $cache = $session->get('horde', 'factory_notification');

        if (is_null($cache)) {
            $save = array();
            $changed = ($auth !== false);

            try {
                $apps = $registry->listApps(null, false, Horde_Perms::READ);
            } catch (Horde_Exception $e) {
                $apps = array();
            }
        } else {
            $apps = $cache;
            $changed = false;
        }

        foreach ($apps as $app) {
            if ($changed) {
                if (!$registry->hasFeature('notificationHandler', $app)) {
                    continue;
                }
                $save[] = $app;
            }

            try {
                $registry->callAppMethod($app, 'setupNotification', array('args' => array($this->_notify), 'noperms' => true));
            } catch (Exception $e) {}
        }

        if ($changed) {
            $session->set('horde', 'factory_notification', $save);
        }

        return $this->_notify;
    }
}
