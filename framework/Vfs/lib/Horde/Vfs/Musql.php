<?php
/**
 * Multi User VFS implementation for Horde's database abstraction layer.
 *
 * Required values for $params:
 * - db: A Horde_Db_Adapter object.
 *
 * Optional values:
 * - table: (string) The name of the vfs table in 'database'. Defaults to
 *          'horde_muvfs'.
 *
 * Known Issues:
 * Delete is not recusive, so files and folders that used to be in a folder
 * that gets deleted live forever in the database, or re-appear when the folder
 * is recreated.
 * Rename has the same issue, so files are lost if a folder is renamed.
 *
 * The table structure for the VFS can be created with the horde-db-migrate
 * script from the Horde_Db package.
 *
 * Copyright 2002-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @author  Mike Cochrane <mike@graftonhall.co.nz>
 * @package Vfs
 */
class Horde_Vfs_Musql extends Horde_Vfs_Sql
{
    /* Permission for read access. */
    const FLAG_READ = 1;

    /* Permission for read access. */
    const FLAG_WRITE = 2;

    /**
     * List of permissions and if they can be changed in this VFS
     *
     * @var array
     */
    protected $_permissions = array(
        'owner' => array(
            'read' => false,
            'write' => false,
            'execute' => false
        ),
        'group' => array(
            'read' => false,
            'write' => false,
            'execute' => false
        ),
        'all' => array(
            'read' => true,
            'write' => true,
            'execute' => false
        )
    );

    /**
     * Stores a file in the VFS from raw data.
     *
     * @param string $path         The path to store the file in.
     * @param string $name         The filename to use.
     * @param string $data         The file data.
     * @param boolean $autocreate  Automatically create directories?
     *
     * @throws Horde_Vfs_Exception
     */
    public function writeData($path, $name, $data, $autocreate = false)
    {
        /* Make sure we have write access to this and all parent paths. */
        if ($path != '') {
            $paths = explode('/', $path);
            $path_name = array_pop($paths);
            if (!$this->isFolder(implode('/', $paths), $path_name)) {
                if (!$autocreate) {
                    throw new Horde_Vfs_Exception(sprintf('Folder "%s" does not exist'), $path);
                } else {
                    $this->autocreatePath($path);
                }
            }
            $paths[] = $path_name;
            $previous = '';

            foreach ($paths as $thispath) {
                $sql = sprintf('SELECT vfs_owner, vfs_perms FROM %s
                                WHERE vfs_path = ? AND vfs_name= ?',
                               $this->_params['table']);
                try {
                    $results = $this->_db->selectAll($sql, array($previous, $thispath));
                } catch (Horde_Db_Exception $e) {
                    throw new Horde_Vfs_Exception($e);
                }
                if (!is_array($results) || count($results) < 1) {
                    throw new Horde_Vfs_Exception('Unable to create VFS file.');
                }

                $allowed = false;
                foreach ($results as $result) {
                    if ($result['vfs_owner'] == $this->_params['user'] ||
                        $result['vfs_perm'] & self::FLAG_WRITE) {
                        $allowed = true;
                        break;
                    }
                }

                if (!$allowed) {
                    throw new Horde_Vfs_Exception('Access denied creating VFS file.');
                }

                $previous = $thispath;
            }
        }

        return parent::writeData($path, $name, $data, $autocreate);
    }

