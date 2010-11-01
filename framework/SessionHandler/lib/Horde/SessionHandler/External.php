<?php
/**
 * Horde_SessionHandler_External implements an external save handler defined
 * via driver configuration parameters.
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @package  SessionHandler
 */
class Horde_SessionHandler_External extends Horde_SessionHandler
{
    /**
     * Constructor.
     *
     * @param array $params  Required parameters:
     * <pre>
     * 'close' - (callback) See session_set_save_handler().
     * 'destroy' - (callback) See session_set_save_handler().
     * 'gc' - (callback) See session_set_save_handler().
     * 'open' - (callback) See session_set_save_handler().
     * 'read' - (callback) See session_set_save_handler().
     * 'write' - (callback) See session_set_save_handler().
     * </pre>
     *
     * @throws InvalidArgumentException
     */
    public function __construct(array $params = array())
    {
        foreach (array('open', 'close', 'read', 'write', 'destroy', 'gc') as $val) {
            if (!isset($params[$val])) {
                throw new InvalidArgumentException('Missing parameter: ' . $val);
            }
        }

        parent::__construct($params);
    }

    /**
     * Open the backend.
     *
     * @param string $save_path     The path to the session object.
     * @param string $session_name  The name of the session.
     */
    protected function _open($save_path = null, $session_name = null)
    {
        call_user_func($this->_params['open'], $save_path, $session_name);
    }

    /**
     * Close the backend.
     */
    protected function _close()
    {
        call_user_func($this->_params['close']);
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
        return call_user_func($this->_params['read']);
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
        return call_user_func($this->_params['write'], $id, $session_data);
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
        return call_user_func($this->_params['destroy'], $id);
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
        return call_user_func($this->_params['gc'], $maxlifetime);
    }

    /**
     * Get a list of the valid session identifiers.
     *
     * @throws Horde_SessionHandler_Exception
     */
    public function getSessionIDs()
    {
        throw new Horde_SessionHandler_Exception('Driver does not support listing session IDs.');
    }

}
