<?php
/**
 * Copyright 2008-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Duck <duck@obala.net>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @package  Group
 */
class Horde_Group_Mock extends Horde_Group {

    /**
     * Constructor.
     */
    public function __construct()
    {
    }

    /**
     * Initializes the object.
     */
    public function __wakeup()
    {
    }

    /**
     * Returns a new group object.
     *
     * @param string $name    The group's name.
     * @param string $parent  The group's parent's name.
     *
     * @return DataTreeObject_Group  A new group object.
     * @throws Horde_Group_Exception
     */
    public function newGroup($name, $parent = GROUP_ROOT)
    {
        throw new Horde_Group_Exception('Unsupported.');
    }

    /**
     * Returns a DataTreeObject_Group object corresponding to the named group,
     * with the users and other data retrieved appropriately.
     *
     * @param string $name The name of the group to retrieve.
     *
     * @throws Horde_Group_Exception
     */
    public function getGroup($name)
    {
        throw new Horde_Group_Exception('Unsupported.');
    }

    /**
     * Returns a group object corresponding to the given unique
     * ID, with the users and other data retrieved appropriately.
     *
     * @param integer $cid  The unique ID of the group to retrieve.
     *
     * @throws Horde_Group_Exception
     */
    public function getGroupById($cid)
    {
        throw new Horde_Group_Exception('Unsupported.');
    }

    /**
     * Adds a group to the groups system. The group must first be created with
     * newGroup(), and have any initial users added to it, before this
     * function is called.
     *
     * @param Horde_Group_DataTreeObject $group  The new group object.
     *
     * @throws Horde_Group_Exception
     */
    public function addGroup(Horde_Group_DataTreeObject $group)
    {
        throw new Horde_Group_Exception('Unsupported.');
    }

    /**
     * Stores updated data - users, etc. - of a group to the backend system.
     *
     * @param Horde_Group_DataTreeObject $group  The group to update.
     *
     * @throws Horde_Group_Exception
     */
    public function updateGroup(Horde_Group_DataTreeObject $group)
    {
        throw new Horde_Group_Exception('Unsupported.');
    }

    /**
     * Removes a group from the groups system permanently.
     *
     * @param Horde_Group_DataTreeObject $group  The group to remove.
     * @param boolean $force               Force to remove every child.
     *
     * @throws Horde_Group_Exception
     */
    public function removeGroup(Horde_Group_DataTreeObject $group,
                                $force = false)
    {
        throw new Horde_Group_Exception('Unsupported.');
    }

    /**
     * Retrieves the name of a group.
     *
     * @param integer|Horde_Group_DataTreeObject $gid  The id of the group or the
     *                                           group object to retrieve the
     *                                           name for.
     *
     * @return string  The group's name.
     */
    public function getGroupName($gid)
    {
        return '';
    }

    /**
     * Strips all parent references off of the given group name.
     *
     * @param string $group  Name of the group.
     *
     * @return The name of the group without parents.
     */
    public function getGroupShortName($group)
    {
        return '';
    }

    /**
     * Retrieves the ID of a group.
     *
     * @param string|Horde_Group_DataTreeObject $group  The group name or object to
     *                                            retrieve the ID for.
     *
     * @return integer  The group's ID.
     */
    public function getGroupId($group)
    {
        return '';
    }

    /**
     * Check if a group exists in the system.
     *
     * @param string $group  The group to check.
     *
     * @return boolean  True if the group exists, false otherwise.
     */
    public function exists($group)
    {
        return false;
    }

    /**
     * Returns a tree of the parents of a child group.
     *
     * @param integer $gid  The id of the child group.
     *
     * @return array  The group parents tree, with groupnames as the keys.
     */
    public function getGroupParents($gid)
    {
        return array();
    }

    /**
     * Returns the single parent ID of the given group.
     *
     * @param integer $gid  The DataTree ID of the child group.
     *
     * @return integer  The parent of the given group.
     */
    public function getGroupParent($gid)
    {
        return null;
    }

    /**
     * Returns a flat list of the parents of a child group
     *
     * @param integer $gid  The id of the group.
     *
     * @return array  A flat list of all of the parents of $group, hashed in
     *                $id => $name format.
     */
    public function getGroupParentList($gid)
    {
        return array();
    }

    /**
     * Returns a list of all groups, in the format id => groupname.
     *
     * @param boolean $refresh  If true, the cached value is ignored and the
     *                          group list is refreshed from the group backend.
     *
     * @return array  ID => groupname hash.
     */
    public function listGroups($refresh = false)
    {
        return array();
    }

    /**
     * Get a list of every user that is a part of this group ONLY.
     *
     * @param integer $gid  The ID of the group.
     *
     * @return array  The user list.
     */
    public function listUsers($gid)
    {
        return array();
    }

    /**
     * Get a list of every user that is part of the specified group
     * and any of its subgroups.
     *
     * @param integer $group  The ID of the parent group.
     *
     * @return array  The complete user list.
     */
    public function listAllUsers($gid)
    {
        return array();
    }

    /**
     * Get a list of every group that $user is in.
     *
     * @param string  $user          The user to get groups for.
     * @param boolean $parentGroups  Also return the parents of any groups?
     *
     * @return array  An array of all groups the user is in.
     */
    public function getGroupMemberships($user, $parentGroups = false)
    {
        return array();
    }

    /**
     * Say if a user is a member of a group or not.
     *
     * @param string $user        The name of the user.
     * @param integer $gid        The ID of the group.
     * @param boolean $subgroups  Return true if the user is in any subgroups
     *                            of group with ID $gid, also.
     *
     * @return boolean
     */
    public function userIsInGroup($user, $gid, $subgroups = true)
    {
        return false;
    }

    /**
     * Returns the nesting level of the given group. 0 is returned for any
     * object directly below GROUP_ROOT.
     *
     * @param integer $gid  The ID of the group.
     *
     * @return The nesting level of the group.
     */
    public function getLevel($gid)
    {
        return 0;
    }

    /**
     * Stores the object in the session cache.
     */
    public function shutdown()
    {
    }

    /**
     * Returns the properties that need to be serialized.
     *
     * @return array  List of serializable properties.
     */
    public function __sleep()
    {
        return array();
    }

}
