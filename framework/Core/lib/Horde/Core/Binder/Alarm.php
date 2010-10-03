<?php
/**
 * @category Horde
 * @package  Core
 */
class Horde_Core_Binder_Alarm implements Horde_Injector_Binder
{
    public function create(Horde_Injector $injector)
    {
        $driver = empty($GLOBALS['conf']['alarms']['driver'])
            ? 'Null'
            : $GLOBALS['conf']['alarms']['driver'];
        $params = Horde::getDriverConfig('alarms', $driver);

        if (strcasecmp($driver, 'Sql') === 0) {
            $params['db'] = $injector->getInstance('Horde_Db_Adapter');
            $params['charset'] = 'UTF-8';
        }

        $params['logger'] = $injector->getInstance('Horde_Log_Logger');

        $alarm = Horde_Alarm::factory($driver, $params);

        /* Add those handlers that need configuration and can't be auto-loaded
         * through Horde_Alarms::handlers(). */
        /*
        $handler_params = array(
            'notification' => $injector->getInstance('Horde_Notification')
        );
        $alarm->addHandler('notify', new Horde_Alarm_Handler_Notification($handler_params)
        );

        $handler_params = array(
            'js_notify' => array('Horde', 'addInlineScript'),
            'icon' => (string)Horde_Themes::img('alerts/alarm.png')
        );
        $alarm->addHandler('desktop', new Horde_Alarm_Handler_Desktop($handler_params));
        */

        $handler_params = array(
            'identity' => $injector->getInstance('Horde_Prefs_Identity'),
            'mail' => $injector->getInstance('Horde_Mail'),
            'charset' => $GLOBALS['registry']->getCharset()
        );
        $alarm->addHandler('mail', new Horde_Alarm_Handler_Mail($handler_params));

        return $alarm;
    }

    public function equals(Horde_Injector_Binder $binder)
    {
        return false;
    }
}
