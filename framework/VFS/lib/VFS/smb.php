<?php
/**
 * Stateless VFS implementation for a SMB server, based on smbclient.
 *
 * Required values for $params:
 * <pre>
 * username - (string)The username with which to connect to the SMB server.
 * password - (string) The password with which to connect to the SMB server.
 * hostspec - (string) The SMB server to connect to.
 * port' - (integer) The SMB port number to connect to.
 * share - (string) The share to access on the SMB server.
 * smbclient - (string) The path to the 'smbclient' executable.
 * </pre>
 *
 * Optional values for $params:
 * <pre>
 * ipaddress - (string) The address of the server to connect to.
 * </pre>
 *
 * Functions not implemented:
 * - changePermissions(): The SMB permission style does not fit with the
 *                        module.
 *
 * Codebase copyright 2002 Paul Gareau <paul@xhawk.net>.  Adapted with
 * permission by Patrice Levesque <wayne@ptaff.ca> from phpsmb-0.8 code, and
 * converted to the LGPL.  Please do not taunt original author, contact
 * Patrice Levesque or dev@lists.horde.org.
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Paul Gareau <paul@xhawk.net>
 * @author  Patrice Levesque <wayne@ptaff.ca>
 * @package VFS
 */
class VFS_smb extends VFS
{
    /**
     * List of additional credentials required for this VFS backend.
     *
     * @var array
     */
    protected $_credentials = array('username', 'password');

