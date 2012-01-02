<?php
/**
 * The Horde_Perms_Base class provides the Horde permissions system.
 *
 * Copyright 2001-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @package  Perms
 */
abstract class Horde_Perms_Base
{
    /**
     * Cache object.
     *
     * @var Horde_Cache
     */
    protected $_cache;

    /**
     * Logger.
     *
     * @var Horde_Log_Logger
     */
    protected $_logger;

    /**
     * Constructor.
     *
     * @param array $params  Configuration parameters:
     * <pre>
     * 'cache' - (Horde_Cache) The object to use to cache perms.
     * 'logger' - (Horde_Log_Logger) A logger object.
     * </pre>
     *
     * @throws Horde_Perms_Exception
     */
    public function __construct($params = array())
    {
        if (isset($params['cache'])) {
            $this->_cache = $params['cache'];
        }

        if (isset($params['logger'])) {
            $this->_logger = $params['logger'];
        }
    }

    /**
     * Returns the short name of an object, the last portion of the full name.
     *
     * @param string $name  The name of the object.
     *
     * @return string  The object's short name.
     */
    public function getShortName($name)
    {
        /* If there are several components to the name, explode and
         * get the last one, otherwise just return the name. */
        if (strpos($name, ':') !== false) {
            $tmp = explode(':', $name);
            return array_pop($tmp);
        }

        return $name;
    }

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
    abstract public function newPermission($name, $type = 'matrix', $params = null);

    /**
     * Returns an object corresponding to the named permission, with the users
     * and other data retrieved appropriately.
     *
     * @param string $name  The name of the permission to retrieve.
     *
     * @return Horde_Perms_Permission  A permissions object.
     * @throws Horde_Perms_Exception
     */
    abstract public function getPermission($name);

    /**
     * Returns an object corresponding to the given unique ID, with the users
     * and other data retrieved appropriately.
     *
     * @param integer $cid  The unique ID of the permission to retrieve.
     *
     * @return Horde_Perms_Permission  A permissions object.
     * @throws Horde_Perms_Exception
     */
    abstract public function getPermissionById($cid);

    /**
     * Adds a permission to the permissions system. The permission must first
     * be created with newPermission(), and have any initial users added to
     * it, before this function is called.
     *
     * @param Horde_Perms_Permission $perm  The permissions object.
     *
     * @throws Horde_Perms_Exception
     */
    abstract public function addPermission(Horde_Perms_Permission $perm);

    /**
     * Removes a permission from the permissions system permanently.
     *
     * @param Horde_Perms_Permission $perm  The permission to remove.
     * @param boolean $force                Force to remove every child.
     *
     * @throws Horde_Perms_Exception
     */
    abstract public function removePermission(Horde_Perms_Permission $perm,
                                              $force = false);

    /**
     * Finds out what rights the given user has to this object.
     *
     * @param mixed $permission  The full permission name of the object to
     *                           check the permissions of, or the
     *                           Horde_Permissions object.
     * @param string $user       The user to check for.
     * @param string $creator    The user who created the event.
     *
     * @return mixed  A bitmask of permissions the user has, false if there
     *                are none.
     */
    public function getPermissions($permission, $user, $creator = null)
    {
        if (is_string($permission)) {
            try {
                $permission = $this->getPermission($permission);
            } catch (Horde_Perms_Exception $e) {
                if ($this->_logger) {
                    $this->_logger->log($e, 'DEBUG');
                }
                return false;
            }
        }

        // If this is a guest user, only check guest permissions.
        if (empty($user)) {
            return $permission->getGuestPermissions();
        }

        // Combine all other applicable permissions.
        $type = $permission->get('type');
        $composite_perm = ($type == 'matrix') ? 0 : array();

        // If $creator was specified, check creator permissions.
        // If the user is the creator of the event see if there are creator
        // permissions.
        if (!is_null($creator) &&
            strlen($user) &&
            ($user === $creator) &&
            (($perms = $permission->getCreatorPermissions()) !== null)) {
            if ($type == 'matrix') {
                $composite_perm |= $perms;
            } else {
                $composite_perm[] = $perms;
            }
        }

        // Check user-level permissions.
        $userperms = $permission->getUserPermissions();
        if (isset($userperms[$user])) {
            if ($type == 'matrix') {
                $composite_perm |= $userperms[$user];
            } else {
                $composite_perm[] = $userperms[$user];
            }
        }

        // If no user permissions are found, try group permissions.
        if (isset($permission->data['groups']) &&
            is_array($permission->data['groups']) &&
            count($permission->data['groups'])) {
            $groups = $GLOBALS['injector']
                ->getInstance('Horde_Group')
                ->listGroups($user);

            foreach ($permission->data['groups'] as $group => $perms) {
                if (isset($groups[$group])) {
                    if ($type == 'matrix') {
                        $composite_perm |= $perms;
                    } else {
                        $composite_perm[] = $perms;
                    }
                }
            }
        }

        // If there are default permissions, return them.
        if (($perms = $permission->getDefaultPermissions()) !== null) {
            if ($type == 'matrix') {
                $composite_perm |= $perms;
            } else {
                $composite_perm[] = $perms;
            }
        }

        // Return composed permissions.
        if ($composite_perm) {
            return $composite_perm;
        }

        // Otherwise, deny all permissions to the object.
        return false;
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
    abstract public function getPermissionId($permission);

    /**
     * Finds out if the user has the specified rights to the given object.
     *
     * @param string $permission  The permission to check.
     * @param string $user        The user to check for.
     * @param integer $perm       The permission level that needs to be checked
     *                            for.
     * @param string $creator     The creator of the event
     *
     * @return boolean  Whether the user has the specified permissions.
     */
    public function hasPermission($permission, $user, $perm, $creator = null)
    {
        return (bool)($this->getPermissions($permission, $user, $creator) & $perm);
    }

    /**
     * Checks if a permission exists in the system.
     *
     * @param string $permission  The permission to check.
     *
     * @return boolean  True if the permission exists.
     */
    abstract public function exists($permission);

    /**
     * Returns a list of parent permissions.
     *
     * @param string $child  The name of the child to retrieve parents for.
     *
     * @return array  A hash with all parents in a tree format.
     * @throws Horde_Perms_Exception
     */
    abstract public function getParents($child);

    /**
     * Returns all permissions of the system in a tree format.
     *
     * @return array  A hash with all permissions in a tree format.
     */
    abstract public function getTree();
}
