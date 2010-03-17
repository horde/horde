<?php
class Horde_Core_Binder_Memcache implements Horde_Injector_Binder
{
    public function create(Horde_Injector $injector)
    {
        return empty($GLOBALS['conf']['memcache']['enabled'])
            ? null
            : new Horde_Memcache(array_merge($GLOBALS['conf']['memcache'], array('logger' => $injector->getInstance('Horde_Log_Logger'))));
    }

    public function equals(Horde_Injector_Binder $binder)
    {
        return false;
    }
}
