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
        $params['parse'] = array($this, 'readSessionData');

        return Horde_SessionHandler::factory($driver, $params);
    }

    public function equals(Horde_Injector_Binder $binder)
    {
        return false;
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
