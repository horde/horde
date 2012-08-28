<?php
/**
 * Factory for folder types.
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
 * Factory for folder types.
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
class Horde_Kolab_Storage_Folder_Types
{
    /**
     * Folder types that have been created so far.
     *
     * @var array
     */
    private $_types;

    /**
     * Create a folder type handler.
     *
     * @param string $annotation The folder type annotation value.
     *
     * @return Horde_Kolab_Storage_Folder_Type The folder type handler.
     */
    public function create($annotation)
    {
        if (!isset($this->_types[$annotation])) {
            $this->_types[$annotation] = new Horde_Kolab_Storage_Folder_Type($annotation);
        }
        return $this->_types[$annotation];
    }
}