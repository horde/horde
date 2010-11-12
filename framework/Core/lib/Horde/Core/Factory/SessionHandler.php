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

        $driver = basename(Horde_String::lower($driver));
        $noset = false;

        switch ($driver) {
        case 'builtin':
            $noset = true;
            break;

        case 'ldap':
            $params['ldap'] = $injector->getInstances('Horde_Core_Factory_Ldap')->getLdap('horde', 'sessionhandler');
            break;

        case 'memcache':
            $params['memcache'] = $injector->getInstance('Horde_Memcache');
            break;

        case 'sql':
            $params['db'] = $injector->getInstance('Horde_Db_Adapter');
            break;
        }

        $class = 'Horde_SessionHandler_Storage_' . Horde_String::ucfirst($driver);
        if (!class_exists($class)) {
            throw new Horde_SessionHandler_Exception('Driver not found: ' . $class);
        }
        $storage = new $class($params);

        if (!empty($conf['sessionhandler']['memcache']) &&
            !in_array($driver, array('builtin', 'memcache'))) {
            $storage = new Horde_SessionHandler_Storage_Stack(array(
                'stack' => array(
                    new Horde_SessionHandler_Storage_Memcache(array(
                        'memcache' => $injector->getInstance('Horde_Memcache')
                    )),
                    $storage
                )
            ));
        }


        return new Horde_SessionHandler(
            $storage,
            array(
                'logger' => $injector->getInstance('Horde_Log_Logger'),
                // TODO: Uncomment once all session data is saved through
                //  Horde_Session.
                //'no_md5' => true,
                'noset' => $noset,
                'parse' => array($this, 'readSessionData')
            )
        );
    }

    /**
     * Reads session data to determine if it contains Horde authentication
     * credentials.
     *
     * @param string $session_data  The session data.
     *
     * @return array  An array of the user's sesion information if
     *                authenticated or false.  The following information is
     *                returned: userid, timestamp, remoteAddr, browser, apps.
     */
    public function readSessionData($session_data)
    {
        if (empty($session_data) ||
            (($pos = strpos($session_data, 'horde_auth|')) === false)) {
            return false;
        }

        $pos += 11;
        $endpos = $pos + 1;

        while ($endpos !== false) {
            $endpos = strpos($session_data, '|', $endpos);
            $data = @unserialize(substr($session_data, $pos, $endpos));
            if (is_array($data)) {
                return empty($data)
                    ? false
                    : array(
                        'apps' => empty($data['app']) ? array('horde') : array_keys($data['app']),
                        'browser' => $data['browser'],
                        'remoteAddr' => $data['remoteAddr'],
                        'timestamp' => $data['timestamp'],
                        'userid' => $data['userId']
                    );
            }
            ++$endpos;
        }

        return false;
    }

}
