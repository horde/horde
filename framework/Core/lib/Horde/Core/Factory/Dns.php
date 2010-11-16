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
        if (is_readable('/etc/resolv.conf')) {
            $resolver->setServers('/etc/resolv.conf');
        }

        // TODO: Fixes for Net_DNS2 v1.0.0
        if (!defined('SOCK_DGRAM')) {
            define('SOCK_STREAM', 1);
            define('SOCK_DGRAM', 2);
        }

        spl_autoload_unregister('Net_DNS2::autoload');

        return $resolver;
    }
}
