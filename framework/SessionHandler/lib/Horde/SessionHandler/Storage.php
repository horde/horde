<?php
/**
 * This is the abstract class that all storage drivers inherit from.
 *
 * Copyright 2002-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @package  SessionHandler
 */
abstract class Horde_SessionHandler_Storage
{
    /**
     * Access session read-only?
     *
     * @var boolean
     */
    public $readonly = false;

    /**
     * A logger instance.
     *
     * @var Horde_Log_Logger
     */
    protected $_logger;

    /**
     * Hash containing connection parameters.
     *
     * @var array
     */
    protected $_params = array();

    /**
     * Constructor.
     *
     * @param array $params  Configuration parameters.
     */
    public function __construct(array $params = array())
    {
        $this->_params = array_merge($this->_params, $params);
    }

    /**
     * Set the logger object.
     *
     * @param Horde_Log_Logger $log  The logger instance.
     */
    public function setLogger(Horde_Log_Logger $log)
    {
        $this->_logger = $log;
    }

    /**
     * Open the backend.
     *
     * @param string $save_path     The path to the session object.
     * @param string $session_name  The name of the session.
     *
     * @throws Horde_SessionHandler_Exception
     */
    abstract public function open($save_path = null, $session_name = null);

    /**
     * Close the backend.
     *
     * @throws Horde_SessionHandler_Exception
     */
    abstract public function close();

    /**
     * Read the data for a particular session identifier from the backend.
     *
     * @param string $id  The session identifier.
     *
     * @return string  The session data.
     */
    abstract public function read($id);

    /**
     * Write session data to the backend.
     *
     * @param string $id            The session identifier.
     * @param string $session_data  The session data.
     *
     * @return boolean  True on success, false otherwise.
     */
    abstract public function write($id, $session_data);

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
     * Get a list of the valid session identifiers.
     *
     * @return array  A list of valid session identifiers.
     * @throws Horde_SessionHandler_Exception
     */
    abstract public function getSessionIDs();

}
