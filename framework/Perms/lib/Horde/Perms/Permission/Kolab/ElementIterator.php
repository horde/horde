<?php
/**
 * Maps Horde permission elements into Kolab_Storage ACL.
 *
 * PHP version 5
 *
 * @category Horde
 * @package  Perms
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Perms
 */

/**
 * Maps Horde permission elements into Kolab_Storage ACL.
 *
 * Copyright 2006-2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category Horde
 * @package  Perms
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Perms
 */
class Horde_Perms_Permission_Kolab_ElementIterator
implements IteratorAggregate
{
    /**
     * The Horde permission elements.
     *
     * @var array
     */
    private $_elements = array();

    /**
     * Constructor.
     *
     * @param array $permissions        The folder permissions as provided by
     *                                  Horde.
     * @param Horde_Group_Base $groups  The group handler.
     */
    public function __construct(array $permissions, Horde_Group_Base $groups)
    {
        foreach ($permissions as $user => $user_perms) {
            switch ($user) {
            case 'default':
                $this->_elements[] = new Horde_Perms_Permission_Kolab_Element_Default(
                    $user_perms
                );
                break;
            case 'guest':
                $this->_elements[] = new Horde_Perms_Permission_Kolab_Element_Guest(
                    $user_perms
                );
                break;
            case 'groups':
                foreach ($user_perms as $user_entry => $perms) {
                    $this->_elements[] = new Horde_Perms_Permission_Kolab_Element_Group(
                        $perms, $user_entry, $groups
                    );
                }
                break;
            case 'users':
                foreach ($user_perms as $user_entry => $perms) {
                    $this->_elements[] = new Horde_Perms_Permission_Kolab_Element_User(
                        $perms, $user_entry
                    );
                }
                break;
            }
        }
    }

    public function getIterator()
    {
        return new ArrayIterator($this->_elements);
    }
}
