<?php
/**
 * @category Horde
 * @package  Core
 */
class Horde_Core_Factory_QueueStorage extends Horde_Core_Factory_Injector
{
    public function create(Horde_Injector $injector)
    {
        $ob = new Horde_Queue_Storage_Array();
        new Horde_Queue_Runner_RequestShutdown($ob);
        return $ob;
    }

}