    /**
     * Deletes a file from the VFS.
     *
     * @param string $path  The path to store the file in.
     * @param string $name  The filename to use.
     *
     * @throws Horde_Vfs_Exception
     */
    public function deleteFile($path, $name)
    {
        $sql = sprintf('SELECT vfs_id, vfs_owner, vfs_perms FROM %s
                        WHERE vfs_path = ? AND vfs_name= ? AND vfs_type = ?',
                       $this->_params['table']);
        try {
            $fileList = $this->_db->selectAll($sql, array($path, $name, self::FILE));
        } catch (Horde_Db_Exception $e) {
            throw new Horde_Vfs_Exception($e);
        }
        if (!is_array($fileList) || count($fileList) < 1) {
            throw new Horde_Vfs_Exception('Unable to delete VFS file.');
        }

        /* There may be one or more files with the same name but the user may
         * not have read access to them, so doesn't see them. So we have to
         * delete the one they have access to. */
        foreach ($fileList as $file) {
            if ($file['vfs_owner'] == $this->_params['user'] ||
                $file['vfs_perms'] & self::FLAG_WRITE) {
                $sql = sprintf('DELETE FROM %s WHERE vfs_id = ?',
                               $this->_params['table']);
                try {
                    $result = $this->_db->delete($sql, array($file['vfs_id']));
                } catch (Horde_Db_Exception $e) {
                    throw new Horde_Vfs_Exception($e);
                }
                if ($result == 0) {
                    throw new Horde_Vfs_Exception('Unable to delete VFS file.');
                }
                return;
            }
        }

        // FIXME: 'Access Denied deleting file %s/%s'
        throw new Horde_Vfs_Exception('Unable to delete VFS file.');
    }

    /**
     * Renames a file or folder in the VFS.
     *
     * @param string $oldpath  The old path to the file.
     * @param string $oldname  The old filename.
     * @param string $newpath  The new path of the file.
     * @param string $newname  The new filename.
     *
     * @throws Horde_Vfs_Exception
     */
    public function rename($oldpath, $oldname, $newpath, $newname)
    {
        $sql = sprintf('SELECT vfs_id, vfs_owner, vfs_perms FROM %s
                        WHERE vfs_path = ? AND vfs_name= ?',
                       $this->_params['table']);
        try {
            $fileList = $this->_db->selectAll($sql, array($oldpath, $oldname));

        } catch (Horde_Db_Exception $e) {
            throw new Horde_Vfs_Exception($e);
        }
        if (!is_array($fileList) || count($fileList) < 1) {
            throw new Horde_Vfs_Exception('Unable to rename VFS file.');
        }

        if (strpos($newpath, '/') === false) {
            $parent = '';
            $path = $newpath;
        } else {
            list($parent, $path) = explode('/', $newpath, 2);
        }
        if (!$this->isFolder($parent, $path)) {
            $this->autocreatePath($newpath);
        }

        /* There may be one or more files with the same name but the user may
         * not have read access to them, so doesn't see them. So we have to
         * rename the one they have access to. */
        foreach ($fileList as $file) {
            if ($file['vfs_owner'] == $this->_params['user'] ||
                $file['vfs_perms'] & self::FLAG_WRITE) {
                $sql = sprintf('UPDATE %s SET vfs_path = ?, vfs_name = ?, vfs_modified = ?
                                WHERE vfs_id = ?',
                               $this->_params['table']);
                try {
                    $this->_db->update(
                        $sql,
                        array($newpath, $newname, time(), $file['vfs_id']));
                } catch (Horde_Db_Exception $e) {
                    throw new Horde_Vfs_Exception($e);
                }
            }
        }

        throw new Horde_Vfs_Exception(sprintf('Unable to rename VFS file %s/%s.', $oldpath, $oldname));
    }

