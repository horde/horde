<?php
class Horde_Core_Binder_Alarm implements Horde_Injector_Binder
{
    public function create(Horde_Injector $injector)
    {
        if (empty($GLOBALS['conf']['alarms']['driver'])) {
            $driver = null;
        } else {
            $driver = $GLOBALS['conf']['alarms']['driver'];
            $params = Horde::getDriverConfig('alarms', $driver);
        }

        $params['logger'] = $injector->getInstance('Horde_Log_Logger');

        return Horde_Alarm::factory($driver, $params);
    }

    public function equals(Horde_Injector_Binder $binder)
    {
        return false;
    }
}
