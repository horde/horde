<?php
/**
 * Copyright 1999-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Duck <duck@obala.net>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @package  Group
 */
class Horde_Group_Sql extends Horde_Group
{
    /**
     * Handle for the current database connection.
     *
     * @var Horde_Db_Adapter_Base
     */
    public $db;

    /**
     * Constructor.
     */
    public function __construct($params)
    {
        $this->_params = $params;
        $this->db = $GLOBALS['injector']->getInstance('Horde_Db')->getDb('horde', 'group');
    }

    /**
     * Initializes the object.
     */
    public function __wakeup()
    {
    }

    /**
     * Returns the properties that need to be serialized.
     *
     * @return array  List of serializable properties.
     */
    public function __sleep()
    {
    }

    /**
     * Stores the object in the session cache.
     */
    public function shutdown()
    {
    }

    /**
     * Replace all occurences of ':' in an object name with '.'.
     *
     * @param string $name  The name of the object.
     *
     * @return string  The encoded name.
     */
    public function encodeName($name)
    {
        return str_replace(':', '.', $name);
    }

    /**
     * Returns a new group object.
     *
     * @param string $name    The group's name.
     * @param string $parent  The group's parent's name.
     *
     * @return Horde_Group_SqlObject  A new group object.
     */
    public function newGroup($name, $parent = self::ROOT)
    {
        if ($parent != self::ROOT) {
            $name = $this->getGroupName($parent) . ':' . $this->encodeName($name);
        }

        $group = new Horde_Group_SqlObject($name);
        $group->setGroupOb($this);

        return $group;
    }

    /**
     * Returns a group object corresponding to the named group,
     * with the users and other data retrieved appropriately.
     *
     * @param string $name The name of the group to retrieve.
     *
     * @throws Horde_Group_Exception
     */
    public function getGroup($name)
    {
        if (!isset($this->_groupCache[$name])) {
            $sql = 'SELECT group_uid, group_email FROM horde_groups WHERE group_name = ?';

            try {
                $group = $this->db->selectRow($sql, array($name));
            } catch (Horde_Db_Exception $e) {
                throw new Horde_Group_Exception($e);
            }

            if (empty($group)) {
                throw new Horde_Group_Exception($name . ' does not exist');
            }

            $sql = 'SELECT user_uid FROM horde_groups_members '
                . ' WHERE group_uid = ? ORDER BY user_uid ASC';

            try {
                $users = $this->db->selectValues($sql, array($group['group_uid']));
            } catch (Horde_Db_Exception $e) {
                throw new Horde_Group_Exception($e);
            }

            $object = new Horde_Group_SqlObject($name);
            $object->id = $group['group_uid'];
            $object->data['email'] = $group['group_email'];

            if (!empty($users)) {
                $object->data['users'] = array_flip($users);
            }

            $this->_groupCache[$name] = $object;
            $this->_groupCache[$name]->setGroupOb($this);
            $this->_groupMap[$this->_groupCache[$name]->getId()] = $name;
        }

        return $this->_groupCache[$name];
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
        if (isset($this->_groupMap[$cid])) {
            return $this->_groupCache[$this->_groupMap[$cid]];
        }

        $sql = 'SELECT group_name, group_email FROM horde_groups WHERE group_uid = ?';

        try {
            $row = $this->db->selectOne($sql, array($cid));
        } catch (Horde_Db_Exception $e) {
            throw new Horde_Group_Exception($e);
        }

        if (empty($row)) {
            throw new Horde_Group_Exception($cid . ' does not exist');
        }

        $sql = 'SELECT user_uid FROM horde_groups_members '
            . ' WHERE group_uid = ? ORDER BY user_uid ASC';

        try {
            $users = $this->db->selectValues($sql, array($cid));
        } catch (Horde_Db_Exception $e) {
            throw new Horde_Group_Exception($e);
        }

        $group = new Horde_Group_SqlObject($row['group_name']);
        $group->id = $cid;
        $group->data['email'] = $row['group_email'];

        if (!empty($users)) {
            $group->data['users'] = array_flip($users);
        }

        $group->setGroupOb($this);
        $name = $group->getName();
        $this->_groupCache[$name] = $group;
        $this->_groupMap[$cid] = $name;

        return $group;
    }

