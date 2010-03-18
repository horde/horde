<?php
class Horde_Core_Binder_Token implements Horde_Injector_Binder
{
    public function create(Horde_Injector $injector)
    {
        $token = isset($GLOBALS['conf']['token'])
            ? Horde_Token::singleton($GLOBALS['conf']['token']['driver'], Horde::getDriverConfig('token', $GLOBALS['conf']['token']['driver']))
            : Horde_Token::singleton('file');
        $token->setLogger($injector->getInstance('Horde_Log_Logger'));

        return $token;
    }

    public function equals(Horde_Injector_Binder $binder)
    {
        return false;
    }
}
