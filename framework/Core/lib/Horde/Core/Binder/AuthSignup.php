<?php
/**
 * @category Horde
 * @package  Core
 */
class Horde_Core_Binder_AuthSignup implements Horde_Injector_Binder
{
    public function create(Horde_Injector $injector)
    {
        $driver = empty($GLOBALS['conf']['signup']['driver'])
            ? 'Null'
            : $GLOBALS['conf']['signup']['driver'];

        return Horde_Core_Auth_Signup::factory($driver);
    }

    public function equals(Horde_Injector_Binder $binder)
    {
        return false;
    }

}
