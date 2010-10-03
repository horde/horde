<?php
/**
 * Factory for creating Horde_Lock objects
 *
 * Copyright 2010 Horde LLC <http://horde.org>
 * 
 * @category Horde
 * @package  Core
 */

class Horde_Core_Factory_Lock
{
    /**
     * Attempts to return a concrete instance based on the configured driver.
     *
     * @return Horde_Lock  The newly created concrete instance.
     * @throws Horde_Lock_Exception
     */
    public function create(Horde_Injector $injector)
    {
        $driver = empty($GLOBALS['conf']['lock']['driver'])
            ? 'Null'
            : $GLOBALS['conf']['lock']['driver'];

        if (strcasecmp($driver, 'None') === 0) {
            $driver = 'Null';
        }

        $params = Horde::getDriverConfig('lock', $driver);
        $params['logger'] = $injector->getInstance('Horde_Log_Logger');

        if (strcasecmp($driver, 'Sql') === 0) {
            $params['db'] = $injector->getInstance('Horde_Db')->getDb('horde', 'lock');
        }

        $driver = Horde_String::ucfirst(basename($driver));
        $class = 'Horde_Lock_' . $driver;

        if (class_exists($class)) {
            return new $class($params);
        }

        throw new Horde_Lock_Exception('Horde_Lock driver (' . $class . ') not found');
    }
}