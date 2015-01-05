<?php
/**
 * Factory for creating Horde_Lock objects
 *
 * Copyright 2010-2015 Horde LLC (http://www.horde.org/)
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
        global $conf;

        $driver = empty($conf['lock']['driver'])
            ? 'null'
            : $conf['lock']['driver'];

        $params = Horde::getDriverConfig('lock', $driver);
        $params['logger'] = $injector->getInstance('Horde_Log_Logger');

        switch (Horde_String::lower($driver)) {
        case 'none':
            $driver = 'null';
            break;

        case 'nosql':
            $nosql = $injector->getInstance('Horde_Core_Factory_Nosql')->create('horde', 'cache');
            if ($nosql instanceof Horde_Mongo_Client) {
                $params['mongo_db'] = $nosql;
                $driver = 'mongo';
            }
            break;

        case 'sql':
            $params['db'] = $injector->getInstance('Horde_Core_Factory_Db')->create('horde', 'lock');
            break;
        }

        $class = $this->_getDriverName($driver, 'Horde_Lock');
        return new $class($params);
    }

}
