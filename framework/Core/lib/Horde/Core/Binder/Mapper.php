<?php
class Horde_Core_Binder_Mapper implements Horde_Injector_Binder
{
    public function create(Horde_Injector $injector)
    {
        $mapper = new Horde_Routes_Mapper();


        return $mapper;
    }

    public function equals(Horde_Injector_Binder $binder)
    {
        return false;
    }
}
