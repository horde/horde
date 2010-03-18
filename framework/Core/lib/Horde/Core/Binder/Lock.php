<?php
class Horde_Core_Binder_Lock implements Horde_Injector_Binder
{
    public function create(Horde_Injector $injector)
    {
        if (empty($GLOBALS['conf']['lock']['driver'])) {
            $driver = null;
        } else {
            $driver = $GLOBALS['conf']['lock']['driver'];
            $params = Horde::getDriverConfig('lock', $driver);
        }

        $params['logger'] = $injector->getInstance('Horde_Log_Logger');

        return Horde_Lock::singleton($driver, $params);
    }

    public function equals(Horde_Injector_Binder $binder)
    {
        return false;
    }
}
