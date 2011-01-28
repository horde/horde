<?php
/**
 * Maps a single Kolab_Storage group ACL element to the Horde permission system.
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
 * Maps a single Kolab_Storage group ACL element to the Horde permission system.
 *
 * Copyright 2006-2011 The Horde Project (http://www.horde.org/)
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
class Horde_Kolab_Storage_Folder_Permission_Acl_Group
extends Horde_Kolab_Storage_Folder_Permission_Acl
{
    /**
     * The group id.
     *
     * @var string
     */
    private $_id;

    /**
     * The group handler.
     *
     * @var Group
     */
    private $_groups;

    /**
     * Constructor.
     *
     * @param string $acl          The folder ACL element as provided by the driver.
     * @param string $id           The group id.
     * @param Horde_Group $groups  The horde group handler.
     */
    public function __construct($acl, $id, Horde_Group $groups)
    {
        $this->_id     = $id;
        $this->_groups = $groups;
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
        $data['groups'][$this->_groups->getGroupId($this->_id)] = $this->convertAclToMask();
    }
}
