<?php
/**
 * Factory for creating Horde_Perms objects
 * 
 * Copyright 2010 Horde LLC <http://horde.org>
 *
 * @category Horde
 * @package  Core
 */
class Horde_Core_Factory_Perms
{
    /**
     * Attempts to return a concrete instance based on $driver.
     *
     * @return Horde_Perms  The newly created concrete instance.
     * @throws Horde_Perms_Exception
     */
    public function create(Horde_Injector $injector)
    {
        $driver = $GLOBALS['conf']['perms']['driver'];
        $params = isset($GLOBALS['conf']['perms'])
            ? Horde::getDriverConfig('perms', $driver)
            : array();

        if (strcasecmp($driver, 'Sql') === 0) {
            $params['db'] = $injector->getInstance('Horde_Db')->getDb('horde', 'perms');
        }

        $params['cache'] = $injector->getInstance('Horde_Cache');
        $params['logger'] = $injector->getInstance('Horde_Log_Logger');

        $class = is_null($driver)
            ? 'Horde_Perms'
            : 'Horde_Perms' . '_' . ucfirst(basename($driver));

        if (class_exists($class)) {
            return new $class($params);
        }

        throw new Horde_Perms_Exception('Unknown driver: ' . $driver);
    }
}
