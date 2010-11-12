<?php
/**
 * This class provides the interface to the session storage backend.
 *
 * Copyright 2002-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @package  SessionHandler
 */
class Horde_SessionHandler
{
    /**
     * If true, indicates the session data has changed.
     *
     * @var boolean
     */
    public $changed = false;

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
     * Configuration parameters.
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
     * The storage object.
     *
     * @var Horde_SessionHandler_Storage
     */
    protected $_storage;

    /**
     * Constructor.
     *
     * @param Horde_SessionHandler_Storage $storage  The storage object.
     * @param array $params                          Configuration parameters:
     * <pre>
     * logger - (Horde_Log_Logger) A logger instance.
     *          DEFAULT: No logging
     * no_md5 - (boolean) If true, does not do MD5 signatures of the session
     *          to determine if the session has changed. If true, calling code
     *          is responsible for marking $changed as true when the session
     *          data has changed.
     *          DEFAULT: false
     * noset - (boolean) If true, don't set the save handler.
     *         DEFAULT: false
     * parse - (callback) A callback function that parses session
     *         information into an array. Is passed the raw session data
     *         as the only argument; expects either false or an array of
     *         session data as a return.
     *         DEFAULT: No
     * </pre>
     */
    public function __construct(Horde_SessionHandler_Storage $storage,
                                array $params = array())
    {
        $params = array_merge($this->_params, $params);

        if (isset($params['logger'])) {
            $this->_logger = $params['logger'];
            unset($params['logger']);

            $storage->setLogger($this->_logger);
        }

        $this->_params = $params;
        $this->_storage = $storage;

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
                $this->_storage->open($save_path, $session_name);
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
     * Close the backend.
     *
     * @return boolean  True on success, false otherwise.
     */
    public function close()
    {
        try {
            $this->_storage->close();
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
        $result = $this->_storage->read($id);
        if (empty($this->_params['no_md5'])) {
            $this->_sig = md5($result);
        }
        return $result;
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
        if ($this->changed ||
            (empty($this->_params['no_md5']) &&
             ($this->_sig != md5($session_data)))) {
            return $this->_storage->write($id, $session_data);
        }

        if ($this->_logger) {
            $this->_logger->log('Session data unchanged (id = ' . $id . ')', 'DEBUG');
        }

        return true;
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
        return $this->_storage->destroy($id);
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
        return $this->_storage->gc($id);
    }

    /**
     * Get a list of the valid session identifiers.
     *
     * @return array  A list of valid session identifiers.
     * @throws Horde_SessionHandler_Exception
     */
    public function getSessionIDs()
    {
        return $this->_storage->getSessionIDs();
    }

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

        $this->_storage->readonly = true;

        foreach ($sessions as $id) {
            try {
                $data = $this->read($id);
            } catch (Horde_SessionHandler_Exception $e) {
                continue;
            }

            $data = call_user_func($this->_params['parse'], $data);
            if ($data !== false) {
                $info[$id] = $data;
            }
        }

        $this->_storage->readonly = false;

        return $info;
    }

}
