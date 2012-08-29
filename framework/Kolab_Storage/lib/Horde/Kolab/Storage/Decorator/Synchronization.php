<?php
/**
 * A synchronization decorator for the Kolab storage handler.
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_Storage
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Kolab_Storage
 */

/**
 * A synchronization decorator for the Kolab storage handler.
 *
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category Kolab
 * @package  Kolab_Storage
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Kolab_Storage
 */
class Horde_Kolab_Storage_Decorator_Synchronization
implements Horde_Kolab_Storage
{
    /**
     * The decorated storage handler.
     *
     * @var Horde_Kolab_Storage
     */
    private $_storage;

    /**
     * The synchronization strategy
     *
     * @var Horde_Kolab_Storage_Synchronization
     */
    private $_synchronization;

    /**
     * Constructor.
     *
     * @param Horde_Kolab_Storage                 $storage The storage handler.
     * @param Horde_Kolab_Storage_Synchronization $synchronization The synchronization strategy.
     */
    public function __construct(Horde_Kolab_Storage $storage,
                                Horde_Kolab_Storage_Synchronization $synchronization)
    {
        $this->_storage = $storage;
        $this->_synchronization = $synchronization;
    }

    /**
     * Get the folder list object.
     *
     * @return Horde_Kolab_Storage_List The handler for the list of folders
     *                                  present in the Kolab backend.
     */
    public function getList()
    {
        $list = $this->_storage->getList();
        $this->_synchronization->synchronizeList($list);
        return $list;
    }

    /**
     * Get a folder list object for a "system" user.
     *
     * @param string $type The type of system user.
     *
     * @return Horde_Kolab_Storage_List The handler for the list of folders
     *                                  present in the Kolab backend.
     */
    public function getSystemList($type)
    {
        $list = $this->_storage->getSystemList($type);
        $this->_synchronization->synchronizeList($list);
        return $list;
    }

    /**
     * Get a Folder object.
     *
     * @param string $folder The folder name.
     *
     * @return Horde_Kolab_Storage_Folder The Kolab folder object.
     */
    public function getFolder($folder)
    {
        return $this->_storage->getFolder($folder);
    }

    /**
     * Return a data handler for accessing data in the specified
     * folder.
     *
     * @param string $folder       The name of the folder.
     * @param string $object_type  The type of data we want to
     *                             access in the folder.
     * @param int    $data_version Format version of the object data.
     *
     * @return Horde_Kolab_Storage_Data The data object.
     */
    public function getData($folder, $object_type = null, $data_version = 1)
    {
        $data = $this->_storage->getData($folder, $object_type, $data_version);
        $this->_synchronization->synchronizeData($data);
        return $data;
    }
}