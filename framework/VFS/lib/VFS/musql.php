<?php

require_once dirname(__FILE__) . '/sql.php';

/**
 * Multi User VFS implementation for PHP's PEAR database
 * abstraction layer.
 *
 * Required values for $params:<pre>
 *      'phptype'       The database type (ie. 'pgsql', 'mysql', etc.).</pre>
 *
 * Optional values:<pre>
 *      'table'         The name of the vfs table in 'database'. Defaults to
 *                      'horde_muvfs'.</pre>
 *
 * Required by some database implementations:<pre>
 *      'hostspec'      The hostname of the database server.
 *      'protocol'      The communication protocol ('tcp', 'unix', etc.).
 *      'database'      The name of the database.
 *      'username'      The username with which to connect to the database.
 *      'password'      The password associated with 'username'.
 *      'options'       Additional options to pass to the database.
 *      'tty'           The TTY on which to connect to the database.
 *      'port'          The port on which to connect to the database.</pre>
 *
 * Known Issues:
 * Delete is not recusive, so files and folders that used to be in a folder
 * that gets deleted live forever in the database, or re-appear when the folder
 * is recreated.
 * Rename has the same issue, so files are lost if a folder is renamed.
 *
 * The table structure for the VFS can be found in
 * data/muvfs.sql.
 *
 * Database specific notes:
 *
 * MSSQL:
 * - The vfs_data field must be of type IMAGE.
 * - You need the following php.ini settings:
 * <code>
 *    ; Valid range 0 - 2147483647. Default = 4096.
 *    mssql.textlimit = 0 ; zero to pass through
 *
 *    ; Valid range 0 - 2147483647. Default = 4096.
 *    mssql.textsize = 0 ; zero to pass through
 * </code>
 *
 * Copyright 2002-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @author  Mike Cochrane <mike@graftonhall.co.nz>
 * @package VFS
 */
