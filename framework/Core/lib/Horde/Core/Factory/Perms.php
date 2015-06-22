<?php
/**
 * Factory for creating Horde_Perms objects
 *
 * Copyright 2010-2015 Horde LLC (http://www.horde.org/)
 *
 * @category Horde
 * @package  Core
 */
class Horde_Core_Factory_Perms extends Horde_Core_Factory_Injector
{
    /**
     * Attempts to return a concrete instance based on $driver.
     *
     * @return Horde_Perms  The newly created concrete instance.
     * @throws Horde_Exception
     */
    public function create(Horde_Injector $injector)
    {
        global $conf;

        $driver = empty($conf['perms']['driver'])
            ? 'null'
            : $conf['perms']['driver'];
        $params = isset($conf['perms'])
            ? Horde::getDriverConfig('perms', $driver)
            : array();

        switch (Horde_String::lower($driver)) {
        case 'sql':
            try {
                $params['db'] = $injector
                    ->getInstance('Horde_Core_Factory_Db')
                    ->create('horde', 'perms');
            } catch (Horde_Exception $e) {
                $driver = 'Null';
            }
            break;
        }

        $params['cache'] = new Horde_Cache(
            new Horde_Cache_Storage_Stack(array(
                'stack' => array(
                    new Horde_Cache_Storage_Memory(),
                    $injector->getInstance('Horde_Cache')
                )
            ))
        );
        $params['logger'] = $injector->getInstance('Horde_Log_Logger');

        $class = $this->_getDriverName($driver, 'Horde_Perms');
        return new $class($params);
    }

}
