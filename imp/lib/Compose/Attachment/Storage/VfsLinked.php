<?php
/**
 * Copyright 2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category  Horde
 * @copyright 2013 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   IMP
 */

/**
 * VFS implementation for attachment data storage that will be stored/served
 * from the VFS backend (i.e. linked attachment).
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2013 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   IMP
 */
class IMP_Compose_Attachment_Storage_VfsLinked extends IMP_Compose_Attachment_Storage_Vfs implements IMP_Compose_Attachment_Linked
{
    /* The name of the metadata file. */
    const METADATA_NAME = 'metadata';

    /* The virtual path to use for VFS data (permanent storage). */
    const VFS_LINK_ATTACH_PATH = '.horde/imp/attachments';

    /**
     * Cached metadata information.
     *
     * @var array
     */
    protected $_md;

    /**
     */
    public function __construct($user, $id = null)
    {
        parent::__construct($user, $id);

        $this->_vfspath = self::VFS_LINK_ATTACH_PATH . '/' . $this->_user;
    }

    /**
     */
    public function _get($name)
    {
        switch ($name) {
        case 'linked':
            return ($this->_vfspath == self::VFS_LINK_ATTACH_PATH);
        }
    }

    /**
     */
    public function write($filename, Horde_Mime_Part $part)
    {
        global $browser, $conf;

        if (filesize($filename) < intval($conf['compose']['link_attach_threshold'])) {
            $this->_vfspath = self::VFS_ATTACH_PATH;
            parent::write($filename);
            return;
        }

        parent::write($filename);

        // Prevent 'jar:' attacks on Firefox.  See Ticket #5892.
        $type = $part->getType();
        if ($browser->isBrowser('mozilla') &&
            in_array(Horde_String::lower($type), array('application/java-archive', 'application/x-jar'))) {
            $type = 'application/octet-stream';
        }

        $md = $this->getMetadata();
        $md->filename = $part->getName(true);
        $md->time = time();
        $md->type = $type;
        $this->saveMetadata($md);
    }

    /**
     */
    public function gc()
    {
        if (!($keep = IMP_Compose_LinkedAttachment::keepDate(true))) {
            return;
        }

        $changed = false;
        $this->_getMetadata();

        foreach ($this->_md as $key => $val) {
            $md = new IMP_Compose_Attachment_Linked_Metadata();
            $md->data = $val;

            if ($md->time < $keep) {
                try {
                    $this->_vfs->deleteFile($this->_vfspath, $key);
                } catch (Exception $e) {}
                unset($this->_md[$key]);
                $changed = true;
            }
        }

        if ($changed) {
            $this->_saveMetadata();
        }
    }

    /**
     */
    public function getMetadata()
    {
        $this->_getMetadata();

        $md = new IMP_Compose_Attachment_Linked_Metadata();
        if (isset($this->_id) && isset($this->_md[$this->_id])) {
            $md->data = $this->_md[$this->_id];
        }

        return $md;
    }

    /**
     * Load metadata into cache.
     */
    protected function _getMetadata()
    {
        if (!isset($this->_md)) {
            try {
                $this->_md = json_decode($this->_vfs->read($this->_vfspath, self::METADATA_NAME), true);
            } catch (Horde_Vfs_Exception $e) {}

            if (!is_array($this->_md)) {
                $this->_md = array();
            }
        }
    }

    /**
     */
    public function saveMetadata($md = null)
    {
        if (!isset($this->_id)) {
            return;
        }

        $this->_getMetadata();

        if (is_null($md)) {
            unset($this->_md[$this->_id]);
        } else {
            $this->_md[$this->_id] = $md->data;
        }

        $this->_saveMetadata();
    }

    /**
     * Saves metadata.
     */
    protected function _saveMetadata()
    {
        if (empty($this->_md)) {
            $this->_vfs->deleteFile($this->_vfspath, self::METADATA_NAME);
        } else {
            $this->_vfs->writeData($this->_vfspath, self::METADATA_NAME, json_encode($this->_md), true);
        }
    }

}
