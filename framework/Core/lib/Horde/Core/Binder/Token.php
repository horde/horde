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
            $write_db = Horde_Core_Binder_Common::createDb($params, 'token SQL');

            /* Check if we need to set up the read DB connection
             * separately. */
            if (empty($params['splitread'])) {
                $params['db'] = $write_db;
            } else {
                $params['write_db'] = $write_db;
                $params['db'] = Horde_Core_Binder_Common::createDb(array_merge($params, $params['read']), 'token SQL');
            }
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
