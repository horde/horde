<?php
class Horde_Core_Binder_Logger implements Horde_Injector_Binder
{
    public function create(Horde_Injector $injector)
    {
        return new Horde_Log_Logger(new Horde_Log_Handler_Null());
    }

    public function equals(Horde_Injector_Binder $binder)
    {
        return false;
    }
}
