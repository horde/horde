<?php
/**
 * A helper for folder data.
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
 * A helper for folder data.
 *
 * Copyright 2012 Horde LLC (http://www.horde.org/)
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
class Horde_Kolab_Storage_Folder_Data
{
    /**
     * Folder path.
     *
     * @var string
     */
    private $_path;

    /**
     * Folder type.
     *
     * @var Horde_Kolab_Storage_Folder_Type
     */
    private $_type;

    /**
     * Namespace handler
     *
     * @var Horde_Kolab_Storage_Folder_Namespace
     */
    private $_namespace;

    /**
     * Constructor.
     *
     * @param string $path The folder path.
     * @param Horde_Kolab_Storage_Folder_Type $type The folder type.
     * @param Horde_Kolab_Storage_Folder_Namespace $namespace The namespace hanlder.
     */
    public function __construct($path,
                                Horde_Kolab_Storage_Folder_Type $type,
                                Horde_Kolab_Storage_Folder_Namespace $namespace)
    {
        $this->_path = $path;
        $this->_type = $type;
        $this->_namespace = $namespace;
    }

    /**
     * Prepare an array representing the folder data.
     *
     * @return array The folder data.
     */
    public function toArray()
    {
        return array(
            'folder'    => $this->_path,
            'type'      => $this->_type->getType(),
            'default'   => $this->_type->isDefault(),
            'owner'     => $this->_namespace->getOwner($this->_path),
            'name'      => $this->_namespace->getTitle($this->_path),
            'subpath'   => $this->_namespace->getSubpath($this->_path),
            'parent'    => $this->_namespace->getParent($this->_path),
            'namespace' => $this->_namespace->matchNamespace($this->_path)->getType(),
            'prefix'    => $this->_namespace->matchNamespace($this->_path)->getName(),
            'delimiter' => $this->_namespace->matchNamespace($this->_path)->getDelimiter(),
        );
    }
}