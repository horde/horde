<?php
class Horde_Core_Binder_Dns implements Horde_Injector_Binder
{
    public function create(Horde_Injector $injector)
    {
        if (!class_exists('Net_DNS_Resolver')) {
            return null;
        }

        $resolver = new Net_DNS_Resolver();
        $resolver->retry = isset($GLOBALS['conf']['dns']['retry'])
            ? $GLOBALS['conf']['dns']['retry']
            : 1;
        $resolver->retrans = isset($GLOBALS['conf']['dns']['retrans'])
            ? $GLOBALS['conf']['dns']['retrans']
            : 1;

        return $resolver;
    }

    public function equals(Horde_Injector_Binder $binder)
    {
        return false;
    }
}
