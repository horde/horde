<?php
/**
 * Copyright 2013-2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2013-2014 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */

/**
 * VFS implementation for attachment data storage.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2013-2014 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
class IMP_Compose_Attachment_Storage_Vfs extends IMP_Compose_Attachment_Storage
{
    /* The virtual path to use for VFS data (temporary storage). */
    const VFS_ATTACH_PATH = '.horde/imp/compose';

    /**
     * The VFS object.
     *
     * @var Horde_Vfs_Base
     */
    protected $_vfs;

    /**
     * The virtual path to use for VFS data.
     *
     * @var string
     */
    protected $_vfspath = self::VFS_ATTACH_PATH;

    /**
     */
    public function __construct($user, $id = null)
    {
        global $conf, $injector;

        parent::__construct($user, $id);

        $this->_vfs = $conf['compose']['use_vfs']
            ? $injector->getInstance('Horde_Core_Factory_Vfs')->create()
            : new Horde_Vfs_File(array('vfsroot' => Horde::getTempDir()));
    }

    /**
     */
    public function read()
    {
        try {
            if (method_exists($this->_vfs, 'readStream')) {
                $stream = new Horde_Stream_Existing(array(
                    'stream' => $this->_vfs->readStream($this->_vfspath, $this->_id)
                ));
                $stream->rewind();
            } else {
                $stream = new Horde_Stream_Temp();
                $stream->add($this->_vfs->read($this->_vfspath, $this->_id), true);
            }
            return $stream;
        } catch (Horde_Vfs_Exception $e) {
            throw new IMP_Compose_Exception($e);
        }
    }

    /**
     */
    public function write($filename, Horde_Mime_Part $part)
    {
        try {
            $this->_vfs->write($this->_vfspath, $this->_id, $filename, true);
        } catch (Horde_Vfs_Exception $e) {
            throw new IMP_Compose_Exception($e);
        }
    }

    /**
     */
    public function delete()
    {
        $this->_vfs->deleteFile($this->_vfspath, $this->_id);
    }

    /**
     */
    public function exists()
    {
        return $this->_vfs->exists($this->_vfspath, $this->_id);
    }

    /**
     */
    public function gc()
    {
        Horde_Vfs_Gc::gc($this->_vfs, $this->_vfspath, 86400);
    }

}
