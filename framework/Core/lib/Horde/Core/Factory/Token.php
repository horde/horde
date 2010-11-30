<?php
/**
 * @category Horde
 * @package  Core
 */
class Horde_Core_Factory_Token
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

        if (isset($GLOBALS['conf']['urls']['token_lifetime'])) {
            $params['token_lifetime'] = $GLOBALS['conf']['urls']['token_lifetime'] * 60;
        }

        $params['secret'] = $injector->getInstance('Horde_Secret')->getKey('auth');
        $params['logger'] = $injector->getInstance('Horde_Log_Logger');

        $class = 'Horde_Token_' . ucfirst($driver);
        if (class_exists($class)) {
            return new $class($params);
        }

        throw new Horde_Token_Exception('Driver ' . $driver . ' not found.');
    }

}
