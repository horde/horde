<?php
/**
 * @category Horde
 * @package  Core
 */
class Horde_Core_Binder_Mapper implements Horde_Injector_Binder
{
    public function create(Horde_Injector $injector)
    {
        return new Horde_Routes_Mapper();
    }

    public function equals(Horde_Injector_Binder $binder)
    {
        return false;
    }
}
