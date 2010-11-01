<?php
/**
 * Maps a single Kolab_Storage ACL element to the Horde permission system.
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
 * Maps a single Kolab_Storage ACL element to the Horde permission system.
 *
 * Copyright 2006-2010 The Horde Project (http://www.horde.org/)
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
abstract class Horde_Kolab_Storage_Folder_Permission_Acl
{
    /**
     * The ACL.
     *
     * @var string
     */
    private $_acl;

    /**
     * Constructor.
     *
     * @param string $acl The folder ACL element as provided by the driver.
     */
    public function __construct($acl)
    {
        $this->_acl = $acl;
    }

    /**
     * Convert the Acl string to a Horde_Perms:: mask and store it in the
     * provided data array.
     *
     * @param array &$data The horde permission data.
     *
     * @return NULL
     */
    abstract public function toHorde(array &$data);

    /**
     * Convert the Acl string to a Horde_Perms:: mask.
     *
     * @return int The permission mask
     */
    protected function convertAclToMask()
    {
        $result = 0;
        if (strpos($this->_acl, 'l') !== false) {
            $result |= Horde_Perms::SHOW;
        }
        if (strpos($this->_acl, 'r') !== false) {
            $result |= Horde_Perms::READ;
        }
        if (strpos($this->_acl, 'i') !== false) {
            $result |= Horde_Perms::EDIT;
        }
        if (strpos($this->_acl, 'd') !== false) {
            $result |= Horde_Perms::DELETE;
        }
        return $result;
    }
}
