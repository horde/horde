<?php
/**
 * VFS implementation for PHP's PEAR database abstraction layer.
 *
 * Required values for $params:<pre>
 * db - (DB) A DB object.
 * </pre>
 *
 * Optional values:<pre>
 * table - (string) The name of the vfs table in 'database'. Defaults to
 *         'horde_vfs'.
 * </pre>
 *
 * Optional values when using separate reading and writing servers, for example
 * in replication settings:<pre>
 * writedb - (DB) A writable DB object
 * </pre>
 *
 * The table structure for the VFS can be found in data/vfs.sql.
 *
 * Database specific notes:
 *
 * MSSQL:
 * <pre>
 * - The vfs_data field must be of type IMAGE.
 * - You need the following php.ini settings:
 *    ; Valid range 0 - 2147483647. Default = 4096.
 *    mssql.textlimit = 0 ; zero to pass through
 *
 *    ; Valid range 0 - 2147483647. Default = 4096.
 *    mssql.textsize = 0 ; zero to pass through
 * </pre>
 *
 * Copyright 2002-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @package  VFS
 */
class VFS_sql extends VFS
{
    /* File value for vfs_type column. */
    const FILE = 1;

    /* Folder value for vfs_type column. */
    const FOLDER = 2;

    /**
     * Handle for the current database connection.
     *
     * @var DB
     */
    protected $_db = false;

    /**
     * Handle for the current database connection, used for writing. Defaults
     * to the same handle as $_db if a separate write database is not required.
     *
     * @var DB
     */
    protected $_write_db;

    /**
     * Boolean indicating whether or not we're connected to the SQL
     * server.
     *
     * @var boolean
     */
    protected $_connected = false;

    /**
     * Retrieves the filesize from the VFS.
     *
     * @param string $path  The pathname to the file.
     * @param string $name  The filename to retrieve.
     *
     * @return integer  The file size.
     * @throws VFS_Exception
     */
    public function size($path, $name)
    {
        $this->_connect();

        $length_op = $this->_getFileSizeOp();
        $sql = sprintf(
            'SELECT %s(vfs_data) FROM %s WHERE vfs_path = ? AND vfs_name = ?',
            $length_op,
            $this->_params['table']
        );
        $values = array($this->_convertPath($path), $name);
        $this->log($sql, PEAR_LOG_DEBUG);
        $size = $this->_db->getOne($sql, $values);

        if (is_null($size)) {
            throw new VFS_Exception(sprintf('Unable to check file size of "%s/%s".', $path, $name));
        }

        return $size;
    }

    /**
     * Returns the size of a file.
     *
     * @param string $path  The path of the file.
     * @param string $name  The filename.
     *
     * @return integer  The size of the folder in bytes.
     * @throws VFS_Exception
     */
    public function getFolderSize($path = null, $name = null)
    {
        $this->_connect();

        $where = is_null($path)
            ? null
            : sprintf('WHERE vfs_path LIKE %s', ((!strlen($path)) ? '""' : $this->_db->quote($this->_convertPath($path) . '%')));
        $length_op = $this->_getFileSizeOp();
        $sql = sprintf(
            'SELECT SUM(%s(vfs_data)) FROM %s %s',
            $length_op,
            $this->_params['table'],
            $where
        );
        $this->log($sql, PEAR_LOG_DEBUG);
        $size = $this->_db->getOne($sql);

        return is_null($size) ? $size : 0;
    }

    /**
     * Retrieve a file from the VFS.
     *
     * @param string $path  The pathname to the file.
     * @param string $name  The filename to retrieve.
     *
     * @return string  The file data.
     * @throws VFS_Exception
     */
    public function read($path, $name)
    {
        $this->_connect();
        return $this->_readBlob($this->_params['table'], 'vfs_data', array(
            'vfs_path' => $this->_convertPath($path),
            'vfs_name' => $name
        ));
    }

