<?php
/**
 * The Horde_Group:: class provides the Horde groups system.
 *
 * Copyright 1999-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Stephane Huther <shuther1@free.fr>
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @package  Group
 */

// This will go away eventually, but Group can't autoload the DataTree class
require_once 'Horde/DataTree.php';

class Horde_Group
{
    /** The parent Group node */
    const ROOT = -1;

    /**
     * Group driver parameters
     *
     * @var array
     */
    protected $_params;

    /**
     * Pointer to a DataTree instance to manage the different groups.
     *
     * @var DataTree
     */
    protected $_datatree;

    /**
     * Cache of previously retrieved group objects.
     *
     * @var array
     */
    protected $_groupCache = array();

    /**
     * Id-name-map of already cached group objects.
     *
     * @var array
     */
    protected $_groupMap = array();

    /**
     * Id-name-hash of all existing groups.
     *
     * @var array
     */
    protected $_groupList;

    /**
     * List of sub groups.
     *
     * @see listAllUsers()
     * @var array
     */
    protected $_subGroups = array();

    /**
     * Cache of parent groups.
     *
     * This is an array with group IDs as keys and the integer group id of the
     * direct parent as values.
     *
     * @see getGroupParent()
     * @var array
     */
    protected $_groupParents = array();

    /**
     * Cache of parent group trees.
     *
     * This is an array with group IDs as keys and id-name-hashes of all
     * parents as values.
     *
     * @see getGroupParentList()
     * @var array
     */
    protected $_groupParentList = array();

    /**
     * Cache of parents tree.
     *
     * @see getGroupParents()
     * @var array
     */
    protected $_parentTree = array();

    /**
     * Hash of groups of certain users.
     *
     * @see getGroupMemberShips()
     * @var array
     */
    protected $_userGroups;

    /**
     * Attempts to return a concrete Group instance based on $driver.
     *
     * @param mixed $driver  The type of concrete Group subclass to return.
     * @param array $params  A hash containing any additional configuration or
     *                       connection parameters a subclass might need.
     *
     * @return Horde_Group  The newly created concrete Group instance.
     * @throws Horde_Group_Exception
     */
    static public function factory($driver = '', $params = null)
    {
        if (is_null($params)) {
            $params = Horde::getDriverConfig('group', $driver);
        }

        if (!$driver) {
            $class = __CLASS__;
        } else {
            $class = __CLASS__ . '_' . Horde_String::ucfirst(Horde_String::lower(basename($driver)));
        }
        if (class_exists($class)) {
            return new $class($params);
        }

        throw new Horde_Group_Exception('Class definition of ' . $class . ' not found.');
    }

    /**
     * Constructor.
     *
     * @param array $params  Configuration parameters.
     */
    public function __construct(array $params = array())
    {
        $this->_params = $params;
        $this->__wakeup();
    }

    /**
     * Stores the object in the session cache.
     */
    public function shutdown()
    {
        $GLOBALS['injector']->getInstance('Horde_SessionObjects')->overwrite('horde_group', $this, false);
    }

    /**
     * Returns the properties that need to be serialized.
     *
     * @return array  List of serializable properties.
     */
    public function __sleep()
    {
        return array_diff(array_keys(get_class_vars(__CLASS__)), array('_datatree'));
    }
    /**
     * Initializes the object.
     *
     * @throws Horde_Group_Exception
     */
    public function __wakeup()
    {
        global $conf;

        if (empty($conf['datatree']['driver'])) {
            throw new Horde_Group_Exception('You must configure a DataTree backend to use Groups.');
        }

        $driver = $conf['datatree']['driver'];
        $this->_datatree = DataTree::singleton($driver, array_merge(Horde::getDriverConfig('datatree', $driver), array('group' => 'horde.groups')));

        foreach (array_keys($this->_groupCache) as $name) {
            $this->_groupCache[$name]->setGroupOb($this);
            $this->_groupCache[$name]->setDataTree($this->_datatree);
        }
    }

    /**
     * Returns a new group object.
     *
     * @param string $name    The group's name.
     * @param string $parent  The group's parent's name.
     *
     * @return Horde_Group_DataTreeObject  A new group object.
     * @throws Horde_Group_Exception
     */
    public function newGroup($name, $parent = self::ROOT)
    {
        if ($parent != self::ROOT) {
            $name = $this->getGroupName($parent) . ':' . DataTree::encodeName($name);
        }

        $group = new Horde_Group_DataTreeObject($name);
        $group->setGroupOb($this);

        return $group;
    }

