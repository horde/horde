<?php
/**
 * VFS API for abstracted file storage and access.
 *
 * Copyright 2002-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @package Vfs
 */
abstract class Horde_Vfs_Base
{
    /**
     * Hash containing connection parameters.
     *
     * @var array
     */
    protected $_params = array();

    /**
     * List of additional credentials required for this VFS backend (example:
     * For FTP, we need a username and password to log in to the server with).
     *
     * @var array
     */
    protected $_credentials = array();

    /**
     * List of permissions and if they can be changed in this VFS backend.
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
            'read' => false,
            'write' => false,
            'execute' => false
        )
    );

    /**
     * List of features that the VFS driver supports.
     *
     * @var array
     */
    protected $_features = array(
        'readByteRange' => false,
    );

    /**
     * The current size, in bytes, of the VFS tree.
     *
     * @var integer
     */
    protected $_vfsSize = null;

    /**
     * Constructor.
     *
     * @param array $params  A hash containing connection parameters.
     */
    public function __construct($params = array())
    {
        $this->setParams(array(
            'user' => '',
            'vfs_quotalimit' => -1,
            'vfs_quotaroot' => ''
        ));
        $this->setParams($params);
    }

    /**
     * Returns whether the drivers supports a certain feature.
     *
     * @param string $feature  A feature name. See {@link $_features} for a
     *                         list of possible features.
     *
     * @return boolean  True if the feature is supported.
     */
    public function hasFeature($feature)
    {
        return !empty($this->_features[$feature]);
    }

    /**
     * Checks the credentials that we have by calling _connect(), to see if
     * there is a valid login.
     *
     * @throws Horde_Vfs_Exception
     */
    public function checkCredentials()
    {
        $this->_connect();
    }

    /**
     * TODO
     *
     * @throws Horde_Vfs_Exception
     */
    protected function _connect()
    {
    }

    /**
     * Sets configuration parameters.
     *
     * @param array $params  An associative array with parameter names as
     *                       keys.
     */
    public function setParams($params = array())
    {
        $this->_params = array_merge($this->_params, $params);
    }

    /**
     * Returns configuration parameters.
     *
     * @param string $name  The parameter to return.
     *
     * @return mixed  The parameter value or null if it doesn't exist.
     */
    public function getParam($name)
    {
        return isset($this->_params[$name])
            ? $this->_params[$name]
            : null;
    }

    /**
     * Retrieves the size of a file from the VFS.
     *
     * @abstract
     *
     * @param string $path  The pathname to the file.
     * @param string $name  The filename to retrieve.
     *
     * @return integer  The file size.
     * @throws Horde_Vfs_Exception
     */
    public function size($path, $name)
    {
        throw new Horde_Vfs_Exception('Not supported.');
    }

    /**
     * Returns the size of a folder.
     *
     * @param string $path  The path of the folder.
     *
     * @return integer  The size of the folder, in bytes.
     * @throws Horde_Vfs_Exception
     */
    public function getFolderSize($path = null)
    {
        $size = 0;
        $root = !is_null($path) ? $path . '/' : '';
        $object_list = $this->listFolder($root, null, true, false, true);

        foreach ($object_list as $key => $val) {
            $size += isset($val['subdirs'])
                ? $this->getFolderSize($root . '/' . $key)
                : $this->size($root, $key);
        }

        return $size;
    }

    /**
     * Retrieves a file from the VFS.
     *
     * @abstract
     *
     * @param string $path  The pathname to the file.
     * @param string $name  The filename to retrieve.
     *
     * @return string  The file data.
     * @throws Horde_Vfs_Exception
     */
    public function read($path, $name)
    {
        throw new Horde_Vfs_Exception('Not supported.');
    }

    /**
     * Retrieves a file from the VFS as an on-disk local file.
     *
     * This function provides a file on local disk with the data of a VFS file
     * in it. This file <em>cannot</em> be modified! The behavior if you do
     * modify it is undefined. It will be removed at the end of the request.
     *
     * @param string $path  The pathname to the file.
     * @param string $name  The filename to retrieve.
     *
     * @return string  A local filename.
     * @throws Horde_Vfs_Exception
     */
    public function readFile($path, $name)
    {
        // Create a temporary file and register it for deletion at the
        // end of this request.
        if (!($localFile = Horde_Util::getTempFile('vfs'))) {
            throw new Horde_Vfs_Exception('Unable to create temporary file.');
        }

        if (is_callable(array($this, 'readStream'))) {
            // Use a stream from the VFS if possible, to avoid reading all data
            // into memory.
            $stream = $this->readStream($path, $name);

            if (!($localStream = fopen($localFile, 'w'))) {
                throw new Horde_Vfs_Exception('Unable to open temporary file.');
            }
            stream_copy_to_stream($stream, $localStream);
            fclose($localStream);
        } else {
            // We have to read all of the data in one shot.
            $data = $this->read($path, $name);
            file_put_contents($localFile, $data);
        }

        // $localFile now has $path/$name's data in it.
        return $localFile;
    }