    /**
     * Retrieves a part of a file from the VFS. Particularly useful
     * when reading large files which would exceed the PHP memory
     * limits if they were stored in a string.
     *
     * @param string  $path       The pathname to the file.
     * @param string  $name       The filename to retrieve.
     * @param integer $offset     The offset of the part. (The new offset will
     *                            be stored in here).
     * @param integer $length     The length of the part. If the length = -1,
     *                            the whole part after the offset is
     *                            retrieved. If more bytes are given as exists
     *                            after the given offset. Only the available
     *                            bytes are read.
     * @param integer $remaining  The bytes that are left, after the part that
     *                            is retrieved.
     *
     * @return string  The file data.
     * @throws VFS_Exception
     */
    public function readByteRange($path, $name, &$offset, $length = -1,
                                  &$remaining)
    {
        $this->_connect();

        $data = $this->_readBlob($this->_params['table'], 'vfs_data', array(
            'vfs_path' => $this->_convertPath($path),
            'vfs_name' => $name
        ));

        // Calculate how many bytes MUST be read, so the remainging
        // bytes and the new offset can be calculated correctly.
        $size = strlen ($data);
        if ($length == -1 || (($length + $offset) > $size)) {
            $length = $size - $offset;
        }
        if ($remaining < 0) {
            $remaining = 0;
        }

        $data = substr($data, $offset, $length);
        $offset = $offset + $length;
        $remaining = $size - $offset;

        return $data;
    }

    /**
     * Stores a file in the VFS.
     *
     * @param string $path         The path to store the file in.
     * @param string $name         The filename to use.
     * @param string $tmpFile      The temporary file containing the data to
     *                             be stored.
     * @param boolean $autocreate  Automatically create directories?
     *
     * @throws VFS_Exception
     */
    public function write($path, $name, $tmpFile, $autocreate = false)
    {
        /* Don't need to check quota here since it will be checked when
         * writeData() is called. */
        return $this->writeData($path,
                                $name,
                                file_get_contents($tmpFile),
                                $autocreate);
    }

    /**
     * Store a file in the VFS from raw data.
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
        $this->_checkQuotaWrite('string', $data);
        $this->_connect();

        $path = $this->_convertPath($path);

        /* Check to see if the data already exists. */
        $sql = sprintf('SELECT vfs_id FROM %s WHERE vfs_path %s AND vfs_name = ?',
                       $this->_params['table'],
                       (!strlen($path) && $this->_db->dbsyntax == 'oci8') ? ' IS NULL' : ' = ' . $this->_db->quote($path));
        $values = array($name);
        $this->log($sql, PEAR_LOG_DEBUG);
        $id = $this->_db->getOne($sql, $values);

        if ($id instanceof PEAR_Error) {
            $this->log($id, PEAR_LOG_ERR);
            throw new VFS_Exception($id->getMessage());
        }

