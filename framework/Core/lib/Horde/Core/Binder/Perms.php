<?php
/**
 * @category Horde
 * @package  Core
 */
class Horde_Core_Binder_Perms implements Horde_Injector_Binder
{
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

        return Horde_Perms::factory($driver, $params);
    }

    public function equals(Horde_Injector_Binder $binder)
    {
        return false;
    }

}