    /**
     * Retrieves a part of a file from the VFS. Particularly useful when
     * reading large files which would exceed the PHP memory limits if they
     * were stored in a string.
     *
     * @abstract
     *
     * @param string  $path       The pathname to the file.
     * @param string  $name       The filename to retrieve.
     * @param integer $offset     The offset of the part. (The new offset will
     *                            be stored in here).
     * @param integer $length     The length of the part. If the length = -1,
     *                            the whole part after the offset is retrieved.
     *                            If more bytes are given as exists after the
     *                            given offset. Only the available bytes are
     *                            read.
     * @param integer $remaining  The bytes that are left, after the part that
     *                            is retrieved.
     *
     * @return string  The file data.
     * @throws Horde_Vfs_Exception
     */
    public function readByteRange($path, $name, &$offset, $length, &$remaining)
    {
        throw new Horde_Vfs_Exception('Not supported.');
    }

    /**
     * Stores a file in the VFS.
     *
     * @abstract
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
        throw new Horde_Vfs_Exception('Not supported.');
    }

    /**
     * Stores a file in the VFS from raw data.
     *
     * @abstract
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
        throw new Horde_Vfs_Exception('Not supported.');
    }

    /**
     * Moves a file through the backend.
     *
     * @param string $path         The path of the original file.
     * @param string $name         The name of the original file.
     * @param string $dest         The destination file name.
     * @param boolean $autocreate  Automatically create directories?
     *
     * @throws Horde_Vfs_Exception
     */
    public function move($path, $name, $dest, $autocreate = false)
    {
        $this->copy($path, $name, $dest, $autocreate);
        $this->deleteFile($path, $name);
    }

    /**
     * Copies a file through the backend.
     *
     * @param string $path         The path of the original file.
     * @param string $name         The name of the original file.
     * @param string $dest         The name of the destination directory.
     * @param boolean $autocreate  Automatically create directories?
     *
     * @throws Horde_Vfs_Exception
     */
    public function copy($path, $name, $dest, $autocreate = false)
    {
        $orig = $this->_getPath($path, $name);
        if (preg_match('|^' . preg_quote($orig) . '/?$|', $dest)) {
            throw new Horde_Vfs_Exception('Cannot copy file(s) - source and destination are the same.');
        }

        if ($autocreate) {
            $this->autocreatePath($dest);
        }

        if ($this->isFolder($path, $name)) {
            $this->_copyRecursive($path, $name, $dest);
        } else {
            $this->writeData($dest, $name, $this->read($path, $name), $autocreate);
        }
    }

    /**
     * Recursively copies a directory through the backend.
     *
     * @param string $path  The path of the original file.
     * @param string $name  The name of the original file.
     * @param string $dest  The name of the destination directory.
     *
     * @throws Horde_Vfs_Exception
     */
    protected function _copyRecursive($path, $name, $dest)
    {
        $this->createFolder($dest, $name);

        $file_list = $this->listFolder($this->_getPath($path, $name));
        foreach ($file_list as $file) {
            $this->copy($this->_getPath($path, $name), $file['name'], $this->_getPath($dest, $name));
        }
    }

    /**
     * Alias to deleteFile()
     */
    public function delete($path, $name)
    {
        $this->deleteFile($path, $name);
    }

    /**
     * Deletes a file from the VFS.
     *
     * @abstract
     *
     * @param string $path  The path to delete the file from.
     * @param string $name  The filename to delete.
     *
     * @throws Horde_Vfs_Exception
     */
    public function deleteFile($path, $name)
    {
        throw new Horde_Vfs_Exception('Not supported.');
    }

    /**
     * Renames a file in the VFS.
     *
     * @abstract
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
        throw new Horde_Vfs_Exception('Not supported.');
    }

    /**
     * Returns if a given file or folder exists in a folder.
     *
     * @param string $path  The path to the folder.
     * @param string $name  The file or folder name.
     *
     * @return boolean  True if it exists, false otherwise.
     */
    public function exists($path, $name)
    {
        try {
            $list = $this->listFolder($path);
            return isset($list[$name]);
        } catch (Horde_Vfs_Exception $e) {
            return false;
        }
    }