class VFS_musql extends VFS_sql
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
     * @throws VFS_Exception
     */
    public function writeData($path, $name, $data, $autocreate = false)
    {
        $this->_connect();

        /* Make sure we have write access to this and all parent paths. */
        if ($path != '') {
            $paths = explode('/', $path);
            $path_name = array_pop($paths);
            if (!$this->isFolder(implode('/', $paths), $path_name)) {
                if (!$autocreate) {
                    throw new VFS_Exception(sprintf('Folder "%s" does not exist'), $path);
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
                $this->log($sql, PEAR_LOG_DEBUG);
                $results = $this->_db->getAll($sql, array($previous, $thispath));
                if ($results instanceof PEAR_Error) {
                    $this->log($results, PEAR_LOG_ERR);
                    throw new VFS_Exception($results->getMessage());
                }
                if (!is_array($results) || count($results) < 1) {
                    throw new VFS_Exception('Unable to create VFS file.');
                }

                $allowed = false;
                foreach ($results as $result) {
                    if ($result[0] == $this->_params['user'] ||
                        $result[1] & self::FLAG_WRITE) {
                        $allowed = true;
                        break;
                    }
                }

                if (!$allowed) {
                    throw new VFS_Exception('Access denied creating VFS file.');
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
     * @throws VFS_Exception
     */
    public function deleteFile($path, $name)
    {
        $this->_connect();

        $sql = sprintf('SELECT vfs_id, vfs_owner, vfs_perms FROM %s
                        WHERE vfs_path = ? AND vfs_name= ? AND vfs_type = ?',
                       $this->_params['table']);
        $this->log($sql, PEAR_LOG_DEBUG);
        $fileList = $this->_db->getAll($sql, array($path, $name, self::FILE));

        if ($fileList instanceof PEAR_Error) {
            $this->log($fileList, PEAR_LOG_ERR);
            throw new VFS_Exception($fileList->getMessage());
        }
        if (!is_array($fileList) || count($fileList) < 1) {
            throw new VFS_Exception('Unable to delete VFS file.');
        }

        /* There may be one or more files with the same name but the user may
         * not have read access to them, so doesn't see them. So we have to
         * delete the one they have access to. */
        foreach ($fileList as $file) {
            if ($file[1] == $this->_params['user'] ||
                $file[2] & self::FLAG_WRITE) {
                $sql = sprintf('DELETE FROM %s WHERE vfs_id = ?',
                               $this->_params['table']);
                $this->log($sql, PEAR_LOG_DEBUG);
                $result = $this->_db->query($sql, array($file[0]));

                if ($result instanceof PEAR_Error) {
                    $this->log($result, PEAR_LOG_ERR);
                    throw new VFS_Exception($result->getMessage());
                }
                if ($this->_db->affectedRows() == 0) {
                    throw new VFS_Exception('Unable to delete VFS file.');
                }
                return $result;
            }
        }

        // FIXME: 'Access Denied deleting file %s/%s'
        throw new VFS_Exception('Unable to delete VFS file.');
    }

    /**
     * Renames a file or folder in the VFS.
     *
     * @param string $oldpath  The old path to the file.
     * @param string $oldname  The old filename.
     * @param string $newpath  The new path of the file.
     * @param string $newname  The new filename.
     *
     * @throws VFS_Exception
     */
    public function rename($oldpath, $oldname, $newpath, $newname)
    {
        $this->_connect();

        $sql = sprintf('SELECT vfs_id, vfs_owner, vfs_perms FROM %s
                        WHERE vfs_path = ? AND vfs_name= ?',
                       $this->_params['table']);
        $this->log($sql, PEAR_LOG_DEBUG);
        $fileList = $this->_db->getAll($sql, array($oldpath, $oldname));

        if ($fileList instanceof PEAR_Error) {
            $this->log($fileList, PEAR_LOG_ERR);
            throw new VFS_Exception($fileList);
        }
        if (!is_array($fileList) || count($fileList) < 1) {
            throw new VFS_Exception('Unable to rename VFS file.');
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
            if ($file[1] == $this->_params['user'] ||
                $file[2] & self::FLAG_WRITE) {
                $sql = sprintf('UPDATE %s SET vfs_path = ?, vfs_name = ?, vfs_modified = ?
                                WHERE vfs_id = ?',
                               $this->_params['table']);
                $this->log($sql, PEAR_LOG_DEBUG);
                $result = $this->_db->query(
                    $sql,
                    array($newpath, $newname, time(), $file[0]));
                if ($result instanceof PEAR_Error) {
                    $this->log($result, PEAR_LOG_ERR);
                    throw new VFS_Exception($result->getMessage());
                }
                return $result;
            }
        }

        throw new VFS_Exception(sprintf('Unable to rename VFS file %s/%s.', $oldpath, $oldname));
    }

    /**
     * Creates a folder on the VFS.
     *
     * @param string $path  Holds the path of directory to create folder.
     * @param string $name  Holds the name of the new folder.
     *
     * @throws VFS_Exception
     */
    public function createFolder($path, $name)
    {
        $this->_connect();

        /* Make sure we have write access to this and all parent paths. */
        if (strlen($path)) {
            $paths = explode('/', $path);
            $previous = '';

            foreach ($paths as $thispath) {
                $sql = sprintf('SELECT vfs_owner, vfs_perms FROM %s
                                WHERE vfs_path = ? AND vfs_name= ?',
                               $this->_params['table']);
                $this->log($sql, PEAR_LOG_DEBUG);
                $results = $this->_db->getAll($sql, array($previous, $thispath));
                if ($results instanceof PEAR_Error) {
                    $this->log($results, PEAR_LOG_ERR);
                    throw new VFS_Exception($results->getMessage());
                }
                if (!is_array($results) || count($results) < 1) {
                    throw new VFS_Exception('Unable to create VFS directory.');
                }

                $allowed = false;
                foreach ($results as $result) {
                    if ($result[0] == $this->_params['user'] ||
                        $result[1] & self::FLAG_WRITE) {
                        $allowed = true;
                        break;
                    }
                }

                if (!$allowed) {
                    throw new VFS_Exception('Access denied creating VFS directory.');
                }

                $previous = $thispath;
            }
        }

        $id = $this->_db->nextId($this->_params['table']);
        $sql = sprintf('INSERT INTO %s
                        (vfs_id, vfs_type, vfs_path, vfs_name, vfs_modified, vfs_owner, vfs_perms)
                        VALUES (?, ?, ?, ?, ?, ?, ?)',
                       $this->_params['table']);
        $this->log($sql, PEAR_LOG_DEBUG);
        $result = $this->_db->query(
            $sql,
            array($id, VFS_FOLDER, $path, $name, time(), $this->_params['user'], 0));
        if ($result instanceof PEAR_Error) {
            $this->log($result, PEAR_LOG_ERR);
            throw new VFS_Exception($result->getMessage());
        }

        return $result;
    }

    /**
     * Deletes a folder from the VFS.
     *
     * @param string $path        The path to delete the folder from.
     * @param string $name        The foldername to use.
     * @param boolean $recursive  Force a recursive delete?
     *
     * @throws VFS_Exception
     */
    public function deleteFolder($path, $name, $recursive = false)
    {
        $this->_connect();

        if ($recursive) {
            $this->emptyFolder($path . '/' . $name);
        } else {
            $list = $this->listFolder($path . '/' . $name);
            if (count($list)) {
                throw new VFS_Exception(sprintf('Unable to delete %s, the directory is not empty', $path . '/' . $name));
            }
        }

        $sql = sprintf('SELECT vfs_id, vfs_owner, vfs_perms FROM %s
                        WHERE vfs_path = ? AND vfs_name= ? AND vfs_type = ?',
                       $this->_params['table']);
        $this->log($sql, PEAR_LOG_DEBUG);
        $fileList = $this->_db->getAll($sql, array($path, $name, VFS_FOLDER));

        if ($fileList instanceof PEAR_Error) {
            $this->log($fileList, PEAR_LOG_ERR);
            throw new VFS_Exception($fileList->getMessage());
        }
        if (!is_array($fileList) || count($fileList) < 1) {
            throw new VFS_Exception('Unable to delete VFS directory.');
        }

        /* There may be one or more folders with the same name but as the user
         * may not have read access to them, they don't see them. So we have
         * to delete the one they have access to */
        foreach ($fileList as $file) {
            if ($file[1] == $this->_params['user'] ||
                $file[2] & self::FLAG_WRITE) {
                $sql = sprintf('DELETE FROM %s WHERE vfs_id = ?',
                               $this->_params['table']);
                $this->log($sql, PEAR_LOG_DEBUG);
                $result = $this->_db->query($sql, array($file[0]));

                if ($result instanceof PEAR_Error) {
                    $this->log($result, PEAR_LOG_ERR);
                    throw new VFS_Exception($result->getMessage());
                }
                if ($this->_db->affectedRows() == 0) {
                    throw new VFS_Exception('Unable to delete VFS directory.');
                }

                return $result;
            }
        }

        // FIXME: 'Access Denied deleting folder %s/%s'
        throw new VFS_Exception('Unable to delete VFS directory.');
    }

    /**
     * Returns a list of the contents of a folder.
     *
     * @param string $path       The path of the directory.
     * @param mixed $filter      String/hash to filter file/dirname on.
     * @param boolean $dotfiles  Show dotfiles?
     * @param boolean $dironly   Show only directories?
     *
     * @return array  File list.
     * @throws VFS_Exception
     */
    protected function _listFolder($path, $filter = null, $dotfiles = true,
                                   $dironly = false)
    {
        $this->_connect();

        $length_op = $this->_getFileSizeOp();
        $sql = sprintf('SELECT vfs_name, vfs_type, vfs_modified, vfs_owner, vfs_perms, %s(vfs_data) FROM %s
                        WHERE vfs_path = ? AND (vfs_owner = ? OR vfs_perms \&\& ?)',
                       $length_op, $this->_params['table']);
        $this->log($sql, PEAR_LOG_DEBUG);
        $fileList = $this->_db->getAll(
            $sql,
            array($path, $this->_params['user'], self::FLAG_READ));
        if ($fileList instanceof PEAR_Error) {
            $this->log($fileList, PEAR_LOG_ERR);
            throw new VFS_Exception($fileList->getMessage());
        }

        $files = array();
        foreach ($fileList as $line) {
            // Filter out dotfiles if they aren't wanted.
            if (!$dotfiles && substr($line[0], 0, 1) == '.') {
                continue;
            }

            $file['name'] = stripslashes($line[0]);

            if ($line[1] == self::FILE) {
                $name = explode('.', $line[0]);

                if (count($name) == 1) {
                    $file['type'] = '**none';
                } else {
                    $file['type'] = self::strtolower($name[count($name) - 1]);
                }

                $file['size'] = $line[5];
            } elseif ($line[1] == self::FOLDER) {
                $file['type'] = '**dir';
                $file['size'] = -1;
            }

            $file['date'] = $line[2];
            $file['owner'] = $line[3];

            $line[4] = intval($line[4]);
            $file['perms']  = ($line[1] == self::FOLDER) ? 'd' : '-';
            $file['perms'] .= 'rw-';
            $file['perms'] .= ($line[4] & self::FLAG_READ) ? 'r' : '-';
            $file['perms'] .= ($line[4] & self::FLAG_WRITE) ? 'w' : '-';
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
     * @param string $path  Holds the path of directory of the Item.
     * @param string $name  Holds the name of the Item.
     *
     * @throws VFS_Exception
     */
    public function changePermissions($path, $name, $permission)
    {
        $this->_connect();

        $val = intval(substr($permission, -1));
        $perm = 0;
        $perm |= ($val & 4) ? self::FLAG_READ : 0;
        $perm |= ($val & 2) ? self::FLAG_WRITE : 0;

        $sql = sprintf('SELECT vfs_id, vfs_owner, vfs_perms FROM %s
                        WHERE vfs_path = ? AND vfs_name= ?',
                       $this->_params['table']);
        $this->log($sql, PEAR_LOG_DEBUG);
        $fileList = $this->_db->getAll($sql, array($path, $name));

        if ($fileList instanceof PEAR_Error) {
            $this->log($fileList, PEAR_LOG_ERR);
            throw new VFS_Exception($fileList->getMessage());
        }
        if (!is_array($fileList) || count($fileList) < 1) {
            throw new VFS_Exception('Unable to rename VFS file.');
        }

        /* There may be one or more files with the same name but the user may
         * not have read access to them, so doesn't see them. So we have to
         * chmod the one they have access to. */
        foreach ($fileList as $file) {
            if ($file[1] == $this->_params['user'] ||
                $file[2] & self::FLAG_WRITE) {
                $sql = sprintf('UPDATE %s SET vfs_perms = ?
                                WHERE vfs_id = ?',
                               $this->_params['table']);
                $this->log($sql, PEAR_LOG_DEBUG);
                $result = $this->_db->query($sql, array($perm, $file[0]));
                if ($result instanceof PEAR_Error) {
                    throw new VFS_Exception($result->getMessage());
                }
                return $result;
            }
        }

        throw new VFS_Exception(sprintf('Unable to change permission for VFS file %s/%s.', $path, $name));
    }

}
