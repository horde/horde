<?php
/**
 * @category Horde
 * @package  Core
 */
class Horde_Core_Binder_Dns implements Horde_Injector_Binder
{
    public function create(Horde_Injector $injector)
    {
        /* Need check for Net_DNS since it defines global variables used
         * in Net_DNS_Resolver::. */
        if (!class_exists('Net_DNS')) {
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
