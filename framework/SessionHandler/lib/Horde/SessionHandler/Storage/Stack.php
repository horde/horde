<?php
/**
 * SessionHandler storage implementation that will loop through a list of
 * storage drivers to handle the session information.
 * This driver allows for use of caching backends on top of persistent
 * backends, for example.
 *
 * Copyright 2010-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @package  SessionHandler
 */
class Horde_SessionHandler_Storage_Stack extends Horde_SessionHandler_Storage
{
    /**
     * Stack of storage objects.
     *
     * @var array
     */
    protected $_stack = array();

    /**
     * Constructor.
     *
     * @param array $params  Parameters:
     * <pre>
     * stack - (array) [REQUIRED] A list of storage objects to loop
     *         through, in order of priority. The last entry is considered
     *         the "master" server.
     * </pre>
     *
     * @throws InvalidArgumentException
     */
    public function __construct(array $params = array())
    {
        if (!isset($params['stack'])) {
            throw new InvalidArgumentException('Missing stack parameter.');
        }

        $this->_stack = $params['stack'];
        unset($params['stack']);

        parent::__construct($params);
    }

    /**
     * Set the logger object.
     *
     * @param Horde_Log_Logger $log  The logger instance.
     */
    public function setLogger(Horde_Log_Logger $log)
    {
        parent::setLogger($log);

        foreach ($this->_stack as $ob) {
            $ob->setLogger($log);
        }
    }

    /**
     */
    public function open($save_path = null, $session_name = null)
    {
        foreach ($this->_stack as $val) {
            $val->open($save_path, $session_name);
        }
    }

    /**
     */
    public function close()
    {
        foreach ($this->_stack as $val) {
            $val->close();
        }
    }

    /**
     */
    public function read($id)
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
     */
    public function write($id, $session_data)
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
     */
    public function getSessionIDs()
    {
        /* Grab session ID list from the master. */
        $ob = end($this->_stack);
        return $ob->getSessionIDs();
    }

}
