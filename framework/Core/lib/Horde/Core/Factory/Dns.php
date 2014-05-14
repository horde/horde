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

        if ($tmpdir = Horde::getTempDir()) {
            $config = array(
                'cache_file' => $tmpdir . '/horde_dns.cache',
                'cache_size' => 100000,
                'cache_type' => 'file',
            );
        } else {
            $config = array();
        }

        $resolver = new Net_DNS2_Resolver($config);

        if (is_readable('/etc/resolv.conf')) {
            try {
                $resolver->setServers('/etc/resolv.conf');
            } catch (Net_DNS2_Exception $e) {}
        }

        return $resolver;
    }
}
