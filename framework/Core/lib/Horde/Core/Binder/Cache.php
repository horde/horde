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

        if (strcasecmp($driver, 'Memcache') === 0) {
            $params['memcache'] = $injector->getInstance('Horde_Memcache');
        } else {
            if (strcasecmp($driver, 'Sql') === 0) {
                $params['db'] = $injector->getInstance('Horde_Db_Adapter_Base');
            }

            if (!empty($params['use_memorycache'])) {
                $params = array(
                    'stack' => array(
                        array(
                            'driver' => 'Memcache',
                            'params' => array_merge($params, array(
                                'logger' => $logger,
                                'memcache' => $injector->getInstance('Horde_Memcache')
                            ))
                        ),
                        array(
                            'driver' => $driver,
                            'params' => array_merge($params, array(
                                'logger' => $logger
                             ))
                         )
                    )
                );
                $driver = 'Stack';
            }
        }

        $params['logger'] = $injector->getInstance('Horde_Log_Logger');

        return Horde_Cache::factory($driver, $params);
    }

    public function equals(Horde_Injector_Binder $binder)
    {
        return false;
    }
}
