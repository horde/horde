<?php
/**
 * Maps a single Kolab_Storage user ACL element to the Horde permission system.
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
 * Maps a single Kolab_Storage user ACL element to the Horde permission system.
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
class Horde_Kolab_Storage_Folder_Permission_Acl_User
extends Horde_Kolab_Storage_Folder_Permission_Acl
{
    /**
     * The group id.
     *
     * @var string
     */
    private $_id;

    /**
     * Constructor.
     *
     * @param string $acl    The folder ACL element as provided by the driver.
     * @param string $id     The group id.
     */
    public function __construct($acl, $id)
    {
        $this->_id = $id;
        parent::__construct($acl);
    }

    /**
     * Convert the Acl string to a Horde_Perms:: mask and store it in the
     * provided data array.
     *
     * @param array &$data The horde permission data.
     *
     * @return NULL
     */
    public function toHorde(array &$data)
    {
        $data['users'][$this->_id] = $this->convertAclToMask();
    }
}
