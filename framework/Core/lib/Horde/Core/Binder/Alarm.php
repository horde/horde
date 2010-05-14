<?php
/**
 * @category Horde
 * @package  Core
 */
class Horde_Core_Binder_Alarm implements Horde_Injector_Binder
{
    public function create(Horde_Injector $injector)
    {
        if (empty($GLOBALS['conf']['alarms']['driver'])) {
            $driver = null;
            $params = array();
        } else {
            $driver = $GLOBALS['conf']['alarms']['driver'];
            $params = Horde::getDriverConfig('alarms', $driver);
        }

        if (strcasecmp($driver, 'Sql') === 0) {
            $write_db = $injector->getInstance('Horde_Db_Pear')->getOb();

            /* Check if we need to set up the read DB connection
             * separately. */
            if (empty($params['splitread'])) {
                $params['db'] = $write_db;
            } else {
                $params['write_db'] = $write_db;
                $params['db'] = $injector->getInstance('Horde_Db_Pear')->getOb('read');
            }
        }

        $params['logger'] = $injector->getInstance('Horde_Log_Logger');

        $alarm = Horde_Alarm::factory($driver, $params);

        /* Add those handlers that need configuration and can't be auto-loaded
         * through Horde_Alarms::handlers(). */
        /*
        $handler_params = array(
            'notification' => $injector->getInstance('Horde_Notification'));
        $alarm->addHandler('notify', new Horde_Alarm_Handler_Notification($handler_params));
        $handler_params = array(
            'notification' => $injector->getInstance('Horde_Notification'),
            'icon' => (string)Horde_Themes::img('alerts/alarm.png'));
        $alarm->addHandler('desktop', new Horde_Alarm_Handler_Desktop($handler_params));
        */
        $handler_params = array(
            'identity' => $injector->getInstance('Horde_Prefs_Identity'),
            'mail' => $injector->getInstance('Horde_Mail'),
            'charset' => Horde_Nls::getCharset()
        );
        $alarm->addHandler('mail', new Horde_Alarm_Handler_Mail($handler_params));

        return $alarm;
    }

    public function equals(Horde_Injector_Binder $binder)
    {
        return false;
    }
}