    /**
     * Creates a folder in the VFS.
     *
     * @abstract
     *
     * @param string $path  The parent folder.
     * @param string $name  The name of the new folder.
     *
     * @throws Horde_Vfs_Exception
     */
    public function createFolder($path, $name)
    {
        throw new Horde_Vfs_Exception('Not supported.');
    }

    /**
     * Automatically creates any necessary parent directories in the specified
     * $path.
     *
     * @param string $path  The VFS path to autocreate.
     *
     * @throws Horde_Vfs_Exception
     */
    public function autocreatePath($path)
    {
        $dirs = explode('/', $path);
        $cur = '/';
        foreach ($dirs as $dir) {
            if (!strlen($dir)) {
                continue;
            }
            if (!$this->isFolder($cur, $dir)) {
                $this->createFolder($cur, $dir);
            }
            if ($cur != '/') {
                $cur .= '/';
            }
            $cur .= $dir;
        }
    }

    /**
     * Checks if a given item is a folder.
     *
     * @param string $path  The parent folder.
     * @param string $name  The item name.
     *
     * @return boolean  True if it is a folder, false otherwise.
     */
    public function isFolder($path, $name)
    {
        try {
            $folderList = $this->listFolder($path, null, true, true);
            return isset($folderList[$name]);
        } catch (Horde_Vfs_Exception $e) {
            return false;
        }
    }

    /**
     * Deletes a folder from the VFS.
     *
     * @abstract
     *
     * @param string $path        The parent folder.
     * @param string $name        The name of the folder to delete.
     * @param boolean $recursive  Force a recursive delete?
     *
     * @throws Horde_Vfs_Exception
     */
    public function deleteFolder($path, $name, $recursive = false)
    {
        throw new Horde_Vfs_Exception('Not supported.');
    }

    /**
     * Recursively remove all files and subfolders from the given
     * folder.
     *
     * @param string $path  The path of the folder to empty.
     *
     * @throws Horde_Vfs_Exception
     */
    public function emptyFolder($path)
    {
        // Get and delete the subfolders.
        foreach ($this->listFolder($path, null, true, true) as $folder) {
            $this->deleteFolder($path, $folder['name'], true);
        }

        // Only files are left, get and delete them.
        foreach ($this->listFolder($path) as $file) {
            $this->deleteFile($path, $file['name']);
        }
    }

    /**
     * Returns a file list of the directory passed in.
     *
     * @param string $path          The path of the directory.
     * @param string|array $filter  Regular expression(s) to filter
     *                              file/directory name on.
     * @param boolean $dotfiles     Show dotfiles?
     * @param boolean $dironly      Show only directories?
     * @param boolean $recursive    Return all directory levels recursively?
     *
     * @return array  File list.
     * @throws Horde_Vfs_Exception
     */
    public function listFolder($path, $filter = null, $dotfiles = true,
                               $dironly = false, $recursive = false)
    {
        $list = $this->_listFolder($path, $filter, $dotfiles, $dironly);
        if (!$recursive) {
            return $list;
        }

        if (strlen($path)) {
            $path .= '/';
        }
        foreach ($list as $name => $values) {
            if ($values['type'] == '**dir') {
                $list[$name]['subdirs'] = $this->listFolder($path . $name, $filter, $dotfiles, $dironly, $recursive);
            }
        }

        return $list;
    }

    /**
     * Returns an an unsorted file list of the specified directory.
     *
     * @abstract
     *
     * @param string $path          The path of the directory.
     * @param string|array $filter  Regular expression(s) to filter
     *                              file/directory name on.
     * @param boolean $dotfiles     Show dotfiles?
     * @param boolean $dironly      Show only directories?
     *
     * @return array  File list.
     * @throws Horde_Vfs_Exception
     */
    protected function _listFolder($path, $filter = null, $dotfiles = true,
                                   $dironly = false)
    {
        throw new Horde_Vfs_Exception('Not supported.');
    }

    /**
     * Returns the current working directory of the VFS backend.
     *
     * @return string  The current working directory.
     */
    public function getCurrentDirectory()
    {
        return '';
    }

    /**
     * Returns whether or not a file or directory name matches an filter
     * element.
     *
     * @param string|array $filter  Regular expression(s) to build the filter
     *                              from.
     * @param string $filename      String containing the file/directory name
     *                              to match.
     *
     * @return boolean  True on match, false on no match.
     */
    protected function _filterMatch($filter, $filename)
    {
        if (is_array($filter)) {
            $filter = implode('|', $filter);
        }

        if (!strlen($filter)) {
            return false;
        }

        return preg_match('/' . $filter . '/', $filename);
    }

