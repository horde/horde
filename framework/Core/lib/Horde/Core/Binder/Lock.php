<?php
/**
 * @category Horde
 * @package  Core
 */
class Horde_Core_Binder_Lock implements Horde_Injector_Binder
{
    public function create(Horde_Injector $injector)
    {
        if (empty($GLOBALS['conf']['lock']['driver'])) {
            $driver = 'Null';
        } else {
            $driver = $GLOBALS['conf']['lock']['driver'];
            if (Horde_String::lower($driver) == 'none') {
                $driver = 'Null';
            }
        }

        $params = Horde::getDriverConfig('lock', $driver);
        $params['logger'] = $injector->getInstance('Horde_Log_Logger');

        if (Horde_String::lower($driver) == 'sql') {
            Horde_Util::assertDriverConfig($params, array('phptype'), 'Lock SQL');
        }

        return Horde_Lock::factory($driver, $params);
    }

    public function equals(Horde_Injector_Binder $binder)
    {
        return false;
    }
}
