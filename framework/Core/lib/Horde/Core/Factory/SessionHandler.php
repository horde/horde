<?php
/**
 * Factory for creating Horde_SessionHandler objects.
 *
 * Copyright 2010 Horde LLC <http://horde.org>
 *
 * @category Horde
 * @package  Core
 */
class Horde_Core_Factory_SessionHandler
{
    /**
     * Attempts to return a concrete instance based on $driver.
     *
     * @param string $driver  The type of concrete subclass to return
     *                        (case-insensitive).
     * @param array $params   A hash containing any additional configuration or
     *                        connection parameters a subclass might need.
     *
     * @return Horde_SessionHandler_Driver  The newly created concrete
     *                                      instance.
     * @throws Horde_SessionHandler_Exception
     */
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
            $params['db'] = $injector->getInstance('Horde_Db_Adapter');
        } elseif (strcasecmp($driver, 'Memcache') === 0) {
            $params['memcache'] = $injector->getInstance('Horde_Memcache');
        } elseif (strcasecmp($driver, 'Ldap') === 0) {
            $params['ldap'] = $injector->getInstances('Horde_Core_Factory_Ldap')->getLdap('horde', 'sessionhandler');
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
        $params['modified'] = array(
            'get' => array($this, 'getModified'),
            'set' => array($this, 'setModified')
        );
        $params['parse'] = array($this, 'readSessionData');

        $driver = basename(strtolower($driver));
        $class = 'Horde_SessionHandler_' . ucfirst($driver);

        if (class_exists($class)) {
            return new $class($params);
        }

        throw new Horde_SessionHandler_Exception('Driver not found: ' . $driver);
    }

    /**
     */
    public function getModified()
    {
        return $GLOBALS['session']['horde:session_mod'];
    }

    /**
     */
    public function setModified($date)
    {
        $GLOBALS['session']['horde:session_mod'] = $date;
    }

}
