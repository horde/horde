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

        return $resolver;
    }
}
