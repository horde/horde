<?php
/**
 * @package Wicked
 */

/**
 * VFS
 */
require_once 'VFS.php';

define('WICKED_PAGE_MATCH_LEFT', 1);
define('WICKED_PAGE_MATCH_RIGHT', 2);
define('WICKED_PAGE_MATCH_ENDS', 3);
define('WICKED_PAGE_MATCH_ANY', 4);

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
class Wicked_Driver {

    /**
     * Hash containing connection parameters.
     *
     * @var array
     */
    var $_params = array();

    /**
     * VFS object for storing attachments.
     *
     * @var VFS
     */
    var $_vfs;

    /**
     * Constructs a new Wicked driver object.
     *
     * @param array $params  A hash containing connection parameters.
     */
    function Wicked_Driver($params = array())
    {
        $this->_params = $params;
    }

    /**
     * Accessor to manage a VFS instance.
     *
     * @throws VFS_Exception
     */
    function getVFS()
    {
        if (!$this->_vfs) {
            try {
                $this->_vfs = $GLOBALS['injector']->getInstance('Horde_Vfs')->getVfs();
            } catch (VFS_Exception $e) {
                return PEAR::raiseError($e->getMessage());
            }
        }

        return $this->_vfs;
    }

    /**
     * Retrieves the page of a particular name from the database.
     *
     * @param string $pagename     The name of the page to retrieve
     *
     * @return boolean  True on success, PEAR_Error on failure.
     */
    function retrieveByName($pagename)
    {
        return PEAR::raiseError(_("Unsupported"));
    }

    /**
     * Retrieves a historic version of a page.
     *
     * @abstract
     * @param string $pagename  The name of the page to retrieve.
     * @param string $version   The version to retrieve.
     *
     * @return array  The page hash, or PEAR_Error on failure.
     */
    function retrieveHistory($pagename, $version)
    {
        return PEAR::raiseError(_("Unsupported"));
    }

    /**
     * Logs a hit to $pagename.
     *
     * @param string $pagename  The page that was viewed.
     *
     * @return boolean  True or PEAR_Error on failure.
     */
    function logPageView($pagename)
    {
        return PEAR::raiseError(_("Unsupported"));
    }

    /**
     * Creates a new page.
     *
     * @abstract
     *
     * @param string $pagename  The new page's name.
     * @param string $text      The new page's text.
     *
     * @return mixed  True, or PEAR_Error on failure.
     */
    function newPage($pagename, $text)
    {
        return PEAR::raiseError(_("Not implemented."));
    }

    function updateText($pagename, $text, $changelog, $minorchange)
    {
        return PEAR::raiseError(_("Unsupported"));
    }

    function renamePage($pagename, $newname)
    {
        return PEAR::raiseError(_("Unsupported"));
    }

    function getPageId($pagename)
    {
        $pages = $this->getPages();
        if (is_a($pages, 'PEAR_Error')) {
            return $pages;
        }
        $ids = array_flip($pages);
        return isset($ids[$pagename]) ? $ids[$pagename] : false;
    }

    function getPage($pagename)
    {
        return array();
    }

    function getPageById($id)
    {
        return array();
    }

    function getSpecialPages()
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

    function getPages($special = true)
    {
        return array();
    }

    function pageExists($pagename)
    {
        return in_array($pagename, $this->getPages());
    }

    function getAllPages()
    {
        return PEAR::raiseError(_("Unsupported"));
    }

    function getHistory($pagename)
    {
        return PEAR::raiseError(_("Unsupported"));
    }

    /**
     * Returns the most recently changed pages.
     *
     * @param integer $days  The number of days to look back.
     *
     * @return mixed  An array of pages, or PEAR_Error on failure.
     */
    function getRecentChanges($days = 3)
    {
        return PEAR::raiseError(_("Unsupported"));
    }

    /**
     * Returns the most popular pages.
     *
     * @abstract
     *
     * @param integer $limit  The number of most popular pages to return.
     *
     * @return mixed  An array of pages, or PEAR_Error on failure.
     */
    function mostPopular($limit = 10)
    {
        return PEAR::raiseError(_("Unsupported"));
    }

    /**
     * Returns the least popular pages.
     *
     * @abstract
     *
     * @param integer $limit  The number of least popular pages to return.
     *
     * @return mixed  An array of pages, or PEAR_Error on failure.
     */
    function leastPopular($limit = 10)
    {
        return PEAR::raiseError(_("Unsupported"));
    }

    /**
     * Finds pages with matches in text or title.
     *
     * @abstract
     *
     * @param string $searchtext  The search expression (Google-like).
     *
     * @return array  A list of pages, or PEAR_Error on failure.
     */
    function searchText($searchtext)
    {
        return PEAR::raiseError(_("Unsupported"));
    }

    function getBackLinks($pagename)
    {
        return PEAR::raiseError(_("Unsupported"));
    }

