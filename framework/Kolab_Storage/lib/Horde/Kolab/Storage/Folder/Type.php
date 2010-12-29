<?php
/**
 * Handles folder types.
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_Storage
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Storage
 */

/**
 * Handles folder types.
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category Kolab
 * @package  Kolab_Storage
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Storage
 */
class Horde_Kolab_Storage_Folder_Type
{
    /**
     * Folder type.
     *
     * @var string
     */
    private $_type;

    /**
     * Default folder?
     *
     * @var boolean
     */
    private $_default;

    /**
     * Constructor.
     *
     * @param string $annotation The folder type annotation value.
     */
    public function __construct($annotation)
    {
        $elements = explode('.', $annotation);
        $this->_type = $elements[0];
        $this->_default = isset($elements[1]) && $elements[1] == 'default';
    }

    /**
     * Return the folder type.
     *
     * @return string The folder type.
     */
    public function getType()
    {
        return $this->_type;
    }

    /**
     * Indicates if this is a default folder.
     *
     * @return boolean True if it is a default folder.
     */
    public function isDefault()
    {
        return $this->_default;
    }
}