    /**
     * Creates a folder on the VFS.
     *
     * @param string $path  Holds the path of directory to create folder.
     * @param string $name  Holds the name of the new folder.
     *
     * @throws Horde_Vfs_Exception
     */
    public function createFolder($path, $name)
    {
        /* Make sure we have write access to this and all parent paths. */
        if (strlen($path)) {
            $paths = explode('/', $path);
            $previous = '';

            foreach ($paths as $thispath) {
                $sql = sprintf('SELECT vfs_owner, vfs_perms FROM %s
                                WHERE vfs_path = ? AND vfs_name= ?',
                               $this->_params['table']);
                try {
                    $results = $this->_db->selectAll($sql, array($previous, $thispath));
                } catch (Horde_Db_Exception $e) {
                    throw new Horde_Vfs_Exception($e);
                }
                if (!is_array($results) || count($results) < 1) {
                    throw new Horde_Vfs_Exception('Unable to create VFS directory.');
                }

                $allowed = false;
                foreach ($results as $result) {
                    if ($result['vfs_owner'] == $this->_params['user'] ||
                        $result['vfs_perms'] & self::FLAG_WRITE) {
                        $allowed = true;
                        break;
                    }
                }

                if (!$allowed) {
                    throw new Horde_Vfs_Exception('Access denied creating VFS directory.');
                }

                $previous = $thispath;
            }
        }

        $sql = sprintf('INSERT INTO %s
                        (vfs_type, vfs_path, vfs_name, vfs_modified, vfs_owner, vfs_perms)
                        VALUES (?, ?, ?, ?, ?, ?)',
                       $this->_params['table']);
        try {
            $this->_db->insert(
                $sql,
                array(self::FOLDER, $path, $name, time(), $this->_params['user'], 0));
        } catch (Horde_Db_Exception $e) {
            throw new Horde_Vfs_Exception($e);
        }
    }

    /**
     * Deletes a folder from the VFS.
     *
     * @param string $path        The path to delete the folder from.
     * @param string $name        The foldername to use.
     * @param boolean $recursive  Force a recursive delete?
     *
     * @throws Horde_Vfs_Exception
     */
    public function deleteFolder($path, $name, $recursive = false)
    {
        if ($recursive) {
            $this->emptyFolder($path . '/' . $name);
        } else {
            $list = $this->listFolder($path . '/' . $name);
            if (count($list)) {
                throw new Horde_Vfs_Exception(sprintf('Unable to delete %s, the directory is not empty', $path . '/' . $name));
            }
        }

        $sql = sprintf('SELECT vfs_id, vfs_owner, vfs_perms FROM %s
                        WHERE vfs_path = ? AND vfs_name= ? AND vfs_type = ?',
                       $this->_params['table']);
        try {
            $fileList = $this->_db->selectAll($sql, array($path, $name, self::FOLDER));
        } catch (Horde_Db_Exception $e) {
            throw new Horde_Vfs_Exception($e);
        }
        if (!is_array($fileList) || count($fileList) < 1) {
            throw new Horde_Vfs_Exception('Unable to delete VFS directory.');
        }

        /* There may be one or more folders with the same name but as the user
         * may not have read access to them, they don't see them. So we have
         * to delete the one they have access to */
        foreach ($fileList as $file) {
            if ($file['vfs_owner'] == $this->_params['user'] ||
                $file['vfs_perms'] & self::FLAG_WRITE) {
                $sql = sprintf('DELETE FROM %s WHERE vfs_id = ?',
                               $this->_params['table']);
                try {
                    $result = $this->_db->delete($sql, array($file['vfs_id']));
                } catch (Horde_Db_Exception $e) {
                    throw new Horde_Vfs_Exception($e);
                }
                if ($result == 0) {
                    throw new Horde_Vfs_Exception('Unable to delete VFS directory.');
                }

                return $result;
            }
        }

        // FIXME: 'Access Denied deleting folder %s/%s'
        throw new Horde_Vfs_Exception('Unable to delete VFS directory.');
    }

