<?php
/**
 * @category Horde
 * @package  Core
 */
class Horde_Core_Factory_Memcache extends Horde_Core_Factory_Injector
{
    public function create(Horde_Injector $injector)
    {
        return empty($GLOBALS['conf']['memcache']['enabled'])
            ? null
            : new Horde_Memcache(array_merge($GLOBALS['conf']['memcache'], array('logger' => $injector->getInstance('Horde_Log_Logger'))));
    }
}
