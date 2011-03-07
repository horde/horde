<?php
/**
 * VFS implementation for a Kolab IMAP server.
 *
 * Copyright 2002-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Gunnar Wrobel <wrobel@pardus.de>
 * @package VFS
 */
class VFS_kolab extends VFS
{
    /**
     * Variable holding the connection to the Kolab storage system.
     *
     * @var Horde_Kolab_IMAP
     */
    protected $_imap = false;

    /**
     * Cache for the list of folders.
     *
     * @var array
     */
    protected $_folders;

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
        list($app, $uid) = $this->_getAppUid($path);
        if ($app && $uid) {
            $handler = $this->_getAppHandler($app, $uid);
            $object = $handler->getObject($uid);

            if (isset($object['_attachments'][$name])) {
                return $handler->getAttachment($object['_attachments'][$name]['key']);
            }
        }

        //FIXME
        if ($this->isFolder(dirname($path), basename($path))) {
            $session = Horde_Kolab_Session::singleton();
            $imap = $session->getImap();

            $result = $imap->select(substr($path,1));
            if ($result instanceof PEAR_Error) {
                throw new VFS_Exception($result->getMessage());
            }

            $file = explode('/', $name);

            return $this->_getFile($imap, $file[0], $file[1]);
        }

        return '';
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
        list($app, $uid) = $this->_getAppUid($path);
        if ($app) {
            $handler = $this->_getAppHandler($app, $uid);
            $object = $handler->getObject($uid);
            $object['_attachments'][$name]['path'] = $tmpFile;
            if (empty($object['link-attachment'])) {
                $object['link-attachment'] = array($name);
            } else {
                $object['link-attachment'][] = $name;
            }

            return $handler->save($object, $uid);
        }

        if ($autocreate && !$this->isFolder(dirname($path), basename($path))) {
            $this->autocreatePath($path);
        }

        //FIXME
        throw new VFS_Exception('Not supported.');
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
        list($app, $uid) = $this->_getAppUid($path);
        if ($app) {
            $handler = $this->_getAppHandler($app, $uid);
            $object = $handler->getObject($uid);
            if (!isset($object['_attachments'][$name])) {
                throw new VFS_Exception('Unable to delete VFS file.');
            }
            unset($object['_attachments'][$name]);
            $object['link-attachment'] = array_values(array_diff($object['link-attachment'], array($name)));

            return $handler->save($object, $uid);
        }

        //FIXME
        throw new VFS_Exception('Not supported.');
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
        $list = Kolab_List::singleton();
        $folder = $this->_getFolder($path, $name);

        $object = $list->getNewFolder();
        $object->setName($folder);

        $object->save(array('type' => 'h-file'));
        if ($result instanceof PEAR_Error) {
            throw new VFS_Exception($result->getMessage());
        }

        $this->_folders = null;
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
        if ($recursive) {
            $this->emptyFolder($path . '/' . $name);
        } else {
            $list = $this->listFolder($path . '/' . $name, null, false);
            if (count($list)) {
                throw new VFS_Exception(sprintf('Unable to delete %s, the directory is not empty', $path . '/' . $name));
            }
        }

        list($app, $uid) = $this->_getAppUid($path . '/' . $name);
        if ($app) {
            /**
             * Objects provide no real folders and we don't delete them.
             */
            return;
        }

        $folders = $this->_getFolders();
        $folder = $this->_getFolder($path, $name);

        if (!empty($folders['/' . $folder])) {
            $folders['/' . $folder]->delete();
            $this->_folders = null;
            return;
        }

        throw new VFS_Exception(sprintf('No such folder %s!', '/' . $folder));
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
        $list = $this->listFolder($path, null, false, true);
        foreach ($list as $folder) {
            $this->deleteFolder($path, $folder['name'], true);
        }

