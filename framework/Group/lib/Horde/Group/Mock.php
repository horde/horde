<?php
/**
 * This class provides a mock driver for the Horde group system.
 *
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
class Horde_Group_Mock extends Horde_Group_Base
{
    /**
     * List of groups.
     *
     * @var array
     */
    protected $_groups;

    /**
     * Creates a new group.
     *
     * @param string $name   A group name.
     * @param string $email  The group's email address.
     *
     * @return mixed  The ID of the created group.
     * @throws Horde_Group_Exception
     */
    public function create($name, $email = null)
    {
        $id = 'group_' . count($this->_groups);
        $this->_groups[$id] = array('name'  => $name,
                                    'email' => $email,
                                    'users' => array());
        return $id;
    }

    /**
     * Renames a group.
     *
     * @param mixed $gid    A group ID.
     * @param string $name  The new name.
     *
     * @throws Horde_Group_Exception
     */
    public function rename($gid, $name)
    {
        if (!isset($this->_groups[$gid])) {
            throw new Horde_Exception_NotFound('Group "' . $gid . '" not found');
        }
        $this->_groups[$gid]['name'] = $name;
    }

    /**
     * Removes a group.
     *
     * @param mixed $gid  A group ID.
     *
     * @throws Horde_Group_Exception
     */
    public function remove($gid)
    {
        unset($this->_groups[$gid]);
    }

    /**
     * Checks if a group exists.
     *
     * @param mixed $gid  A group ID.
     *
     * @return boolean  True if the group exists.
     * @throws Horde_Group_Exception
     */
    public function exists($gid)
    {
        return isset($this->_groups[$gid]);
    }

    /**
     * Returns a group name.
     *
     * @param mixed $gid  A group ID.
     *
     * @return string  The group's name.
     * @throws Horde_Group_Exception
     */
    public function getName($gid)
    {
        if (!isset($this->_groups[$gid])) {
            throw new Horde_Exception_NotFound('Group ' . $gid . ' not found');
        }
        return $this->_groups[$gid]['name'];
    }

    /**
     * Returns all available attributes of a group.
     *
     * @param mixed $gid  A group ID.
     *
     * @return array  The group's date.
     * @throws Horde_Group_Exception
     * @throws Horde_Exception_NotFound
     */
    public function getData($gid)
    {
        if (!isset($this->_groups[$gid])) {
            throw new Horde_Exception_NotFound('Group ' . $gid . ' not found');
        }
        return $this->_groups[$gid];
    }

    /**
     * Sets one or more attributes of a group.
     *
     * @param mixed $gid               A group ID.
     * @param array|string $attribute  An attribute name or a hash of
     *                                 attributes.
     * @param string $value            An attribute value if $attribute is a
     *                                 string.
     *
     * @throws Horde_Group_Exception
     * @throws Horde_Exception_NotFound
     */
    public function setData($gid, $attribute, $value = null)
    {
        if (!isset($this->_groups[$gid])) {
            throw new Horde_Exception_NotFound('Group ' . $gid . ' not found');
        }
        if (is_array($attribute)) {
            $this->_groups[$gid] = array_merge($this->_groups[$gid], $attribute);
        } else {
            $this->_groups[$gid][$attribute] = $value;
        }
    }

    /**
     * Returns a list of all groups, with IDs as keys and names as values.
     *
     * @return array  All existing groups.
     * @throws Horde_Group_Exception
     */
    public function listAll()
    {
        $groups = array();
        foreach ($this->_groups as $gid => $group) {
            $groups[$gid] = $group['name'];
        }
        asort($groups);
        return $groups;
    }

    /**
     * Returns a list of users in a group.
     *
     * @param mixed $gid  A group ID.
     *
     * @return array  List of group users.
     * @throws Horde_Group_Exception
     */
    public function listUsers($gid)
    {
        if (!isset($this->_groups[$gid])) {
            throw new Horde_Exception_NotFound('Group ' . $gid . ' not found');
        }
        return $this->_groups[$gid]['users'];
    }

    /**
     * Returns a list of groups a user belongs to.
     *
     * @param string $user  A user name.
     *
     * @return array  A list of groups, with IDs as keys and names as values.
     * @throws Horde_Group_Exception
     */
    public function listGroups($user)
    {
        $groups = array();
        foreach ($this->_groups as $gid => $group) {
            if (in_array($user, $group['users'])) {
                $groups[$gid] = $group['name'];
            }
        }
        asort($groups);
        return $groups;
    }

    /**
     * Add a user to a group.
     *
     * @param mixed $gid    A group ID.
     * @param string $user  A user name.
     *
     * @throws Horde_Group_Exception
     */
    public function addUser($gid, $user)
    {
        if (!isset($this->_groups[$gid])) {
            throw new Horde_Exception_NotFound('Group ' . $gid . ' not found');
        }
        $this->_groups[$gid]['users'][] = $user;
    }

    /**
     * Removes a user from a group.
     *
     * @param mixed $gid    A group ID.
     * @param string $user  A user name.
     *
     * @throws Horde_Group_Exception
     */
    public function removeUser($gid, $user)
    {
        if (!isset($this->_groups[$gid])) {
            throw new Horde_Exception_NotFound('Group ' . $gid . ' not found');
        }
        $key = array_search($user, $this->_groups[$gid]['users']);
        if ($key !== false) {
            unset($this->_groups[$gid]['users'][$key]);
        }
    }

    /**
     * Searches for group names.
     *
     * @param string $name  A search string.
     *
     * @return array  A list of matching groups, with IDs as keys and names as
     *                values.
     * @throws Horde_Group_Exception
     */
    public function search($name)
    {
        $groups = array();
        foreach ($this->_groups as $gid => $group) {
            if (strpos($group['name'], $name) !== false) {
                $groups[$gid] = $group['name'];
            }
        }
        asort($groups);
        return $groups;
    }
}
