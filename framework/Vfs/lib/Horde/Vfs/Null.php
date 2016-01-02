<?php
/**
 * Copyright 2014-2016 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category  Horde
 * @copyright 2014-2016 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Vfs
 */

/**
 * Null implementation of the VFS API.
 *
 * Copyright 2014-2016 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2014-2016 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Vfs
 * @since     2.1.3
 */
class Horde_Vfs_Null extends Horde_Vfs_Base
{
    /**
     */
    public function size($path, $name)
    {
        return 0;
    }

    /**
     */
    public function read($path, $name)
    {
        return '';
    }

    /**
     */
    public function readFile($path, $name)
    {
        throw new Horde_Vfs_Exception('Unable to create temporary file.');
    }

    /**
     */
    public function readByteRange($path, $name, &$offset, $length, &$remaining)
    {
        $remaining = 0;
        return '';
    }

    /**
     */
    public function write($path, $name, $tmpFile, $autocreate = false)
    {
    }

    /**
     */
    public function writeData($path, $name, $data, $autocreate = false)
    {
    }

    /**
     */
    public function deleteFile($path, $name)
    {
    }

    /**
     */
    public function rename($oldpath, $oldname, $newpath, $newname)
    {
    }

    /**
     */
    public function createFolder($path, $name)
    {
    }

    /**
     */
    public function deleteFolder($path, $name, $recursive = false)
    {
    }

    /**
     */
    protected function _listFolder($path, $filter = null, $dotfiles = true,
                                   $dironly = false)
    {
        return array();
    }

    /**
     */
    public function changePermissions($path, $name, $permission)
    {
    }

}
