<?php
/**
 * Horde_Group_Base is the base class for all drivers of the Horde group
 * system.
 *
 * Copyright 1999-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @package  Group
 */
abstract class Horde_Group_Base
{
    /**
     * Creates a new group.
     *
     * @param string $name   A group name.
     * @param string $email  The group's email address.
     *
     * @return mixed  The ID of the created group.
     * @throws Horde_Group_Exception
     */
    abstract public function create($name, $email = null);

    /**
     * Renames a group.
     *
     * @param mixed $gid    A group ID.
     * @param string $name  The new name.
     */
    abstract public function rename($gid, $name);

    /**
     * Removes a group.
     *
     * @param mixed $gid  A group ID.
     */
    abstract public function remove($gid);

    /**
     * Checks if a group exists.
     *
     * @param mixed $gid  A group ID.
     *
     * @return boolean  True if the group exists.
     */
    abstract public function exists($gid);

    /**
     * Returns a group name.
     *
     * @param mixed $gid  A group ID.
     *
     * @return string  The group's name.
     */
    abstract public function getName($gid);

    /**
     * Returns a list of all groups, with IDs as keys and names as values.
     *
     * @return array  All existing groups.
     */
    abstract public function listAll();

    /**
     * Returns a list of users in a group.
     *
     * @param mixed $gid  A group ID.
     *
     * @return array  List of group users.
     */
    abstract public function listUsers($gid);

    /**
     * Returns a list of groups a user belongs to.
     *
     * @param string $user  A user name.
     *
     * @return array  A list of groups, with IDs as keys and names as values.
     */
    abstract public function listGroups($user);

    /**
     * Add a user to a group.
     *
     * @param mixed $gid    A group ID.
     * @param string $user  A user name.
     */
    abstract public function addUser($gid, $user);

    /**
     * Removes a user from a group.
     *
     * @param mixed $gid    A group ID.
     * @param string $user  A user name.
     */
    abstract public function removeUser($gid, $user);

    /**
     * Searches for group names.
     *
     * @param string $name  A search string.
     *
     * @return array  A list of matching groups, with IDs as keys and names as
     *                values.
     */
    abstract public function search($name);
}
