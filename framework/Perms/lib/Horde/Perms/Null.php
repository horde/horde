<?php
/**
 * Horde_Perms_Null
 *
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @package  Perms
 */
class Horde_Perms_Null extends Horde_Perms_Base
{
    /**
     * Returns a new permissions object.
     *
     * @param string $name   The permission's name.
     * @param string $type   The permission type.
     * @param array $params  The permission parameters.
     *
     * @return Horde_Perms_Permission  A new permissions object.
     * @throws Horde_Perms_Exception
     */
    public function newPermission($name, $type = 'matrix', $params = null)
    {
        throw new Horde_Perms_Exception();
    }

    /**
     * Returns an object corresponding to the named permission, with the users
     * and other data retrieved appropriately.
     *
     * @param string $name  The name of the permission to retrieve.
     *
     * @return Horde_Perms_Permission  A permissions object.
     * @throws Horde_Perms_Exception
     */
    public function getPermission($name)
    {
        throw new Horde_Perms_Exception();
    }

    /**
     * Returns an object corresponding to the given unique ID, with the users
     * and other data retrieved appropriately.
     *
     * @param integer $cid  The unique ID of the permission to retrieve.
     *
     * @return Horde_Perms_Permission  A permissions object.
     * @throws Horde_Perms_Exception
     */
    public function getPermissionById($cid)
    {
        throw new Horde_Perms_Exception();
    }

    /**
     * Adds a permission to the permissions system. The permission must first
     * be created with newPermission(), and have any initial users added to
     * it, before this function is called.
     *
     * @param Horde_Perms_Permission $perm  The permissions object.
     *
     * @throws Horde_Perms_Exception
     */
    public function addPermission(Horde_Perms_Permission $perm)
    {
        throw new Horde_Perms_Exception();
    }

    /**
     * Removes a permission from the permissions system permanently.
     *
     * @param Horde_Perms_Permission $perm  The permission to remove.
     * @param boolean $force                Force to remove every child.
     *
     * @throws Horde_Perms_Exception
     */
    public function removePermission(Horde_Perms_Permission $perm,
                                     $force = false)
    {
        throw new Horde_Perms_Exception();
    }

    /**
     * Returns the unique identifier of this permission.
     *
     * @param Horde_Perms_Permission $permission  The permission object to get
     *                                            the ID of.
     *
     * @return integer  The unique id.
     * @throws Horde_Perms_Exception
     */
    public function getPermissionId($permission)
    {
        throw new Horde_Perms_Exception();
    }

    /**
     * Checks if a permission exists in the system.
     *
     * @param string $permission  The permission to check.
     *
     * @return boolean  True if the permission exists.
     */
    public function exists($permission)
    {
        return false;
    }

    /**
     * Returns a list of parent permissions.
     *
     * @param string $child  The name of the child to retrieve parents for.
     *
     * @return array  A hash with all parents in a tree format.
     * @throws Horde_Perms_Exception
     */
    public function getParents($child)
    {
        throw new Horde_Perms_Exception();
    }

    /**
     * Returns all permissions of the system in a tree format.
     *
     * @return array  A hash with all permissions in a tree format.
     */
    public function getTree()
    {
        return array();
    }
}
