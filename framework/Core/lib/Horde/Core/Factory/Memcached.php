<?php
/**
 * @category   Horde
 * @deprecated Use HashTable instead.
 * @package    Core
 */
class Horde_Core_Factory_Memcached extends Horde_Core_Factory_Injector
{
    public function create(Horde_Injector $injector)
    {
        return empty($GLOBALS['conf']['memcached']['enabled'])
            ? null
            : new Horde_Memcached(array_merge($GLOBALS['conf']['memcached'], array('logger' => $injector->getInstance('Horde_Core_Log_Wrapper'))));
    }
}
