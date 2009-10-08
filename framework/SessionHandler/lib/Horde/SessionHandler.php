<?php
/**
 * Horde_SessionHandler:: defines an API for implementing custom PHP session
 * handlers.
 *
 * Optional parameters:<pre>
 *   'memcache' - (boolean) Use memcache to cache session data?
 * </pre>
 *
 * Copyright 2002-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Mike Cochrane <mike@graftonhall.co.nz>
 * @author  Michael Slusarz <slusarz@curecanti.org>
 * @package Horde_SessionHandler
 */
class Horde_SessionHandler
{
    /**
     * Singleton instances.
     *
     * @var array
     */
    static protected $_instances = array();

    /**
     * Hash containing connection parameters.
     *
     * @var array
     */
    protected $_params = array();

    /**
     * Initial session data signature.
     *
     * @var string
     */
    protected $_sig;

    /**
     * Force saving the session data?
     *
     * @var boolean
     */
    protected $_force = false;

    /**
     * Has a connection been made to the backend?
     *
     * @var boolean
     */
    protected $_connected = false;

    /**
     * Attempts to return a concrete Horde_SessionHandler instance based on
     * $driver.
     *
     * @param string $driver  The type of concrete subclass to return
     *                        (case-insensitive).
     * @param array $params   A hash containing any additional configuration or
     *                        connection parameters a subclass might need.
     *
     * @return Horde_SessionHandler  The newly created concrete instance.
     * @throws Horde_Exception
     */
    static public function factory($driver, $params = array())
    {
        $driver = basename(strtolower($driver));
        $persistent_params = array();

        if ($driver == 'memcached') {
            // Trap for old driver name.
            $driver = 'memcache';
        } elseif (($driver != 'memcache') && !empty($params['memcache'])) {
            unset($params['memcache']);
            $persistent_params = array('persistent_driver' => $driver, 'persistent_params' => $params);
            $driver = 'memcache';
            $params = array();
        }

        $class = 'Horde_SessionHandler_' . ucfirst($driver);

        if (class_exists($class)) {
            if (empty($params)) {
                $params = Horde::getDriverConfig('sessionhandler', $driver);
            }
            return new $class(array_merge($params, $persistent_params));
        }

        throw new Horde_Exception('Driver "' . $driver . '" not found.');
    }

    /**
     * Attempts to return a reference to a concrete Horde_SessionHandler
     * instance based on $driver. It will only create a new instance
     * if no Horde_SessionHandler instance with the same parameters
     * currently exists.
     *
     * This method must be invoked as:
     *   $var = Horde_SessionHandler::singleton()
     *
     * @param string $driver  See Horde_SessionHandler::factory().
     * @param array $params   See Horde_SessionHandler::factory().
     *
     * @return Horde_SessionHandler  The singleton instance.
     * @throws Horde_Exception
     */
    static public function singleton($driver, $params = array())
    {
        ksort($params);
        $signature = hash('md5', serialize(array($driver, $params)));
        if (empty(self::$_instances[$signature])) {
            self::$_instances[$signature] = self::factory($driver, $params);
        }

        return self::$_instances[$signature];
    }

    /**
     * Constructor.
     *
     * @param array $params  A hash containing connection parameters.
     *
     * @throws Horde_Exception
     */
    protected function __construct($params = array())
    {
        $this->_params = $params;
        register_shutdown_function(array($this, 'shutdown'));
    }

    /**
     * Destructor.
     */
    public function __destruct()
    {
        /* This is necessary as of PHP 5.0.5 because objects are not available
         * when the write() handler is called at the end of a session
         * access. */
        session_write_close();
    }

    /**
     * Shutdown function.
     *
     * Used to determine if we need to write the session to avoid a session
     * timeout, even though the session is unchanged.
     * Theory: On initial login, set the current time plus half of the max
     * lifetime in the session.  Then check this timestamp before saving.
     * If we exceed, force a write of the session and set a new timestamp.
     * Why half the maxlifetime?  It guarantees that if we are accessing the
     * server via a periodic mechanism (think folder refreshing in IMP) that
     * we will catch this refresh.
     */
    public function shutdown()
    {
        $curr_time = time();

        if (!isset($_SESSION['sessionhandler']) ||
            ($curr_time >= $_SESSION['sessionhandler'])) {
            $_SESSION['sessionhandler'] = $curr_time + (ini_get('session.gc_maxlifetime') / 2);
            $this->_force = true;
        }
    }

