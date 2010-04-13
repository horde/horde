<?php
/**
 * @category Horde
 * @package  Core
 */
class Horde_Core_Binder_Template implements Horde_Injector_Binder
{
    public function create(Horde_Injector $injector)
    {
        return new Horde_Template(array(
            'cacheob' => $injector->getInstance('Horde_Cache'),
            'logger' => $injector->getInstance('Horde_Log_Logger')
        ));
    }

    public function equals(Horde_Injector_Binder $binder)
    {
        return false;
    }
}
