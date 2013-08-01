<?php
/**
 * @category Horde
 * @package  Core
 */
class Horde_Core_Factory_Token extends Horde_Core_Factory_Injector
{
    public function create(Horde_Injector $injector)
    {
        global $conf;

        $driver = empty($conf['token'])
            ? 'null'
            : $conf['token']['driver'];
        $params = empty($conf['token'])
            ? array()
            : Horde::getDriverConfig('token', $conf['token']['driver']);

        $params['logger'] = $injector->getInstance('Horde_Log_Logger');
        $params['secret'] = $injector->getInstance('Horde_Secret')->getKey();

        switch (Horde_String::lower($driver)) {
        case 'none':
            $driver = 'null';
            break;

        case 'nosql':
            $nosql = $injector->getInstance('Horde_Core_Factory_Nosql')->create('horde', 'token');
            if ($nosql instanceof Horde_Mongo_Client) {
                $params['mongo_db'] = $nosql;
                $driver = 'Horde_Token_Mongo';
            }
            break;

        case 'sql':
            $params['db'] = $injector->getInstance('Horde_Core_Factory_Db')->create('horde', 'token');
            break;
        }

        if (isset($conf['urls']['token_lifetime'])) {
            $params['token_lifetime'] = $conf['urls']['token_lifetime'] * 60;
        }

        $class = $this->_getDriverName($driver, 'Horde_Token');
        return new $class($params);
    }

}
