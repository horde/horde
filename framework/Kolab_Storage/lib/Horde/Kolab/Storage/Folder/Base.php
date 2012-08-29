<?php
/**
 * The Kolab_Folder class represents an single folder in the Kolab
 * backend.
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
 * The Kolab_Folder class represents an single folder in the Kolab
 * backend.
 *
 * Copyright 2004-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author  Stuart Binge <omicron@mighty.co.za>
 * @author  Gunnar Wrobel <wrobel@pardus.de>
 * @author  Thomas Jarosch <thomas.jarosch@intra2net.com>
 * @package Kolab_Storage
 */
class Horde_Kolab_Storage_Folder_Base
implements Horde_Kolab_Storage_Folder
{
    /**
     * The handler for the list of folders.
     *
     * @var Horde_Kolab_Storage_List
     */
    private $_list;

    /**
     * The folder path.
     *
     * @var string
     */
    private $_path;

    /**
     * Additional folder information.
     *
     * @var array
     */
    private $_data;

    /**
     * Constructor.
     *
     * @param Horde_Kolab_Storage_List_Query_List $list The query handler for
     *                                       the list of folders.
     * @param string                   $path Path of the folder.
     */
    public function __construct(Horde_Kolab_Storage_List_Query_List $list, $path)
    {
        $this->_list = $list;
        $this->_path = $path;
    }

    /**
     * Fetch the data array.
     *
     * @return NULL
     */
    private function _init()
    {
        if ($this->_data === null) {
            $this->_data = $this->_list->folderData($this->_path);
        }
    }

    /**
     * Fetch a data value.
     *
     * @param string $key The name of the data value to fetch.
     *
     * @return mixed The data value
     */
    public function get($key)
    {
        $this->_init();
        if (isset($this->_data[$key])) {
            return $this->_data[$key];
        }
        throw new Horde_Kolab_Storage_Exception(
            sprintf('No "%s" information available!', $key)
        );
    }

    /**
     * Fetch a data value and accept a missing value (represented by the return value NULL).
     *
     * @param string $key The name of the data value to fetch.
     *
     * @return mixed The data value
     */
    public function getWithNull($key)
    {
        $this->_init();
        if (isset($this->_data[$key])) {
            return $this->_data[$key];
        }
    }

    /**
     * Return the storage path of the folder.
     *
     * @return string The storage path of the folder.
     */
    public function getPath()
    {
        return $this->_path;
    }

    /**
     * Return the namespace type of the folder.
     *
     * @return string The namespace type of the folder.
     */
    public function getNamespace()
    {
        return $this->get('namespace');
    }

    /**
     * Return the namespace prefix of the folder.
     *
     * @since Horde_Kolab_Storage 1.1.0
     *
     * @return string The namespace prefix of the folder.
     */
    public function getPrefix()
    {
        return $this->get('prefix');
    }

    /**
     * Returns a readable title for this folder.
     *
     * @return string  The folder title.
     */
    public function getTitle()
    {
        return $this->get('name');
    }

    /**
     * Returns the owner of the folder.
     *
     * @return string The owner of this folder.
     */
    public function getOwner()
    {
        return $this->getWithNull('owner');
    }

    /**
     * Returns the folder path without namespace components.
     *
     * @return string The subpath of this folder.
     */
    public function getSubpath()
    {
        return $this->get('subpath');
    }

    /**
     * Returns the folder parent.
     *
     * @return string The parent of this folder.
     */
    public function getParent()
    {
        return $this->get('parent');
    }

    /**
     * Is this a default folder?
     *
     * @return boolean Boolean that indicates the default status.
     */
    public function isDefault()
    {
        return $this->get('default');
    }

    /**
     * The type of this folder.
     *
     * @return string The folder type.
     */
    public function getType()
    {
        return $this->get('type');
    }
}
