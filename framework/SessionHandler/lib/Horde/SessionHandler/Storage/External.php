<?php
/**
 * SessionHandler storage implementation for an external save handler defined
 * via configuration parameters.
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @package  SessionHandler
 */
class Horde_SessionHandler_Storage_External extends Horde_SessionHandler_Storage
{
    /**
     * Constructor.
     *
     * @param array $params  Required parameters:
     * <pre>
     * close - (callback) See session_set_save_handler().
     * destroy - (callback) See session_set_save_handler().
     * gc - (callback) See session_set_save_handler().
     * open - (callback) See session_set_save_handler().
     * read - (callback) See session_set_save_handler().
     * write - (callback) See session_set_save_handler().
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
     */
    public function open($save_path = null, $session_name = null)
    {
        call_user_func($this->_params['open'], $save_path, $session_name);
    }

    /**
     */
    public function close()
    {
        call_user_func($this->_params['close']);
    }

    /**
     */
    public function read($id)
    {
        return call_user_func($this->_params['read']);
    }

    /**
     */
    public function write($id, $session_data)
    {
        return call_user_func($this->_params['write'], $id, $session_data);
    }

    /**
     */
    public function destroy($id)
    {
        return call_user_func($this->_params['destroy'], $id);
    }

    /**
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