    /**
     * Returns a group object corresponding to the named group, with the users
     * and other data retrieved appropriately.
     *
     * @param string $name The name of the group to retrieve.
     *
     * @return Horde_Group_DataTreeObject  Group object.
     */
    public function getGroup($name)
    {
        if (!isset($this->_groupCache[$name])) {
            $this->_groupCache[$name] = $this->_datatree->getObject($name, 'Horde_Group_DataTreeObject');
            if (!($this->_groupCache[$name] instanceof  PEAR_Error)) {
                $this->_groupCache[$name]->setGroupOb($this);
                $this->_groupMap[$this->_groupCache[$name]->getId()] = $name;
            }
        }

        return $this->_groupCache[$name];
    }

    /**
     * Returns a group object corresponding to the given unique ID, with the
     * users and other data retrieved appropriately.
     *
     * @param integer $cid  The unique ID of the group to retrieve.
     *
     * @return Horde_Group_DataTreeObject  Group object.
     */
    public function getGroupById($cid)
    {
        if (isset($this->_groupMap[$cid])) {
            $group = $this->_groupCache[$this->_groupMap[$cid]];
        } else {
            $group = $this->_datatree->getObjectById($cid, 'Horde_Group_DataTreeObject');
            if (!($group instanceof PEAR_Error)) {
                $group->setGroupOb($this);
                $name = $group->getName();
                $this->_groupCache[$name] = &$group;
                $this->_groupMap[$cid] = $name;
            }
        }

        return $group;
    }

    /**
     * Returns a globally unique ID for a group.
     *
     * @param DataTreeObject_Group $group  The group.
     *
     * @return string  A GUID referring to $group.
     */
    public function getGUID($group)
    {
        return 'horde:group:' . $this->getGroupId($group);
    }

    /**
     * Adds a group to the groups system. The group must first be created with
     * newGroup(), and have any initial users added to it, before this
     * function is called.
     *
     * @param Horde_Group_DataTreeObject $group  The new group object.
     *
     * @throws Horde_Group_Exception
     * @throws Horde_History_Exception
     * @throws InvalidArgumentException
     */
    public function addGroup(Horde_Group_DataTreeObject $group)
    {
        $result = $this->_datatree->add($group);
        if ($result instanceof PEAR_Error) {
            throw new Horde_Group_Exception($result);
        }

        $id = $group->getId();
        $name = $group->getName();
        $this->_groupCache[$name] = &$group;
        $this->_groupMap[$id] = $name;
        if (isset($this->_groupList)) {
            $this->_groupList[$id] = $name;
        }

        /* Log the addition of the group in the history log. */
        $GLOBALS['injector']->getInstance('Horde_History')->log($this->getGUID($group), array('action' => 'add'), true);

        return $result;
    }

