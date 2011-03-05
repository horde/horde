<?php
/**
 * The Horde_Kolab_Storage_Folder_Namespace_Element_Shared:: class represents
 * the shared namespace and hides the prefix of that shared namespace.
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
 * The Horde_Kolab_Storage_Folder_Namespace_Element_Shared:: class represents
 * the shared namespace and hides the prefix of that shared namespace.
 *
 * Copyright 2010-2011 The Horde Project (http://www.horde.org/)
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
class Horde_Kolab_Storage_Folder_Namespace_Element_SharedWithPrefix
extends Horde_Kolab_Storage_Folder_Namespace_Element_Shared
{
    /**
     * The prefix to hide when referencing this namespace.
     *
     * @var string
     */
    protected $_prefix;

    /**
     * Constructor.
     *
     * @param string $name      The prefix identifying this namespace.
     * @param string $delimiter The delimiter used for this namespace.
     * @param string $user      The current user.
     * @param string $prefix    The prefix to hide.
     */
    public function __construct($name, $delimiter, $user, $prefix)
    {
        parent::__construct($name, $delimiter, $user);
        $this->_prefix = $prefix;
    }

    /**
     * Return an array describing the path elements of the folder.
     *
     * @param string $name The name of the folder.
     *
     * @return array The path elements.
     */
    protected function _subpath($name)
    {
        $path = parent::_subpath($name);
        if (!empty($path) && strpos($path[0], $this->_prefix) === 0) {
            $path[0] = substr($path[0], strlen($this->_prefix));
        }
        return $path;
    }

    /**
     * Generate a folder path for the given path in this namespace.
     *
     * @param array $path The path of the folder.
     *
     * @return string The name of the folder.
     */
    public function generateName($path)
    {
        return $this->_prefix . parent::generateName($path);
    }
}