<?php
/**
 * Copyright 2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category  Horde
 * @copyright 2014 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Core
 */

/**
 * Vfs HashTable implementation that uses Horde configuration for the VFS,
 * allows filenames to be directly used in set(), and enables stream data to
 * be returned from get().
 *
 * This is a common use-case for Horde applications (i.e. it allows direct
 * manipulation of filedata uploaded from the browser without needing to
 * read the file into memory), and allows us to take advantage of these VFS
 * features in combination with the cleanup and simplicity features of
 * HashTable.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2014 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Core
 * @since     2.13.0
 */
class Horde_Core_HashTable_Vfs
extends Horde_HashTable_Vfs
{
    /**
     * Return get data as stream objects?
     *
     * @var boolean
     */
    protected $_stream = false;

    /**
     */
    public function __construct(array $params = array())
    {
        global $injector;

        try {
            $vfs = $injector->getInstance('Horde_Core_Factory_Vfs')->create();
        } catch (Horde_Vfs_Exception $e) {}

        if (!isset($vfs) || ($vfs instanceof Horde_Vfs_Null)) {
            $vfs = new Horde_Vfs_File(array('vfsroot' => Horde::getTempDir()));
        }

        parent::__construct(array_merge($params, array(
            'logger' => $injector->getInstance('Horde_Core_Log_Wrapper'),
            'vfs' => $vfs
        )));
    }

    /**
     * Get data associated with a key(s).
     *
     * @param mixed $keys  The key or an array of keys.
     *
     * @return mixed  The string/array on success (return type is the type of
     *                $keys); Horde_Stream objects are returned on success,
     *                false value(s) on failure.
     */
    public function getStream($keys)
    {
        $this->_stream = true;
        $out = $this->get($keys);
        $this->_stream = false;
        return $out;
    }

    /**
     */
    protected function _get($keys)
    {
        if (!$this->_stream) {
            return parent::_get($keys);
        }

        $out = array();

        foreach ($keys as $key) {
            try {
                if (method_exists($this->_vfs, 'readStream')) {
                    $data = new Horde_Stream_Existing(array(
                        'stream' => $this->_vfs->readStream($this->_params['vfspath'], $key)
                    ));
                    $data->rewind();
                } else {
                    $data = new Horde_Stream_Temp();
                    $data->add(
                        $this->_vfs->read($this->_params['vfspath'], $key),
                        true
                    );
                }
            } catch (Horde_Vfs_Exception $e) {
                $data = false;
            }

            $out[$key] = $data;
        }

        return $out;
    }

    /**
     * @param array $opts  Additional option honored in this driver:
     *   - filename: (boolean) If true, $val is a filename containing the
     *               data to be saved rather than the data itself.
     */
    protected function _set($key, $val, $opts)
    {
        if (empty($opts['filename'])) {
            return parent::_set($key, $val, $opts);
        }

        try {
            $this->_vfs->write($this->_params['vfspath'], $key, $val, true);
        } catch (Horde_Vfs_Exception $e) {
            return false;
        }

        return true;
    }

    /**
     * Perform garbage collection on the VFS path.
     *
     * @param integer $expire  Expire entries older than this value (in
     *                         seconds).
     */
    public function gc($expire)
    {
        Horde_Vfs_Gc::gc($this->_vfs, $this->_params['vfspath'], $expire);
    }

}
