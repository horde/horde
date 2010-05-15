<?php
/**
 * Instance of a single permissioning object.
 *
 * Copyright 2009-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @package  Horde_Perms
 */
class Horde_Perms_Permission
{
    /**
     * TODO
     */
    public $data;

    /**
     * TODO
     */
    public $name;

    /**
     * Incrementing version number if cached classes change.
     *
     * @var integer
     */
    protected $_cacheVersion;

    /**
     * Constructor.
     *
     * @param string $name           The name of the perm.
     * @param integer $cacheVersion  The revision number of the class.
     * @param string $type           The permission type.
     * @param array $params          A hash with any parameters that the
     *                               permission type needs.
     */
    public function __construct($name, $cacheVersion = null, $type = 'matrix',
                                $params = null)
    {
        $this->setName($name);
        $this->setCacheVersion($cacheVersion);
        $this->data['type'] = $type;
        if (is_array($params)) {
            $this->data['params'] = $params;
        }
    }

    /**
     * Sets the revision number of the class.
     *
     * @param integer $cacheVersion  The revision number of the class.
     */
    public function setCacheVersion($cacheVersion)
    {
        $this->_cacheVersion = $cacheVersion;
    }

    /**
     * Gets one of the attributes of the object, or null if it isn't defined.
     *
     * @param string $attribute  The attribute to get.
     *
     * @return mixed  The value of the attribute, or null.
     */
    public function get($attribute)
    {
        if (isset($this->data[$attribute])) {
            return $this->data[$attribute];
        }

        return ($attribute == 'type') ? 'matrix' : null;
    }

    /**
     * Get permission name.
     *
     * @return string  Permission name.
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set permission name
     *
     * @param string $name  Permission name.
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Get permission details.
     *
     * @return array  Permission details.
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * Set permission details.
     *
     * @param string $data  Permission details.
     */
    public function setData($data)
    {
        $this->data = $data;
    }

    /**
     * Updates the permissions based on data passed in the array.
     *
     * @param array $perms  An array containing the permissions which are to
     *                      be updated.
     */
    public function updatePermissions($perms)
    {
        $type = $this->get('type');

        if ($type == 'matrix') {
            /* Array of permission types to iterate through. */
            $perm_types = Horde_Perms::getPermsArray();
        }

        foreach ($perms as $perm_class => $perm_values) {
            switch ($perm_class) {
            case 'default':
            case 'guest':
            case 'creator':
                if ($type == 'matrix') {
                    foreach ($perm_types as $val => $label) {
                        if (!empty($perm_values[$val])) {
                            $this->setPerm($perm_class, $val, false);
                        } else {
                            $this->unsetPerm($perm_class, $val, false);
                        }
                    }
                } elseif (!empty($perm_values)) {
                    $this->setPerm($perm_class, $perm_values, false);
                } else {
                    $this->unsetPerm($perm_class, null, false);
                }
                break;

            case 'u':
            case 'g':
                $permId = array('class' => $perm_class == 'u' ? 'users' : 'groups');
                /* Figure out what names that are stored in this permission
                 * class have not been submitted for an update, ie. have been
                 * removed entirely. */
                $current_names = isset($this->data[$permId['class']])
                    ? array_keys($this->data[$permId['class']])
                    : array();
                $updated_names = array_keys($perm_values);
                $removed_names = array_diff($current_names, $updated_names);

                /* Remove any names that have been completely unset. */
                foreach ($removed_names as $name) {
                    unset($this->data[$permId['class']][$name]);
                }

                /* If nothing to actually update finish with this case. */
                if (is_null($perm_values)) {
                    continue;
                }

                /* Loop through the names and update permissions for each. */
                foreach ($perm_values as $name => $name_values) {
                    $permId['name'] = $name;

                    if ($type == 'matrix') {
                        foreach ($perm_types as $val => $label) {
                            if (!empty($name_values[$val])) {
                                $this->setPerm($permId, $val, false);
                            } else {
                                $this->unsetPerm($permId, $val, false);
                            }
                        }
                    } elseif (!empty($name_values)) {
                        $this->setPerm($permId, $name_values, false);
                    } else {
                        $this->unsetPerm($permId, null, false);
                    }
                }
                break;
            }
        }
    }

    /**
     * TODO
     */
    public function setPerm($permId, $permission, $update = true)
    {
        if (is_array($permId)) {
            if (empty($permId['name'])) {
                return;
            }
            if ($this->get('type') == 'matrix' &&
                isset($this->data[$permId['class']][$permId['name']])) {
                $this->data[$permId['class']][$permId['name']] |= $permission;
            } else {
                $this->data[$permId['class']][$permId['name']] = $permission;
            }
        } else {
            if ($this->get('type') == 'matrix' &&
                isset($this->data[$permId])) {
                $this->data[$permId] |= $permission;
            } else {
                $this->data[$permId] = $permission;
            }
        }

        if ($update) {
            $this->save();
        }
    }

