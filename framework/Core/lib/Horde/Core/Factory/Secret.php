<?php
/**
 * @category Horde
 * @package  Core
 */
class Horde_Core_Factory_Secret extends Horde_Core_Factory_Injector
{
    public function create(Horde_Injector $injector)
    {
        global $conf;

        return new Horde_Core_Secret(array(
            'cookie_domain' => $conf['cookie']['domain'],
            'cookie_path' => $conf['cookie']['path'],
            'cookie_ssl' => $conf['use_ssl'] == 1,
            'session_name' => $conf['session']['name']
        ));
    }
}
