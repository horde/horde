<?php
/**
 * VFS implementation for an FTP server.
 *
 * Required values for $params:<pre>
 * username - (string) The username with which to connect to the ftp server.
 * password - (string) The password with which to connect to the ftp server.
 * hostspec - (string) The ftp server to connect to.</pre>
 *
 * Optional values for $params:<pre>
 * lsformat - (string) The return formatting from the 'ls' command).
 *                       Values: 'aix', 'standard' (default)
 * maplocalids - (boolean) If true and the POSIX extension is available, the
 *               driver will map the user and group IDs returned from the FTP
 *               server with the local IDs from the local password file.  This
 *               is useful only if the FTP server is running on localhost or
 *               if the local user/group IDs are identical to the remote FTP
 *               server.
 * pasv - (boolean) If true, connection will be set to passive mode.
 * port - (integer) The port used to connect to the ftp server if other than
 *        21 (FTP default).
 * ssl - (boolean) If true, and PHP had been compiled with OpenSSL support,
 *        TLS transport-level encryption will be negotiated with the server.
 * timeout -(integer) The timeout for the server.
 * type - (string) The type of the remote FTP server.
 *        Possible values: 'unix', 'win', 'netware'
 *        By default, we attempt to auto-detect type.</pre>
 *
 * Copyright 2002-2010 The Horde Project (http://www.horde.org/)
 * Copyright 2002-2007 Michael Varghese <mike.varghese@ascellatech.com>
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @author  Michael Varghese <mike.varghese@ascellatech.com>
 * @package VFS
 */
class VFS_ftp extends VFS
{
    /**
     * List of additional credentials required for this VFS backend.
     *
     * @var array
     */
    protected $_credentials = array('username', 'password');

    /**
     * List of permissions and if they can be changed in this VFS backend.
     *
     * @var array
     */
    protected $_permissions = array(
        'owner' => array(
            'read' => true,
            'write' => true,
            'execute' => true
        ),
        'group' => array(
            'read' => true,
            'write' => true,
            'execute' => true
        ),
        'all' => array(
            'read' => true,
            'write' => true,
            'execute' => true
        )
    );

    /**
     * Variable holding the connection to the ftp server.
     *
     * @var resource
     */
    protected $_stream = false;

    /**
     * Local cache array for user IDs.
     *
     * @var array
     */
    protected $_uids = array();

    /**
     * Local cache array for group IDs.
     *
     * @var array
     */
    protected $_gids = array();

    /**
     * The FTP server type.
     *
     * @var string
     */
    protected $_type;

    /**
     * Returns the size of a file.
     *
     * @param string $path  The path of the file.
     * @param string $name  The filename.
     *
     * @return integer  The size of the file in bytes.
     * @throws VFS_Exception
     */
    public function size($path, $name)
    {
        $this->_connect();

        if (($size = @ftp_size($this->_stream, $this->_getPath($path, $name))) === false) {
            throw new VFS_Exception(sprintf('Unable to check file size of "%s".', $this->_getPath($path, $name)));
        }

        return $size;
    }

    /**
     * Retrieves a file from the VFS.
     *
     * @param string $path  The pathname to the file.
     * @param string $name  The filename to retrieve.
     *
     * @return string  The file data.
     */
    public function read($path, $name)
    {
        $file = $this->readFile($path, $name);
        $size = filesize($file);

        return ($size === 0)
            ? ''
            : file_get_contents($file);
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
        $this->_connect();

        // Create a temporary file and register it for deletion at the
        // end of this request.
        if (!($localFile = tempnam(null, 'vfs'))) {
            throw new VFS_Exception('Unable to create temporary file.');
        }
        register_shutdown_function(create_function('', '@unlink(\'' . addslashes($localFile) . '\');'));

        $result = @ftp_get(
            $this->_stream,
            $localFile,
            $this->_getPath($path, $name),
            FTP_BINARY);

        if ($result === false) {
            throw new VFS_Exception(sprintf('Unable to open VFS file "%s".', $this->_getPath($path, $name)));
        }

        return $localFile;
    }

