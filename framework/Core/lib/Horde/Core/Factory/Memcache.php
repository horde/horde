<?php
/**
 * @category   Horde
 * @deprecated Use HashTable instead.
 * @package    Core
 */
class Horde_Core_Factory_Memcache extends Horde_Core_Factory_Injector
{
    public function create(Horde_Injector $injector)
    {
        return empty($GLOBALS['conf']['memcache']['enabled'])
            ? null
            : new Horde_Memcache(array_merge($GLOBALS['conf']['memcache'], array('logger' => $injector->getInstance('Horde_Core_Log_Wrapper'))));
    }
}
