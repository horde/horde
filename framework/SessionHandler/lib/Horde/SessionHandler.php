<?php
/**
 * Horde_SessionHandler_Base is the abstract class that all drivers inherit
 * from.
 *
 * Copyright 2002-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @package  SessionHandler
 */
abstract class Horde_SessionHandler
{
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
     * A logger instance.
     *
     * @var Horde_Log_Logger
     */
    protected $_logger;

    /**
     * Constructor.
     *
     * @param array $params  Parameters:
     * <pre>
     * 'logger' - (Horde_Log_Logger) A logger instance.
     *            DEFAULT: No logging
     * 'modified' - (array) Callbacks used to store the session last modified
     *              value.  Needs to define two keys: 'get' and 'set'. 'get'
     *              returns the last modified value, 'set' receives the last
     *              modified value as the only parameter.
     *              DEFAULT: Not saved
     * 'noset' - (boolean) If true, don't set the save handler.
     *           DEFAULT: false
     * 'parse' - (callback) A callback function that parses session
     *           information into an array. Is passed the raw session data
     *           as the only argument; expects either false or an array of
     *           session data as a return.
     *           DEFAULT: No
     * </pre>
     */
    public function __construct(array $params = array())
    {
        $params = array_merge($this->_params, $params);

        if (isset($params['logger'])) {
            $this->_logger = $params['logger'];
            unset($params['logger']);
        }

        $this->_params = $params;

        if (isset($this->_params['modified'])) {
            register_shutdown_function(array($this, 'shutdown'));
        }

        if (empty($this->_params['noset'])) {
            ini_set('session.save_handler', 'user');
            session_set_save_handler(
                array($this, 'open'),
                array($this, 'close'),
                array($this, 'read'),
                array($this, 'write'),
                array($this, 'destroy'),
                array($this, 'gc')
            );
        }
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
        if ($curr_time >= intval(call_user_func($this->_params['modified']['get']))) {
            call_user_func($this->_params['modified']['set'], $curr_time + (ini_get('session.gc_maxlifetime') / 2));
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
            } catch (Horde_SessionHandler_Exception $e) {
                if ($this->_logger) {
                    $this->_logger->log($e, 'ERR');
                }
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
     * @throws Horde_SessionHandler_Exception
     */
    abstract protected function _open($save_path = null, $session_name = null);

    /**
     * Close the backend.
     *
     * @return boolean  True on success, false otherwise.
     */
    public function close()
    {
        try {
            $this->_close();
        } catch (Horde_SessionHandler_Exception $e) {
            if ($this->_logger) {
                $this->_logger->log($e, 'ERR');
            }
            return false;
        }

        $this->_connected = false;
        return true;
    }

    /**
     * Close the backend.
     *
     * @throws Horde_SessionHandler_Exception
     */
    abstract protected function _close();

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
    abstract protected function _read($id);

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
            if ($this->_logger) {
                $this->_logger->log('Session data unchanged (id = ' . $id . ')', 'DEBUG');
            }
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
    abstract protected function _write($id, $session_data);

    /**
     * Destroy the data for a particular session identifier in the backend.
     * This method should only be called internally by PHP via
     * session_set_save_handler().
     *
     * @param string $id  The session identifier.
     *
     * @return boolean  True on success, false otherwise.
     */
    abstract public function destroy($id);

    /**
     * Garbage collect stale sessions from the backend.
     * This method should only be called internally by PHP via
     * session_set_save_handler().
     *
     * @param integer $maxlifetime  The maximum age of a session.
     *
     * @return boolean  True on success, false otherwise.
     */
    abstract public function gc($maxlifetime = 300);

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
     * @throws Horde_SessionHandler_Exception
     */
    abstract public function getSessionIDs();

    /**
     * Returns a list of authenticated users and data about their session.
     *
     * @return array  For authenticated users, the sessionid as a key and the
     *                session information as value. If no parsing function
     *                was provided, will always return an empty array.
     * @throws Horde_SessionHandler_Exception
     */
    public function getSessionsInfo()
    {
        $info = array();

        if (empty($this->_params['parse']) ||
            !is_callable($this->_params['parse'])) {
            return $info;
        }

        $sessions = $this->getSessionIDs();

        foreach ($sessions as $id) {
            try {
                $data = $this->_readOnly($id);
            } catch (Horde_SessionHandler_Exception $e) {
                continue;
            }

            $data = call_user_func($this->_params['parse'], $data);
            if ($data !== false) {
                $info[$id] = $data;
            }
        }

        return $info;
    }

}
