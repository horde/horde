<?php
/**
 * Factory for creating Horde_Lock objects
 *
 * Copyright 2010-2012 Horde LLC (http://www.horde.org/)
 *
 * @category Horde
 * @package  Core
 */

class Horde_Core_Factory_Lock extends Horde_Core_Factory_Injector
{
    /**
     * Attempts to return a concrete instance based on the configured driver.
     *
     * @return Horde_Lock  The newly created concrete instance.
     * @throws Horde_Exception
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
            $params['db'] = $injector->getInstance('Horde_Db_Adapter');
        }

        $class = $this->_getDriverName($driver, 'Horde_Lock');
        return new $class($params);
    }

}