    /**
     * Open the backend.
     *
     * @param string $save_path     The path to the session object.
     * @param string $session_name  The name of the session.
     *
     * @return boolean  True on success, false otherwise.
     */
    public function open($save_path = null, $session_name = null)
    {
        if (!$this->_connected) {
            try {
                $this->_open($save_path, $session_name);
            } catch (Horde_Exception $e) {
                Horde::logMessage($e, __FILE__, __LINE__, PEAR_LOG_ERR);
                return false;
            }

            $this->_connected = true;
        }

        return true;
    }

    /**
     * Open the backend.
     *
     * @param string $save_path     The path to the session object.
     * @param string $session_name  The name of the session.
     *
     * @throws Horde_Exception
     */
    protected function _open($save_path = null, $session_name = null)
    {
    }

    /**
     * Close the backend.
     *
     * @return boolean  True on success, false otherwise.
     */
    public function close()
    {
        try {
            $this->_close();
        } catch (Horde_Exception $e) {
            Horde::logMessage($e, __FILE__, __LINE__, PEAR_LOG_ERR);
            return false;
        }

        $this->_connected = false;
        return true;
    }

    /**
     * Close the backend.
     *
     * @throws Horde_Exception
     */
    protected function _close()
    {
    }

    /**
     * Read the data for a particular session identifier from the backend.
     * This method should only be called internally by PHP via
     * session_set_save_handler().
     *
     * @param string $id  The session identifier.
     *
     * @return string  The session data.
     */
    public function read($id)
    {
        $result = $this->_read($id);
        $this->_sig = md5($result);
        return $result;
    }

    /**
     * Read the data for a particular session identifier from the backend.
     *
     * @param string $id  The session identifier.
     *
     * @return string  The session data.
     */
    protected function _read($id)
    {
        return '';
    }

    /**
     * Write session data to the backend.
     * This method should only be called internally by PHP via
     * session_set_save_handler().
     *
     * @param string $id            The session identifier.
     * @param string $session_data  The session data.
     *
     * @return boolean  True on success, false otherwise.
     */
    public function write($id, $session_data)
    {
        if (!$this->_force && ($this->_sig == md5($session_data))) {
            Horde::logMessage('Session data unchanged (id = ' . $id . ')', __FILE__, __LINE__, PEAR_LOG_DEBUG);
            return true;
        }

        return $this->_write($id, $session_data);
    }

    /**
     * Write session data to the backend.
     *
     * @param string $id            The session identifier.
     * @param string $session_data  The session data.
     *
     * @return boolean  True on success, false otherwise.
     */
    protected function _write($id, $session_data)
    {
        return false;
    }

    /**
     * Destroy the data for a particular session identifier in the backend.
     * This method should only be called internally by PHP via
     * session_set_save_handler().
     *
     * @param string $id  The session identifier.
     *
     * @return boolean  True on success, false otherwise.
     */
    public function destroy($id)
    {
        return false;
    }

    /**
     * Garbage collect stale sessions from the backend.
     * This method should only be called internally by PHP via
     * session_set_save_handler().
     *
     * @param integer $maxlifetime  The maximum age of a session.
     *
     * @return boolean  True on success, false otherwise.
     */
    public function gc($maxlifetime = 300)
    {
        return false;
    }

    /**
     * Get session data read-only.
     *
     * @param string $id  The session identifier.
     *
     * @return string  The session data.
     */
    protected function _readOnly($id)
    {
        return $this->read($id);
    }

    /**
     * Get a list of the valid session identifiers.
     *
     * @return array  A list of valid session identifiers.
     * @throws Horde_Exception
     */
    public function getSessionIDs()
    {
        throw new Horde_Exception(_("Not supported."));
    }

    /**
     * Returns a list of authenticated users and data about their session.
     *
     * @return array  For authenticated users, the sessionid as a key and the
     *                information returned from Horde_Auth::readSessionData()
     *                as values.
     * @throws Horde_Exception
     */
    public function getSessionsInfo()
    {
        $sessions = $this->getSessionIDs();

        $info = array();

        foreach ($sessions as $id) {
            try {
                $data = $this->_readOnly($id);
            } catch (Horde_Exception $e) {
                continue;
            }
            $data = Horde_Auth::readSessionData($data, true);
            if ($data !== false) {
                $info[$id] = $data;
            }
        }

        return $info;
    }

}
