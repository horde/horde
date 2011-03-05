<?php
/**
 * Maps Kolab_Storage ACL to the Horde permission system.
 *
 * PHP version 5
 *
 * @category Horde
 * @package  Perms
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Perms
 */

/**
 * Maps Kolab_Storage ACL to the Horde permission system.
 *
 * Copyright 2006-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category Horde
 * @package  Perms
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Perms
 */
class Horde_Perms_Permission_Kolab_AclIterator
implements IteratorAggregate
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
     * @param array $acl                The folder ACL as provided by the
     *                                  driver.
     * @param Horde_Group_Base $groups  The group handler.
     * @param string $creator           The ID of the folder creator.
     */
    public function __construct(array $acl, Horde_Group_Base $groups, $creator)
    {
        foreach ($acl as $user => $rights) {
            if ($user == $creator) {
                $this->_acl[] = new Horde_Perms_Permission_Kolab_Acl_Creator(
                    $rights
                );
            } else if (substr($user, 0, 6) == 'group:') {
                $this->_acl[] = new Horde_Perms_Permission_Kolab_Acl_Group(
                    $rights, substr($user, 6), $groups
                );
            } else if ($user == 'anyone' || $user == 'anonymous'){
                $class = 'Horde_Perms_Permission_Kolab_Acl_' . ucfirst($user);
                $this->_acl[] = new $class(
                    $rights
                );
            } else {
                $this->_acl[] = new Horde_Perms_Permission_Kolab_Acl_User(
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
