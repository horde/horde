<?php
/**
 * @category Horde
 * @package  Core
 */
class Horde_Core_Factory_Notification extends Horde_Core_Factory_Injector
{
    /* Cache constants. */
    const CACHE_SKIP = 1;
    const CACHE_RUN = 2;

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

        /* Cache notification handler application method existence.
         * Logic: keep track of list of applications previously seen. Skip
         * further checks of the same application if we are authenticated and
         * no setupNotification handler exists for the app. */
        $cache = $session->get('horde', 'factory_notification', Horde_Session::TYPE_ARRAY);
        $changed = false;

        try {
            $apps = $registry->listApps(null, false, Horde_Perms::READ);
        } catch (Horde_Exception $e) {
            $apps = array();
        }

        foreach ($apps as $app) {
            if ((!isset($cache[$app]) || ($cache[$app] == self::CACHE_RUN)) &&
                $registry->isAuthenticated(array('app' => $app, 'notransparent' => true))) {
                try {
                    $result = $registry->callAppMethod($app, 'setupNotification', array('args' => array($this->_notify), 'noperms' => true));
                    $cache[$app] = self::CACHE_SKIP;
                } catch (Exception $e) {
                    $result = false;
                }

                if ($result) {
                    if (!isset($cache[$app])) {
                        $cache[$app] = self::CACHE_RUN;
                        $changed = true;
                    }
                } else {
                    $cache[$app] = self::CACHE_SKIP;
                    $changed = true;
                }
            }
        }

        if ($changed && ($auth !== false)) {
            $session->set('horde', 'factory_notification', $cache);
        }

        return $this->_notify;
    }
}
