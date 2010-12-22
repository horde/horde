<?php
/**
 * A Horde_Injector:: based Horde_Alarm:: factory.
 *
 * PHP version 5
 *
 * @category Horde
 * @package  Core
 * @author   Michael J. Rubinsky <mrubinsk@horde.org>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Core
 */

/**
 * A Horde_Injector:: based Horde_Core_Ajax_Application:: factory.
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category Horde
 * @package  Core
 * @author   Michael J. Rubinsky <mrubinsk@horde.org>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Core
 */
class Horde_Core_Factory_Alarm
{
    /**
     * Return a Horde_Alarm instance
     *
     */
    public function create(Horde_Injector $injector)
    {
        $driver = empty($GLOBALS['conf']['alarms']['driver'])
            ? 'Null'
            : $GLOBALS['conf']['alarms']['driver'];

        $params = Horde::getDriverConfig('alarms', $driver);

        if (strcasecmp($driver, 'Sql') === 0) {
            $params['db'] = $injector->getInstance('Horde_Db_Adapter');
        }

        $params['logger'] = $injector->getInstance('Horde_Log_Logger');

        $class = 'Horde_Alarm_' . $driver;
        $alarm = new $class($params);
        $alarm->initialize();
        $alarm->gc();

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
            'identity' => $injector->getInstance('Horde_Core_Factory_Identity'),
            'mail' => $injector->getInstance('Horde_Mail'),
        );
        $alarm->addHandler('mail', new Horde_Alarm_Handler_Mail($handler_params));

        return $alarm;
    }

}
