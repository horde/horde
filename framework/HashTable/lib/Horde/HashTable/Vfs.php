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
 * @package   HashTable
 */

/**
 * Implementation of HashTable for a VFS backend.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2014 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   HashTable
 * @since     1.2.0
 */
class Horde_HashTable_Vfs
extends Horde_HashTable_Base
{
    /* The virtual path to use for VFS data (temporary storage). */
    const VFS_PATH = '.horde/core/psession_data';

    /**
     */
    protected $_persistent = true;

    /**
     * The VFS object.
     *
     * @var Horde_Vfs_Base
     */
    protected $_vfs;

    /**
     * @param array $params  Additional configuration parameters:
     * <pre>
     *   - vfs: (Horde_Vfs_Base) [REQUIRED] VFS object.
     *   - vfspath: (string) VFS path to use.
     * </pre>
     */
    public function __construct(array $params = array())
    {
        if (!isset($params['vfs'])) {
            throw new InvalidArgumentException('Missing vfs parameter.');
        }

        parent::__construct(array_merge(array(
            'vfspath' => 'hashtable_vfs'
        ), $params));
    }

    /**
     */
    protected function _init()
    {
        $this->_vfs = $this->_params['vfs'];
    }

    /**
     */
    protected function _delete($keys)
    {
        $ret = true;

        foreach ($keys as $key) {
            try {
                $this->_vfs->deleteFile($this->_params['vfspath'], $key);
            } catch (Horde_Vfs_Exception $e) {
                $ret = false;
            }
        }

        return $ret;
    }

    /**
     */
    protected function _exists($keys)
    {
        $out = array();

        foreach ($keys as $key) {
            $out[$key] = $this->_vfs->exists($this->_params['vfspath'], $key);
        }
        return $out;
    }

    /**
     */
    protected function _get($keys)
    {
        $out = array();

        foreach ($keys as $key) {
            try {
                $out[$key] = $this->_vfs->read($this->_params['vfspath'], $key);
            } catch (Horde_Vfs_Exception $e) {
                $out[$key] = false;
            }
        }

        return $out;
    }

    /**
     */
    protected function _set($key, $val, $opts)
    {
        try {
            $this->_vfs->writeData($this->_params['vfspath'], $key, $val, true);
        } catch (Horde_Vfs_Exception $e) {
            return false;
        }

        return true;
    }

    /**
     */
    public function clear()
    {
        try {
            $this->_vfs->emptyFolder($this->_params['vfspath']);
        } catch (Horde_Vfs_Exception $e) {}
    }

}