    /**
     * Stores updated data - users, etc. - of a group to the backend system.
     *
     * @param Horde_Group_DataTreeObject $group  The group to update.
     *
     * @throws Horde_History_Exception
     * @throws InvalidArgumentException
     */
    public function updateGroup(Horde_Group_DataTreeObject $group)
    {
        $result = $this->_datatree->updateData($group);
        if ($result instanceof PEAR_Error) {
            return $result;
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
     * @param Horde_Group_DataTreeObject $group  The group to remove.
     * @param boolean $force                     Force to remove every child?
     *
     * @throws Horde_History_Exception
     * @throws InvalidArgumentException
     */
    public function removeGroup(Horde_Group_DataTreeObject $group,
                                $force = false)
    {
        $id = $group->getId();
        unset($this->_groupMap[$id]);
        if (isset($this->_groupList)) {
            unset($this->_groupList[$id]);
        }
        unset($this->_groupCache[$group->getName()]);

        $GLOBALS['injector']->getInstance('Horde_History')->log($this->getGUID($group), array('action' => 'delete'), true);

        return $this->_datatree->remove($group, $force);
    }

    /**
     * Retrieves the name of a group.
     *
     * @param integer|Horde_Group_DataTreeObject $gid  The id of the group or
     *                                                 the group object to
     *                                                 retrieve the name for.
     *
     * @return string  The group's name.
     */
    public function getGroupName($gid)
    {
        if ($gid instanceof Horde_Group_DataTreeObject) {
            $gid = $gid->getId();
        }

        if (isset($this->_groupMap[$gid])) {
            return $this->_groupMap[$gid];
        }
        if (isset($this->_groupList[$gid])) {
            return $this->_groupList[$gid];
        }

        return $this->_datatree->getName($gid);
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
        return $this->_datatree->getShortName($group);
    }

    /**
     * Retrieves the ID of a group.
     *
     * @param integer|Horde_Group_DataTreeObject $gid  The id of the group or
     *                                                 the group object to
     *                                                 retrieve the ID for.
     *
     * @return integer  The group's ID.
     */
    public function getGroupId($group)
    {
        if ($group instanceof Horde_Group_DataTreeObject) {
            $group = $group->getName();
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

        return $this->_datatree->getId($group);
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

        return $this->_datatree->exists($group);
    }

    /**
     * Returns a tree of the parents of a child group.
     *
     * @param integer $gid  The id of the child group.
     *
     * @return array  The group parents tree, with groupnames as the keys.
     * @throws Horde_Group_Exception
     */
    public function getGroupParents($gid)
    {
        if (!isset($this->_parentTree[$gid])) {
            $name = $this->getGroupName($gid);
            $parents = $this->_datatree->getParents($name);
            if ($parents instanceof PEAR_Error) {
                throw new Horde_Group_Exception($parents);
            }
            $this->_parentTree[$gid] = $parents;
        }

        return $this->_parentTree[$gid];
    }

    /**
     * Returns the single parent ID of the given group.
     *
     * @param integer $gid  The DataTree ID of the child group.
     *
     * @return integer  The parent of the given group.
     * @throws Horde_Group_Exception
     */
    public function getGroupParent($gid)
    {
        if (!isset($this->_groupParents[$gid])) {
            $parent = $this->_datatree->getParentById($gid);
            if ($parent instanceof PEAR_Error) {
                throw new Horde_Group_Exception($parent);
            }
            $this->_groupParents[$gid] = $parent;
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
     * @throws Horde_Group_Exception
     */
    public function getGroupParentList($gid)
    {
        if (!isset($this->_groupParentList[$gid])) {
            $parents = $this->_datatree->getParentList($gid);
            if ($parents instanceof PEAR_Error) {
                throw new Horde_Group_Exception($parents);
            }
            $this->_groupParentList[$gid] = $parents;
        }

        return $this->_groupParentList[$gid];
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
        if ($refresh || !isset($this->_groupList)) {
            $this->_groupList = $this->_datatree->get(DATATREE_FORMAT_FLAT, self::ROOT, true);
            unset($this->_groupList[self::ROOT]);
        }

        return $this->_groupList;
    }

    /**
     * Get a list of every user that is a part of this group ONLY.
     *
     * @param integer $gid  The ID of the group.
     *
     * @return array  The user list.
     * @throws Horde_Group_Exception
     */
    public function listUsers($gid)
    {
        $groupOb = $this->getGroupById($gid);

        if (!isset($groupOb->data['users']) ||
            !is_array($groupOb->data['users'])) {
            return array();
        }

        return array_keys($groupOb->data['users']);
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
            $groups = $this->_datatree->get(DATATREE_FORMAT_FLAT, $this->getGroupName($gid), true);
            if ($groups instanceof PEAR_Error) {
                throw new Horde_Group_Exception($groups);
            }
            $this->_subGroups[$gid] = array_keys($groups);
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
        if (!isset($this->_userGroups[$user])) {
            $criteria = array(
                'AND' => array(
                    array('field' => 'name', 'op' => '=', 'test' => 'user'),
                    array('field' => 'key', 'op' => '=', 'test' => $user)));
            $groups = $this->_datatree->getByAttributes($criteria);
            if ($groups instanceof PEAR_Error) {
                throw new Horde_Group_Exception($groups);
            }

            if ($parentGroups) {
                foreach ($groups as $id => $g) {
                    $parents = $this->_datatree->getParentList($id);
                    if ($parents instanceof PEAR_Error) {
                        throw new Horde_Group_Exception($parents);
                    }
                    $groups += $parents;
                }
            }

            $this->_userGroups[$user] = $groups;
        }

        return $this->_userGroups[$user];
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
        if (!$this->exists($this->getGroupName($gid))) {
            return false;
        } elseif ($subgroups) {
            try {
                $groups = $this->getGroupMemberships($user, true);
            } catch (Horde_Group_Exception $e) {
                Horde::logMessage($e, 'ERR');
                return false;
            }

            return !empty($groups[$gid]);
        } else {
            try {
                $users = $this->listUsers($gid);
            } catch (Horde_Group_Exception $e) {
                Horde::logMessage($e, 'ERR');
                return false;
            }
            return in_array($user, $users);
        }
    }

    /**
     * Returns the nesting level of the given group. 0 is returned for any
     * object directly below self::ROOT.
     *
     * @param integer $gid  The DataTree ID of the group.
     *
     * @return The DataTree level of the group.
     */
    public function getLevel($gid)
    {
        return substr_count($this->getGroupName($gid), ':');
    }

}
