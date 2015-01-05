<?php
/**
 * Copyright 2013-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2013-2015 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */

/**
 * Temporary data storage implementation for attachment data storage.
 *
 * Stores data using Horde_VFS to ensure that data is persistent for the
 * session. Compose data will be garbage collected at the end of a session
 * (if a user logs out properly).
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2013-2015 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
class IMP_Compose_Attachment_Storage_Temp
extends IMP_Compose_Attachment_Storage
{
    /**
     * The VFS HashTable object.
     *
     * @var Horde_HashTable_Vfs
     */
    protected $_ht;

    /**
     */
    public function __construct($user, $id = null)
    {
        parent::__construct($user, $id);

        $this->_ht = new Horde_Core_HashTable_PersistentSession();
    }

    /**
     */
    protected function _read()
    {
        try {
            return $this->_ht->getStream($this->_id);
        } catch (Exception $e) {
            throw new IMP_Compose_Exception($e);
        }
    }

    /**
     */
    protected function _write($filename, Horde_Mime_Part $part)
    {
        if (!$this->_ht->set($this->_id, $filename, array('filename' => true))) {
            throw new IMP_Compose_Exception('Could not save attachment data.');
        }
    }

    /**
     */
    public function delete()
    {
        $this->_ht->delete($this->_id);
    }

    /**
     */
    public function exists()
    {
        return $this->_ht->exists($this->_id);
    }

}