    /**
     * Adds a group to the groups system. The group must first be created with
     * newGroup(), and have any initial users added to it, before this
     * function is called.
     *
     * @param Horde_Group_SqlObject $group  The new group object.
     *
     * @throws Horde_Group_Exception
     * @throws Horde_History_Exception
     * @throws InvalidArgumentException
     */
    public function addGroup(Horde_Group_SqlObject $group)
    {
        $group->setGroupOb($this);
        $name = $group->getName();

        $email = isset($group->data['email']) ? $group->data['email'] : '';

        $query = 'INSERT INTO horde_groups (group_name, group_parents, group_email) VALUES (?, ?, ?)';

        try {
            $result = $this->db->insert($query, array($name, '', $email));
        } catch (Horde_Db_Exception $e) {
            throw new Horde_Group_Exception($e);
        }

        $group->id = $result;

        if (!empty($group->data['users'])) {
            $query = 'INSERT INTO horde_groups_members (group_uid, user_uid)'
                .' VALUES (?, ?)';
            foreach ($group->data['users'] as $user) {
                try {
                    $this->db->insert($query, array($result, $user));
                } catch (Horde_Db_Exception $e) {
                    throw new Horde_Group_Exception($e);
                }
            }
        }

        $this->_groupCache[$name] = $group;
        $this->_groupMap[$group_id] = $name;
        if (isset($this->_groupList)) {
            $this->_groupList[$group_id] = $name;
        }

        /* Log the addition of the group in the history log. */
        $GLOBALS['injector']->getInstance('Horde_History')->log($this->getGUID($group), array('action' => 'add'), true);
    }

    /**
     * Stores updated data - users, etc. - of a group to the backend system.
     *
     * @param Horde_Group_SqlObject $group  The group to update.
     *
     * @throws Horde_Group_Exception
     * @throws Horde_History_Exception
     * @throws InvalidArgumentException
     */
    public function updateGroup(Horde_Group_SqlObject $group)
    {
        $query = 'UPDATE horde_groups SET group_email = ? WHERE group_uid = ?';

        try {
            $this->db->update($query, array($this->data['email'], $this->id));
        } catch (Horde_Db_Exception $e) {
            throw new Horde_Group_Exception($e);
        }

        $query = 'DELETE FROM horde_groups_members WHERE group_uid = ?';

        try {
            $this->db->delete($query, array($this->id));
        } catch (Horde_Db_Exception $e) {
            throw new Horde_Group_Exception($e);
        }

        $query = 'INSERT INTO horde_groups_members (group_uid, user_uid)' .
                 ' VALUES (?, ?)';
        foreach ($this->data['users'] as $user) {
            try {
                $this->db->insert($query, array(intval($this->id), $user));
            } catch (Horde_Db_Exception $e) {
                throw new Horde_Group_Exception($e);
            }
        }

        $this->_groupCache[$group->getName()] = &$group;

        /* Log the update of the group users on the history log. */
        $history = $GLOBALS['injector']->getInstance('Horde_History');
        $guid = $this->getGUID($group);
        foreach ($group->getAuditLog() as $userId => $action) {
            $history->log($guid, array('action' => $action, 'user' => $userId), true);
        }

        $group->clearAuditLog();

        /* Log the group modification. */
        $history->log($guid, array('action' => 'modify'), true);

        return $result;
    }

    /**
     * Removes a group from the groups system permanently.
     *
     * @param Horde_Group_SqlObject $group  The group to remove.
     * @param boolean $force               Force to remove every child.
     *
     * @throws Horde_Group_Exception
     * @throws Horde_History_Exception
     * @throws InvalidArgumentException
     */
    public function removeGroup(Horde_Group_SqlObject $group, $force = false)
    {
        $id = $group->getId();
        $name = $group->getName();
        unset($this->_groupMap[$id]);
        if (isset($this->_groupList)) {
            unset($this->_groupList[$id]);
        }
        unset($this->_groupCache[$name]);

        $GLOBALS['injector']->getInstance('Horde_History')->log($this->getGUID($group), array('action' => 'delete'), true);

        $query = 'DELETE FROM horde_groups_members WHERE group_uid = ?';

        try {
            $this->db->delete($query, array($id));
        } catch (Horde_Db_Exception $e) {
            throw new Horde_Group_Exception($e);
        }

        $query = 'DELETE FROM horde_groups WHERE group_uid = ?';
        try {
            $this->db->delete($query, array($id));
        } catch (Horde_Db_Exception $e) {
            throw new Horde_Group_Exception($e);
        }

        if ($force) {
            $query = 'DELETE FROM horde_groups WHERE group_name LIKE ?';

            try {
                $this->db->delete($query, array($name . ':%'));
            } catch (Horde_Db_Exception $e) {
                throw new Horde_Group_Exception($e);
            }
        }
    }

