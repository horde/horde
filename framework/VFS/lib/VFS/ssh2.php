<?php
/**
 * VFS implementation for an SSH2 server.
 * This module requires the SSH2 (version 0.10+) PECL package.
 *
 * Required values for $params:<pre>
 * username - (string) The username with which to connect to the ssh2 server.
 * password - (string) The password with which to connect to the ssh2 server.
 * hostspec - (string) The ssh2 server to connect to.</pre>
 *
 * Optional values for $params:<pre>
 * port - (integer) The port used to connect to the ssh2 server if other than
 *        22.</pre>
 *
 * Copyright 2006-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @editor  Cliff Green <green@umdnj.edu>
 * @package VFS
 */
class VFS_ssh2 extends VFS
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
    var $_permissions = array(
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
     * Variable holding the connection to the ssh2 server.
     *
     * @var resource
     */
    protected $_stream = false;

    /**
     * The SFTP resource stream.
     *
     * @var resource
     */
    protected $_sftp;

    /**
     * The current working directory.
     *
     * @var string
     */
    protected $_cwd;

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

        $statinfo = @ssh2_sftp_stat($this->_sftp, $this->_getPath($path, $name));
        if (($size = $statinfo['size']) === false) {
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
     * @throws VFS_Exception
     */
    public function read($path, $name)
    {
        $file = $this->readFile($path, $name);
        clearstatcache();

        return (filesize($file) === 0)
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

        if (!$this->_recv($this->_getPath($path, $name), $localFile)) {
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

        if (!$this->_send($tmpFile, $this->_getPath($path, $name)))  {
            if ($autocreate) {
                $this->autocreatePath($path);
                if ($this->_send($tmpFile, $this->_getPath($path, $name))) {
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
        $this->_checkQuotaDelete($path, $name);
        $this->_connect();

        if (!@ssh2_sftp_unlink($this->_sftp, $this->_getPath($path, $name))) {
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
        try {
            $this->_connect();
        } catch (VFS_Exception $e) {
            return false;
        }

        /* See if we can stat the remote filename. ANDed with 040000 is true
         * if it is a directory. */
        $statinfo = @ssh2_sftp_stat($this->_sftp, $this->_getPath($path, $name));
        return $statinfo['mode'] & 040000;
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
                throw new VFS_Exception(sprintf('Unable to delete "%s", the directory is not empty.', $this->_getPath($path, $name)));
            }
            foreach ($file_list as $file) {
                if ($file['type'] == '**dir') {
                    $this->deleteFolder($this->_getPath($path, $name), $file['name'], $recursive);
                } else {
                    $this->deleteFile($this->_getPath($path, $name), $file['name']);
                }
            }

            if (!@ssh2_sftp_rmdir($this->_sftp, $this->_getPath($path, $name))) {
                throw new VFS_Exception(sprintf('Cannot remove directory "%s".', $this->_getPath($path, $name)));
            }
        } else {
            if (!@ssh2_sftp_unlink($this->_sftp, $this->_getPath($path, $name))) {
                throw new VFS_Exception(sprintf('Cannot delete file "%s".', $this->_getPath($path, $name)));
            }
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

        if (!@ssh2_sftp_rename($this->_sftp, $this->_getPath($oldpath, $oldname), $this->_getPath($newpath, $newname))) {
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

        if (!@ssh2_sftp_mkdir($this->_sftp, $this->_getPath($path, $name))) {
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

        if (!@ssh2_exec($this->_stream, 'chmod ' . escapeshellarg($permission) . ' ' . escapeshellarg($this->_getPath($path, $name)))) {
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

        $files = array();

        /* If 'maplocalids' is set, check for the POSIX extension. */
        $mapids = (!empty($this->_params['maplocalids']) && extension_loaded('posix'));

        // THIS IS A PROBLEM....  there is no builtin systype() fn for SSH2.
        // Go with unix-style listings for now...
        $type = 'unix';

        $olddir = $this->getCurrentDirectory();
        if (strlen($path)) {
            $this->_setPath($path);
        }

        if ($type == 'unix') {
            $ls_args = 'l';

            // Get numeric ids if we're going to use posix_* functions to
            // map them.
            if ($mapids) {
                $ls_args .= 'n';
            }

            // If we don't want dotfiles, We can save work here by not doing
            // an ls -a and then not doing the check later (by setting
            // $dotfiles to true, the if is short-circuited).
            if ($dotfiles) {
                $ls_args .= 'a';
                $dotfiles = true;
            }

            $stream = @ssh2_exec($this->_stream, 'LC_TIME=C ls -' . $ls_args . ' ' . escapeshellarg($path));
        } else {
            $stream = @ssh2_exec($this->_stream, '');
        }

        stream_set_blocking($stream, true);
        unset($list);
        while (!feof($stream)) {
            $line = fgets($stream);
            if ($line === false) {
                break;
            }
            $list[] = trim($line);
        }
        fclose($stream);

        if (!is_array($list)) {
            if (isset($olddir)) {
                $this->_setPath($olddir);
            }
            return array();
        }

        $currtime = time();

        foreach ($list as $line) {
            $file = array();
            $item = preg_split('/\s+/', $line);
            if (($type == 'unix') ||
                (($type == 'win') &&
                 !preg_match('|\d\d-\d\d-\d\d|', $item[0]))) {
                if ((count($item) < 8) || (substr($line, 0, 5) == 'total')) {
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
                $file['name'] = substr($line, strpos($line, sprintf("%s %2s %5s", $item[5], $item[6], $item[7])) + 13);

                // Filter out '.' and '..' entries.
                if (preg_match('/^\.\.?\/?$/', $file['name'])) {
                    continue;
                }

                // Filter out dotfiles if they aren't wanted.
                if (!$dotfiles && (substr($file['name'], 0, 1) == '.')) {
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
                       if ((count($name) == 1) ||
                           (($name[0] === '') && (count($name) == 2))) {
                           $file['linktype'] = '**none';
                       } else {
                           $file['linktype'] = self::strtolower(array_pop($name));
                       }
                   }
                } elseif ($p1 === 'd') {
                    $file['type'] = '**dir';
                } else {
                    $name = explode('.', $file['name']);
                    if ((count($name) == 1) ||
                        ((substr($file['name'], 0, 1) === '.') &&
                         (count($name) == 2))) {
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
                    // If the ssh2 server reports a file modification date more
                    // less than one day in the future, don't try to subtract
                    // a year from the date.  There is no way to know, for
                    // example, if the VFS server and the ssh2 server reside
                    // in different timezones.  We should simply report to the
                    //  user what the SSH2 server is returning.
                    if ($file['date'] > ($currtime + 86400)) {
                        $file['date'] = strtotime($item[7] . ':00' . $item[5] . ' ' . $item[6] . ' ' . (date('Y', $currtime) - 1));
                    }
                } else {
                    $file['date'] = strtotime('00:00:00' . $item[5] . ' ' . $item[6] . ' ' . $item[7]);
                }
            } elseif ($type == 'netware') {
                $file = Array();
                $file['perms'] = $item[1];
                $file['owner'] = $item[2];
                if ($item[0] == 'd') {
                    $file['type'] = '**dir';
                } else {
                    $file['type'] = '**none';
                }
                $file['size'] = $item[3];
                $file['name'] = $item[7];
                $index = 8;
                while ($index < count($item)) {
                    $file['name'] .= ' ' . $item[$index];
                    $index++;
                }
            } else {
                /* Handle Windows SSH2 servers returning DOS-style file
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
                    if ((count($name) == 1) ||
                        ((substr($file['name'], 0, 1) === '.') &&
                         (count($name) == 2))) {
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
            $res = $this->_setPath($olddir);
            if (is_a($res, 'PEAR_Error')) {
                return $res;
            }
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
            'val' => $this->_parentDir($path),
            'label' => '..'
        );
        $folders[$folder['val']] = $folder;

        $folderList = $this->listFolder($path, null, $dotfolders, true);
        foreach ($folderList as $files) {
            $folders[$folder['val']] = array(
                'val' => $this->_getPath($path, $files['name']),
                'abbrev' => $files['name'],
                'label' => $folder['val']
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
     * @param boolean $autocreate  Auto-create the directory if it doesn't
     *                             exist?
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
                throw new VFS_Exception(sprintf('%s already exists.', $this->_getPath($dest, $name)));
            }
        }

        if ($this->isFolder($path, $name)) {
            $this->_copyRecursive($path, $name, $dest);
        } else {
            $tmpFile = tempnam(null, 'vfs');
            if (!$this->_recv($orig, $tmpFile)) {
                unlink($tmpFile);
                throw new VFS_Exception(sprintf('Failed to copy from "%s".', $orig));
            }

            $this->_checkQuotaWrite('file', $tmpFile);

            if (!$this->_send($tmpFile, $this->_getPath($dest, $name))) {
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
     * @param boolean $autocreate  Auto-create the directory if it doesn't
     *                             exist?
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

        if (!@ssh2_sftp_rename($this->_sftp, $orig, $this->_getPath($dest, $name))) {
            throw new VFS_Exception(sprintf('Failed to move to "%s".', $this->_getPath($dest, $name)));
        }
    }

    /**
     * Returns the current working directory on the SSH2 server.
     *
     * @return string  The current working directory.
     * @throws VFS_Exception
     */
    public function getCurrentDirectory()
    {
        $this->_connect();

        if (!strlen($this->_cwd)) {
            $stream = @ssh2_exec($this->_stream, 'pwd');
            stream_set_blocking($stream, true);
            $this->_cwd = trim(fgets($stream));
        }

        return $this->_cwd;
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
        if (!($stream = @ssh2_exec($this->_stream, 'cd ' . escapeshellarg($path) . '; pwd'))) {
            throw new VFS_Exception(sprintf('Unable to change to %s.', $path));
        }

        stream_set_blocking($stream, true);
        $this->_cwd = trim(fgets($stream));
        fclose($stream);
    }

    /**
     * Returns the full path of an item.
     *
     * @param string $path  The directory of the item.
     * @param string $name  The name of the item.
     *
     * @return mixed  Full path to the file when $path is not empty and just
     *                $name when not set.
     */
    protected function _getPath($path, $name)
    {
        return ($path !== '')
            ? ($path . '/' . $name)
            : $name;
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
        $this->_setPath('cd ' . $path . '/..');

        return $this->getCurrentDirectory();
    }

    /**
     * Attempts to open a connection to the SSH2 server.
     *
     * @throws VFS_Exception
     */
    protected function _connect()
    {
        if ($this->_stream !== false) {
            return;
        }

        if (!extension_loaded('ssh2')) {
            throw new VFS_Exception('The SSH2 PECL extension is not available.');
        }

        if (!is_array($this->_params)) {
            throw new VFS_Exception('No configuration information specified for SSH2 VFS.');
        }

        $required = array('hostspec', 'username', 'password');
        foreach ($required as $val) {
            if (!isset($this->_params[$val])) {
                throw new VFS_Exception(sprintf('Required "%s" not specified in VFS configuration.', $val));
            }
        }

        /* Connect to the ssh2 server using the supplied parameters. */
        if (empty($this->_params['port'])) {
            $this->_stream = @ssh2_connect($this->_params['hostspec']);
        } else {
            $this->_stream = @ssh2_connect($this->_params['hostspec'], $this->_params['port']);
        }

        if (!$this->_stream) {
            $this->_stream = false;
            throw new VFS_Exception('Connection to SSH2 server failed.');
        }

        if (!@ssh2_auth_password($this->_stream, $this->_params['username'], $this->_params['password'])) {
            $this->_stream = false;
            throw new VFS_Exception('Authentication to SSH2 server failed.');
        }

        /* Create sftp resource. */
        $this->_sftp = @ssh2_sftp($this->_stream);
    }

    /**
     * Sends local file to remote host.
     * This function exists because the php_scp_* functions doesn't seem to
     * work on some hosts.
     *
     * @param string $local   Full path to the local file.
     * @param string $remote  Full path to the remote location.
     *
     * @return boolean  Success.
     */
    protected function _send($local, $remote)
    {
        return @copy($local, $this->_wrap($remote));
    }

    /**
     * Receives file from remote host.
     * This function exists because the php_scp_* functions doesn't seem to
     * work on some hosts.
     *
     * @param string $local   Full path to the local file.
     * @param string $remote  Full path to the remote location.
     *
     * @return boolean  Success.
     */
    protected function _recv($remote, $local)
    {
        return @copy($this->_wrap($remote), $local);
    }

    /**
     * Generate a stream wrapper file spec for a remote file path
     *
     * @param string $remote  Full path to the remote location
     *
     * @return string  A full stream wrapper path to the remote location
     */
    protected function _wrap($remote)
    {
        return 'ssh2.sftp://' . $this->_params['username'] . ':' .
            $this->_params['password'] . '@' . $this->_params['hostspec'] .
            ':' . $this->_params['port'] . $remote;
    }

}