        if (!is_null($id)) {
            return $this->_updateBlob($this->_params['table'], 'vfs_data',
                                      $data, array('vfs_id' => $id),
                                      array('vfs_modified' => time()));
        } else {
            /* Check to see if the folder already exists. */
            $dirs = explode('/', $path);
            $path_name = array_pop($dirs);
            $parent = implode('/', $dirs);
            if (!$this->isFolder($parent, $path_name)) {
                if (!$autocreate) {
                    throw new VFS_Exception(sprintf('Folder "%s" does not exist', $path));
                }

                $this->autocreatePath($path);
            }

            $id = $this->_write_db->nextId($this->_params['table']);
            if ($id instanceof PEAR_Error) {
                $this->log($id, PEAR_LOG_ERR);
                throw new VFS_Exception($id->getMessage());
            }

            return $this->_insertBlob($this->_params['table'], 'vfs_data', $data, array(
                'vfs_id' => $id,
                'vfs_type' => self::FILE,
                'vfs_path' => $path,
                'vfs_name' => $name,
                'vfs_modified' => time(),
                'vfs_owner' => $this->_params['user']
            ));
        }
    }

    /**
     * Delete a file from the VFS.
     *
     * @param string $path  The path to store the file in.
     * @param string $name  The filename to use.
     *
     * @throws VFS_Exception
     */
    public function deleteFile($path, $name)
    {
        $this->_checkQuotaDelete($path, $name);
        $this->_connect();

        $path = $this->_convertPath($path);

        $sql = sprintf('DELETE FROM %s WHERE vfs_type = ? AND vfs_path %s AND vfs_name = ?',
                       $this->_params['table'],
                       (!strlen($path) && $this->_db->dbsyntax == 'oci8') ? ' IS NULL' : ' = ' . $this->_db->quote($path));
        $values = array(self::FILE, $name);
        $this->log($sql, PEAR_LOG_DEBUG);
        $result = $this->_write_db->query($sql, $values);

        if ($this->_db->affectedRows() == 0) {
            throw new VFS_Exception('Unable to delete VFS file.');
        }

        return $result;
    }

    /**
     * Rename a file or folder in the VFS.
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

        if (strpos($newpath, '/') === false) {
            $parent = '';
            $path = $newpath;
        } else {
            list($parent, $path) = explode('/', $newpath, 2);
        }

        if (!$this->isFolder($parent, $path)) {
            $this->autocreatePath($newpath);
        }

        $oldpath = $this->_convertPath($oldpath);
        $newpath = $this->_convertPath($newpath);

        $sql  = 'UPDATE ' . $this->_params['table'];
        $sql .= ' SET vfs_path = ?, vfs_name = ?, vfs_modified = ? WHERE vfs_path = ? AND vfs_name = ?';
        $this->log($sql, PEAR_LOG_DEBUG);

        $values = array($newpath, $newname, time(), $oldpath, $oldname);

        $result = $this->_write_db->query($sql, $values);

        if ($this->_write_db->affectedRows() == 0) {
            throw new VFS_Exception('Unable to rename VFS file.');
        }

        $rename = $this->_recursiveRename($oldpath, $oldname, $newpath, $newname);
        if ($rename instanceof PEAR_Error) {
            $this->log($rename, PEAR_LOG_ERR);
            throw new VFS_Exception(sprintf('Unable to rename VFS directory: %s.', $rename->getMessage()));
        }

        return $result;
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

        $id = $this->_write_db->nextId($this->_params['table']);
        if ($id instanceof PEAR_Error) {
            $this->log($id, PEAR_LOG_ERR);
            throw new VFS_Exception($id->getMessage());
        }

        $sql = 'INSERT INTO ' . $this->_params['table'] .
               ' (vfs_id, vfs_type, vfs_path, vfs_name, vfs_modified, vfs_owner) VALUES (?, ?, ?, ?, ?, ?)';
        $this->log($sql, PEAR_LOG_DEBUG);

        $values = array($id, self::FOLDER, $this->_convertPath($path), $name, time(), $this->_params['user']);

        return $this->_write_db->query($sql, $values);
    }

    /**
     * Delete a folder from the VFS.
     *
     * @param string $path        The path of the folder.
     * @param string $name        The folder name to use.
     * @param boolean $recursive  Force a recursive delete?
     *
     * @throws VFS_Exception
     */
    public function deleteFolder($path, $name, $recursive = false)
    {
        $this->_connect();

        $path = $this->_convertPath($path);
        $folderPath = $this->_getNativePath($path, $name);

        /* Check if not recursive and fail if directory not empty */
        if (!$recursive) {
            $folderList = $this->listFolder($folderPath, null, true);
            if (!empty($folderList)) {
                throw new VFS_Exception(sprintf('Unable to delete %s, the directory is not empty', $path . '/' . $name));
            }
        }

        /* First delete everything below the folder, so if error we
         * get no orphans */
        $sql = sprintf('DELETE FROM %s WHERE vfs_path %s',
                       $this->_params['table'],
                       (!strlen($folderPath) && $this->_write_db->dbsyntax == 'oci8') ? ' IS NULL' : ' LIKE ' . $this->_write_db->quote($this->_getNativePath($folderPath, '%')));
        $this->log($sql, PEAR_LOG_DEBUG);
        $deleteContents = $this->_write_db->query($sql);
        if ($deleteContents instanceof PEAR_Error) {
            $this->log($deleteContents, PEAR_LOG_ERR);
            throw new VFS_Exception(sprintf('Unable to delete VFS recursively: %s.', $deleteContents->getMessage()));
        }

        /* Now delete everything inside the folder. */
        $sql = sprintf('DELETE FROM %s WHERE vfs_path %s',
                       $this->_params['table'],
                       (!strlen($path) && $this->_write_db->dbsyntax == 'oci8') ? ' IS NULL' : ' = ' . $this->_write_db->quote($folderPath));
        $this->log($sql, PEAR_LOG_DEBUG);
        $delete = $this->_write_db->query($sql);
        if ($delete instanceof PEAR_Error) {
            $this->log($delete, PEAR_LOG_ERR);
            throw new VFS_Exception(sprintf('Unable to delete VFS directory: %s.', $delete->getMessage()));
        }

        /* All ok now delete the actual folder */
        $sql = sprintf('DELETE FROM %s WHERE vfs_path %s AND vfs_name = ?',
                       $this->_params['table'],
                       (!strlen($path) && $this->_write_db->dbsyntax == 'oci8') ? ' IS NULL' : ' = ' . $this->_write_db->quote($path));
        $values = array($name);
        $this->log($sql, PEAR_LOG_DEBUG);
        $delete = $this->_write_db->query($sql, $values);
        if ($delete instanceof PEAR_Error) {
            $this->log($delete, PEAR_LOG_ERR);
            throw new VFS_Exception(sprintf('Unable to delete VFS directory: %s.', $delete->getMessage()));
        }
    }

    /**
     * Return a list of the contents of a folder.
     *
     * @param string $path       The directory path.
     * @param mixed $filter      String/hash of items to filter based on
     *                           filename.
     * @param boolean $dotfiles  Show dotfiles?
     * @param boolean $dironly   Show directories only?
     *
     * @return array  File list.
     * @throws VFS_Exception
     */
    protected function _listFolder($path, $filter = null, $dotfiles = true,
                                   $dironly = false)
    {
        $this->_connect();

        $path = $this->_convertPath($path);

        // Fix for Oracle not differentiating between '' and NULL.
        if (!strlen($path) && $this->_db->dbsyntax == 'oci8') {
            $where = 'vfs_path IS NULL';
        } else {
            $where = 'vfs_path = ' . $this->_db->quote($path);
        }

        $length_op = $this->_getFileSizeOp();
        $sql = sprintf('SELECT vfs_name, vfs_type, %s(vfs_data), vfs_modified, vfs_owner FROM %s WHERE %s',
                       $length_op,
                       $this->_params['table'],
                       $where);
        $this->log($sql, PEAR_LOG_DEBUG);
        $fileList = $this->_db->getAll($sql);
        if ($fileList instanceof PEAR_Error) {
            throw new VFS_Exception($fileList->getMessage());
        }

        $files = array();
        foreach ($fileList as $line) {
            // Filter out dotfiles if they aren't wanted.
            if (!$dotfiles && substr($line[0], 0, 1) == '.') {
                continue;
            }

            $file['name'] = $line[0];

            if ($line[1] == self::FILE) {
                $name = explode('.', $line[0]);

                if (count($name) == 1) {
                    $file['type'] = '**none';
                } else {
                    $file['type'] = self::strtolower($name[count($name) - 1]);
                }

                $file['size'] = $line[2];
            } elseif ($line[1] == self::FOLDER) {
                $file['type'] = '**dir';
                $file['size'] = -1;
            }

            $file['date'] = $line[3];
            $file['owner'] = $line[4];
            $file['perms'] = '';
            $file['group'] = '';

            // filtering
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
     * Returns a sorted list of folders in specified directory.
     *
     * @param string $path         The path of the directory to get the
     *                             directory list for.
     * @param mixed $filter        String/hash of items to filter based on
     *                             folderlist.
     * @param boolean $dotfolders  Include dotfolders?
     *
     * @return array  Folder list.
     * @throws VFS_Exception
     */
    public function listFolders($path = '', $filter = array(),
                                $dotfolders = true)
    {
        $this->_connect();

        $path = $this->_convertPath($path);

        $sql  = 'SELECT vfs_name, vfs_path FROM ' . $this->_params['table'];
        $sql .= ' WHERE vfs_path = ? AND vfs_type = ?';
        $this->log($sql, PEAR_LOG_DEBUG);

        $values = array($path, self::FOLDER);

        $folderList = $this->_db->getAll($sql, $values);
        if ($folderList instanceof PEAR_Error) {
            throw new VFS_Exception($folderList->getMessage());
        }

        $folders = array();
        foreach ($folderList as $line) {
            $folder['val'] = $this->_getNativePath($line[1], $line[0]);
            $folder['abbrev'] = '';
            $folder['label'] = '';

            $count = substr_count($folder['val'], '/');

            $x = 0;
            while ($x < $count) {
                $folder['abbrev'] .= '    ';
                $folder['label'] .= '    ';
                $x++;
            }

            $folder['abbrev'] .= $line[0];
            $folder['label'] .= $line[0];

            $strlen = VFS::strlen($folder['label']);
            if ($strlen > 26) {
                $folder['abbrev'] = substr($folder['label'], 0, ($count * 4));
                $length = (29 - ($count * 4)) / 2;
                $folder['abbrev'] .= substr($folder['label'], ($count * 4), $length);
                $folder['abbrev'] .= '...';
                $folder['abbrev'] .= substr($folder['label'], -1 * $length, $length);
            }

            $found = false;
            foreach ($filter as $fltr) {
                if ($folder['val'] == $fltr) {
                    $found = true;
                }
            }

            if (!$found) {
                $folders[$folder['val']] = $folder;
            }
        }

        ksort($folders);
        return $folders;
    }

    /**
     * Garbage collect files in the VFS storage system.
     *
     * @param string $path   The VFS path to clean.
     * @param integer $secs  The minimum amount of time (in seconds) required
     *                       before a file is removed.
     *
     * @throws VFS_Exception
     */
    public function gc($path, $secs = 345600)
    {
        $this->_connect();

        $sql = 'DELETE FROM ' . $this->_params['table']
            . ' WHERE vfs_type = ? AND vfs_modified < ? AND (vfs_path = ? OR vfs_path LIKE ?)';
        $this->log($sql, PEAR_LOG_DEBUG);

        $values = array(
            self::FILE,
            time() - $secs,
            $this->_convertPath($path),
            $this->_convertPath($path) . '/%'
        );

        $this->_write_db->query($sql, $values);
    }

    /**
     * Renames all child paths.
     *
     * @param string $path  The path of the folder to rename.
     * @param string $name  The foldername to use.
     *
     * @throws VFS_Exception
     */
    protected function _recursiveRename($oldpath, $oldname, $newpath, $newname)
    {
        $oldpath = $this->_convertPath($oldpath);
        $newpath = $this->_convertPath($newpath);

        $sql  = 'SELECT vfs_name FROM ' . $this->_params['table'];
        $sql .= ' WHERE vfs_type = ? AND vfs_path = ?';
        $this->log($sql, PEAR_LOG_DEBUG);

        $values = array(self::FOLDER, $this->_getNativePath($oldpath, $oldname));

        $folderList = $this->_db->getCol($sql, 0, $values);

        foreach ($folderList as $folder) {
            $this->_recursiveRename($this->_getNativePath($oldpath, $oldname), $folder, $this->_getNativePath($newpath, $newname), $folder);
        }

        $sql = 'UPDATE ' . $this->_params['table'] . ' SET vfs_path = ? WHERE vfs_path = ?';
        $this->log($sql, PEAR_LOG_DEBUG);

        $values = array($this->_getNativePath($newpath, $newname), $this->_getNativePath($oldpath, $oldname));

        $this->_write_db->query($sql, $values);
    }

    /**
     * Return a full filename on the native filesystem, from a VFS
     * path and name.
     *
     * @param string $path  The VFS file path.
     * @param string $name  The VFS filename.
     *
     * @return string  The full native filename.
     */
    protected function _getNativePath($path, $name)
    {
        if (!strlen($path)) {
            return $name;
        }

        if (isset($this->_params['home']) &&
            preg_match('|^~/?(.*)$|', $path, $matches)) {
            $path = $this->_params['home'] . '/' . $matches[1];
        }

        return $path . '/' . $name;
    }

    /**
     * Attempts to open a persistent connection to the SQL server.
     *
     * @throws VFS_Exception
     */
    protected function _connect()
    {
        if ($this->_connected) {
            return;
        }

        if (!isset($this->_params['db'])) {
            throw new VFS_Exception('Required "db" not specified in VFS configuration.');
        }

        $this->_params = array_merge(array(
            'table' => 'horde_vfs'
        ), $this->_params);

        $this->_db = $this->_params['db'];
        $this->_write_db = isset($this->_params['writedb'])
            ? $this->_params['writedb']
            : $this->_params['db'];

        $this->_connected = true;
    }

    /**
     * Read file data from the SQL VFS backend.
     *
     * @param string $table    The VFS table name.
     * @param string $field    TODO
     * @param array $criteria  TODO
     *
     * @return mixed  TODO
     * @throws VFS_Exception
     */
    protected function _readBlob($table, $field, $criteria)
    {
        if (!count($criteria)) {
            throw new VFS_Exception('You must specify the fetch criteria');
        }

        $where = '';

        switch ($this->_db->dbsyntax) {
        case 'oci8':
            foreach ($criteria as $key => $value) {
                if (!empty($where)) {
                    $where .= ' AND ';
                }
                if (!strlen($value)) {
                    $where .= $key . ' IS NULL';
                } else {
                    $where .= $key . ' = ' . $this->_db->quote($value);
                }
            }

            $statement = OCIParse($this->_db->connection,
                                  sprintf('SELECT %s FROM %s WHERE %s',
                                          $field, $table, $where));
            OCIExecute($statement);
            if (OCIFetchInto($statement, $lob)) {
                $result = $lob[0]->load();
                if (is_null($result)) {
                    throw new VFS_Exception('Unable to load SQL data.');
                }
            } else {
                OCIFreeStatement($statement);
                throw new VFS_Exception('Unable to load SQL data.');
            }
            OCIFreeStatement($statement);
            break;

        default:
            foreach ($criteria as $key => $value) {
                if (!empty($where)) {
                    $where .= ' AND ';
                }
                $where .= $key . ' = ' . $this->_db->quote($value);
            }

            $sql = sprintf('SELECT %s FROM %s WHERE %s',
                           $field, $table, $where);
            $this->log($sql, PEAR_LOG_DEBUG);
            $result = $this->_db->getOne($sql);

            if (is_null($result)) {
                throw new VFS_Exception('Unable to load SQL data.');
            } else {
                switch ($this->_db->dbsyntax) {
                case 'pgsql':
                    $result = pack('H' . strlen($result), $result);
                    break;
                }
            }
        }

        return $result;
    }

    /**
     * TODO
     *
     * @param string $table       TODO
     * @param string $field       TODO
     * @param string $data        TODO
     * @param string $attributes  TODO
     *
     * @return mixed  TODO
     * @throws VFS_Exception
     */
    protected function _insertBlob($table, $field, $data, $attributes)
    {
        $fields = $values = array();

        switch ($this->_write_db->dbsyntax) {
        case 'oci8':
            foreach ($attributes as $key => $value) {
                $fields[] = $key;
                $values[] = $this->_write_db->quoteSmart($value);
            }

            $statement = OCIParse($this->_write_db->connection,
                                  sprintf('INSERT INTO %s (%s, %s)' .
                                          ' VALUES (%s, EMPTY_BLOB()) RETURNING %s INTO :blob',
                                          $table,
                                          implode(', ', $fields),
                                          $field,
                                          implode(', ', $values),
                                          $field));

            $lob = OCINewDescriptor($this->_write_db->connection);
            OCIBindByName($statement, ':blob', $lob, -1, SQLT_BLOB);
            OCIExecute($statement, OCI_DEFAULT);
            $lob->save($data);
            $result = OCICommit($this->_write_db->connection);
            $lob->free();
            OCIFreeStatement($statement);
            if ($result) {
                return true;
            }
            throw new VFS_Exception('Unknown Error');

        default:
            foreach ($attributes as $key => $value) {
                $fields[] = $key;
                $values[] = $value;
            }

            $query = sprintf('INSERT INTO %s (%s, %s) VALUES (%s)',
                             $table,
                             implode(', ', $fields),
                             $field,
                             '?' . str_repeat(', ?', count($values)));
            break;
        }

        switch ($this->_write_db->dbsyntax) {
        case 'mssql':
        case 'pgsql':
            $values[] = bin2hex($data);
            break;

        default:
            $values[] = $data;
        }

        /* Execute the query. */
        $this->log($query, PEAR_LOG_DEBUG);
        return $this->_write_db->query($query, $values);
    }

    /**
     * TODO
     *
     * @param string $table      TODO
     * @param string $field      TODO
     * @param string $data       TODO
     * @param string $where      TODO
     * @param array $alsoupdate  TODO
     *
     * @return mixed  TODO
     * @throws VFS_Exception
     */
    protected function _updateBlob($table, $field, $data, $where, $alsoupdate)
    {
        $fields = $values = array();

        switch ($this->_write_db->dbsyntax) {
        case 'oci8':
            $wherestring = '';
            foreach ($where as $key => $value) {
                if (!empty($wherestring)) {
                    $wherestring .= ' AND ';
                }
                $wherestring .= $key . ' = ' . $this->_write_db->quote($value);
            }

            $statement = OCIParse($this->_write_db->connection,
                                  sprintf('SELECT %s FROM %s WHERE %s FOR UPDATE',
                                          $field,
                                          $table,
                                          $wherestring));

            OCIExecute($statement, OCI_DEFAULT);
            OCIFetchInto($statement, $lob);
            $lob[0]->save($data);
            $result = OCICommit($this->_write_db->connection);
            $lob[0]->free();
            OCIFreeStatement($statement);
            if ($result) {
                return true;
            }
            throw new VFS_Exception('Unknown Error');

        default:
            $updatestring = '';
            $values = array();
            foreach ($alsoupdate as $key => $value) {
                $updatestring .= $key . ' = ?, ';
                $values[] = $value;
            }
            $updatestring .= $field . ' = ?';
            switch ($this->_write_db->dbsyntax) {
            case 'mssql':
            case 'pgsql':
                $values[] = bin2hex($data);
                break;

            default:
                $values[] = $data;
            }

            $wherestring = '';
            foreach ($where as $key => $value) {
                if (!empty($wherestring)) {
                    $wherestring .= ' AND ';
                }
                $wherestring .= $key . ' = ?';
                $values[] = $value;
            }

            $query = sprintf('UPDATE %s SET %s WHERE %s',
                             $table,
                             $updatestring,
                             $wherestring);
            break;
        }

        /* Execute the query. */
        $this->log($query, PEAR_LOG_DEBUG);
        return $this->_write_db->query($query, $values);
    }

    /**
     * Converts the path name from regular filesystem form to the internal
     * format needed to access the file in the database.
     *
     * Namely, we will treat '/' as a base directory as this is pretty much
     * the standard way to access base directories over most filesystems.
     *
     * @param string $path  A VFS path.
     *
     * @return string  The path with any surrouding slashes stripped off.
     */
    protected function _convertPath($path)
    {
        return trim($path, '/');
    }

    /**
     * TODO
     *
     * @return string  TODO
     */
    protected function _getFileSizeOp()
    {
        switch ($this->_db->dbsyntax) {
        case 'mysql':
            return 'LENGTH';

        case 'oci8':
            return 'LENGTHB';

        case 'mssql':
        case 'sybase':
            return 'DATALENGTH';

        case 'pgsql':
        default:
            return 'OCTET_LENGTH';
        }
    }

    /**
     * VFS_sql override of isFolder() to check for root folder.
     *
     * @param string $path  Path to possible folder
     * @param string $name  Name of possible folder
     *
     * @return boolean  True if $path/$name is a folder
     */
    public function isFolder($path, $name)
    {
        return (($path == '') && ($name == ''))
            // The root of VFS is always a folder.
            ? true
            : parent::isFolder($path, $name);
    }

}
