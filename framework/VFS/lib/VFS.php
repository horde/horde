<?php

require_once 'Log.php';
require_once dirname(__FILE__) . '/VFS/Exception.php';

/**
 * VFS API for abstracted file storage and access.
 *
 * Copyright 2002-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @package VFS
 */
class VFS
{
    /* Quota constants. */
    const QUOTA_METRIC_BYTE = 1;
    const QUOTA_METRIC_KB = 2;
    const QUOTA_METRIC_MB = 3;
    const QUOTA_METRIC_GB = 4;

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
     * A PEAR Log object. If present, will be used to log errors and
     * informational messages about VFS activity.
     *
     * @var Log
     */
    protected $_logger = null;

    /**
     * The log level to use - messages with a higher log level than configured
     * here will not be logged. Defaults to only logging errors or higher.
     *
     * @var integer
     */
    protected $_logLevel = PEAR_LOG_ERR;

    /**
     * The current size, in bytes, of the VFS item.
     *
     * @var integer
     */
    protected $_vfsSize = null;

    /**
     * Attempts to return a concrete instance based on $driver.
     *
     * @param mixed $driver  The type of concrete subclass to return. This
     *                       is based on the storage driver ($driver). The
     *                       code is dynamically included.
     * @param array $params  A hash containing any additional configuration or
     *                       connection parameters a subclass might need.
     *
     * @return VFS  The newly created concrete VFS instance.
     * @throws VFS_Exception
     */
    static public function factory($driver, $params = array())
    {
        $driver = basename($driver);
        $class = __CLASS__ . '_' . $driver;

        if (class_exists($class)) {
            return new $class($params);
        }

        throw new VFS_Exception('Class definition of ' . $class . ' not found.');
    }

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
            'vfs_quotaroot' => '/'
        ));
        $this->setParams($params);
    }

    /**
     * Checks the credentials that we have by calling _connect(), to see if
     * there is a valid login.
     *
     * @throws VFS_Exception
     */
    public function checkCredentials()
    {
        $this->_connect();
    }

    /**
     * TODO
     *
     * @throws VFS_Exception
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
     * Logs a message if a PEAR Log object is available, and the message's
     * priority is lower than or equal to the configured log level.
     *
     * @param mixed   $message   The message to be logged.
     * @param integer $priority  The message's priority.
     */
    public function log($message, $priority = PEAR_LOG_ERR)
    {
        if (!isset($this->_logger) || ($priority > $this->_logLevel)) {
            return;
        }

        if ($message instanceof PEAR_Error) {
            $userinfo = $message->getUserInfo();
            $message = $message->getMessage();
            if ($userinfo) {
                if (is_array($userinfo)) {
                    $userinfo = implode(', ', $userinfo);
                }
                $message .= ': ' . $userinfo;
            }
        }

        /* Make sure to log in the system's locale. */
        $locale = setlocale(LC_TIME, 0);
        setlocale(LC_TIME, 'C');

        $this->_logger->log($message, $priority);

        /* Restore original locale. */
        setlocale(LC_TIME, $locale);
    }

    /**
     * Sets the PEAR Log object used to log informational or error messages.
     *
     * @param Log $logger  The Log object to use.
     */
    public function setLogger($logger, $logLevel = null)
    {
        if (is_callable(array($logger, 'log'))) {
            $this->_logger = $logger;
            if (!is_null($logLevel)) {
                $this->_logLevel = $logLevel;
            }
        }
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
     * @throws VFS_Exception
     */
    public function size($path, $name)
    {
        throw new VFS_Exception('Not supported.');
    }

    /**
     * Returns the size of a folder
     *
     * @param string $path  The path to the folder.
     * @param string $name  The name of the folder.
     *
     * @return integer  The size of the folder, in bytes.
     * @throws VFS_Exception
     */
    public function getFolderSize($path = null, $name = null)
    {
        $size = 0;
        $root = (!is_null($path) ? $path . '/' : '') . $name;
        $object_list = $this->listFolder($root, null, true, false, true);

        foreach ($object_list as $key => $val) {
            $size += isset($val['subdirs'])
                ? $this->getFolderSize($root, $key)
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
     * @throws VFS_Exception
     */
    public function read($path, $name)
    {
        throw new VFS_Exception('Not supported.');
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
     * @throws VFS_Exception
     */
    public function readFile($path, $name)
    {
        // Create a temporary file and register it for deletion at the
        // end of this request.
        if (!($localFile = tempnam(null, 'vfs'))) {
            throw new VFS_Exception('Unable to create temporary file.');
        }
        register_shutdown_function(create_function('', 'unlink(\'' . addslashes($localFile) . '\');'));

        if (is_callable(array($this, 'readStream'))) {
            // Use a stream from the VFS if possible, to avoid reading all data
            // into memory.
            $stream = $this->readStream($path, $name);

            if (!($localStream = fopen($localFile, 'w'))) {
                throw new VFS_Exception('Unable to open temporary file.');
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
     * @throws VFS_Exception
     */
    public function readByteRange($path, $name, &$offset, $length = -1,
                                  &$remaining)
    {
        throw new VFS_Exception('Not supported.');
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
     * @throws VFS_Exception
     */
    public function write($path, $name, $tmpFile, $autocreate = false)
    {
        throw new VFS_Exception('Not supported.');
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
     * @throws VFS_Exception
     */
    public function writeData($path, $name, $data, $autocreate = false)
    {
        throw new VFS_Exception('Not supported.');
    }

    /**
     * Moves a file through the backend.
     *
     * @param string $path         The path of the original file.
     * @param string $name         The name of the original file.
     * @param string $dest         The destination file name.
     * @param boolean $autocreate  Automatically create directories?
     *
     * @throws VFS_Exception
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
     * @throws VFS_Exception
     */
    public function copy($path, $name, $dest, $autocreate = false)
    {
        $orig = $this->_getPath($path, $name);
        if (preg_match('|^' . preg_quote($orig) . '/?$|', $dest)) {
            throw new VFS_Exception('Cannot copy file(s) - source and destination are the same.');
        }

        if ($autocreate) {
            $this->autocreatePath($dest);
        }

        if ($this->isFolder($path, $name)) {
            $this->_copyRecursive($path, $name, $dest);
        } else {
            return $this->writeData($dest, $name, $this->read($path, $name), $autocreate);
        }
    }

    /**
     * Recursively copies a directory through the backend.
     *
     * @param string $path  The path of the original file.
     * @param string $name  The name of the original file.
     * @param string $dest  The name of the destination directory.
     *
     * @throws VFS_Exception
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
        return $this->deleteFile($path, $name);
    }

    /**
     * Deletes a file from the VFS.
     *
     * @abstract
     *
     * @param string $path  The path to delete the file from.
     * @param string $name  The filename to delete.
     *
     * @throws VFS_Exception
     */
    public function deleteFile($path, $name)
    {
        throw new VFS_Exception('Not supported.');
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
     * @throws VFS_Exception
     */
    public function rename($oldpath, $oldname, $newpath, $newname)
    {
        throw new VFS_Exception('Not supported.');
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
        } catch (VFS_Exception $e) {
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
     * @throws VFS_Exception
     */
    public function createFolder($path, $name)
    {
        throw new VFS_Exception('Not supported.');
    }

    /**
     * Automatically creates any necessary parent directories in the specified
     * $path.
     *
     * @param string $path  The VFS path to autocreate.
     *
     * @throws VFS_Exception
     */
    public function autocreatePath($path)
    {
        $dirs = explode('/', $path);
        if (is_array($dirs)) {
            $cur = '/';
            foreach ($dirs as $dir) {
                if (!strlen($dir)) {
                    continue;
                }
                if (!$this->isFolder($cur, $dir)) {
                    $result = $this->createFolder($cur, $dir);
                }
                if ($cur != '/') {
                    $cur .= '/';
                }
                $cur .= $dir;
            }
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
        } catch (VFS_Exception $e) {
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
     * @throws VFS_Exception
     */
    public function deleteFolder($path, $name, $recursive = false)
    {
        throw new VFS_Exception('Not supported.');
    }

    /**
     * Recursively remove all files and subfolders from the given
     * folder.
     *
     * @param string $path  The path of the folder to empty.
     *
     * @throws VFS_Exception
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
     * @param string $path        The path of the directory.
     * @param mixed $filter       String/hash to filter file/dirname on.
     * @param boolean $dotfiles   Show dotfiles?
     * @param boolean $dironly    Show only directories?
     * @param boolean $recursive  Return all directory levels recursively?
     *
     * @return array  File list.
     * @throws VFS_Exception
     */
    public function listFolder($path, $filter = null, $dotfiles = true,
                               $dironly = false, $recursive = false)
    {
        $list = $this->_listFolder($path, $filter, $dotfiles, $dironly);
        if (!$recursive) {
            return $list;
        }

        foreach ($list as $name => $values) {
            if ($values['type'] == '**dir') {
                $list[$name]['subdirs'] = $this->listFolder($path . '/' . $name, $filter, $dotfiles, $dironly, $recursive);
            }
        }

        return $list;
    }

    /**
     * Returns an an unsorted file list of the specified directory.
     *
     * @abstract
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
        throw new VFS_Exception('Not supported.');
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
     * Returns whether or not a filename matches any filter element.
     *
     * @param mixed $filter     String/hash to build the regular expression
     *                          from.
     * @param string $filename  String containing the filename to match.
     *
     * @return boolean  True on match, false on no match.
     */
    protected function _filterMatch($filter, $filename)
    {
        $namefilter = null;

        // Build a regexp based on $filter.
        if (!is_null($filter)) {
            $namefilter = '/';
            if (is_array($filter)) {
                $once = false;
                foreach ($filter as $item) {
                    if ($once !== true) {
                        $once = true;
                    } else {
                        $namefilter .= '|';
                    }
                    $namefilter .= '(' . $item . ')';
                }
            } else {
                $namefilter .= '(' . $filter . ')';
            }
            $namefilter .= '/';
        }

        $match = false;
        if (!is_null($namefilter)) {
            $match = preg_match($namefilter, $filename);
        }

        return $match;
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
     * @throws VFS_Exception
     */
    public function changePermissions($path, $name, $permission)
    {
        throw new VFS_Exception('Not supported.');
    }

    /**
     * Returns a sorted list of folders in the specified directory.
     *
     * @abstract
     *
     * @param string $path         The path of the directory to get the
     *                             directory list for.
     * @param mixed $filter        Hash of items to filter based on folderlist.
     * @param boolean $dotfolders  Include dotfolders?
     *
     * @return array  Folder list.
     * @throws VFS_Exception
     */
    public function listFolders($path = '', $filter = null, $dotfolders = true)
    {
        throw new VFS_Exception('Not supported.');
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
    public function setQuota($quota, $metric = self::QUOTA_METRIC_BYTE)
    {
        switch ($metric) {
        case self::QUOTA_METRIC_KB:
            $quota *= pow(2, 10);
            break;

        case self::QUOTA_METRIC_MB:
            $quota *= pow(2, 20);
            break;

        case self::QUOTA_METRIC_GB:
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
     * @throws VFS_Exception
     */
    public function getQuota()
    {
        if (empty($this->_params['vfs_quotalimit'])) {
            throw new VFS_Exception('No quota set.');
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
     * @throws VFS_Exception
     */
    protected function _checkQuotaWrite($mode, $data)
    {
        if ($this->_params['vfs_quotalimit'] == -1) {
            return;
        }

        if ($mode == 'file') {
            $filesize = filesize($data);
            if ($filesize === false) {
                throw new VFS_Exception('Unable to read VFS file (filesize() failed).');
            }
        } else {
            $filesize = strlen($data);
        }

        $vfssize = $this->getVFSSize();
        if (($vfssize + $filesize) > $this->_params['vfs_quotalimit']) {
            throw new VFS_Exception('Unable to write VFS file, quota will be exceeded.');
        } elseif ($this->_vfsSize !== 0) {
            $this->_vfsSize += $filesize;
        }
    }

    /**
     * Checks the quota when preparing to delete data.
     *
     * @param string $path  The path the file is located in.
     * @param string $name  The filename.
     *
     * @throws VFS_Exception
     */
    protected function _checkQuotaDelete($path, $name)
    {
        if (($this->_params['vfs_quotalimit'] != -1) &&
            !empty($this->_vfsSize)) {
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
            } else {
                return $path . '/' . $name;
            }
        }

        return $name;
    }

    /**
     * Converts a string to all lowercase characters ignoring the current
     * locale.
     *
     * @param string $string  The string to be lowercased
     *
     * @return string  The string with lowercase characters
     */
    public function strtolower($string)
    {
        $language = setlocale(LC_CTYPE, 0);
        setlocale(LC_CTYPE, 'C');
        $string = strtolower($string);
        setlocale(LC_CTYPE, $language);
        return $string;
    }

    /**
     * Returns the character (not byte) length of a string.
     *
     * @param string $string   The string to return the length of.
     * @param string $charset  The charset to use when calculating the
     *                         string's length.
     *
     * @return string  The string's length.
     */
    public function strlen($string, $charset = null)
    {
        if (extension_loaded('mbstring')) {
            if (is_null($charset)) {
                $charset = 'ISO-8859-1';
            }
            $result = @mb_strlen($string, $charset);
            if (!empty($result)) {
                return $result;
            }
        }
        return strlen($string);
    }


}
