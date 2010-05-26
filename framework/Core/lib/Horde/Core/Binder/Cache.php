<?php
/**
 * @category Horde
 * @package  Core
 */
class Horde_Core_Binder_Cache implements Horde_Injector_Binder
{
    public function create(Horde_Injector $injector)
    {
        $driver = empty($GLOBALS['conf']['cache']['driver'])
            ? 'Null'
            : $GLOBALS['conf']['cache']['driver'];
        if (strcasecmp($driver, 'None') === 0) {
            $driver = 'Null';
        }

        $params = Horde::getDriverConfig('cache', $driver);
        if (isset($GLOBALS['conf']['cache']['default_lifetime'])) {
            $params['lifetime'] = $GLOBALS['conf']['cache']['default_lifetime'];
        }

        $logger = $injector->getInstance('Horde_Log_Logger');
        $params['logger'] = $logger;

        $base_params = $params;

        if (strcasecmp($driver, 'Memcache') === 0) {
            $params['memcache'] = $injector->getInstance('Horde_Memcache');
        } elseif (strcasecmp($driver, 'Sql') === 0) {
            $params['db'] = $injector->getInstance('Horde_Db')->getOb('horde', 'cache');
        }

        if (!empty($GLOBALS['conf']['cache']['use_memorycache']) &&
            ((strcasecmp($driver, 'Sql') === 0) ||
             (strcasecmp($driver, 'File') === 0))) {
            if (strcasecmp($GLOBALS['conf']['cache']['use_memorycache'], 'Memcache') === 0) {
                $base_params['memcache'] = $injector->getInstance('Horde_Memcache');
            }

            $params = array(
                'stack' => array(
                    array(
                        'driver' => $GLOBALS['conf']['cache']['use_memorycache'],
                        'params' => $base_params
                    ),
                    array(
                        'driver' => $driver,
                        'params' => $params
                    )
                )
            );
            $driver = 'Stack';
        }

        return Horde_Cache::factory($driver, $params);
    }

    public function equals(Horde_Injector_Binder $binder)
    {
        return false;
    }

}
