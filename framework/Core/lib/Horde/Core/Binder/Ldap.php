<?php
/**
 * @category Horde
 * @package  Core
 */
class Horde_Core_Binder_Ldap implements Horde_Injector_Binder
{
    public function create(Horde_Injector $injector)
    {
        return new Horde_Ldap();
    }

    public function equals(Horde_Injector_Binder $binder)
    {
        return false;
    }

}
