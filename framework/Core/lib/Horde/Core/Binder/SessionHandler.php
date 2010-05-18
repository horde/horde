<?php
/**
 * @category Horde
 * @package  Core
 */
class Horde_Core_Binder_SessionHandler implements Horde_Injector_Binder
{
    public function create(Horde_Injector $injector)
    {
        global $conf;

        if (empty($conf['sessionhandler']['type'])) {
            $driver = 'Builtin';
        } else {
            $driver = $conf['sessionhandler']['type'];
            if (!strcasecmp($driver, 'None')) {
                $driver = 'Builtin';
            }
        }
        $params = Horde::getDriverConfig('sessionhandler', $driver);

        if (strcasecmp($driver, 'Sql') === 0) {
            $params['db'] = $injector->getInstance('Horde_Db_Adapter_Base');
        }

        $logger = $injector->getInstance('Horde_Log_Logger');

        if (!empty($conf['sessionhandler']['memcache']) &&
            (strcasecmp($driver, 'Builtin') != 0) &&
            (strcasecmp($driver, 'Memcache') != 0)) {
            $params = array(
                'stack' => array(
                    array(
                        'driver' => 'Memcache',
                        'params' => array(
                            'memcache' => $injector->getInstance('Horde_Memcache'),
                            'logger' => $logger
                        )
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

        $params['logger'] = $logger;

        return Horde_SessionHandler::factory($driver, $params);
    }

    public function equals(Horde_Injector_Binder $binder)
    {
        return false;
    }
}
