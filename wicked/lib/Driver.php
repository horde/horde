<?php
/**
 * @package Wicked
 */

/**
 * Wicked_Driver:: defines an API for implementing storage backends for
 * Wicked.
 *
 * Copyright 2003-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Tyler Colbert <tyler@colberts.us>
 * @package Wicked
 */
abstract class Wicked_Driver {

    /**
     * Hash containing connection parameters.
     *
     * @var array
     */
    protected $_params = array();

    /**
     * VFS object for storing attachments.
     *
     * @var VFS
     */
    protected $_vfs;

    /**
     * Constructs a new Wicked driver object.
     *
     * @param array $params  A hash containing connection parameters.
     */
    public function __construct($params = array())
    {
        $this->_params = $params;
    }

    /**
     * Accessor to manage a VFS instance.
     *
     * @throws Wicked_Exception
     */
    public function getVFS()
    {
        if (!$this->_vfs) {
            try {
                $this->_vfs = $GLOBALS['injector']->getInstance('Horde_Core_Factory_Vfs')->create();
            } catch (VFS_Exception $e) {
                throw new Wicked_Exception($e);
            }
        }

        return $this->_vfs;
    }

    /**
     * Retrieves the page of a particular name from the database.
     *
     * @param string $pagename     The name of the page to retrieve
     *
     */
    abstract function retrieveByName($pagename);

    /**
     * Retrieves a historic version of a page.
     *
     * @abstract
     * @param string $pagename  The name of the page to retrieve.
     * @param string $version   The version to retrieve.
     *
     */
    abstract function retrieveHistory($pagename, $version);

    /**
     * Logs a hit to $pagename.
     *
     * @param string $pagename  The page that was viewed.
     */
    abstract function logPageView($pagename);

    /**
     * Creates a new page.
     *
     * @abstract
     *
     * @param string $pagename  The new page's name.
     * @param string $text      The new page's text.
     */
    abstract function newPage($pagename, $text);

    abstract function updateText($pagename, $text, $changelog, $minorchange);

    abstract function renamePage($pagename, $newname);

    public function getPageId($pagename)
    {
        $pages = $this->getPages();
        $ids = array_flip($pages);
        return isset($ids[$pagename]) ? $ids[$pagename] : false;
    }

    public function getPage($pagename)
    {
        return array();
    }

    public function getPageById($id)
    {
        return array();
    }

    public function getSpecialPages()
    {
        static $pages;
        if (isset($pages)) {
            return $pages;
        }

        $dh = opendir(WICKED_BASE . '/lib/Page');
        $pages = array();
        while (($dent = readdir($dh)) !== false) {
            if (!preg_match('/(.*)\.php$/', $dent, $matches)) {
                continue;
            }
            $pageName = $matches[1];
            if ($pageName == 'StandardPage') {
                continue;
            }
            $pages[$pageName] = $pageName;
        }
        closedir($dh);
        return $pages;
    }

    public function getPages($special = true)
    {
        return array();
    }

    public function pageExists($pagename)
    {
        return in_array($pagename, $this->getPages());
    }

    abstract function getAllPages();

    abstract function getHistory($pagename);

    /**
     * Returns the most recently changed pages.
     *
     * @param integer $days  The number of days to look back.
     *
     * @return array  Pages.
     */
    abstract function getRecentChanges($days = 3);

    /**
     * Returns the most popular pages.
     *
     * @abstract
     *
     * @param integer $limit  The number of most popular pages to return.
     *
     * @return array  Pages.
     */
    abstract function mostPopular($limit = 10);

    /**
     * Returns the least popular pages.
     *
     * @abstract
     *
     * @param integer $limit  The number of least popular pages to return.
     *
     * @return array  Pages.
     */
    abstract function leastPopular($limit = 10);

    /**
     * Finds pages with matches in text or title.
     *
     * @abstract
     *
     * @param string $searchtext  The search expression (Google-like).
     *
     * @return array  A list of pages
     */
    abstract function searchText($searchtext);

    abstract function getBackLinks($pagename);

    abstract function getLikePages($pagename);

    /**
     * Retrieves data on files attached to a page.
     *
     * @abstract
     *
     * @param string $pageId        This is the Id of the page for which we'd
     *                              like to find attached files.
     * @param boolean $allversions  Whether to include all versions. If false
     *                              or omitted, only the most recent version
     *                              of each attachment is returned.
     * @return array  An array of key/value arrays describing the attached
     *                files.
     */
    abstract function getAttachedFiles($pageId, $allversions = false);

