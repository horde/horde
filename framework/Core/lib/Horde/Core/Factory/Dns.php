<?php
/**
 * @category Horde
 * @package  Core
 */
class Horde_Core_Factory_Dns
{
    public function create(Horde_Injector $injector)
    {
        $resolver = new Net_DNS2_Resolver();
        $resolver->setServers('/etc/resolv.conf');

        spl_autoload_unregister('Net_DNS2::autoload');

        return $resolver;
    }
}