    /**
     * TODO
     */
    public function unsetPerm($permId, $permission, $update = true)
    {
        if (is_array($permId)) {
            if (empty($permId['name'])) {
                return;
            }

            if ($this->get('type') == 'matrix') {
                if (isset($this->data[$permId['class']][$permId['name']])) {
                    $this->data[$permId['class']][$permId['name']] &= ~$permission;
                    if (empty($this->data[$permId['class']][$permId['name']])) {
                        unset($this->data[$permId['class']][$permId['name']]);
                    }
                } else {
                    $update = false;
                }
            } else {
                unset($this->data[$permId['class']][$permId['name']]);
            }
        } else {
            if ($this->get('type') == 'matrix') {
                if (isset($this->data[$permId])) {
                    $this->data[$permId] &= ~$permission;
                } else {
                    $update = false;
                }
            } else {
                unset($this->data[$permId]);
            }
        }

        if ($update) {
            $this->save();
        }
    }

    /**
     * Grants a user additional permissions to this object.
     *
     * @param string $uer          The user to grant additional permissions
     *                             to.
     * @param integer $permission  The permission (DELETE, etc.) to add.
     * @param boolean $update      Whether to automatically update the
     *                             backend.
     */
    public function addUserPermission($user, $permission, $update = true)
    {
        if (empty($user)) {
            return;
        }

        if ($this->get('type') == 'matrix' &&
            isset($this->data['users'][$user])) {
            $this->data['users'][$user] |= $permission;
        } else {
            $this->data['users'][$user] = $permission;
        }

        if ($update) {
            $this->save();
        }
    }

    /**
     * Grants guests additional permissions to this object.
     *
     * @param integer $permission  The permission (DELETE, etc.) to add.
     * @param boolean $update      Whether to automatically update the
     *                             backend.
     */
    public function addGuestPermission($permission, $update = true)
    {
        if ($this->get('type') == 'matrix' &&
            isset($this->data['guest'])) {
            $this->data['guest'] |= $permission;
        } else {
            $this->data['guest'] = $permission;
        }

        if ($update) {
            $this->save();
        }
    }

    /**
     * Grants creators additional permissions to this object.
     *
     * @param integer $permission  The permission (DELETE, etc.) to add.
     * @param boolean $update      Whether to automatically update the
     *                             backend.
     */
    public function addCreatorPermission($permission, $update = true)
    {
        if ($this->get('type') == 'matrix' &&
            isset($this->data['creator'])) {
            $this->data['creator'] |= $permission;
        } else {
            $this->data['creator'] = $permission;
        }

        if ($update) {
            $this->save();
        }
    }

    /**
     * Grants additional default permissions to this object.
     *
     * @param integer $permission  The permission (DELETE, etc.) to add.
     * @param boolean $update      Whether to automatically update the
     *                             backend.
     */
    public function addDefaultPermission($permission, $update = true)
    {
        if ($this->get('type') == 'matrix' &&
            isset($this->data['default'])) {
            $this->data['default'] |= $permission;
        } else {
            $this->data['default'] = $permission;
        }

        if ($update) {
            $this->save();
        }
    }

    /**
     * Grants a group additional permissions to this object.
     *
     * @param integer $groupId     The id of the group to grant additional
     *                             permissions to.
     * @param integer $permission  The permission (DELETE, etc.) to add.
     * @param boolean $update      Whether to automatically update the
     *                             backend.
     */
    public function addGroupPermission($groupId, $permission, $update = true)
    {
        if (empty($groupId)) {
            return;
        }

        if ($this->get('type') == 'matrix' &&
            isset($this->data['groups'][$groupId])) {
            $this->data['groups'][$groupId] |= $permission;
        } else {
            $this->data['groups'][$groupId] = $permission;
        }

        if ($update) {
            $this->save();
        }
    }

    /**
     * Removes a permission that a user currently has on this object.
     *
     * @param string $user         The user to remove the permission from.
     *                             Defaults to all users.
     * @param integer $permission  The permission (DELETE, etc.) to
     *                             remove. Defaults to all permissions.
     * @param boolean $update      Whether to automatically update the
     *                             backend.
     */
    public function removeUserPermission($user = null, $permission = null,
                                         $update = true)
    {
        if (is_null($user)) {
            $this->data['users'] = array();
        } else {
            if (!isset($this->data['users'][$user])) {
                return;
            }

            if ($permission && $this->get('type') == 'matrix') {
                $this->data['users'][$user] &= ~$permission;
                if (empty($this->data['users'][$user])) {
                    unset($this->data['users'][$user]);
                }
            } else {
                unset($this->data['users'][$user]);
            }
        }

        if ($update) {
            $this->save();
        }
    }

