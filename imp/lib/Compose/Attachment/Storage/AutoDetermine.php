<?php
/**
 * Copyright 2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2014 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */

/**
 * Auto-determine attachment storage status based on IMP configuration and
 * attachment data.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2014 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
class IMP_Compose_Attachment_Storage_AutoDetermine
extends IMP_Compose_Attachment_Storage
{
    /**
     * The underlying storage driver.
     *
     * @var IMP_Compose_Attachment_Storage
     */
    protected $_storage;

    /**
     */
    public function __construct($user, $id = null)
    {
        global $injector;

        parent::__construct($user, $id);

        /* Default to linked storage. */
        $factory = $injector->getInstance('IMP_Factory_ComposeAtc');
        $this->_storage = new $factory->classLinked($this->_user, $this->_id);
    }

    /**
     */
    public function __get($name)
    {
        switch ($name) {
        case 'linked':
            return ($this->_storage instanceof IMP_Compose_Attachment_Linked);
        }

        return parent::__get($name);
    }

    /**
     */
    public function read()
    {
        return $this->_storage->read();
    }

    /**
     */
    protected function _read()
    {
    }

    /**
     */
    public function write($filename, Horde_Mime_Part $part)
    {
        global $conf, $injector;

        if (filesize($filename) < intval($conf['compose']['link_attach_threshol'])) {
            $factory = $injector->getInstance('IMP_Factory_ComposeAtc');
            $this->_storage = new $factory->classAtc($this->_user, $this->_id);
        }

        $this->_storage->write($filename, $part);
    }

    /**
     */
    protected function _write($filename, Horde_Mime_Part $part)
    {
    }

    /**
     */
    public function delete()
    {
        $this->_storage->delete();
    }

    /**
     */
    public function exists()
    {
        return $this->_storage->exists();
    }

    /**
     */
    public function getMetadata()
    {
        return $this->_storage->getMetadata();
    }

    /**
     */
    public function saveMetadata($md = null)
    {
        return $this->_storage->saveMetadata($md);
    }

    /**
     */
    public function gc()
    {
        $this->_storage->gc();
    }

}