        // Only files are left, get and delete them.
        $list = $this->listFolder($path, null, false);
        foreach ($list as $file) {
            $this->deleteFile($path, $file['name']);
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
    protected function _listFolder($path = '', $filter = null, $dotfiles = true,
                                   $dironly = false)
    {
        list($app, $uid) = $this->_getAppUid($path);
        if ($app) {
            if ($dironly) {
                /**
                 * Objects dont support directories.
                 */
                return array();
            }
            if ($uid) {
                $handler = $this->_getAppHandler($app, $uid);
                $object = $handler->getObject($uid);
                if ($object instanceof PEAR_Error) {
                    throw new VFS_Exception($object->getMessage());
                }

                $filenames = isset($object['_attachments'])
                    ? array_keys($object['_attachments'])
                    : array();
            } else {
                $filenames = $this->_getAppUids($app);
            }

            $owner = $GLOBALS['registry']->getAuth();

            $file = $files = array();
            foreach ($filenames as $filename) {
                $name = explode('.', $filename);

                if (count($name) == 1) {
                    $file['type'] = '**none';
                } else {
                    $file['type'] = self::strtolower($name[count($name) - 1]);
                }

                $file['size'] = '-1';
                $file['name'] = $filename;
                $file['group'] = 'none';
                $file['owner'] = $owner;
                $file['date'] = 0;
                $file['perms'] = 'rwxrwx---';

                $files[$file['name']] = $file;
            }

            return $files;
        }

        $owner = $GLOBALS['registry']->getAuth();

        $file = $files = array();

        $folders = $this->listFolders($path, $filter, $dotfiles);
        $list = $this->_getFolders();

        foreach ($folders as $folder) {
            $file['type'] = '**dir';
            $file['size'] = -1;
            $file['name'] = $folder['abbrev'];
            //FIXME
            $file['group'] = 'none';
            //FIXME
            $file['owner'] = $owner;
            //FIXME
            $file['date'] = 0;
            //FIXME
            $file['perms'] = 'rwxrwx---';

            $files[$file['name']] = $file;
        }

        if (!$dironly &&
            $this->isFolder(basename($path), basename($path)) &&
            !empty($list[$path])) {
            $session = Horde_Kolab_Session::singleton();
            $imap = $session->getImap();

            $result = $imap->select(substr($path, 1));
            if ($result instanceof PEAR_Error) {
                throw new VFS_Exception($result->getMessage());
            }

            $uids = $imap->getUids();
            if ($uids instanceof PEAR_Error) {
                throw new VFS_Exception($uids->getMessage());
            }

            foreach ($uids as $uid) {
                $result = array_merge($files, $this->_parseMessage($imap, $uid));
                $files = $result;
            }
        }

        return $files;
    }

    /**
     */
    protected function _parseMessage($imap, $uid)
    {
        $result = $imap->getMessageHeader($uid);
        if ($result instanceof PEAR_Error) {
            throw new VFS_Exception($result->getMessage());
        }

        $raw_headers = $result;

        $body = $imap->getMessageBody($uid);
        if ($body instanceof PEAR_Error) {
            throw new VFS_Exception($body->getMessage());
        }

        $raw_message = $raw_headers . $body;

        $mime_message = Horde_Mime_Part::parseMessage($raw_message);
        $parts = $mime_message->contentTypeMap();

        $owner = $GLOBALS['registry']->getAuth();

        $file = $files = array();

        foreach ($parts as $part_id => $disposition) {
            $part = $mime_message->getPart($part_id);

            $filename = $part->getDispositionParameter('filename');

            if ($filename) {
                $file['type'] = '**file';
                $file['size'] = $part->getSize();
                $file['name'] = $uid . '/' . $filename;
                //FIXME
                $file['group'] = 'none';
                //FIXME
                $file['owner'] = $owner;
                //FIXME
                $file['date'] = 0;
                //FIXME
                $file['perms'] = 'rwxrwx---';

                $files[$file['name']] = $file;
            }

        }

        return $files;
    }

    /**
     */
    protected function _getFile($imap, $uid, $filename)
    {
        $result = $imap->getMessageHeader($uid);
        if ($result instanceof PEAR_Error) {
            throw new VFS_Exception($result->getMessage());
        }

        $raw_headers = $result;

        $body = $imap->getMessageBody($uid);
        if ($body instanceof PEAR_Error) {
            throw new VFS_Exception($body->getMessage());
        }

        $raw_message = $raw_headers . $body;

        $mime_message = Horde_Mime_Part::parseMessage($raw_message);
        $parts = $mime_message->contentTypeMap();

        $owner = $GLOBALS['registry']->getAuth();

        foreach ($parts as $part_id => $disposition) {
            $part = $mime_message->getPart($part_id);

            $f = $part->getDispositionParameter('filename');
            if ($f && $f == $filename) {
                return $part->transferDecode();
            }
        }

        return '';
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
        if (substr($path, -1) != '/') {
            $path .= '/';
        }

        $aFolder = $aFolders = array();

        if ($dotfolders && $path != '/') {
            $aFolder['val'] = dirname($path);
            $aFolder['abbrev'] = '..';
            $aFolder['label'] = '..';

            $aFolders[$aFolder['val']] = $aFolder;
        }

        $folders = $this->_getFolders();

        $base_len = strlen($path);
        foreach (array_keys($folders) as $folder) {
            if (substr($folder, 0, $base_len) == $path) {
                $name = substr($folder, $base_len);
                if (!strpos($name, '/')) {
                    $aFolder['val']	= $folder;
                    $aFolder['abbrev'] = $name;
                    $aFolder['label'] = $folder;
                    $aFolders[$aFolder['val']] = $aFolder;
                }
            }
        }

        ksort($aFolders);
        return $aFolders;
    }

    /**
     */
    protected function _getFolder($path, $name)
    {
        $folder = $path . '/' . $name;

        while (substr($folder, 0, 1) == '/') {
            $folder = substr($folder, 1);
        }

        while (substr($folder, -1) == '/') {
            $folder = substr($folder, 0, -1);
        }

        return $folder;
    }

    /**
     */
    protected function _getFolders()
    {
        if (!isset($this->_folders)) {
            $vfs_folders = array();

            $list = Kolab_List::singleton();

            if (!empty($this->_params['all_folders'])) {
                $folders = $list->getFolders();
            } else {
                $folders = $list->getByType('h-file');
            }

            if ($folders instanceof PEAR_Error) {
                throw new VFS_Exception($folders->getMessage());
            }

            foreach ($folders as $folder) {
                $vfs_folders['/' . $folder->name] = &$folder;
            }

            foreach (array_keys($vfs_folders) as $name) {
                $dir = dirname($name);
                while ($dir != '/') {
                    if (!isset($vfs_folders[$dir])) {
                        $vfs_folders[$dir] = null;
                    }
                    $dir = dirname($dir);
                }
            }
            $this->_folders = $vfs_folders;
        }

        return $this->_folders;
    }

    /**
     */
    protected function _getAppUid($path)
    {
        if (defined('TURBA_VFS_PATH') &&
            substr($path, 0, strlen(TURBA_VFS_PATH)) == TURBA_VFS_PATH) {
            return array('turba', substr($path, strlen(TURBA_VFS_PATH) + 1));
        }

        return array(false, false);
    }

    /**
     */
    protected function _getAppHandler($app, $uid)
    {
        global $registry;

        switch ($app) {
        case 'turba':
            $sources = $registry->call('contacts/sources',
                                       array('writeable' => true));
            $fields = array();
            foreach (array_keys($sources) as $source) {
                $fields[$source] = array('__uid');
            }
            $result = $registry->call('contacts/search',
                                      array('names' => $uid,
                                            'sources' => array_keys($sources),
                                            'fields' => $fields));
            if (!isset($result[$uid])) {
                throw new VFS_Exception('No such contact!');
            }
            $list = Kolab_List::singleton();
            $share = $list->getByShare($result[$uid][0]['source'], 'contact');
            if ($share instanceof PEAR_Error) {
                throw new VFS_Exception($share->getMessage());
            }
            return $share->getData();
        }
    }

    /**
     */
    protected function _getAppUids($app)
    {
        global $registry;

        switch ($app) {
        case 'turba':
            $sources = $registry->call('contacts/sources',
                                       array('writeable' => true));
            $result = $registry->call('contacts/search',
                                      array('names' => '',
                                            'sources' => array_keys($sources),
                                            'fields' => array()));
            $uids = array();
            foreach ($result[''] as $contact) {
                if (isset($contact['__uid'])) {
                    $uids[] = $contact['__uid'];
                }
            }
            return $uids;
        }
    }

}
