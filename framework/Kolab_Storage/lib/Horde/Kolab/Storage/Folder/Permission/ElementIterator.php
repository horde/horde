<?php
/**
 * Maps Horde permission elements into Kolab_Storage ACL.
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
 * Maps Horde permission elements into Kolab_Storage ACL.
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
class Horde_Kolab_Storage_Folder_Permission_ElementIterator implements IteratorAggregate
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
     * @param array       $permissions The folder permissions as provided by Horde.
     * @param Horde_Group $groups      The group handler.
     * @param string      $creator     The ID of the folder creator.
     */
    public function __construct(array $permissions, Horde_Group $groups, $creator)
    {
        foreach ($permissions as $user => $user_perms) {
            if ($user == 'default') {
                $this->_elements[] = new Horde_Kolab_Storage_Folder_Permission_Element_Default(
                    $user_perms
                );
            } else if ($user == 'guest') {
                $this->_elements[] = new Horde_Kolab_Storage_Folder_Permission_Element_Guest(
                    $user_perms
                );
            } else if ($user == 'creator') {
                $this->_elements[] = new Horde_Kolab_Storage_Folder_Permission_Element_Creator(
                    $user_perms, $creator
                );
            } else if ($user == 'groups') {
                foreach ($user_perms as $user_entry => $perms) {
                    $this->_elements[] = new Horde_Kolab_Storage_Folder_Permission_Element_Group(
                        $perms, $user_entry, $groups
                    );
                }
            } else if ($user == 'users') {
                foreach ($user_perms as $user_entry => $perms) {
                    $this->_elements[] = new Horde_Kolab_Storage_Folder_Permission_Element_User(
                        $perms, $user_entry
                    );
                }
            }
        }
    }

    public function getIterator()
    {
        return new ArrayIterator($this->_elements);
    }
}