    /**
     * Retrieves the name of a group.
     *
     * @param integer|Horde_Group_SqlObject $gid  The id of the group or the
     *                                            group object to retrieve the
     *                                            name for.
     *
     * @return string  The group's name.
     * @throws Horde_Group_Exception
     */
    public function getGroupName($gid)
    {
        if ($gid instanceof Horde_Group_SqlObject) {
            $gid = $gid->getId();
        }

        if (isset($this->_groupMap[$gid])) {
            return $this->_groupMap[$gid];
        }
        if (isset($this->_groupList[$gid])) {
            return $this->_groupList[$gid];
        }

        $query = 'SELECT group_name FROM horde_groups WHERE group_uid = ?';

        try {
            return $this->db->selectValue($query, array($gid));
        } catch (Horde_Db_Exception $e) {
            throw new Horde_Group_Exception($e);
        }
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
        /* If there are several components to the name, explode and get the
         * last one, otherwise just return the name. */
        if (strpos($group, ':') !== false) {
            $name = explode(':', $group);
            return array_pop($name);
        }

        return $group;
    }

    /**
     * Retrieves the ID of a group.
     *
     * @param string|Horde_Group_SqlObject $group  The group name or object to
     *                                             retrieve the ID for.
     *
     * @return integer  The group's ID.
     * @throws Horde_Group_Exception
     */
    public function getGroupId($group)
    {
        if ($group instanceof Horde_Group_SqlObject) {
            return $group->getId();
        }

        $id = array_search($group, $this->_groupMap);
        if ($id !== false) {
            return $id;
        }
        if (isset($this->_groupList)) {
            $id = array_search($group, $this->_groupList);
            if ($id !== false) {
                return $id;
            }
        }

        $query = 'SELECT group_uid FROM horde_groups WHERE group_name = ?';

        try {
            return $this->db->selectValue($query, $group);
        } catch (Horde_Db_Exception $e) {
            throw new Horde_Group_Exception($e);
        }
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
        if (isset($this->_groupCache[$group]) ||
            (isset($this->_groupList) &&
             array_search($group, $this->_groupList) !== false)) {
            return true;
        }

        $query = 'SELECT COUNT(*) FROM horde_groups WHERE group_name = ?';

        try {
            return (bool)$this->db->selectValue($query, array($group));
        } catch (Horde_Db_Exception $e) {
            return false;
        }
    }

    /**
     * Returns a tree of the parents of a child group.
     *
     * @param integer $gid  The id of the child group.
     *
     * @return array  The group parents tree, with groupnames as the keys.
     * @throws Horde_Group_Exception
     */
    function getGroupParents($gid)
    {
        if (!isset($this->_parentTree[$gid])) {
            $name = $this->getGroupName($gid);
            $this->_parentTree[$gid] = $this->_getGroupParents($name);
        }

        return $this->_parentTree[$gid];
    }

    /**
     * Returns a list of parent permissions.
     *
     * @param string $child  The name of the child to retrieve parents for.
     *
     * @return array  A hash with all parents in a tree format.
     */
    protected function _getGroupParents($child)
    {
        if (($pos = strrpos($child, ':')) !== false) {
            $child = substr($child, 0, $pos);
        }

        return $this->_getParents($child);
    }

    /**
     */
    protected function _getParents($parents)
    {
        $mother = array();
        if (!empty($parents)) {
            $pname = $parents;
            $parents = substr($parents, 0, strrpos($parents, ':'));
            $mother[$pname] = $this->_getParents($parents);
        } else {
            return array(self::ROOT => true);
        }

        return $mother;
    }

    /**
     * Returns the single parent ID of the given group.
     *
     * @param integer $gid  The ID of the child group.
     *
     * @return integer  The parent of the given group.
     */
    public function getGroupParent($gid)
    {
        if (!isset($this->_groupParents[$gid])) {
            $name = $this->getGroupName($gid);
            if (($pos = strrpos($name, ':')) !== false) {
                $this->_groupParents[$gid] = $this->getGroupId(substr($name, 0, $pos));
            } else {
                $this->_groupParents[$gid] = self::ROOT;
            }
        }

        return $this->_groupParents[$gid];
    }

    /**
     * Returns a flat list of the parents of a child group
     *
     * @param integer $gid  The id of the group.
     *
     * @return array  A flat list of all of the parents of $group, hashed in
     *                $id => $name format.
     *@throws Horde_Group_Exception
     */
    public function getGroupParentList($gid)
    {
        if (!isset($this->_groupParentList[$gid])) {
            $name = $this->getGroupName($gid);
            $pos = strpos($name, ':');
            if ($pos == false) {
                $this->_groupParentList[$gid] = array();
                return $this->_groupParentList[$gid];
            }

            $parents = array();
            while ($pos) {
                $name = substr($name, 0, $pos);
                $parents[] = $name;
                $pos = strpos($name, ':');
            }

            $query = 'SELECT group_uid, group_name FROM horde_groups '
                . ' WHERE group_name IN (' . str_repeat('?, ', count($parents) - 1) . '?) ';
            try {
                $this->_groupParentList[$gid] = $this->db->selectAssoc($query, $parents);
            } catch (Horde_Db_Exception $e) {
                throw new Horde_Group_Exception($e);
            }
        }

        return $this->_groupParentList[$gid];
    }

