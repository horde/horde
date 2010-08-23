<?php
/**
 * Horde_SessionHandler_Stack is an implementation that will loop through a
 * given list of Horde_SessionHandler drivers to return the session
 * information.  This driver allows for use of caching backends on top of
 * persistent backends.
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
class Horde_SessionHandler_Stack extends Horde_SessionHandler_Base
{
    /**
     * Stack of sessionhandlers.
     *
     * @var string
     */
    protected $_stack = array();

    /**
     * Constructor.
     *
     * @param array $params  Parameters:
     * <pre>
     * 'stack' - (array) [REQUIRED] A list of sessionhandlers to loop
     *           through, in order of priority. The last entry is considered
     *           the "master" server.
     *           Each value should contain an array with two keys: 'driver', a
     *           string value with the SessionHandler driver to use, and
     *           'params', containing any parameters needed by this driver.
     * </pre>
     *
     * @throws InvalidArgumentException
     */
    public function __construct(array $params = array())
    {
        if (!isset($params['stack'])) {
            throw new InvalidArgumentException('Missing stack parameter.');
        }

        foreach ($params['stack'] as $val) {
            $this->_stack[] = Horde_SessionHandler::factory($val['driver'], $val['params']);
        }

        unset($params['stack']);

        parent::__construct($params);
    }

    /**
     * Open the backend.
     *
     * @param string $save_path     The path to the session object.
     * @param string $session_name  The name of the session.
     *
     * @throws Horde_SessionHandler_Exception
     */
    protected function _open($save_path = null, $session_name = null)
    {
        foreach ($this->_stack as $val) {
            $val->open($save_path, $session_name);
        }
    }

    /**
     * Close the backend.
     *
     * @throws Horde_SessionHandler_Exception
     */
    protected function _close()
    {
        foreach ($this->_stack as $val) {
            $val->close();
        }
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
        foreach ($this->_stack as $val) {
            $result = $val->read($id);
            if ($result === false) {
                break;
            }
        }

        return $result;
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
        /* Do writes in *reverse* order - it is OK if a write to one of the
         * non-master backends fails. */
        $master = true;

        foreach (array_reverse($this->_stack) as $val) {
            $result = $val->write($id, $session_data);
            if ($result === false) {
                if ($master) {
                    return false;
                }
                /* Attempt to invalidate cache if write failed. */
                $val->destroy($id);
            }
            $master = false;
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
        /* Only report success from master. */
        $master = $success = true;

        foreach (array_reverse($this->_stack) as $val) {
            $result = $val->destroy($id);
            if ($master && ($result === false)) {
                $success = false;
            }
            $master = false;
        }

        return $success;
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
        /* Only report GC results from master. */
        foreach ($this->_stack as $val) {
            $result = $val->gc($maxlifetime);
        }

        return $result;
    }

    /**
     * Get a list of the valid session identifiers.
     *
     * @return array  A list of valid session identifiers.
     * @throws Horde_SessionHandler_Exception
     */
    public function getSessionIDs()
    {
        /* Grab session ID list from the master. */
        $ob = end($this->_stack);
        return $ob->getSessionIDs();
    }

}