    function getLikePages($pagename)
    {
        return PEAR::raiseError(_("Unsupported"));
    }

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
     * @return mixed  An array of key/value arrays describing the attached
     *                files or a PEAR_Error:: instance on failure.
     */
    function getAttachedFiles($pageId, $allversions = false)
    {
        return PEAR::raiseError(_("Unsupported"));
    }

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
     * @return boolean  True or PEAR_Error:: instance on failure.
     */
    function attachFile($file, $data)
    {
        $vfs =& $this->getVFS();
        if (is_a($vfs, 'PEAR_Error')) {
            return $vfs;
        }

        if (!isset($file['change_author'])) {
            $file['change_author'] = $GLOBALS['registry']->getAuth();
        }

        $result = $this->_attachFile($file);
        if (is_a($result, 'PEAR_Error')) {
            return $result;
        }

        /* We encode the path quoted printable so we won't get any nasty
         * characters the filesystem might reject. */
        $path = WICKED_VFS_ATTACH_PATH . '/' . $file['page_id'];
        try {
            $vfs->writeData($path, $file['attachment_name'] . ';' . $result, $data, true);
        } catch (VFS_Exception $e) {
            return PEAR::raiseError($e->getMessage());
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
     * @return boolean|PEAR_Error  Either true or a PEAR_Error describing failure.
     */
    function removeAttachment($pageId, $attachment, $version = null)
    {
        $vfs =& $this->getVFS();
        if (is_a($vfs, 'PEAR_Error')) {
            return $vfs;
        }

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
                    return PEAR::raiseError($result->getMessage());
                }
            }
        }

        return true;
    }

    /**
     * Removes all attachments to $pageId from the VFS backend.
     *
     * @param integer $pageId  The Id of the page to remove attachments from.
     *
     * @return boolean|PEAR_Error  Either true or a PEAR_Error describing failure.
     */
    function removeAllAttachments($pageId)
    {
        $vfs =& $this->getVFS();
        if (is_a($vfs, 'PEAR_Error')) {
            return $vfs;
        }

        if (!$vfs->isFolder(WICKED_VFS_ATTACH_PATH, $pageId)) {
            return true;
        }

        try {
            $vfs->deleteFolder(WICKED_VFS_ATTACH_PATH, $pageId, true);
            return true;
        } catch (VFS_Exception $e) {
            return PEAR::raiseError($e->getMessage());
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
     * @return boolean  The new version of the file attached, or a PEAR_Error::
     *                  instance on failure.
     */
    function _attachFile($file)
    {
        return PEAR::raiseError(_("Unsupported"));
    }

    /**
     * Retrieves the contents of an attachment.
     *
     * @param string $pageId    This is the name of the page to which the file
     *                          is attached.
     * @param string $filename  This is the name of the attachment.
     * @param string $version   This is the version of the attachment.
     *
     * @return string  The file's contents or a PEAR_Error on error.
     */
    function getAttachmentContents($pageId, $filename, $version)
    {
        $vfs =& $this->getVFS();
        if (is_a($vfs, 'PEAR_Error')) {
            return $vfs;
        }

        $path = WICKED_VFS_ATTACH_PATH . '/' . $pageId;

        try {
            return $vfs->read($path, $filename . ';' . $version);
        } catch (VFS_Exception $e) {
            return PEAR::raiseError($e->getMessage());
        }
    }

    function removeVersion($pagename, $version)
    {
        return PEAR::raiseError(_("Unsupported"));
    }

    function removeAllVersions($pagename)
    {
        /* When deleting a page, also delete all its attachments. */
        $result = $this->removeAllAttachments($this->getPageId($pagename));
        if (is_a($result, 'PEAR_Error')) {
            return $result;
        }

        return true;
    }

    function searchTitles($searchtext)
    {
        return PEAR::raiseError(_("Unsupported"));
    }

    /**
     * Returns the charset used by the backend.
     *
     * @return string  The backend's charset
     */
    function getCharset()
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
     * @return mixed  The newly created concrete Wicked_Driver instance, or
     *                false on an error.
     */
    function factory($driver = null, $params = null)
    {
        if ($driver === null) {
            $driver = $GLOBALS['conf']['storage']['driver'];
        }
        $driver = basename($driver);

        if ($params === null) {
            $params = Horde::getDriverConfig('storage', $driver);
        }

        $class = 'Wicked_Driver_' . $driver;
        if (!class_exists($class)) {
            include_once dirname(__FILE__) . '/Driver/' . $driver . '.php';
        }
        if (class_exists($class)) {
            $wicked = new $class($params);
            $result = $wicked->connect();
            if (is_a($result, 'PEAR_Error')) {
                return $result;
            }
            return $wicked;
        } else {
            return PEAR::raiseError('Definition of ' . $class . ' not found.');
        }

        return $wicked;
    }

}