    /**
     * Returns a flat list of the parents of a child group
     *
     * @param integer $gid  The id of the group.
     *
     * @return array  A flat list of all of the parents of $group, hashed in
     *                $id => $name format.
     */
    protected function _getGroupParentNameList($name)
    {
        $parents = array();

        while ($pos) {
            $name = substr($name, 0, $pos);
            $parents[] = $name;
            $pos = strpos($name, ':');
        }

        return $parents;
    }

    /**
     * Returns a list of all groups, in the format id => groupname.
     *
     * @param boolean $refresh  If true, the cached value is ignored and the
     *                          group list is refreshed from the group backend.
     *
     * @return array  ID => groupname hash.
     * @throws Horde_Group_Exception
     */
    public function listGroups($refresh = false)
    {
        if ($refresh || !isset($this->_groupList)) {
            $sql = 'SELECT group_uid, group_name FROM horde_groups ORDER BY group_uid';
            try {
                $this->_groupList = $this->db->selectAssoc($sql);
            } catch (Horde_Db_Exception $e) {
                throw new Horde_Group_Exception($e);
            }
        }

        return $this->_groupList;
    }

    /**
     * Get a list of every user that is part of the specified group
     * and any of its subgroups.
     *
     * @param integer $group  The ID of the parent group.
     *
     * @return array  The complete user list.
     * @throws Horde_Group_Exception
     */
    public function listAllUsers($gid)
    {
        if (!isset($this->_subGroups[$gid])) {
            // Get a list of every group that is a sub-group of $group.
            $name = $this->getGroupName($gid);
            $query = 'SELECT group_uid FROM horde_groups WHERE group_name LIKE ?';

            try {
                $this->_subGroups[$gid] = $this->db->selectValues($query, array($name .  ':%'));
            } catch (Horde_Db_Exception $e) {
                throw new Horde_Group_Exception($e);
            }
            $this->_subGroups[$gid][] = $gid;
        }

        $users = array();
        foreach ($this->_subGroups[$gid] as $groupId) {
            $users = array_merge($users, $this->listUsers($groupId));
        }

        return array_values(array_flip(array_flip($users)));
    }

    /**
     * Get a list of every group that $user is in.
     *
     * @param string  $user          The user to get groups for.
     * @param boolean $parentGroups  Also return the parents of any groups?
     *
     * @return array  An array of all groups the user is in.
     * @throws Horde_Group_Exception
     */
    public function getGroupMemberships($user, $parentGroups = false)
    {
        if (isset($_SESSION['horde']['groups']['m'][$user][$parentGroups])) {
            return $_SESSION['horde']['groups']['m'][$user][$parentGroups];
        }

        $sql = 'SELECT g.group_uid AS group_uid, g.group_name AS group_name FROM horde_groups g, horde_groups_members m '
            . ' WHERE m.user_uid = ? AND g.group_uid = m.group_uid ORDER BY g.group_name';
        try {
            $result = $this->db->selectAll($sql, array($user));
        } catch (Horde_Db_Exception $e) {
            throw new Horde_Group_Exception($e);
        }

        $groups = array();
        foreach ($result as $row) {
            $groups[(int)$row['group_uid']] = $this->getGroupShortName($row['group_name']);
        }

        if ($parentGroups) {
            foreach ($groups as $id => $g) {
                $groups += $this->getGroupParentList($id);
            }
        }

        $_SESSION['horde']['groups']['m'][$user][$parentGroups] = $groups;

        return $groups;
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
        if (isset($_SESSION['horde']['groups']['i'][$user][$subgroups][$gid])) {
            return $_SESSION['horde']['groups']['i'][$user][$subgroups][$gid];
        }

        if ($subgroups) {
            try {
                $groups = $this->getGroupMemberships($user, true);
            } catch (Horde_Group_Exception $e) {
                Horde::logMessage($e, 'ERR');
                return false;
            }

            $result = !empty($groups[$gid]);
        } else {
            $query = 'SELECT COUNT(*) FROM horde_groups_members WHERE group_uid = ? AND user_uid = ?';
            try {
                $result = $this->db->selectValue($query, array($gid, $user));
            } catch (Horde_Db_Exception $e) {
                $result = false;
            }

        }

        $_SESSION['horde']['groups']['i'][$user][$subgroups][$gid] = (bool)$result;
        return (bool)$result;
    }

}
