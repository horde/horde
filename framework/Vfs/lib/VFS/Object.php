<?php
/**
 * A wrapper for the VFS class to return objects, instead of arrays.
 *
 * Copyright 2002-2007 Jon Wood <jon@jellybob.co.uk>
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Jon Wood <jon@jellybob.co.uk>
 * @package VFS
 */
class VFS_Object
{
    /**
     * The actual vfs object that does the work.
     *
     * @var VFS
     */
    protected $_vfs;

    /**
     * The current path that has been passed to listFolder(), if this
     * changes, the list will be rebuilt.
     *
     * @var string
     */
    protected $_currentPath;

    /**
     * The return value from a standard VFS listFolder() call, to
     * be read with the Object listFolder().
     *
     * @var array
     */
    protected $_folderList;

    /**
     * Constructor.
     *
     * If you pass in an existing VFS object, it will be used as the VFS
     * object for this object.
     *
     * @param VFS $vfs  The VFS object to wrap.
     */
    public function __construct($vfs)
    {
        if (isset($vfs)) {
            $this->_vfs = $vfs;
        }
    }

    /**
     * Check the credentials that we have to see if there is a valid login.
     *
     * @throws VFS_Exception;
     */
    public function checkCredentials()
    {
        $this->_vfs->checkCredentials();
    }

    /**
     * Set configuration parameters.
     *
     * @param array $params  An associative array of parameter name/value
     *                       pairs.
     */
    public function setParams($params = array())
    {
        $this->_vfs->setParams($params);
    }

    /**
     * Retrieve a file from the VFS.
     *
     * @param string $path  The pathname to the file.
     *
     * @return string  The file data.
     * @throws VFS_Exception
     */
    public function read($path)
    {
        return $this->_vfs->read(dirname($path), basename($path));
    }

    /**
     * Store a file in the VFS.
     *
     * @param string $path         The path to store the file in.
     * @param string $tmpFile      The temporary file containing the data to be
     *                             stored.
     * @param boolean $autocreate  Automatically create directories?
     *
     * @throws VFS_Exception
     */
    public function write($path, $tmpFile, $autocreate = false)
    {
        $this->_vfs->write(dirname($path), basename($path), $tmpFile, $autocreate);
    }

    /**
     * Store a file in the VFS from raw data.
     *
     * @param string $path         The path to store the file in.
     * @param string $data         The file data.
     * @param boolean $autocreate  Automatically create directories?
     *
     * @throws VFS_Exception
     */
    public function writeData($path, $data, $autocreate = false)
    {
        $this->_vfs->writeData(dirname($path), basename($path), $data, $autocreate);
    }

    /**
     * Delete a file from the VFS.
     *
     * @param string $path  The path to store the file in.
     * @param string $name  The filename to use.
     *
     * @throws VFS_Exception
     */
    public function deleteFile($path)
    {
        $this->_vfs->deleteFile(dirname($path), basename($path));
    }

    /**
     * Rename a file in the VFS.
     *
     * @param string $oldpath  The old path to the file.
     * @param string $oldname  The old filename.
     * @param string $newpath  The new path of the file.
     * @param string $newname  The new filename.
     *
     * @throws VFS_Exception
     */
    public function rename($oldpath, $newpath)
    {
        return $this->_vfs->rename(dirname($oldpath), basename($oldpath), dirname($newpath), basename($newpath));
    }

    /**
     * Create a folder in the VFS.
     *
     * @param string $path  The path to the folder.
     *
     * @throws VFS_Exception
     */
    public function createFolder($path)
    {
        $this->_vfs->createFolder(dirname($path));
    }

    /**
     * Deletes a folder from the VFS.
     *
     * @param string $path The path of the folder to delete.
     *
     * @throws VFS_Exception
     */
    public function deleteFolder($path)
    {
        $this->_vfs->deleteFolder(dirname($path));
    }

    /**
     * Returns a VFS_ListItem object if the folder can
     * be read, or a PEAR_Error if it can't be. Returns false once
     * the folder has been completely read.
     *
     * @param string $path  The path of the diretory.
     *
     * @return mixed  File list (array) on success or false if the folder is
     *                completely read.
     * @throws VFS_Exception
     */
    public function listFolder($path)
    {
        if (!($path === $this->_currentPath)) {
            $folderList = $this->_vfs->listFolder($path);
            if ($folderList) {
                $this->_folderList = $folderList;
                $this->_currentPath = $path;
            } else {
                throw new VFS_Exception('Could not read ' . $path . '.');
            }
        }

        return ($file = array_shift($this->_folderList))
            ? new VFS_ListItem($path, $file)
            : false;
    }

    /**
     * Changes permissions for an Item on the VFS.
     *
     * @param string $path        Holds the path of directory of the Item.
     * @param string $permission  TODO
     *
     * @throws VFS_Exception
     */
    public function changePermissions($path, $permission)
    {
        $this->_vfs->changePermissions(dirname($path), basename($path), $permission);
    }

    /**
     * Return the list of additional credentials required, if any.
     *
     * @return array  Credential list.
     */
    public function getRequiredCredentials()
    {
        return $this->_vfs->getRequiredCredentials();
    }

    /**
     * Return the array specificying what permissions are changeable for this
     * implementation.
     *
     * @return array  Changeable permisions.
     */
    public function getModifiablePermissions()
    {
        return $this->_vfs->getModifiablePermissions();
    }

}
