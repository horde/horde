<?php
/**
 * @category Horde
 * @package  Core
 */
class Horde_Core_Factory_Token extends Horde_Core_Factory_Injector
{
    public function create(Horde_Injector $injector)
    {
        $driver = isset($GLOBALS['conf']['token'])
            ? $GLOBALS['conf']['token']['driver']
            : 'Null';
        $params = isset($GLOBALS['conf']['token'])
            ? Horde::getDriverConfig('token', $GLOBALS['conf']['token']['driver'])
            : array();

        $params['logger'] = $injector->getInstance('Horde_Log_Logger');
        $params['secret'] = $injector->getInstance('Horde_Secret')->getKey();

        if (strcasecmp($driver, 'Sql') === 0) {
            $params['db'] = $injector->getInstance('Horde_Db_Adapter');
        } elseif (strcasecmp($driver, 'None') === 0) {
            $driver = 'Null';
        }

        if (isset($GLOBALS['conf']['urls']['token_lifetime'])) {
            $params['token_lifetime'] = $GLOBALS['conf']['urls']['token_lifetime'] * 60;
        }

        $class = $this->_getDriverName($driver, 'Horde_Token');
        return new $class($params);
    }

}
