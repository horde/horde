<?php
/**
 * VFS implementation for Horde's database abstraction layer.
 *
 * Required values for $params:
 * - db: A Horde_Db_Adapter object.
 *
 * Optional values:
 * - table: (string) The name of the vfs table in 'database'. Defaults to
 *          'horde_vfs'.
 *
 * The table structure for the VFS can be created with the horde-db-migrate
 * script from the Horde_Db package.
 *
 * Copyright 2002-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  VFS
 */
class Horde_Vfs_Sql extends Horde_Vfs_Base
{
    /* File value for vfs_type column. */
    const FILE = 1;

    /* Folder value for vfs_type column. */
    const FOLDER = 2;

    /**
     * Handle for the current database connection.
     *
     * @var Horde_Db
     */
    protected $_db = false;

    /**
     * Constructor.
     *
     * @param array $params  A hash containing connection parameters.
     */
    public function __construct($params = array())
    {
        $params = array_merge(array('table' => 'horde_vfs'), $params);
        parent::__construct($params);
        $this->_db = $this->_params['db'];
        unset($this->_params['db']);
    }

    /**
     * Retrieves the filesize from the VFS.
     *
     * @param string $path  The pathname to the file.
     * @param string $name  The filename to retrieve.
     *
     * @return integer  The file size.
     * @throws Horde_Vfs_Exception
     */
    public function size($path, $name)
    {
        $length_op = $this->_getFileSizeOp();
        $sql = sprintf(
            'SELECT %s(vfs_data) FROM %s WHERE vfs_path = ? AND vfs_name = ?',
            $length_op,
            $this->_params['table']
        );
        $values = array($this->_convertPath($path), $name);
        try {
            $size = $this->_db->selectValue($sql, $values);
        } catch (Horde_Db_Exception $e) {
            throw new Horde_Vfs_Exception($e);
        }

        if ($size === false) {
            throw new Horde_Vfs_Exception(sprintf('Unable to check file size of "%s/%s".', $path, $name));
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
     * @throws Horde_Vfs_Exception
     */
    public function getFolderSize($path = null, $name = null)
    {
        try {
            $where = null;
            $params = array();
            if (strlen($path)) {
                $where = 'WHERE vfs_path = ? OR vfs_path LIKE ?';
                $path = $this->_convertPath($path);
                $params = array($path, $path . '/%');
            }
            $sql = sprintf('SELECT SUM(%s(vfs_data)) FROM %s %s',
                           $this->_getFileSizeOp(),
                           $this->_params['table'],
                           $where);
            $size = $this->_db->selectValue($sql, $params);
        } catch (Horde_Db_Exception $e) {
            throw new Horde_Vfs_Exception($e);
        }

        return (int)$size;
    }

    /**
     * Retrieves a file from the VFS.
     *
     * @param string $path  The pathname to the file.
     * @param string $name  The filename to retrieve.
     *
     * @return string  The file data.
     * @throws Horde_Vfs_Exception
     */
    public function read($path, $name)
    {
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
     * @throws Horde_Vfs_Exception
     */
    public function readByteRange($path, $name, &$offset, $length, &$remaining)
    {
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
     * @throws Horde_Vfs_Exception
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
     * @throws Horde_Vfs_Exception
     */
    public function writeData($path, $name, $data, $autocreate = false)
    {
        $this->_checkQuotaWrite('string', $data);

        $path = $this->_convertPath($path);

        /* Check to see if the data already exists. */
        try {
            $sql = sprintf('SELECT vfs_id FROM %s WHERE vfs_path %s AND vfs_name = ?',
                           $this->_params['table'],
                           (!strlen($path) && $this->_db->dbsyntax == 'oci8') ? ' IS NULL' : ' = ' . $this->_db->quote($path));
            $values = array($name);
            $id = $this->_db->selectValue($sql, $values);
        } catch (Horde_Db_Exception $e) {
            throw new Horde_Vfs_Exception($e);
        }

        if ($id) {
            $this->_updateBlob($this->_params['table'], 'vfs_data', $data,
                               array('vfs_id' => $id),
                               array('vfs_modified' => time()));
            return;
        }

        /* Check to see if the folder already exists. */
        $dirs = explode('/', $path);
        $path_name = array_pop($dirs);
        $parent = implode('/', $dirs);
        if (!$this->isFolder($parent, $path_name)) {
            if (!$autocreate) {
                throw new Horde_Vfs_Exception(sprintf('Folder "%s" does not exist', $path));
            }

            $this->autocreatePath($path);
        }

        return $this->_insertBlob($this->_params['table'], 'vfs_data', $data, array(
            'vfs_type' => self::FILE,
            'vfs_path' => $path,
            'vfs_name' => $name,
            'vfs_modified' => time(),
            'vfs_owner' => $this->_params['user']
        ));
    }

    /**
     * Delete a file from the VFS.
     *
     * @param string $path  The path to store the file in.
     * @param string $name  The filename to use.
     *
     * @throws Horde_Vfs_Exception
     */
    public function deleteFile($path, $name)
    {
        $this->_checkQuotaDelete($path, $name);

        $path = $this->_convertPath($path);

        try {
            $sql = sprintf('DELETE FROM %s WHERE vfs_type = ? AND vfs_path %s AND vfs_name = ?',
                           $this->_params['table'],
                           (!strlen($path) && $this->_db->dbsyntax == 'oci8') ? ' IS NULL' : ' = ' . $this->_db->quote($path));
            $values = array(self::FILE, $name);
            $result = $this->_db->delete($sql, $values);
        } catch (Horde_Db_Exception $e) {
            throw new Horde_Vfs_Exception($e);
        }

        if ($result == 0) {
            throw new Horde_Vfs_Exception('Unable to delete VFS file.');
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
     * @throws Horde_Vfs_Exception
     */
    public function rename($oldpath, $oldname, $newpath, $newname)
    {
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

        $sql  = 'UPDATE ' . $this->_params['table']
            . ' SET vfs_path = ?, vfs_name = ?, vfs_modified = ? WHERE vfs_path = ? AND vfs_name = ?';
        $values = array($newpath, $newname, time(), $oldpath, $oldname);

        try {
            $result = $this->_db->update($sql, $values);
        } catch (Horde_Db_Exception $e) {
            throw new Horde_Vfs_Exception($e);
        }

        if ($result == 0) {
            throw new Horde_Vfs_Exception('Unable to rename VFS file.');
        }

        $this->_recursiveRename($oldpath, $oldname, $newpath, $newname);
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
        $sql = 'INSERT INTO ' . $this->_params['table']
            . ' (vfs_type, vfs_path, vfs_name, vfs_modified, vfs_owner) VALUES (?, ?, ?, ?, ?)';
        $values = array(self::FOLDER, $this->_convertPath($path), $name, time(), $this->_params['user']);

        try {
            $this->_db->insert($sql, $values);
        } catch (Horde_Db_Exception $e) {
            throw new Horde_Vfs_Exception($e);
        }
    }

    /**
     * Delete a folder from the VFS.
     *
     * @param string $path        The path of the folder.
     * @param string $name        The folder name to use.
     * @param boolean $recursive  Force a recursive delete?
     *
     * @throws Horde_Vfs_Exception
     */
    public function deleteFolder($path, $name, $recursive = false)
    {
        $path = $this->_convertPath($path);
        $folderPath = $this->_getNativePath($path, $name);

        /* Check if not recursive and fail if directory not empty */
        if (!$recursive) {
            $folderList = $this->listFolder($folderPath, null, true);
            if (!empty($folderList)) {
                throw new Horde_Vfs_Exception(sprintf('Unable to delete %s, the directory is not empty', $path . '/' . $name));
            }
        }

        /* First delete everything below the folder, so if error we get no
         * orphans. */
        try {
            $sql = sprintf('DELETE FROM %s WHERE vfs_path %s',
                           $this->_params['table'],
                           (!strlen($folderPath) && $this->_db->dbsyntax == 'oci8') ? ' IS NULL' : ' LIKE ' . $this->_db->quote($this->_getNativePath($folderPath, '%')));
            $this->_db->delete($sql);
        } catch (Horde_Db_Exception $e) {
            throw new Horde_Vfs_Exception('Unable to delete VFS recursively: ' . $e->getMessage());
        }

        /* Now delete everything inside the folder. */
        try {
            $sql = sprintf('DELETE FROM %s WHERE vfs_path %s',
                           $this->_params['table'],
                           (!strlen($path) && $this->_db->dbsyntax == 'oci8') ? ' IS NULL' : ' = ' . $this->_db->quote($folderPath));
            $this->_db->delete($sql);
        } catch (Horde_Db_Exception $e) {
            throw new Horde_Vfs_Exception('Unable to delete VFS directory: ' . $e->getMessage());
        }

        /* All ok now delete the actual folder */
        try {
            $sql = sprintf('DELETE FROM %s WHERE vfs_path %s AND vfs_name = ?',
                           $this->_params['table'],
                           (!strlen($path) && $this->_db->dbsyntax == 'oci8') ? ' IS NULL' : ' = ' . $this->_db->quote($path));
            $values = array($name);
            $this->_db->delete($sql, $values);
        } catch (Horde_Db_Exception $e) {
            throw new Horde_Vfs_Exception('Unable to delete VFS directory: ' . $e->getMessage());
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
     * @throws Horde_Vfs_Exception
     */
    protected function _listFolder($path, $filter = null, $dotfiles = true,
                                   $dironly = false)
    {
        $path = $this->_convertPath($path);

        try {
            // Fix for Oracle not differentiating between '' and NULL.
            if (!strlen($path) &&
                ($this->_db->adapterName() == 'Oracle' ||
                 $this->_db->adapterName() == 'PDO_Oracle')) {
                $where = 'vfs_path IS NULL';
            } else {
                $where = 'vfs_path = ' . $this->_db->quote($path);
            }

            $length_op = $this->_getFileSizeOp();
            $sql = sprintf('SELECT vfs_name, vfs_type, %s(vfs_data) length, vfs_modified, vfs_owner FROM %s WHERE %s',
                           $length_op,
                           $this->_params['table'],
                           $where);
            $fileList = $this->_db->select($sql);
        } catch (Horde_Db_Exception $e) {
            throw new Horde_Vfs_Exception($e);
        }

        $files = array();
        foreach ($fileList as $line) {
            // Filter out dotfiles if they aren't wanted.
            if (!$dotfiles && substr($line['vfs_name'], 0, 1) == '.') {
                continue;
            }

            $file['name'] = $line['vfs_name'];

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
     * @throws Horde_Vfs_Exception
     */
    public function listFolders($path = '', $filter = array(),
                                $dotfolders = true)
    {
        $path = $this->_convertPath($path);

        $sql  = 'SELECT vfs_name, vfs_path FROM ' . $this->_params['table']
            . ' WHERE vfs_path = ? AND vfs_type = ?';
        $values = array($path, self::FOLDER);

        try {
            $folderList = $this->_db->select($sql, $values);
        } catch (Horde_Db_Exception $e) {
            throw new Horde_Vfs_Exception($e);
        }

        $folders = array();
        foreach ($folderList as $line) {
            $folder['val'] = $this->_getNativePath($line['vfs_path'], $line['vfs_name']);
            $folder['abbrev'] = '';
            $folder['label'] = '';

            $count = substr_count($folder['val'], '/');

            $x = 0;
            while ($x < $count) {
                $folder['abbrev'] .= '    ';
                $folder['label'] .= '    ';
                $x++;
            }

            $folder['abbrev'] .= $line['vfs_name'];
            $folder['label'] .= $line['vfs_name'];

            $strlen = Horde_String::length($folder['label']);
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
     * @throws Horde_Vfs_Exception
     */
    public function gc($path, $secs = 345600)
    {
        $sql = 'DELETE FROM ' . $this->_params['table']
            . ' WHERE vfs_type = ? AND vfs_modified < ? AND (vfs_path = ? OR vfs_path LIKE ?)';
        $values = array(
            self::FILE,
            time() - $secs,
            $this->_convertPath($path),
            $this->_convertPath($path) . '/%'
        );

        try {
            $this->_db->delete($sql, $values);
        } catch (Horde_Db_Exception $e) {
            throw new Horde_Vfs_Exception($e);
        }
    }

    /**
     * Renames all child paths.
     *
     * @param string $path  The path of the folder to rename.
     * @param string $name  The foldername to use.
     *
     * @throws Horde_Vfs_Exception
     */
    protected function _recursiveRename($oldpath, $oldname, $newpath, $newname)
    {
        $oldpath = $this->_convertPath($oldpath);
        $newpath = $this->_convertPath($newpath);

        $sql  = 'SELECT vfs_name FROM ' . $this->_params['table']
            . ' WHERE vfs_type = ? AND vfs_path = ?';
        $values = array(self::FOLDER, $this->_getNativePath($oldpath, $oldname));

        try {
            $folderList = $this->_db->selectValues($sql, $values);
        } catch (Horde_Db_Exception $e) {
            throw new Horde_Vfs_Exception($e);
        }

        foreach ($folderList as $folder) {
            $this->_recursiveRename($this->_getNativePath($oldpath, $oldname), $folder, $this->_getNativePath($newpath, $newname), $folder);
        }

        $sql = 'UPDATE ' . $this->_params['table']
            . ' SET vfs_path = ? WHERE vfs_path = ?';
        $values = array($this->_getNativePath($newpath, $newname), $this->_getNativePath($oldpath, $oldname));

        try {
            $this->_db->update($sql, $values);
        } catch (Horde_Db_Exception $e) {
            throw new Horde_Vfs_Exception($e);
        }
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
     * Read file data from the SQL VFS backend.
     *
     * @param string $table    The VFS table name.
     * @param string $field    TODO
     * @param array $criteria  TODO
     *
     * @return mixed  TODO
     * @throws Horde_Vfs_Exception
     */
    protected function _readBlob($table, $field, $criteria)
    {
        if (!count($criteria)) {
            throw new Horde_Vfs_Exception('You must specify the fetch criteria');
        }

        $where = '';
        foreach ($criteria as $key => $value) {
            if (!empty($where)) {
                $where .= ' AND ';
            }
            $where .= $key . ' = ' . $this->_db->quote($value);
        }

        $sql = sprintf('SELECT %s FROM %s WHERE %s',
                       $field, $table, $where);

        try {
            $result = $this->_db->selectValue($sql);
            $columns = $this->_db->columns($table);
        } catch (Horde_Db_Exception $e) {
            throw new Horde_Vfs_Exception($e);
        }

        if ($result === false) {
            throw new Horde_Vfs_Exception('Unable to load SQL data.');
        }

        return $columns[$field]->binaryToString($result);
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
     * @throws Horde_Vfs_Exception
     */
    protected function _insertBlob($table, $field, $data, $attributes)
    {
        $fields = $values = array();
        foreach ($attributes as $key => $value) {
            $fields[] = $key;
            $values[] = $value;
        }
        $values[] = new Horde_Db_Value_Binary($data);

        $query = sprintf('INSERT INTO %s (%s, %s) VALUES (%s)',
                         $table,
                         implode(', ', $fields),
                         $field,
                         '?' . str_repeat(', ?', count($fields)));

        /* Execute the query. */
        try {
            $this->_db->insert($query, $values);
        } catch (Horde_Db_Exception $e) {
            throw new Horde_Vfs_Exception($e);
        }
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
     * @throws Horde_Vfs_Exception
     */
    protected function _updateBlob($table, $field, $data, $where, $alsoupdate)
    {
        $updatestring = '';
        $values = array();
        foreach ($alsoupdate as $key => $value) {
            $updatestring .= $key . ' = ?, ';
            $values[] = $value;
        }
        $updatestring .= $field . ' = ?';
        $values[] = new Horde_Db_Value_Binary($data);

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

        /* Execute the query. */
        try {
            $this->_db->update($query, $values);
        } catch (Horde_Db_Exception $e) {
            throw new Horde_Vfs_Exception($e);
        }
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
        switch ($this->_db->adapterName()) {
        case 'Oracle':
        case 'PDO_Oracle':
            return 'LENGTHB';

        case 'PostgreSQL':
        case 'PDO_PostgreSQL':
            return 'OCTET_LENGTH';

        default:
            return 'LENGTH';
        }
    }

    /**
     * Horde_Vfs_Sql override of isFolder() to check for root folder.
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
