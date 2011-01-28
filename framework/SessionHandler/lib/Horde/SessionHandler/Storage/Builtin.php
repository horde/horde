<?php
/**
 * SessionHandler storage implementation for PHP's built-in session handler.
 * This doesn't do any session handling itself - instead, it exists to allow
 * utility features to be used with the built-in PHP handler.
 *
 * Copyright 2005-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Matt Selsky <selsky@columbia.edu>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @package  SessionHandler
 */
class Horde_SessionHandler_Storage_Builtin extends Horde_SessionHandler_Storage
{
    /**
     * Directory with session files.
     *
     * @var string
     */
    protected $_path;

    /**
     */
    public function __construct(array $params = array())
    {
        parent::__construct($params);

        $this->_path = session_save_path();
        if (!$this->_path) {
            $this->_path = Horde_Util::getTempDir();
        }
    }

    /**
     */
    public function open($save_path = null, $session_name = null)
    {
    }

     /**
      */
    public function close()
    {
    }

    /**
     */
    public function read($id)
    {
        $file = $this->_path . '/sess_' . $id;
        $session_data = @file_get_contents($file);
        if (($session_data === false) && $this->_logger) {
            $this->_logger->log('Unable to read file: ' . $file, 'ERR');
        }

        return strval($session_data);
    }

    /**
     */
    public function write($id, $session_data)
    {
        return false;
    }

    /**
     */
    public function destroy($id)
    {
        return false;
    }

    /**
     */
    public function gc($maxlifetime = 300)
    {
        return false;
    }

    /**
     */
    public function getSessionIDs()
    {
        $sessions = array();

        try {
            $di = new DirectoryIterator($this->_path);
        } catch (UnexpectedValueException $e) {
            return $sessions;
        }

        foreach ($di as $val) {
            /* Make sure we're dealing with files that start with sess_. */
            if ($val->isFile() &&
                (strpos($val->getFilename(), 'sess_') === 0)) {
                $sessions[] = substr($val->getFilename(), strlen('sess_'));
            }
        }

        return $sessions;
    }

}
