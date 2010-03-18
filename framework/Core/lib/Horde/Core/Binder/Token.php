<?php
class Horde_Core_Binder_Token implements Horde_Injector_Binder
{
    public function create(Horde_Injector $injector)
    {
        $driver = isset($GLOBALS['conf']['token'])
            ? $GLOBALS['conf']['token']['driver']
            : 'file';
        $params = isset($GLOBALS['conf']['token'])
            ? Horde::getDriverConfig('token', $GLOBALS['conf']['token']['driver'])
            : array();
        $params['logger'] = $injector->getInstance('Horde_Log_Logger');
        return Horde_Token::singleton($driver, $params);
    }

    public function equals(Horde_Injector_Binder $binder)
    {
        return false;
    }
}
