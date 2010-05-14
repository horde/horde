<?php
/**
 * @category Horde
 * @package  Core
 */
class Horde_Core_Binder_DbPear implements Horde_Injector_Binder
{
    public function create(Horde_Injector $injector)
    {
        return new Horde_Core_Factory_DbPear($injector);
    }

    public function equals(Horde_Injector_Binder $binder)
    {
        return false;
    }
}