    /**
     * Returns an an unsorted file list of the specified directory.
     *
     * @param string $path       The path of the directory.
     * @param mixed $filter      String/hash to filter file/dirname on.
     * @param boolean $dotfiles  Show dotfiles?
     * @param boolean $dironly   Show only directories?
     *
     * @return array  File list.
     * @throws Horde_Vfs_Exception
     */
    protected function _listFolder($path, $filter = null, $dotfiles = true,
                                   $dironly = false)
    {
        $length_op = $this->_getFileSizeOp();
        $sql = sprintf('SELECT vfs_name, vfs_type, vfs_modified, vfs_owner, vfs_perms, %s(vfs_data) length FROM %s
                        WHERE vfs_path = ? AND (vfs_owner = ? OR vfs_perms \&\& ?)',
                       $length_op, $this->_params['table']);
        try {
            $fileList = $this->_db->selectAll(
                $sql,
                array($path, $this->_params['user'], self::FLAG_READ));
        } catch (Horde_Db_Exception $e) {
            throw new Horde_Vfs_Exception($e);
        }

        $files = array();
        foreach ($fileList as $line) {
            // Filter out dotfiles if they aren't wanted.
            if (!$dotfiles && substr($line['vfs_name'], 0, 1) == '.') {
                continue;
            }

            $file['name'] = stripslashes($line['vfs_name']);

            if ($line['vfs_type'] == self::FILE) {
                $name = explode('.', $line['vfs_name']);

                if (count($name) == 1) {
                    $file['type'] = '**none';
                } else {
                    $file['type'] = Horde_String::lower($name[count($name) - 1]);
                }

                $file['size'] = $line['length'];
            } elseif ($line['vfs_type'] == self::FOLDER) {
                $file['type'] = '**dir';
                $file['size'] = -1;
            }

            $file['date'] = $line['vfs_modified'];
            $file['owner'] = $line['vfs_owner'];

            $line['vfs_perms'] = intval($line['vfs_perms']);
            $file['perms']  = ($line['vfs_type'] == self::FOLDER) ? 'd' : '-';
            $file['perms'] .= 'rw-';
            $file['perms'] .= ($line['vfs_perms'] & self::FLAG_READ) ? 'r' : '-';
            $file['perms'] .= ($line['vfs_perms'] & self::FLAG_WRITE) ? 'w' : '-';
            $file['perms'] .= '-';
            $file['group'] = '';

            // Filtering.
            if ($this->_filterMatch($filter, $file['name'])) {
                unset($file);
                continue;
            }
            if ($dironly && $file['type'] !== '**dir') {
                unset($file);
                continue;
            }

            $files[$file['name']] = $file;
            unset($file);
        }

        return $files;
    }

    /**
     * Changes permissions for an Item on the VFS.
     *
     * @param string $path        The path of directory of the item.
     * @param string $name        The name of the item.
     * @param string $permission  The permission to set in octal notation.
     *
     * @throws Horde_Vfs_Exception
     */
    public function changePermissions($path, $name, $permission)
    {
        $val = intval(substr($permission, -1));
        $perm = 0;
        $perm |= ($val & 4) ? self::FLAG_READ : 0;
        $perm |= ($val & 2) ? self::FLAG_WRITE : 0;

        $sql = sprintf('SELECT vfs_id, vfs_owner, vfs_perms FROM %s
                        WHERE vfs_path = ? AND vfs_name= ?',
                       $this->_params['table']);
        try {
            $fileList = $this->_db->selectAll($sql, array($path, $name));
        } catch (Horde_Db_Exception $e) {
            throw new Horde_Vfs_Exception($e);
        }
        if (!is_array($fileList) || count($fileList) < 1) {
            throw new Horde_Vfs_Exception('Unable to rename VFS file.');
        }

        /* There may be one or more files with the same name but the user may
         * not have read access to them, so doesn't see them. So we have to
         * chmod the one they have access to. */
        foreach ($fileList as $file) {
            if ($file['vfs_owner'] == $this->_params['user'] ||
                $file['vfs_perms'] & self::FLAG_WRITE) {
                $sql = sprintf('UPDATE %s SET vfs_perms = ?
                                WHERE vfs_id = ?',
                               $this->_params['table']);
                try {
                    $result = $this->_db->update($sql, array($perm, $file['vfs_id']));
                } catch (Horde_Db_Exception $e) {
                    throw new Horde_Vfs_Exception($e);
                }
            }
        }

        throw new Horde_Vfs_Exception(sprintf('Unable to change permission for VFS file %s/%s.', $path, $name));
    }

}
