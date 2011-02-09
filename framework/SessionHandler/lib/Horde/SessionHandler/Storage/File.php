<?php
/**
 * SessionHandler implementation for storage in text files.
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
class Horde_SessionHandler_Storage_File extends Horde_SessionHandler_Storage
{
    /* File prefix. */
    const PREFIX = 'horde_sh_';

    /**
     * File stream.
     *
     * @var resource
     */
    protected $_fp;

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
     * Open the file stream connection.
     *
     * @param string $id  The session ID.
     */
    protected function _open($id)
    {
        if (!empty($this->_fp)) {
            return;
        }

        $filename = $this->_params['path'] . '/' . self::PREFIX . $id;

        $this->_fp = fopen($filename, is_readable($filename) ? 'r+' : 'w+');
        if ($this->_fp) {
            flock($this->_fp, LOCK_EX);
        }
    }

    /**
     */
    public function close()
    {
        if (!empty($this->_fp)) {
            flock($this->_fp, LOCK_UN);
            fclose($this->_fp);
            unset($this->_fp);
        }

        return true;
    }

    /**
     */
    public function read($id)
    {
        $this->_open($id);

        return $this->_fp
            ? strval(stream_get_contents($this->_fp, -1, 0))
            : '';
    }

    /**
     */
    public function write($id, $session_data)
    {
        $this->_open($id);

        if (!$this->_fp) {
            return false;
        }

        fseek($this->_fp, 0);
        ftruncate($this->_fp, 0);
        fwrite($this->_fp, $session_data);

        return true;
    }

    /**
     */
    public function destroy($id)
    {
        $this->close();

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
