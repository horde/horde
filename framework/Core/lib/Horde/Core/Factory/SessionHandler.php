<?php
/**
 * Factory for creating Horde_SessionHandler objects.
 *
 * Copyright 2010 Horde LLC <http://horde.org>
 *
 * @category Horde
 * @package  Core
 */
class Horde_Core_Factory_SessionHandler extends Horde_Core_Factory_Injector
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
            $params['ldap'] = $injector
                ->getInstances('Horde_Core_Factory_Ldap')
                ->create('horde', 'sessionhandler');
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
                'no_md5' => true,
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
        if (empty($session_data)) {
            return false;
        }

        /* Need to do some session magic.  Store old session, clear it out,
         * and use PHP's session_decode() to decode the incoming data.  Then
         * search for the needed auth entries and swap the old session data
         * back. */
        $old_sess = $_SESSION;
        $_SESSION = array();

        if (session_id()) {
            $new_sess = false;
        } else {
            $stub = new Horde_Support_Stub();

            session_set_save_handler(
                array($stub, 'open'),
                array($stub, 'close'),
                array($stub, 'read'),
                array($stub, 'write'),
                array($stub, 'destroy'),
                array($stub, 'gc')
            );

            ob_start();
            session_start();
            ob_end_clean();

            $new_sess = true;
        }

        session_decode($session_data);

        $data = $GLOBALS['session']->get('horde', 'auth/');
        $apps = $GLOBALS['session']->get('horde', 'auth_app/');

        if ($new_sess) {
            session_destroy();
        }

        $_SESSION = $old_sess;

        return isset($data['userId'])
            ? array(
                'apps' => array_keys($apps),
                'browser' => $data['browser'],
                'remoteAddr' => $data['remoteAddr'],
                'timestamp' => $data['timestamp'],
                'userid' => $data['userId']
            )
            : false;
    }

}