    /**
     * Open a stream to a file in the VFS.
     *
     * @param string $path  The pathname to the file.
     * @param string $name  The filename to retrieve.
     *
     * @return resource  The stream.
     * @throws VFS_Exception
     */
    public function readStream($path, $name)
    {
        return fopen($this->readFile($path, $name), OS_WINDOWS ? 'rb' : 'r');
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
        $this->_connect();
        $this->_checkQuotaWrite('file', $tmpFile);

        if (!@ftp_put($this->_stream, $this->_getPath($path, $name), $tmpFile, FTP_BINARY)) {
            if ($autocreate) {
                $this->autocreatePath($path);
                if (@ftp_put($this->_stream, $this->_getPath($path, $name), $tmpFile, FTP_BINARY)) {
                    return;
                }
            }

            throw new VFS_Exception(sprintf('Unable to write VFS file "%s".', $this->_getPath($path, $name)));
        }
    }

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
        $this->_checkQuotaWrite('string', $data);
        $tmpFile = tempnam(null, 'vfs');
        file_put_contents($tmpFile, $data);
        try {
            $this->write($path, $name, $tmpFile, $autocreate);
            unlink($tmpFile);
        } catch (VFS_Exception $e) {
            unlink($tmpFile);
            throw $e;
        }
    }

    /**
     * Deletes a file from the VFS.
     *
     * @param string $path  The path to delete the file from.
     * @param string $name  The filename to delete.
     *
     * @throws VFS_Exception
     */
    public function deleteFile($path, $name)
    {
        $this->_connect();
        $this->_checkQuotaDelete($path, $name);

        if (!@ftp_delete($this->_stream, $this->_getPath($path, $name))) {
            throw new VFS_Exception(sprintf('Unable to delete VFS file "%s".', $this->_getPath($path, $name)));
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
        $result = false;

        try {
            $this->_connect();

            $olddir = $this->getCurrentDirectory();

            /* See if we can change to the given path. */
            $result = @ftp_chdir($this->_stream, $this->_getPath($path, $name));

            $this->_setPath($olddir);
        } catch (VFS_Exception $e) {}

        return $result;
    }

    /**
     * Deletes a folder from the VFS.
     *
     * @param string $path        The parent folder.
     * @param string $name        The name of the folder to delete.
     * @param boolean $recursive  Force a recursive delete?
     *
     * @throws VFS_Exception
     */
    public function deleteFolder($path, $name, $recursive = false)
    {
        $this->_connect();

        $isDir = false;
        foreach ($this->listFolder($path) as $file) {
            if ($file['name'] == $name && $file['type'] == '**dir') {
                $isDir = true;
                break;
            }
        }

        if ($isDir) {
            $file_list = $this->listFolder($this->_getPath($path, $name));
            if (count($file_list) && !$recursive) {
                throw new VFS_Exception(sprintf('Unable to delete "%s", as the directory is not empty.', $this->_getPath($path, $name)));
            }

            foreach ($file_list as $file) {
                if ($file['type'] == '**dir') {
                    $this->deleteFolder($this->_getPath($path, $name), $file['name'], $recursive);
                } else {
                    $this->deleteFile($this->_getPath($path, $name), $file['name']);
                }
            }

            if (!@ftp_rmdir($this->_stream, $this->_getPath($path, $name))) {
                throw new VFS_Exception(sprintf('Cannot remove directory "%s".', $this->_getPath($path, $name)));
            }
        } elseif (!@ftp_delete($this->_stream, $this->_getPath($path, $name))) {
            throw new VFS_Exception(sprintf('Cannot delete file "%s".', $this->_getPath($path, $name)));
        }
    }

    /**
     * Renames a file in the VFS.
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
        $this->autocreatePath($newpath);

        if (!@ftp_rename($this->_stream, $this->_getPath($oldpath, $oldname), $this->_getPath($newpath, $newname))) {
            throw new VFS_Exception(sprintf('Unable to rename VFS file "%s".', $this->_getPath($oldpath, $oldname)));
        }
    }

    /**
     * Creates a folder on the VFS.
     *
     * @param string $path  The parent folder.
     * @param string $name  The name of the new folder.
     *
     * @throws VFS_Exception
     */
    public function createFolder($path, $name)
    {
        $this->_connect();

        if (!@ftp_mkdir($this->_stream, $this->_getPath($path, $name))) {
            throw new VFS_Exception(sprintf('Unable to create VFS directory "%s".', $this->_getPath($path, $name)));
        }
    }

    /**
     * Changes permissions for an item on the VFS.
     *
     * @param string $path        The parent folder of the item.
     * @param string $name        The name of the item.
     * @param string $permission  The permission to set.
     *
     * @throws VFS_Exception
     */
    public function changePermissions($path, $name, $permission)
    {
        $this->_connect();

        if (!@ftp_site($this->_stream, 'CHMOD ' . $permission . ' ' . $this->_getPath($path, $name))) {
            throw new VFS_Exception(sprintf('Unable to change permission for VFS file "%s".', $this->_getPath($path, $name)));
        }
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
     * @throws VFS_Exception
     */
    protected function _listFolder($path = '', $filter = null,
                                   $dotfiles = true, $dironly = false)
    {
        $this->_connect();

        if (empty($this->_type)) {
            if (!empty($this->_params['type'])) {
                $this->_type = $this->_params['type'];
            } else {
                $type = self::strtolower(@ftp_systype($this->_stream));
                if ($type == 'unknown') {
                    // Go with unix-style listings by default.
                    $type = 'unix';
                } elseif (strpos($type, 'win') !== false) {
                    $type = 'win';
                } elseif (strpos($type, 'netware') !== false) {
                    $type = 'netware';
                }

                $this->_type = $type;
            }
        }

        $olddir = $this->getCurrentDirectory();
        if (strlen($path)) {
            $this->_setPath($path);
        }

        if ($this->_type == 'unix') {
            // If we don't want dotfiles, We can save work here by not
            // doing an ls -a and then not doing the check later (by
            // setting $dotfiles to true, the if is short-circuited).
            if ($dotfiles) {
                $list = ftp_rawlist($this->_stream, '-al');
                $dotfiles = true;
            } else {
                $list = ftp_rawlist($this->_stream, '-l');
            }
        } else {
           $list = ftp_rawlist($this->_stream, '');
        }

        if (!is_array($list)) {
            if (isset($olddir)) {
                $this->_setPath($olddir);
            }
            return array();
        }

        /* If 'maplocalids' is set, check for the POSIX extension. */
        $mapids = (!empty($this->_params['maplocalids']) && extension_loaded('posix'));

        $currtime = time();
        $files = array();

        foreach ($list as $line) {
            $file = array();

            $item = preg_split('/\s+/', $line);
            if (($this->_type == 'unix') ||
                (($this->_type == 'win') && !preg_match('|\d\d-\d\d-\d\d|', $item[0]))) {
                if (count($item) < 8 || substr($line, 0, 5) == 'total') {
                    continue;
                }
                $file['perms'] = $item[0];
                if ($mapids) {
                    if (!isset($this->_uids[$item[2]])) {
                        $entry = posix_getpwuid($item[2]);
                        $this->_uids[$item[2]] = (empty($entry)) ? $item[2] : $entry['name'];
                    }
                    $file['owner'] = $this->_uids[$item[2]];
                    if (!isset($this->_uids[$item[3]])) {
                        $entry = posix_getgrgid($item[3]);
                        $this->_uids[$item[3]] = (empty($entry)) ? $item[3] : $entry['name'];
                    }
                    $file['group'] = $this->_uids[$item[3]];

                } else {
                    $file['owner'] = $item[2];
                    $file['group'] = $item[3];
                }

                if (!empty($this->_params['lsformat']) &&
                    ($this->_params['lsformat'] == 'aix')) {
                    $file['name'] = substr($line, strpos($line, sprintf("%s %2s %-5s", $item[5], $item[6], $item[7])) + 13);
                } else {
                    $file['name'] = substr($line, strpos($line, sprintf("%s %2s %5s", $item[5], $item[6], $item[7])) + 13);
                }

                // Filter out '.' and '..' entries.
                if (preg_match('/^\.\.?\/?$/', $file['name'])) {
                    continue;
                }

                // Filter out dotfiles if they aren't wanted.
                if (!$dotfiles && substr($file['name'], 0, 1) == '.') {
                    continue;
                }

                $p1 = substr($file['perms'], 0, 1);
                if ($p1 === 'l') {
                    $file['link'] = substr($file['name'], strpos($file['name'], '->') + 3);
                    $file['name'] = substr($file['name'], 0, strpos($file['name'], '->') - 1);
                    $file['type'] = '**sym';

                   if ($this->isFolder('', $file['link'])) {
                       $file['linktype'] = '**dir';
                   } else {
                       $parts = explode('/', $file['link']);
                       $name = explode('.', array_pop($parts));
                       if (count($name) == 1 || ($name[0] === '' && count($name) == 2)) {
                           $file['linktype'] = '**none';
                       } else {
                           $file['linktype'] = VFS::strtolower(array_pop($name));
                       }
                   }
                } elseif ($p1 === 'd') {
                    $file['type'] = '**dir';
                } else {
                    $name = explode('.', $file['name']);
                    if (count($name) == 1 || (substr($file['name'], 0, 1) === '.' && count($name) == 2)) {
                        $file['type'] = '**none';
                    } else {
                        $file['type'] = self::strtolower($name[count($name) - 1]);
                    }
                }
                if ($file['type'] == '**dir') {
                    $file['size'] = -1;
                } else {
                    $file['size'] = $item[4];
                }
                if (strpos($item[7], ':') !== false) {
                    $file['date'] = strtotime($item[7] . ':00' . $item[5] . ' ' . $item[6] . ' ' . date('Y', $currtime));
                    // If the ftp server reports a file modification date more
                    // less than one day in the future, don't try to subtract
                    // a year from the date.  There is no way to know, for
                    // example, if the VFS server and the ftp server reside
                    // in different timezones.  We should simply report to the
                    //  user what the FTP server is returning.
                    if ($file['date'] > ($currtime + 86400)) {
                        $file['date'] = strtotime($item[7] . ':00' . $item[5] . ' ' . $item[6] . ' ' . (date('Y', $currtime) - 1));
                    }
                } else {
                    $file['date'] = strtotime('00:00:00' . $item[5] . ' ' . $item[6] . ' ' . $item[7]);
                }
            } elseif ($this->_type == 'netware') {
                if (count($item) < 8 || substr($line, 0, 5) == 'total') {
                    continue;
                }

                $file = array();
                $file['perms'] = $item[1];
                $file['owner'] = $item[2];
                if ($item[0] == 'd') {
                    $file['type'] = '**dir';
                } else {
                    $file['type'] = '**none';
                }
                $file['size'] = $item[3];

                // We don't know the timezone here. Just report what the FTP server says.
                if (strpos($item[6], ':') !== false) {
                    $file['date'] = strtotime($item[6] . ':00 ' . $item[5] . ' ' . $item[4] . ' ' . date('Y'));
                } else {
                    $file['date'] = strtotime('00:00:00 ' . $item[5] . ' ' . $item[4] . ' ' . $item[6]);
                }

                $file['name'] = $item[7];
                $index = 8;
                while ($index < count($item)) {
                    $file['name'] .= ' ' . $item[$index];
                    $index++;
                }
            } else {
                /* Handle Windows FTP servers returning DOS-style file
                 * listings. */
                $file['perms'] = '';
                $file['owner'] = '';
                $file['group'] = '';
                $file['name'] = $item[3];
                $index = 4;
                while ($index < count($item)) {
                    $file['name'] .= ' ' . $item[$index];
                    $index++;
                }
                $file['date'] = strtotime($item[0] . ' ' . $item[1]);
                if ($item[2] == '<DIR>') {
                    $file['type'] = '**dir';
                    $file['size'] = -1;
                } else {
                    $file['size'] = $item[2];
                    $name = explode('.', $file['name']);
                    if (count($name) == 1 || (substr($file['name'], 0, 1) === '.' && count($name) == 2)) {
                        $file['type'] = '**none';
                    } else {
                        $file['type'] = self::strtolower($name[count($name) - 1]);
                    }
                }
            }

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

        if (isset($olddir)) {
            $this->_setPath($olddir);
        }

        return $files;
    }

    /**
     * Returns a sorted list of folders in the specified directory.
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
        $this->_connect();

        $folder = array(
            'abbrev' => '..',
            'label' => '..',
            'val' => $this->_parentDir($path)
        );
        $folders = array($folder['val'] => $folder);

        $folderList = $this->listFolder($path, null, $dotfolders, true);
        foreach ($folderList as $files) {
            $folders[$folder['val']] = array(
                'abbrev' => $files['name'],
                'label' => $folder['val'],
                'val' => $this->_getPath($path, $files['name'])
            );
        }

        ksort($folders);

        return $folders;
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

        $this->_connect();

        if ($autocreate) {
            $this->autocreatePath($dest);
        }

        foreach ($this->listFolder($dest, null, true) as $file) {
            if ($file['name'] == $name) {
                throw new VFS_Exception(sprintf('%s already exists.'), $this->_getPath($dest, $name));
            }
        }

        if ($this->isFolder($path, $name)) {
            $this->_copyRecursive($path, $name, $dest);
        } else {
            $tmpFile = tempnam(null, 'vfs');
            $fetch = @ftp_get($this->_stream, $tmpFile, $orig, FTP_BINARY);
            if (!$fetch) {
                unlink($tmpFile);
                throw new VFS_Exception(sprintf('Failed to copy from "%s".', $orig));
            }

            $this->_checkQuotaWrite('file', $tmpFile);

            if (!@ftp_put($this->_stream, $this->_getPath($dest, $name), $tmpFile, FTP_BINARY)) {
                unlink($tmpFile);
                throw new VFS_Exception(sprintf('Failed to copy to "%s".', $this->_getPath($dest, $name)));
            }

            unlink($tmpFile);
        }
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
        $orig = $this->_getPath($path, $name);
        if (preg_match('|^' . preg_quote($orig) . '/?$|', $dest)) {
            throw new VFS_Exception('Cannot move file(s) - destination is within source.');
        }

        $this->_connect();

        if ($autocreate) {
            $this->autocreatePath($dest);
        }

        foreach ($this->listFolder($dest, null, true) as $file) {
            if ($file['name'] == $name) {
                throw new VFS_Exception(sprintf('%s already exists.', $this->_getPath($dest, $name)));
            }
        }

        if (!@ftp_rename($this->_stream, $orig, $this->_getPath($dest, $name))) {
            throw new VFS_Exception(sprintf('Failed to move to "%s".', $this->_getPath($dest, $name)));
        }
    }

    /**
     * Returns the current working directory on the FTP server.
     *
     * @return string  The current working directory.
     * @throws VFS_Exception
     */
    public function getCurrentDirectory()
    {
        $this->_connect();
        return @ftp_pwd($this->_stream);
    }

    /**
     * Changes the current directory on the server.
     *
     * @param string $path  The path to change to.
     *
     * @throws VFS_Exception
     */
    protected function _setPath($path)
    {
        if (!@ftp_chdir($this->_stream, $path)) {
            throw new VFS_Exception(sprintf('Unable to change to %s.', $path));
        }
    }

    /**
     * Returns the parent directory of the specified path.
     *
     * @param string $path  The path to get the parent of.
     *
     * @return string  The parent directory.
     * @throws VFS_Exception
     */
    protected function _parentDir($path)
    {
        $this->_connect();

        $olddir = $this->getCurrentDirectory();
        @ftp_cdup($this->_stream);

        $parent = $this->getCurrentDirectory();
        $this->_setPath($olddir);

        if (!$parent) {
            throw new VFS_Exception('Unable to determine current directory.');
        }

        return $parent;
    }

    /**
     * Attempts to open a connection to the FTP server.
     *
     * @throws VFS_Exception
     */
    protected function _connect()
    {
        if ($this->_stream !== false) {
            return;
        }

        if (!extension_loaded('ftp')) {
            throw new VFS_Exception('The FTP extension is not available.');
        }

        if (!is_array($this->_params)) {
            throw new VFS_Exception('No configuration information specified for FTP VFS.');
        }

        $required = array('hostspec', 'username', 'password');
        foreach ($required as $val) {
            if (!isset($this->_params[$val])) {
                throw new VFS_Exception(sprintf('Required "%s" not specified in VFS configuration.', $val));
            }
        }

        /* Connect to the ftp server using the supplied parameters. */
        if (!empty($this->_params['ssl'])) {
            if (function_exists('ftp_ssl_connect')) {
                $this->_stream = @ftp_ssl_connect($this->_params['hostspec'], $this->_params['port']);
            } else {
                throw new VFS_Exception('Unable to connect with SSL.');
            }
        } else {
            $this->_stream = @ftp_connect($this->_params['hostspec'], $this->_params['port']);
        }

        if (!$this->_stream) {
            throw new VFS_Exception('Connection to FTP server failed.');
        }

        if (!@ftp_login($this->_stream, $this->_params['username'], $this->_params['password'])) {
            @ftp_quit($this->_stream);
            $this->_stream = false;
            throw new VFS_Exception('Authentication to FTP server failed.');
        }

        if (!empty($this->_params['pasv'])) {
            @ftp_pasv($this->_stream, true);
        }

        if (!empty($this->_params['timeout'])) {
            ftp_set_option($this->_stream, FTP_TIMEOUT_SEC, $this->_params['timeout']);
        }
    }

}
