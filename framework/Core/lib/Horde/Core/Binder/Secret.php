<?php
class Horde_Core_Binder_Secret implements Horde_Injector_Binder
{
    public function create(Horde_Injector $injector)
    {
        global $conf;

        return new Horde_Secret(array(
            'cookie_domain' => $conf['cookie']['domain'],
            'cookie_expire' => $conf['session']['timeout'],
            'cookie_path' => $conf['cookie']['path'],
            'cookie_ssl' => (bool) $conf['use_ssl'],
            'session_name' => $conf['session']['name']
        ));
    }

    public function equals(Horde_Injector_Binder $binder)
    {
        return false;
    }
}