    /**
     * Removes a permission that guests currently have on this object.
     *
     * @param integer $permission  The permission (DELETE, etc.) to
     *                             remove. Defaults to all permissions.
     * @param boolean $update      Whether to automatically update the
     *                             backend.
     */
    public function removeGuestPermission($permission = null, $update = true)
    {
        if (!isset($this->data['guest'])) {
            return;
        }

        if ($permission && $this->get('type') == 'matrix') {
            $this->data['guest'] &= ~$permission;
        } else {
            unset($this->data['guest']);
        }

        if ($update) {
            $this->save();
        }
    }

    /**
     * Removes a permission that creators currently have on this object.
     *
     * @param integer $permission  The permission (DELETE, etc.) to
     *                             remove. Defaults to all permissions.
     * @param boolean $update      Whether to automatically update the
     *                             backend.
     */
    public function removeCreatorPermission($permission = null, $update = true)
    {
        if (!isset($this->data['creator'])) {
            return;
        }

        if ($permission && $this->get('type') == 'matrix') {
            $this->data['creator'] &= ~$permission;
        } else {
            unset($this->data['creator']);
        }

        if ($update) {
            $this->save();
        }
    }

    /**
     * Removes a default permission on this object.
     *
     * @param integer $permission  The permission (DELETE, etc.) to
     *                             remove. Defaults to all permissions.
     * @param boolean $update      Whether to automatically update the
     *                             backend.
     */
    public function removeDefaultPermission($permission = null, $update = true)
    {
        if (!isset($this->data['default'])) {
            return;
        }

        if ($permission && $this->get('type') == 'matrix') {
            $this->data['default'] &= ~$permission;
        } else {
            unset($this->data['default']);
        }

        if ($update) {
            $this->save();
        }
    }

    /**
     * Removes a permission that a group currently has on this object.
     *
     * @param integer $groupId     The id of the group to remove the
     *                             permission from. Defaults to all groups.
     * @param integer $permission  The permission (DELETE, etc.) to
     *                             remove. Defaults to all permissions.
     * @param boolean $update      Whether to automatically update the
     *                             backend.
     */
    public function removeGroupPermission($groupId = null, $permission = null,
                                          $update = true)
    {
        if (is_null($groupId)) {
            $this->data['groups'] = array();
        } else {
            if (!isset($this->data['groups'][$groupId])) {
                return;
            }

            if ($permission && $this->get('type') == 'matrix') {
                $this->data['groups'][$groupId] &= ~$permission;
                if (empty($this->data['groups'][$groupId])) {
                    unset($this->data['groups'][$groupId]);
                }
            } else {
                unset($this->data['groups'][$groupId]);
            }
        }

        if ($update) {
            $this->save();
        }
    }

    /**
     * Returns an array of all user permissions on this object.
     *
     * @param integer $perm  List only users with this permission level.
     *                       Defaults to all users.
     *
     * @return array  All user permissions for this object, indexed by user.
     */
    public function getUserPermissions($perm = null)
    {
        if (!isset($this->data['users']) || !is_array($this->data['users'])) {
            return array();
        } elseif (!$perm) {
            return $this->data['users'];
        }

        $users = array();
        foreach ($this->data['users'] as $user => $uperm) {
            if ($uperm & $perm) {
                $users[$user] = $uperm;
            }
        }

        return $users;
    }

    /**
     * Returns the guest permissions on this object.
     *
     * @return integer  The guest permissions on this object.
     */
    public function getGuestPermissions()
    {
        return empty($this->data['guest'])
            ? null
            : $this->data['guest'];
    }

    /**
     * Returns the creator permissions on this object.
     *
     * @return integer  The creator permissions on this object.
     */
    public function getCreatorPermissions()
    {
        return empty($this->data['creator'])
            ? null
            : $this->data['creator'];
    }

    /**
     * Returns the default permissions on this object.
     *
     * @return integer  The default permissions on this object.
     */
    public function getDefaultPermissions()
    {
        return empty($this->data['default'])
            ? null
            : $this->data['default'];
    }

    /**
     * Returns an array of all group permissions on this object.
     *
     * @param integer $perm  List only users with this permission level.
     *                       Defaults to all users.
     *
     * @return array  All group permissions for this object, indexed by group.
     */
    public function getGroupPermissions($perm = null)
    {
        if (!isset($this->data['groups']) ||
            !is_array($this->data['groups'])) {
            return array();
        } elseif (!$perm) {
            return $this->data['groups'];
        }

        $groups = array();
        foreach ($this->data['groups'] as $group => $gperm) {
            if ($gperm & $perm) {
                $groups[$group] = $gperm;
            }
        }

        return $groups;
    }

    /**
     * TODO
     */
    public function save()
    {
    }

}