    /**
     * Attaches a file to a page or update an attachment.
     *
     * @param array $file   This is a key/value array describing the
     *                      attachment:<pre>
     *   'page_id' =>          This is the id of the page to which we would
     *                         like to attach the file.
     *   'attachment_name' =>  This is the filename of the attachment.
     *   'minor' =>            This is a boolean which indicates whether this
     *                         is a minor version update.
     *   'change_log' =>       A change log entry for this attach or update
     *                         operation.  (Optional)
     *   'change_author' =>    The user uploading this file.  If not present,
     *                         the currently logged-in user is assumed.</pre>
     * @param string $data  This is the contents of the file to be attached.
     *
     * @throws Wicked_Exception
     */
    public function attachFile($file, $data)
    {
        $vfs = $this->getVFS();
        if (!isset($file['change_author'])) {
            $file['change_author'] = $GLOBALS['registry']->getAuth();
        }
        $result = $this->_attachFile($file);

        /* We encode the path quoted printable so we won't get any nasty
         * characters the filesystem might reject. */
        $path = WICKED_VFS_ATTACH_PATH . '/' . $file['page_id'];
        try {
            $vfs->writeData($path, $file['attachment_name'] . ';' . $result, $data, true);
        } catch (VFS_Exception $e) {
            throw new Wicked_Exception($e);
        }
    }

    /**
     * Remove a single version or all versions of an attachment to
     * $pageId from the VFS backend.
     *
     * @param integer $pageId  The Id of the page the file is attached to.
     * @param string $attachment  The name of the file.
     * @param string $version  If specified, the version to delete. If null,
     *                         then all versions of $attachment will be removed.
     *
     * @throws Wicked_Exception
     */
    public function removeAttachment($pageId, $attachment, $version = null)
    {
        $vfs = $this->getVFS();
        $path = WICKED_VFS_ATTACH_PATH . '/' . $pageId;

        $fileList = $this->getAttachedFiles($pageId, true);
        foreach ($fileList as $file) {
            $fileversion = $file['attachment_majorversion'] . '.' . $file['attachment_minorversion'];
            if ($file['attachment_name'] == $attachment &&
                (is_null($version) || $fileversion == $version)) {
                /* Skip any attachments that don't exist so they can
                 * be cleared out of the backend. */
                if (!$vfs->exists($path, $attachment . ';' . $fileversion)) {
                    continue;
                }
                try {
                    $vfs->deleteFile($path, $attachment . ';' . $fileversion);
                } catch (VFS_Exception $e) {
                    throw new Wicked_Exception($e);
                }
            }
        }
    }

    /**
     * Removes all attachments to $pageId from the VFS backend.
     *
     * @param integer $pageId  The Id of the page to remove attachments from.
     *
     * @throws Wicked_Exception
     */
    public function removeAllAttachments($pageId)
    {
        $vfs = $this->getVFS();
        if (!$vfs->isFolder(WICKED_VFS_ATTACH_PATH, $pageId)) {
            return;
        }

        try {
            $vfs->deleteFolder(WICKED_VFS_ATTACH_PATH, $pageId, true);
        } catch (VFS_Exception $e) {
            throw new Wicked_Exception($e);
        }
    }

    /**
     * Handles the driver-specific portion of attaching a file.
     *
     * Wicked_Driver::attachFile() calls down to this method for the driver-
     * specific portion, and then uses VFS to store the attachment.
     *
     * @abstract
     *
     * @access protected
     *
     * @param array $file  See Wicked_Driver::attachFile().
     *
     * @return boolean  The new version of the file attached
     * @throws Wicked_Exception
     */
    abstract protected function _attachFile($file);

    /**
     * Retrieves the contents of an attachment.
     *
     * @param string $pageId    This is the name of the page to which the file
     *                          is attached.
     * @param string $filename  This is the name of the attachment.
     * @param string $version   This is the version of the attachment.
     *
     * @return string  The file's contents.
     * @throws Wicked_Exception
     */
    public function getAttachmentContents($pageId, $filename, $version)
    {
        $vfs = $this->getVFS();
        $path = WICKED_VFS_ATTACH_PATH . '/' . $pageId;

        try {
            return $vfs->read($path, $filename . ';' . $version);
        } catch (VFS_Exception $e) {
            throw new Wicked_Exception($e);
        }
    }

    abstract function removeVersion($pagename, $version);

    public function removeAllVersions($pagename)
    {
        /* When deleting a page, also delete all its attachments. */
        $this->removeAllAttachments($this->getPageId($pagename));
    }

    abstract function searchTitles($searchtext);

    /**
     * Returns the charset used by the backend.
     *
     * @return string  The backend's charset
     */
    public function getCharset()
    {
        return 'UTF-8';
    }

    /**
     * Attempts to return a concrete Wicked_Driver instance based on $driver.
     *
     * @param string $driver  The type of the concrete Wicked_Driver subclass
     *                        to return.
     * @param array $params   A hash containing any additional configuration or
     *                        connection parameters a subclass might need.
     *
     * @return Wicked_Driver  The newly created concrete Wicked_Driver instance.
     * @throws Wicked_Exception
     */
    public function factory($driver = null, $params = null)
    {
        if ($driver === null) {
            $driver = $GLOBALS['conf']['storage']['driver'];
        }
        $driver = Horde_String::ucfirst(basename($driver));

        if ($params === null) {
            $params = Horde::getDriverConfig('storage', $driver);
        }

        $class = 'Wicked_Driver_' . $driver;
        if (!class_exists($class)) {
            throw new Wicked_Exception('Definition of ' . $class . ' not found.');
        }

        $wicked = new $class($params);
        $result = $wicked->connect();
        return $wicked;
    }

}
