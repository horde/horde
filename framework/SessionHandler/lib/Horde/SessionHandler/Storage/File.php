<?php
/**
 * SessionHandler implementation for storage in text files.
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
class Horde_SessionHandler_Storage_File extends Horde_SessionHandler_Storage
{
    const PREFIX = 'horde_sh_';

    /**
     * Constructor.
     *
     * @param array $params  Parameters:
     * <pre>
     * path - (string) [REQUIRED] The path to save the files.
     * </pre>
     *
     * @throws InvalidArgumentException
     */
    public function __construct(array $params = array())
    {
        if (!isset($params['path'])) {
            throw new InvalidArgumentException('Missing path parameter.');
        }
        $params['path'] = rtrim($params['path'], '/');

        parent::__construct($params);
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
        $filename = $this->_params['path'] . '/' . self::PREFIX . $id;

        return is_readable($filename)
            ? file_get_contents($filename)
            : '';
    }

    /**
     */
    public function write($id, $session_data)
    {
        $filename = $this->_params['path'] . '/' . self::PREFIX . $id;

        return @file_put_contents($filename, $session_data);
    }

    /**
     */
    public function destroy($id)
    {
        $filename = $this->_params['path'] . '/' . self::PREFIX . $id;

        return @unlink($filename);
    }

    /**
     */
    public function gc($maxlifetime = 300)
    {
        try {
            $di = new DirectoryIterator($this->_params['path']);
        } catch (UnexpectedValueException $e) {
            return false;
        }

        $expire_time = time() - $maxlifetime;

        foreach ($di as $val) {
            if ($val->isFile() &&
                (strpos($val->getFilename(), self::PREFIX) === 0) &&
                ($val->getMTime() < $expire_time)) {
                @unlink($val->getPathname());
            }
        }

        return true;
    }

    /**
     */
    public function getSessionIDs()
    {
        $ids = array();

        try {
            $di = new DirectoryIterator($this->_params['path']);
            foreach ($di as $val) {
                if ($val->isFile() &&
                    (strpos($val->getFilename(), self::PREFIX) === 0)) {
                    $ids[] = substr($val->getFilename(), strlen(self::PREFIX));
                }
            }
        } catch (UnexpectedValueException $e) {}

        return $ids;
    }

}