    /**
     * Changes permissions for an item on the VFS.
     *
     * @abstract
     *
     * @param string $path        The parent folder of the item.
     * @param string $name        The name of the item.
     * @param string $permission  The permission to set.
     *
     * @throws Horde_Vfs_Exception
     */
    public function changePermissions($path, $name, $permission)
    {
        throw new Horde_Vfs_Exception('Not supported.');
    }

    /**
     * Returns the list of additional credentials required, if any.
     *
     * @return array  Credential list.
     */
    public function getRequiredCredentials()
    {
        return array_diff($this->_credentials, array_keys($this->_params));
    }

    /**
     * Returns an array specifying what permissions are changeable for this
     * VFS implementation.
     *
     * @return array  Changeable permisions.
     */
    public function getModifiablePermissions()
    {
        return $this->_permissions;
    }

    /**
     * Returns the size of the VFS item.
     *
     * @return integer  The size, in bytes, of the VFS item.
     */
    public function getVFSSize()
    {
        if (is_null($this->_vfsSize)) {
            $this->_vfsSize = $this->getFolderSize($this->_params['vfs_quotaroot']);
        }
        return $this->_vfsSize;
    }

    /**
     * Sets the VFS quota limit.
     *
     * @param integer $quota   The limit to apply.
     * @param integer $metric  The metric to multiply the quota into.
     */
    public function setQuota($quota, $metric = Horde_Vfs::QUOTA_METRIC_BYTE)
    {
        switch ($metric) {
        case Horde_Vfs::QUOTA_METRIC_KB:
            $quota *= pow(2, 10);
            break;

        case Horde_Vfs::QUOTA_METRIC_MB:
            $quota *= pow(2, 20);
            break;

        case Horde_Vfs::QUOTA_METRIC_GB:
            $quota *= pow(2, 30);
            break;
        }

        $this->_params['vfs_quotalimit'] = $quota;
    }

    /**
     * Sets the VFS quota root.
     *
     * @param string $dir  The root directory for the quota determination.
     */
    public function setQuotaRoot($dir)
    {
        $this->_params['vfs_quotaroot'] = $dir;
    }

    /**
     * Get quota information (used/allocated), in bytes.
     *
     * @return mixed  An associative array.
     * <pre>
     * 'limit' = Maximum quota allowed
     * 'usage' = Currently used portion of quota (in bytes)
     * </pre>
     * @throws Horde_Vfs_Exception
     */
    public function getQuota()
    {
        if (empty($this->_params['vfs_quotalimit'])) {
            throw new Horde_Vfs_Exception('No quota set.');
        }

        return array(
            'limit' => $this->_params['vfs_quotalimit'],
            'usage' => $this->getVFSSize()
        );
    }

    /**
     * Checks the quota when preparing to write data.
     *
     * @param string $mode   Either 'string' or 'file'.  If 'string', $data is
     *                       the data to be written.  If 'file', $data is the
     *                       filename containing the data to be written.
     * @param string $data   Either the data or the filename to the data.
     *
     * @throws Horde_Vfs_Exception
     */
    protected function _checkQuotaWrite($mode, $data)
    {
        if ($this->_params['vfs_quotalimit'] == -1 &&
            is_null($this->_vfsSize)) {
            return;
        }

        if ($mode == 'file') {
            $filesize = filesize($data);
            if ($filesize === false) {
                throw new Horde_Vfs_Exception('Unable to read VFS file (filesize() failed).');
            }
        } else {
            $filesize = strlen($data);
        }

        $vfssize = $this->getVFSSize();
        if ($this->_params['vfs_quotalimit'] > -1 &&
            ($vfssize + $filesize) > $this->_params['vfs_quotalimit']) {
            throw new Horde_Vfs_Exception('Unable to write VFS file, quota will be exceeded.');
        }

        if (!is_null($this->_vfsSize)) {
            $this->_vfsSize += $filesize;
        }
    }

    /**
     * Checks the quota when preparing to delete data.
     *
     * @param string $path  The path the file is located in.
     * @param string $name  The filename.
     *
     * @throws Horde_Vfs_Exception
     */
    protected function _checkQuotaDelete($path, $name)
    {
        if (!is_null($this->_vfsSize)) {
            $this->_vfsSize -= $this->size($path, $name);
        }
    }

    /**
     * Returns the full path of an item.
     *
     * @param string $path  The path of directory of the item.
     * @param string $name  The name of the item.
     *
     * @return mixed  Full path when $path isset and just $name when not set.
     */
    protected function _getPath($path, $name)
    {
        if (strlen($path) > 0) {
            if (substr($path, -1) == '/') {
                return $path . $name;
            }
            return $path . '/' . $name;
        }

        return $name;
    }
}