    /**
     * Authenticates a user on the SMB server and share.
     *
     * @throws VFS_Exception
     */
    protected function _connect()
    {
        try {
            $this->_command('', array('quit'));
        } catch (VFS_Exception $e) {
            throw new VFS_Exception('Authentication to the SMB server failed.');
        }
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
     */
    public function readFile($path, $name)
    {
        // Create a temporary file and register it for deletion at the
        // end of this request.
        if (!($localFile = tempnam(null, 'vfs'))) {
            throw new VFS_Exception('Unable to create temporary file.');
        }
        register_shutdown_function(create_function('', '@unlink(\'' . addslashes($localFile) . '\');'));

        list($path, $name) = $this->_escapeShellCommand($path, $name);
        $cmd = array('get \"' . $name . '\" ' . $localFile);
        $this->_command($path, $cmd);
        if (!file_exists($localFile)) {
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
     */
    public function readStream($path, $name)
    {
        return fopen($this->readFile($path, $name),OS_WINDOWS ? 'rb' : 'r');
    }

    /**
     * Stores a file in the VFS.
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
        // Double quotes not allowed in SMB filename.
        $name = str_replace('"', "'", $name);

        list($path, $name) = $this->_escapeShellCommand($path, $name);
        $cmd = array('put \"' . $tmpFile . '\" \"' . $name . '\"');
        // do we need to first autocreate the directory?
        if ($autocreate) {
            $this->autocreatePath($path);
        }

        $this->_command($path, $cmd);
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
     * @param string $name  The filename to use.
     *
     * @throws VFS_Exception
     */
    public function deleteFile($path, $name)
    {
        // In some samba versions after samba-3.0.25-pre2, $path must
        // end in a trailing slash.
        if (substr($path, -1) != '/') {
            $path .= '/';
        }

        list($path, $name) = $this->_escapeShellCommand($path, $name);
        $cmd = array('del \"' . $name . '\"');
        $this->_command($path, $cmd);
    }

    /**
     * Checks if a given pathname is a folder.
     *
     * @param string $path  The path to the folder.
     * @param string $name  The file or folder name.
     *
     * @return boolean  True if it is a folder, false otherwise.
     */
    public function isFolder($path, $name)
    {
        list($path, $name) = $this->_escapeShellCommand($path, $name);
        $cmd = array('quit');
        try {
            $this->_command($this->_getPath($path, $name), array('quit'));
            return true;
        } catch (VFS_Exception $e) {
            return false;
        }
    }

    /**
     * Deletes a folder from the VFS.
     *
     * @param string $path        The path to delete the folder from.
     * @param string $name        The name of the folder to delete.
     * @param boolean $recursive  Force a recursive delete?
     *
     * @throws VFS_Exception
     */
    public function deleteFolder($path, $name, $recursive = false)
    {
        // In some samba versions after samba-3.0.25-pre2, $path must
        // end in a trailing slash.
        if (substr($path, -1) != '/') {
            $path .= '/';
        }

        if (!$this->isFolder($path, $name)) {
            throw new VFS_Exception(sprintf('"%s" is not a directory.', $path . '/' . $name));
        }

        $file_list = $this->listFolder($this->_getPath($path, $name));

        if ($file_list && !$recursive) {
            throw new VFS_Exception(sprintf('Unable to delete "%s", the directory is not empty.', $this->_getPath($path, $name)));
        }

        foreach ($file_list as $file) {
            if ($file['type'] == '**dir') {
                $this->deleteFolder($this->_getPath($path, $name), $file['name'], $recursive);
            } else {
                $this->deleteFile($this->_getPath($path, $name), $file['name']);
            }
        }

        // Really delete the folder.
        list($path, $name) = $this->_escapeShellCommand($path, $name);
        $cmd = array('rmdir \"' . $name . '\"');

        try {
            $this->_command($path, $cmd);
        } catch (VFS_Exception $e) {
            throw new VFS_Exception(sprintf('Unable to delete VFS folder "%s".', $this->_getPath($path, $name)));
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
        $this->autocreatePath($newpath);

        // Double quotes not allowed in SMB filename. The '/' character should
        // also be removed from the beginning/end of the names.
        $oldname = str_replace('"', "'", trim($oldname, '/'));
        $newname = str_replace('"', "'", trim($newname, '/'));

        if (empty($oldname)) {
            throw new VFS_Exception('Unable to rename VFS file to same name.');
        }

        /* If the path was not empty (i.e. the path is not the root path),
         * then add the trailing '/' character to path. */
        if (!empty($oldpath)) {
            $oldpath .= '/';
        }
        if (!empty($newpath)) {
            $newpath .= '/';
        }

        list($file, $name) = $this->_escapeShellCommand($oldname, $newname);
        $cmd = array('rename \"' .  str_replace('/', '\\\\', $oldpath) . $file . '\" \"' .
                                    str_replace('/', '\\\\', $newpath) . $name . '\"');

        try {
            $this->_command('', $cmd);
        } catch (VFS_Exception $e) {
            throw new VFS_Exception(sprintf('Unable to rename VFS file "%s".', $this->_getPath($path, $name)));
        }
    }

    /**
     * Creates a folder on the VFS.
     *
     * @param string $path  The path of directory to create folder.
     * @param string $name  The name of the new folder.
     *
     * @throws VFS_Exception
     */
    public function createFolder($path, $name)
    {
        // In some samba versions after samba-3.0.25-pre2, $path must
        // end in a trailing slash.
        if (substr($path, -1) != '/') {
            $path .= '/';
        }

        // Double quotes not allowed in SMB filename.
        $name = str_replace('"', "'", $name);

        list($dir, $mkdir) = $this->_escapeShellCommand($path, $name);
        $cmd = array('mkdir \"' . $mkdir . '\"');

        try {
            $this->_command($dir, $cmd);
        } catch (VFS_Exception $e) {
            throw new VFS_Exception(sprintf('Unable to create VFS folder "%s".', $this->_getPath($path, $name)));
        }
    }

    /**
     * Returns an unsorted file list.
     *
     * @param string $path       The path of the directory to get the file list
     *                           for.
     * @param mixed $filter      Hash of items to filter based on filename.
     * @param boolean $dotfiles  Show dotfiles? This is irrelevant with
     *                           smbclient.
     * @param boolean $dironly   Show directories only?
     *
     * @return array  File list.
     * @throws VFS_Exception
     */
    public function listFolder($path = '', $filter = null, $dotfiles = true,
                               $dironly = false)
    {
        list($path) = $this->_escapeShellCommand($path);
        return $this->parseListing($this->_command($path, array('ls')), $filter, $dotfiles, $dironly);
    }

    /**
     */
    public function parseListing($res, $filter, $dotfiles, $dironly)
    {
        $num_lines = count($res);
        $files = array();
        for ($r = 0; $r < $num_lines; $r++) {
            // Match file listing.
            if (!preg_match('/^  (.+?) +([A-Z]*) +(\d+)  (\w\w\w \w\w\w [ \d]\d \d\d:\d\d:\d\d \d\d\d\d)$/', $res[$r], $match)) {
                continue;
            }

            // If the file name isn't . or ..
            if ($match[1] == '.' || $match[1] == '..') {
                continue;
            }

            $my_name = $match[1];

            // Filter out dotfiles if they aren't wanted.
            if (!$dotfiles && substr($my_name, 0, 1) == '.') {
                continue;
            }

            $my_size = $match[3];
            $ext_name = explode('.', $my_name);

            if ((strpos($match[2], 'D') !== false)) {
                $my_type = '**dir';
                $my_size = -1;
            } else {
                $my_type = self::strtolower($ext_name[count($ext_name) - 1]);
            }
            $my_date = strtotime($match[4]);
            $filedata = array('owner' => '',
                              'group' => '',
                              'perms' => '',
                              'name' => $my_name,
                              'type' => $my_type,
                              'date' => $my_date,
                              'size' => $my_size);
            // watch for filters and dironly
            if ($this->_filterMatch($filter, $my_name)) {
                unset($file);
                continue;
            }
            if ($dironly && $my_type !== '**dir') {
                unset($file);
                continue;
            }

            $files[$filedata['name']] = $filedata;
        }

        return $files;
    }

    /**
     * Returns a sorted list of folders in specified directory.
     *
     * @param string $path         The path of the directory to get the
     *                             directory list for.
     * @param mixed $filter        Hash of items to filter based on folderlist.
     * @param boolean $dotfolders  Include dotfolders? Irrelevant for SMB.
     *
     * @return array  Folder list.
     * @throws VFS_Exception
     */
    public function listFolders($path = '', $filter = null, $dotfolders = true)
    {
        // dirname will strip last component from path, even on a directory
        $folder = array(
            'val' => dirname($path),
            'abbrev' => '..',
            'label' => '..'
        );
        $folders = array($folder['val'] => $folder);

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
     * @param string $path         The path to store the file in.
     * @param string $name         The filename to use.
     * @param string $dest         The destination of the file.
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

        foreach ($this->listFolder($dest, null, true) as $file) {
            if ($file['name'] == $name) {
                throw new VFS_Exception(sprintf('%s already exists.', $this->_getPath($dest, $name)));
            }
        }

        if ($this->isFolder($path, $name)) {
            $this->_copyRecursive($path, $name, $dest);
        } else {
            try {
                $this->write($dest, $name, $this->readFile($path, $name));
            } catch (VFS_Exception $e) {
                throw new VFS_Exception(sprintf('Copy failed: %s', $this->_getPath($dest, $name)));
            }
        }
    }

    /**
     * Moves a file through the backend.
     *
     * @param string $path         The path to store the file in.
     * @param string $name         The filename to use.
     * @param string $dest         The destination of the file.
     * @param boolean $autocreate  Automatically create directories?
     *
     * @throws VFS_Exception
     */
    public function move($path, $name, $dest, $autocreate = false)
    {
        $orig = $this->_getPath($path, $name);
        if (preg_match('|^' . preg_quote($orig) . '/?$|', $dest)) {
            throw new VFS_Exception('Cannot copy file(s) - destination is within source.');
        }

        if ($autocreate) {
            $this->autocreatePath($dest);
        }

        foreach ($this->listFolder($dest, null, true) as $file) {
            if ($file['name'] == $name) {
                throw new VFS_Exception(sprintf('%s already exists.', $this->_getPath($dest, $name)));
            }
        }

        try {
            $this->rename($path, $name, $dest, $name);
        } catch (VFS_Exception $e) {
            throw new VFS_Exception(sprintf('Failed to move to "%s".', $this->_getPath($dest, $name)));
        }
    }

    /**
     * Replacement for escapeshellcmd(), variable length args, as we only want
     * certain characters escaped.
     *
     * @param array $array  Strings to escape.
     *
     * @return array  TODO
     */
    protected function _escapeShellCommand()
    {
        $ret = array();
        $args = func_get_args();
        foreach ($args as $arg) {
            $ret[] = str_replace(array(';', '\\'), array('\;', '\\\\'), $arg);
        }
        return $ret;
    }

    /**
     * Executes a command and returns output lines in array.
     *
     * @param string $cmd  Command to be executed.
     *
     * @return array  Array on success.
     * @throws VFS_Exception
     */
    protected function _execute($cmd)
    {
        $cmd = str_replace('"-U%"', '-N', $cmd);
        exec($cmd, $out, $ret);

        // In some cases, (like trying to delete a nonexistant file),
        // smbclient will return success (at least on 2.2.7 version I'm
        // testing on). So try to match error strings, even after success.
        if ($ret != 0) {
            $err = '';
            foreach ($out as $line) {
                if (strpos($line, 'Usage:') === 0) {
                    $err = 'Command syntax incorrect';
                    break;
                }
                if (strpos($line, 'ERRSRV') !== false ||
                    strpos($line, 'ERRDOS') !== false) {
                    $err = preg_replace('/.*\((.+)\).*/', '\\1', $line);
                    if (!$err) {
                        $err = $line;
                    }
                    break;
                }
            }
            if (!$err) {
                $err = $out ? $out[count($out) - 1] : $ret;
            }

            throw new VFS_Exception($err);
        }

        // Check for errors even on success.
        $err = '';
        foreach ($out as $line) {
            if (strpos($line, 'NT_STATUS_NO_SUCH_FILE') !== false ||
                strpos($line, 'NT_STATUS_OBJECT_NAME_NOT_FOUND') !== false) {
                $err = Horde_VFS_Translation::t("No such file");
                break;
            } elseif (strpos($line, 'NT_STATUS_ACCESS_DENIED') !== false) {
                $err = Horde_VFS_Translation::t("Permission Denied");
                break;
            }
        }

        if ($err) {
            throw new VFS_Exception($err);
        }

        return $out;
    }

    /**
     * Executes SMB commands - without authentication - and returns output
     * lines in array.
     *
     * @param array $path  Base path for command.
     * @param array $cmd   Commands to be executed.
     *
     * @return array  Array on success.
     * @throws VFS_Exception
     */
    protected function _command($path, $cmd)
    {
        list($share) = $this->_escapeShellCommand($this->_params['share']);
        putenv('PASSWD=' . $this->_params['password']);
        $ipoption = (isset($this->_params['ipaddress'])) ? (' -I ' . $this->_params['ipaddress']) : null;
        $fullcmd = $this->_params['smbclient'] .
            ' "//' . $this->_params['hostspec'] . '/' . $share . '"' .
            ' "-p' . $this->_params['port'] . '"' .
            ' "-U' . $this->_params['username'] . '"' .
            ' -D "' . $path . '" ' .
            $ipoption .
            ' -c "';
        foreach ($cmd as $c) {
            $fullcmd .= $c . ";";
        }
        $fullcmd .= '"';

        return $this->_execute($fullcmd);
    }

}
