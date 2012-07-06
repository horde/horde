<?php
/**
 * @category Horde
 * @package  Core
 */
class Horde_Core_Factory_Dns extends Horde_Core_Factory_Injector
{
    public function create(Horde_Injector $injector)
    {
        if (!class_exists('Net_DNS2_Resolver')) {
            return null;
        }

        $resolver = new Net_DNS2_Resolver();
        if (is_readable('/etc/resolv.conf')) {
            try {
                $resolver->setServers('/etc/resolv.conf');
            } catch (Net_DNS2_Exception $e) {}
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
