<?php
/**
 * @category Horde
 * @package  Core
 */
class Horde_Core_Factory_AuthSignup
{
    public function create(Horde_Injector $injector)
    {
        $driver = empty($GLOBALS['conf']['signup']['driver'])
            ? 'Null'
            : $GLOBALS['conf']['signup']['driver'];

        $class = 'Horde_Core_Auth_Signup_' . Horde_String::ucfirst($driver);
        if (class_exists($class)) {
            return new $class($GLOBALS['conf']['signup']['params']);
        }
        throw new Horde_Exception($class . ' driver not found');
    }

}
