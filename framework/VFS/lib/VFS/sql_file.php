<?php
/**
 * VFS:: implementation using PHP's PEAR database abstraction
 * layer and local file system for file storage.
 *
 * Required values for $params:<pre>
 * db - (DB) The DB object.
 * vfsroot - (string) The root directory of where the files should be
 *           actually stored.</pre>
 *
 * Optional values:<pre>
 * table - (string) The name of the vfs table in 'database'. Defaults to
 *         'horde_vfs'.</pre>
 *
 * The table structure for the VFS can be found in data/vfs.sql.
 *
 * @author   Michael Varghese <mike.varghese@ascellatech.com>
 * @category Horde
 * @package  VFS
 */
class VFS_sql_file extends VFS_file
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
     * Store a file in the VFS, with the data copied from a temporary
     * file.
     *
     * @param string $path         The path to store the file in.
     * @param string $name         The filename to use.
     * @param string $tmpFile      The temporary file containing the data to be
     *                             stored.
     * @param boolean $autocreate  Automatically create directories?
     *
     * @throws VFS_Exception
     */
    public function write($path, $name, $tmpFile, $autocreate = false)
    {
        /* No need to check quota here as we will check it when we call
         * writeData(). */
        $data = file_get_contents($tmpFile);
        return $this->writeData($path, $name, $data, $autocreate);
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

        $fp = @fopen($this->_getNativePath($path, $name), 'w');
        if (!$fp) {
            if ($autocreate) {
                $this->autocreatePath($path);
                $fp = @fopen($this->_getNativePath($path, $name), 'w');
                if (!$fp) {
                    throw new VFS_Exception('Unable to open VFS file for writing.');
                }
            } else {
                throw new VFS_Exception('Unable to open VFS file for writing.');
            }
        }

        if (!@fwrite($fp, $data)) {
            throw new VFS_Exception('Unable to write VFS file data.');
        }

        if ($this->_writeSQLData($path, $name, $autocreate) instanceof PEAR_Error) {
            @unlink($this->_getNativePath($path, $name));
            throw new VFS_Exception('Unable to write VFS file data.');
        }
    }

    /**
     * Moves a file in the database and the file system.
     *
     * @param string $path         The path to store the file in.
     * @param string $name         The old filename.
     * @param string $dest         The new filename.
     * @param boolean $autocreate  Automatically create directories?
     *
     * @throws VFS_Exception
     */
    public function move($path, $name, $dest, $autocreate = false)
    {
        $orig = $this->_getNativePath($path, $name);
        if (preg_match('|^' . preg_quote($orig) . '/?$|', $dest)) {
            throw new VFS_Exception('Cannot move file(s) - destination is within source.');
        }

        if ($autocreate) {
            $this->autocreatePath($dest);
        }

        foreach ($this->listFolder($dest, null, false) as $file) {
            if ($file['name'] == $name) {
                throw new VFS_Exception('Unable to move VFS file.');
            }
        }

        if (strpos($dest, $this->_getSQLNativePath($path, $name)) !== false) {
            throw new VFS_Exception('Unable to move VFS file.');
        }

        $this->rename($path, $name, $dest, $name);
    }

    /**
     * Copies a file through the backend.
     *
     * @param string $path         The path to store the file in.
     * @param string $name         The filename to use.
     * @param string $dest         The destination of the file.
     * @param boolean $autocreate  Automatically create directories?
     *
     * @throws VFS_Exception
     */
    public function copy($path, $name, $dest, $autocreate = false)
    {
        $orig = $this->_getNativePath($path, $name);
        if (preg_match('|^' . preg_quote($orig) . '/?$|', $dest)) {
            throw new VFS_Exception('Cannot copy file(s) - source and destination are the same.');
        }

        $this->_connect();

        if ($autocreate) {
            $this->autocreatePath($dest);
        }

        foreach ($this->listFolder($dest, null, false) as $file) {
            if ($file['name'] == $name) {
                throw new VFS_Exception('Unable to copy VFS file.');
            }
        }

        if (strpos($dest, $this->_getSQLNativePath($path, $name)) !== false) {
            throw new VFS_Exception('Unable to copy VFS file.');
        }

        if (is_dir($orig)) {
            return $this->_recursiveCopy($path, $name, $dest);
        }

        $this->_checkQuotaWrite('file', $orig);

        if (!@copy($orig, $this->_getNativePath($dest, $name))) {
            throw new VFS_Exception('Unable to copy VFS file.');
        }

        $id = $this->_db->nextId($this->_params['table']);

        $query = sprintf('INSERT INTO %s (vfs_id, vfs_type, vfs_path, vfs_name, vfs_modified, vfs_owner) VALUES (?, ?, ?, ?, ?, ?)',
                         $this->_params['table']);
        $values = array($id, self::FILE, $dest, $name, time(), $this->_params['user']);

        $result = $this->_db->query($query, $values);

        if ($result instanceof PEAR_Error) {
            unlink($this->_getNativePath($dest, $name));
            throw new VFS_Exception($result->getMessage());
        }
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

        $id = $this->_db->nextId($this->_params['table']);
        $result = $this->_db->query(sprintf('INSERT INTO %s (vfs_id, vfs_type, vfs_path, vfs_name, vfs_modified, vfs_owner)
                                            VALUES (?, ?, ?, ?, ?, ?)',
                                            $this->_params['table']),
                                    array($id, self::FOLDER, $path, $name, time(), $this->_params['user']));
        if ($result instanceof PEAR_Error) {
            throw new VFS_Exception($result->getMessage());
        }

        if (!@mkdir($this->_getNativePath($path, $name))) {
            $result = $this->_db->query(sprintf('DELETE FROM %s WHERE vfs_id = ?',
                                                $this->_params['table']),
                                        array($id));
            throw new VFS_Exception('Unable to create VFS directory.');
        }
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

        $this->_db->query(sprintf('UPDATE %s SET vfs_path = ?, vfs_name = ?, vfs_modified = ? WHERE vfs_path = ? AND vfs_name = ?', $this->_params['table']), array($newpath, $newname, time(), $oldpath, $oldname));

        if ($this->_db->affectedRows() == 0) {
            throw new VFS_Exception('Unable to rename VFS file.');
        }

        if (is_a($this->_recursiveSQLRename($oldpath, $oldname, $newpath, $newname), 'PEAR_Error')) {
            $this->_db->query(sprintf('UPDATE %s SET vfs_path = ?, vfs_name = ?  WHERE vfs_path = ? AND vfs_name = ?', $this->_params['table']), array($oldpath, $oldname, $newpath, $newname));
            throw new VFS_Exception('Unable to rename VFS directory.');
        }

        if (!@is_dir($this->_getNativePath($newpath))) {
            $this->autocreatePath($newpath);
        }

        if (!@rename($this->_getNativePath($oldpath, $oldname), $this->_getNativePath($newpath, $newname))) {
            $this->_db->query(sprintf('UPDATE %s SET vfs_path = ?, vfs_name = ? WHERE vfs_path = ? AND vfs_name = ?', $this->_params['table']), array($oldpath, $oldname, $newpath, $newname));
            return PEAR::raiseError(Horde_VFS_Translation::t("Unable to rename VFS file."));
        }
    }

    /**
     * Delete a folder from the VFS.
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

        $result = $this->_db->query(sprintf('DELETE FROM %s WHERE vfs_type = ? AND vfs_path = ? AND vfs_name = ?', $this->_params['table']), array(self::FOLDER, $path, $name));

        if ($this->_db->affectedRows() == 0 || ($result instanceof PEAR_Error)) {
            throw new VFS_Exception('Unable to delete VFS directory.');
        }

        if ($this->_recursiveSQLDelete($path, $name) instanceof PEAR_Error ||
            $this->_recursiveLFSDelete($path, $name) instanceof PEAR_Error) {
            throw new VFS_Exception('Unable to delete VFS directory recursively.');
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

        $result = $this->_db->query(sprintf('DELETE FROM %s WHERE vfs_type = ? AND vfs_path = ? AND vfs_name = ?',
                                            $this->_params['table']),
                                    array(self::FILE, $path, $name));

        if ($this->_db->affectedRows() == 0) {
            throw new VFS_Exception('Unable to delete VFS file.');
        }

        if ($result instanceof PEAR_Error) {
            throw new VFS_Exception($result->getMessage());
        }

        if (!@unlink($this->_getNativePath($path, $name))) {
            throw new VFS_Exception('Unable to delete VFS file.');
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

        $files = array();

        $fileList = $this->_db->getAll(sprintf('SELECT vfs_name, vfs_type, vfs_modified, vfs_owner FROM %s
                                               WHERE vfs_path = ?',
                                               $this->_params['table']),
                                       array($path));
        if ($fileList instanceof PEAR_Error) {
            throw new VFS_Exception($fileList->getMessage());
        }

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

                $file['size'] = filesize($this->_getNativePath($path, $line[0]));
            } elseif ($line[1] == self::FOLDER) {
                $file['type'] = '**dir';
                $file['size'] = -1;
            }

            $file['date'] = $line[2];
            $file['owner'] = $line[3];
            $file['perms'] = '';
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

        $sql = sprintf('SELECT vfs_name, vfs_path FROM %s WHERE vfs_path = ? AND vfs_type = ?',
                       $this->_params['table']);

        $folderList = $this->_db->getAll($sql, array($path, self::FOLDER));
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

            $strlen = self::strlen($folder['label']);
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
     * Recursively copies the contents of a folder to a destination.
     *
     * @param string $path  The path to store the directory in.
     * @param string $name  The name of the directory.
     * @param string $dest  The destination of the directory.
     *
     * @throws VFS_Exception
     */
    function _recursiveCopy($path, $name, $dest)
    {
        $this->createFolder($dest, $name);

        $file_list = $this->listFolder($this->_getSQLNativePath($path, $name));
        foreach ($file_list as $file) {
            $this->copy($this->_getSQLNativePath($path, $name), $file['name'], $this->_getSQLNativePath($dest, $name));
        }
     }

    /**
     * Store a files information within the database.
     *
     * @param string $path         The path to store the file in.
     * @param string $name         The filename to use.
     * @param boolean $autocreate  Automatically create directories?
     *
     * @throws VFS_Exception
     */
    protected function _writeSQLData($path, $name, $autocreate = false)
    {
        $this->_connect();

        // File already exists in database
        if ($this->exists($path, $name)) {
            $query = 'UPDATE ' . $this->_params['table'] .
                     ' SET vfs_modified = ?' .
                     ' WHERE vfs_path = ? AND vfs_name = ?';
            $values = array(time(), $path, $name);
        } else {
            $id = $this->_db->nextId($this->_params['table']);

            $query = 'INSERT INTO ' . $this->_params['table'] .
                     ' (vfs_id, vfs_type, vfs_path, vfs_name, vfs_modified,' .
                     ' vfs_owner) VALUES (?, ?, ?, ?, ?, ?)';
            $values = array($id, self::FILE, $path, $name, time(),
                            $this->_params['user']);
        }
        return $this->_db->query($query, $values);
    }

    /**
     * Renames all child paths.
     *
     * @param string $oldpath  The old path of the folder to rename.
     * @param string $oldname  The old name.
     * @param string $newpath  The new path of the folder to rename.
     * @param string $newname  The new name.
     *
     * @throws VFS_Exception
     */
    protected function _recursiveSQLRename($oldpath, $oldname, $newpath,
                                           $newname)
    {
        $folderList = $this->_db->getCol(sprintf('SELECT vfs_name FROM %s WHERE vfs_type = ? AND vfs_path = ?',
                                                 $this->_params['table']),
                                         0,
                                         array(self::FOLDER, $this->_getSQLNativePath($oldpath, $oldname)));

        foreach ($folderList as $folder) {
            $this->_recursiveSQLRename($this->_getSQLNativePath($oldpath, $oldname), $folder, $this->_getSQLNativePath($newpath, $newname), $folder);
        }

        $result = $this->_db->query(sprintf('UPDATE %s SET vfs_path = ? WHERE vfs_path = ?',
                                            $this->_params['table']),
                                    array($this->_getSQLNativePath($newpath, $newname),
                                          $this->_getSQLNativePath($oldpath, $oldname)));

        if ($result instanceof PEAR_Error) {
            throw new VFS_Exception($result->getMessage());
        }
    }

    /**
     * Delete a folders contents from the VFS in the SQL database,
     * recursively.
     *
     * @param string $path  The path of the folder.
     * @param string $name  The foldername to use.
     *
     * @throws VFS_Exception
     */
    protected function _recursiveSQLDelete($path, $name)
    {
        $result = $this->_db->query(sprintf('DELETE FROM %s WHERE vfs_type = ? AND vfs_path = ?', $this->_params['table']), array(self::FILE, $this->_getSQLNativePath($path, $name)));
        if ($result instanceof PEAR_Error) {
            throw new VFS_Exception($result->getMessage());
        }

        $folderList = $this->_db->getCol(sprintf('SELECT vfs_name FROM %s WHERE vfs_type = ? AND vfs_path = ?', $this->_params['table']), 0, array(self::FOLDER, $this->_getSQLNativePath($path, $name)));

        foreach ($folderList as $folder) {
            $this->_recursiveSQLDelete($this->_getSQLNativePath($path, $name), $folder);
        }

        $this->_db->query(sprintf('DELETE FROM %s WHERE vfs_type = ? AND vfs_name = ? AND vfs_path = ?', $this->_params['table']), array(self::FOLDER, $name, $path));
    }

    /**
     * Delete a folders contents from the VFS, recursively.
     *
     * @param string $path  The path of the folder.
     * @param string $name  The foldername to use.
     *
     * @throws VFS_Exception
     */
    protected function _recursiveLFSDelete($path, $name)
    {
        $dir = $this->_getNativePath($path, $name);
        $dh = @opendir($dir);

        while (false !== ($file = readdir($dh))) {
            if ($file != '.' && $file != '..') {
                if (is_dir($dir . '/' . $file)) {
                    $this->_recursiveLFSDelete(!strlen($path) ? $name : $path . '/' . $name, $file);
                } else {
                    @unlink($dir . '/' . $file);
                }
            }
        }
        @closedir($dh);

        rmdir($dir);
    }

    /**
     * Attempts to open a persistent connection to the SQL server.
     *
     * @throws VFS_Exception
     */
    protected function _connect()
    {
        if ($this->_db !== false) {
            return;
        }

        $required = array('db', 'vfsroot');
        foreach ($required as $val) {
            if (!isset($this->_params[$val])) {
                throw new VFS_Exception(sprintf('Required "%s" not specified in VFS configuration.', $val));
            }
        }

        $this->_params = array_merge(array(
            'table' => 'horde_vfs',
        ), $this->_params);

        $this->_db = $this->_params['db'];
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
        if (strlen($name)) {
            $name = '/' . $name;
        }

        if (strlen($path)) {
            if (isset($this->_params['home']) &&
                preg_match('|^~/?(.*)$|', $path, $matches)) {
                $path = $this->_params['home']  . '/' . $matches[1];
            }

            return $this->_params['vfsroot'] . '/' . $path . $name;
        }

        return $this->_params['vfsroot'] . $name;
    }

    /**
     * Return a full SQL filename on the native filesystem, from a VFS
     * path and name.
     *
     * @param string $path  The VFS file path.
     * @param string $name  The VFS filename.
     *
     * @return string  The full native filename.
     */
    protected function _getSQLNativePath($path, $name)
    {
        return strlen($path)
            ? $path . '/' . $name
            : $name;
    }

}
