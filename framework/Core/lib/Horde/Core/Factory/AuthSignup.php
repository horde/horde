<?php
/**
 * @category Horde
 * @package  Core
 */
class Horde_Core_Factory_AuthSignup extends Horde_Core_Factory_Injector
{
    public function create(Horde_Injector $injector)
    {
        $driver = empty($GLOBALS['conf']['signup']['driver'])
            ? 'Null'
            : $GLOBALS['conf']['signup']['driver'];

        $class = $this->_getDriverName($driver, 'Horde_Core_Auth_Signup');
        return new $class($GLOBALS['conf']['signup']['params']);
    }

}
