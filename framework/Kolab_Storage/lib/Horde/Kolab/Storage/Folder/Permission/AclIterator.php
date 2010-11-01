<?php
/**
 * Maps Kolab_Storage ACL to the Horde permission system.
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
 * Maps Kolab_Storage ACL to the Horde permission system.
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
class Horde_Kolab_Storage_Folder_Permission_AclIterator implements IteratorAggregate
{
    /**
     * The ACL elements.
     *
     * @var array
     */
    private $_acl = array();

    /**
     * Constructor.
     *
     * @param array       $acl     The folder ACL as provided by the driver.
     * @param Horde_Group $groups  The group handler.
     * @param string      $creator The ID of the folder creator.
     */
    public function __construct(array $acl, Horde_Group $groups, $creator)
    {
        foreach ($acl as $user => $rights) {
            if ($user == $creator) {
                $this->_acl[] = new Horde_Kolab_Storage_Folder_Permission_Acl_Creator(
                    $rights
                );
            } else if (substr($user, 0, 6) == 'group:') {
                $this->_acl[] = new Horde_Kolab_Storage_Folder_Permission_Acl_Group(
                    $rights, substr($user, 6), $groups
                );
            } else if ($user == 'anyone' || $user == 'anonymous'){
                $class = 'Horde_Kolab_Storage_Folder_Permission_Acl_' . ucfirst($user);
                $this->_acl[] = new $class(
                    $rights
                );
            } else {
                $this->_acl[] = new Horde_Kolab_Storage_Folder_Permission_Acl_User(
                    $rights, $user
                );
            }
        }
    }

    public function getIterator()
    {
        return new ArrayIterator($this->_acl);
    }
}
