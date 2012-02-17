<?php
/**
 * The Horde_Kolab_Storage_Folder_Namespace_Element_Other:: class represents the
 * namespace for folders of other users.
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
 * The Horde_Kolab_Storage_Folder_Namespace_Element_Other:: class represents the
 * namespace for folders of other users.
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
class Horde_Kolab_Storage_Folder_Namespace_Element_Other
extends Horde_Kolab_Storage_Folder_Namespace_Element
{
    /**
     * Return the type of this namespace (personal, other, or shared).
     *
     * @return string The type.
     */
    public function getType()
    {
        return Horde_Kolab_Storage_Folder_Namespace::OTHER;
    }

    /**
     * Return the owner of a folder.
     *
     * @param string $name The name of the folder.
     *
     * @return string|boolean The owner of the folder.
     */
    public function getOwner($name)
    {
        $path = explode($this->_delimiter, $name);
        $user = $path[1];
        if (strpos($user, '@') === false) {
            $domain = strstr(array_pop($path), '@');
            if (!empty($domain)) {
                $user .= $domain;
            } else {
                $domain = strstr($this->_user, '@');
                if (!empty($domain)) {
                    $user .= $domain;
                }
            }
        }
        return $user;
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
        if (strpos($owner, '@') !== false) {
            $local = strstr($owner, '@', true);
        } else {
            $local = $owner;
        }
        $start = join(
            array($this->_name, $local, $subpath),
            $this->_delimiter
        );
        if (strstr($this->_user, '@') !== strstr($owner, '@')) {
            return $start . strstr($owner, '@');
        } else {
            return $start;
        }
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
        array_shift($path);
        return $path;
    }
}