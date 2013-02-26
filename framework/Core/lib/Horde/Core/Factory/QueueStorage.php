<?php
/**
 * @category Horde
 * @package  Core
 */
class Horde_Core_Factory_QueueStorage extends Horde_Core_Factory_Injector
{
    public function create(Horde_Injector $injector)
    {
        return $injector->getInstance('Horde_ShutdownRunner');
    }
}
