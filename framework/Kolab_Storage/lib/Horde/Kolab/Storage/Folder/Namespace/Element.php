<?php
/**
 * The Horde_Kolab_Storage_Folder_Namespace_Element:: class represents a namespace type.
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
 * The Horde_Kolab_Storage_Folder_Namespace_Element:: class represents a namespace type.
 *
 * Copyright 2010-2012 Horde LLC (http://www.horde.org/)
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
abstract class Horde_Kolab_Storage_Folder_Namespace_Element
{
    /**
     * The prefix identifying this namespace.
     *
     * @var string
     */
    protected $_name;

    /**
     * The delimiter used for this namespace.
     *
     * @var string
     */
    protected $_delimiter;

    /**
     * The current user.
     *
     * @var string
     */
    protected $_user;

    /**
     * Constructor.
     *
     * @param string $name      The prefix identifying this namespace.
     * @param string $delimiter The delimiter used for this namespace.
     * @param string $user      The current user.
     */
    public function __construct($name, $delimiter, $user)
    {
        if (substr($name, -1) == $delimiter) {
            $name = substr($name, 0, -1);
        }
        $this->_name = $name;
        $this->_delimiter = $delimiter;
        $this->_user = $user;
    }

    /**
     * Return the type of this namespace (personal, other, or shared).
     *
     * @return string The type.
     */
    abstract public function getType();

    /**
     * Return the name of this namespace.
     *
     * @return string The name/prefix.
     */
    public function getName()
    {
        return $this->_name;
    }

    /**
     * Return the delimiter for this namespace.
     *
     * @return string The delimiter.
     */
    public function getDelimiter()
    {
        return $this->_delimiter;
    }

    /**
     * Does the folder name lie in this namespace?
     *
     * @param string $name The name of the folder.
     *
     * @return boolean True if the folder is element of this namespace.
     */
    public function matches($name)
    {
        return (strpos($name, $this->_name) === 0);
    }

    /**
     * Return the owner of a folder.
     *
     * @param string $name The name of the folder.
     *
     * @return string|boolean The owner of the folder.
     */
    abstract public function getOwner($name);

    /**
     * Return the title of a folder.
     *
     * @param string $name The name of the folder.
     *
     * @return string The title of the folder.
     */
    public function getTitle($name)
    {
        $subpath = $this->_subpath($name);
        if (!empty($subpath)) {
            return array_pop($subpath);
        } else {
            return '';
        }
    }

    /**
     * Get the sub path for the given folder name.
     *
     * @param string $name The folder name.
     *
     * @return string The sub path.
     */
    public function getSubpath($name)
    {
        return join($this->_subpath($name), $this->_delimiter);
    }

    /**
     * Get the parent for the given folder name.
     *
     * @param string $name The parent folder name.
     *
     * @return string The parent.
     */
    public function getParent($name)
    {
        $path = explode($this->_delimiter, $name);
        array_pop($path);
        return join($path, $this->_delimiter);
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
        $path = explode($this->_delimiter, $name);
        if ($path[0] == $this->_name) {
            array_shift($path);
        }
        //@todo: What about the potential trailing domain?
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
        if (!empty($this->_name)) {
            array_unshift($path, $this->_name);
        }
        return join($path, $this->_delimiter);
    }

    /**
     * Generate a folder path for the given subpath and owner.
     *
     * @since Horde_Kolab_Storage 1.1.0
     *
     * @param string $subpath The subpath of the folder.
     * @param string $owner   The folder owner.
     *
     * @return string The name of the folder.
     */
    public function generatePath($subpath, $owner)
    {
        return empty($this->_name) ?  $subpath : $this->_name . $this->_delimiter . $subpath;
    }

    /**
     * Convert the namespace description to a string.
     *
     * @return string The namespace description.
     */
    public function __toString()
    {
        return '"' . $this->_name . '" (' . $this->getType() . ', "' . $this->_delimiter . '")';
    }
}