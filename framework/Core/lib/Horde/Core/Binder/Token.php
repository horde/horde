<?php
/**
 * @category Horde
 * @package  Core
 */
class Horde_Core_Binder_Token implements Horde_Injector_Binder
{
    public function create(Horde_Injector $injector)
    {
        $driver = isset($GLOBALS['conf']['token'])
            ? $GLOBALS['conf']['token']['driver']
            : 'Null';
        $params = isset($GLOBALS['conf']['token'])
            ? Horde::getDriverConfig('token', $GLOBALS['conf']['token']['driver'])
            : array();

        if (strcasecmp($driver, 'Sql') === 0) {
            $params['db'] = $injector->getInstance('Horde_Db_Adapter');
        } elseif (strcasecmp($driver, 'None') === 0) {
            $driver = 'Null';
        }

        $params['logger'] = $injector->getInstance('Horde_Log_Logger');

        return Horde_Token::factory($driver, $params);
    }

    public function equals(Horde_Injector_Binder $binder)
    {
        return false;
    }

}
