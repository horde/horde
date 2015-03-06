<?php
/**
 * @todo  Replace Horde_Core_Factory_Secret with this class.
 *
 * @category Horde
 * @package  Core
 */
class Horde_Core_Factory_Secret_Cbc extends Horde_Core_Factory_Injector
{
    public function create(Horde_Injector $injector)
    {
        global $conf;

        return new Horde_Core_Secret_Cbc(array(
            'cookie_domain' => $conf['cookie']['domain'],
            'cookie_path' => $conf['cookie']['path'],
            'cookie_ssl' => $conf['use_ssl'] == 1,
            'iv' => $conf['secret_key'],
            'session_name' => $conf['session']['name']
        ));
    }
}
