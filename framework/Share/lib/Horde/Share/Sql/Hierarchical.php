<?php
/**
 * Implementation of Horde_Share class for shared objects that are hierarchical
 * in nature.
 *
 * @author  Duck <duck@obala.net>
 * @author  Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Horde_Share
 */
class Horde_Share_Sql_Hierarchical extends Horde_Share_Sql
{
    /**
     * The Horde_Share_Object subclass to instantiate objects as
     *
     * @var string
     */
    protected $_shareObject = 'Horde_Share_Object_Sql_Hierarchical';

    /**
     * Override new share creation so we can allow for shares with empty
     * share_names.
     *
     */
    public function newShare($name = '')
    {
        $share = $this->_newShare();
        $share->setShareOb($this);
        //@TODO: inject the Auth object
        $share->set('owner', $GLOBALS['registry']->getAuth());

        return $share;
    }

    /**
     * Returns a new share object.
     *
     * @param string $name  The share's name.
     *
     * @return Horde_Share_Object_sql  A new share object.
     */
    protected function _newShare()
    {
        $share = new $this->_shareObject();
        return $share;
    }

    /**
     * Returns an array of all shares that $userid has access to.
     *
     * @param string $userid      The userid of the user to check access for.
     * @param integer $perm       The level of permissions required.
     * @param mixed $attributes   Restrict the shares counted to those
     *                            matching $attributes. An array of
     *                            attribute/values pairs or a share owner
     *                            username.
     * @param integer $from       The share to start listing from.
     * @param integer $count      The number of shares to return.
     * @param string $sort_by     The field to sort by
     * @param integer $direction  The sort direction
     * @param mixed $parent       Either a share_id, Horde_Share_Object or null.
     * @param boolean $alllevels  List all levels or just the direct children
     *                            of $parent?
     *
     * @return mixed  The shares the user has access to
     * @throws Horde_Share_Exception
     */
    public function &listShares($userid, $perm = Horde_Perms::SHOW, $attributes = null,
                                $from = 0,  $count = 0, $sort_by = null,
                                $direction = 0, $parent = null,
                                $allLevels = true, $ignorePerms = false)
    {
        $shares = array();
        if (is_null($sort_by)) {
            $sortfield = 's.share_id';
        } elseif ($sort_by == 'owner' || $sort_by == 'id') {
            $sortfield = 's.share_' . $sort_by;
        } else {
            $sortfield = 's.attribute_' . $sort_by;
        }

        $query = 'SELECT DISTINCT s.* '
                 . $this->getShareCriteria($userid, $perm, $attributes,
                                            $parent, $allLevels, $ignorePerms)
                 . ' ORDER BY ' . $sortfield
                 . (($direction == 0) ? ' ASC' : ' DESC');
        if ($from > 0 || $count > 0) {
            $this->_db->setLimit($count, $from);
        }

        Horde::logMessage('Query By Horde_Share_sql_hierarchical: ' . $query, 'DEBUG');
        $result = $this->_db->query($query);
        if ($result instanceof PEAR_Error) {
            Horde::logMessage($result, 'ERR');
            throw new Horde_Share_Exception($result->getMessage());
        } elseif (empty($result)) {
            return array();
        }

        $users = array();
        $groups = array();
        while ($share = $result->fetchRow(MDB2_FETCHMODE_ASSOC)) {
            $shares[(int)$share['share_id']] = $this->_fromDriverCharset($share);
            if ($this->_hasUsers($share)) {
                $users[] = (int)$share['share_id'];
            }
            if ($this->_hasGroups($share)) {
                $groups[] = (int)$share['share_id'];
            }
        }
        $result->free();

        // Get users permissions
        if (!empty($users)) {
            $query = 'SELECT share_id, user_uid, perm FROM ' . $this->_table
                     . '_users WHERE share_id IN (' . implode(', ', $users)
                     . ')';
            $result = $this->_db->query($query);
            if ($result instanceof PEAR_Error) {
                Horde::logMessage($result, 'ERR');
                throw new Horde_Share_Exception($result->getMessage());
            } elseif (!empty($result)) {
                while ($share = $result->fetchRow(MDB2_FETCHMODE_ASSOC)) {
                    $shares[$share['share_id']]['perm']['users'][$share['user_uid']] = (int)$share['perm'];
                }
                $result->free();
            }
        }

        // Get groups permissions
        if (!empty($groups)) {
            $query = 'SELECT share_id, group_uid, perm FROM ' . $this->_table
                     . '_groups WHERE share_id IN (' . implode(', ', $groups)
                     . ')';
            $result = $this->_db->query($query);
            if ($result instanceof PEAR_Error) {
                Horde::logMessage($result, 'ERR');
                throw new Horde_Share_Exception($result->getMessage());
            } elseif (!empty($result)) {
                while ($share = $result->fetchRow(MDB2_FETCHMODE_ASSOC)) {
                    $shares[$share['share_id']]['perm']['groups'][$share['group_uid']] = (int)$share['perm'];
                }
                $result->free();
            }
        }

        $sharelist = array();
        foreach ($shares as $id => $data) {
            $this->_getSharePerms($data);
            $sharelist[$id] = new $this->_shareObject($data);
            $sharelist[$id]->setShareOb($this);
        }
        unset($shares);

        try {
            return Horde::callHook('share_list', array($userid, $perm, $attributes, $sharelist));
        } catch (Horde_Exception_HookNotSet $e) {}

        return $sharelist;
    }

    /**
     * Returns an array of criteria for querying shares.
     * @access protected
     *
     * @param string $userid      The userid of the user to check access for.
     * @param integer $perm       The level of permissions required.
     * @param mixed $attributes   Restrict the shares returned to those who
     *                            have these attribute values.
     * @param mixed $parent       The share to start searching in.
     *                            (A Horde_Share_Object, share_id or null)
     * @param boolean $allLevels  Return all levels, or just the direct
     *                            children of $parent? Defaults to all levels.
     *
     * @return string  The criteria string for fetching this user's shares.
     * @throws Horde_Share_Exception
     */
    public function getShareCriteria($userid, $perm = Horde_Perms::SHOW, $attributes = null,
                                         $parent = null, $allLevels = true,
                                         $ignorePerms = false)
    {
        static $criteria;

        if ($parent instanceof Horde_Share_Object) {
            $parent_id = $parent->getId();
        } else {
            $parent_id = $parent;
        }
        $key = $userid . $perm . $parent_id . $allLevels
               . (is_array($attributes) ? serialize($attributes) : $attributes);
        if (isset($criteria[$key])) {
            return $criteria[$key];
        }

        $query = ' FROM ' . $this->_table . ' s ';
        $where = '';

        if (!$ignorePerms) {
            if (empty($userid)) {
                $where = '(' . Horde_SQL::buildClause($this->_db, 's.perm_guest', '&', $perm) . ')';
            } else {
                // (owner == $userid)
                $where = 's.share_owner = ' . $this->_db->quote($userid);

                // (name == perm_creator and val & $perm)
                $where .= ' OR (' . Horde_SQL::buildClause($this->_db, 's.perm_creator', '&', $perm) . ')';

                // (name == perm_creator and val & $perm)
                $where .= ' OR (' . Horde_SQL::buildClause($this->_db, 's.perm_default',  '&', $perm) . ')';

                // (name == perm_users and key == $userid and val & $perm)
                $query .= ' LEFT JOIN ' . $this->_table . '_users u ON u.share_id = s.share_id';
                $where .= ' OR ( u.user_uid = ' .  $this->_write_db->quote($userid)
                . ' AND (' . Horde_SQL::buildClause($this->_db, 'u.perm', '&', $perm) . '))';

                // If the user has any group memberships, check for those also.
                // @TODO: Inject the group driver
                try {
                    $group = Horde_Group::singleton();
                    $groups = $group->getGroupMemberships($userid, true);
                    if ($groups) {
                        // (name == perm_groups and key in ($groups) and val & $perm)
                        $ids = array_keys($groups);
                        $group_ids = array();
                        foreach ($ids as $id) {
                            $group_ids[] = $this->_db->quote((string)$id);
                        }
                        $query .= ' LEFT JOIN ' . $this->_table . '_groups g ON g.share_id = s.share_id';
                        $where .= ' OR (g.group_uid IN (' . implode(',', $group_ids) . ')'
                            . ' AND (' . Horde_SQL::buildClause($this->_db, 'g.perm', '&', $perm) . '))';
                    }
                } catch (Horde_Group_Exception $e) {}
            }
        }

        /* Convert to driver's keys */
        $attributes = $this->_toDriverKeys($attributes);

        /* ...and to driver charset */
        $attributes = $this->toDriverCharset($attributes);

        if (is_array($attributes)) {
            // Build attribute/key filter.
            if (!empty($where)) {
                $where = ' (' . $where . ') ';
            }
            foreach ($attributes as $key => $value) {
                $where .= ' AND ' . $key;
                if (is_array($value)) {
                    $where .= ' ' . $value[0]. ' ' . $this->_db->quote($value[1]);
                } else {
                    $where .= ' = ' . $this->_db->quote($value);
                }
            }
        } elseif (!empty($attributes)) {
            // Restrict to shares owned by the user specified
            $where = (!empty($where) ? ' (' . $where . ') AND ' : ' ') . 's.share_owner = ' . $this->_db->quote($attributes);
        }

        // See if we need to filter by parent or get the parent object
        if ($parent != null) {
            if (!($parent instanceof Horde_Share_Object)) {
                $parent = $this->getShareById($parent);
                if ($parent instanceof PEAR_Error) {
                    throw new Horde_Share_Exception($parent->getMessage());
                }
            }

            // Need to append the parent's share id to the list of parents in
            // order to search the share_parents field.
            $parents = $parent->get('parents') . ':' . $parent->getId();
            if ($allLevels) {
                $where_parent = '(share_parents = ' . $this->_db->quote($parents)
                        . ' OR share_parents LIKE ' . $this->_db->quote($parents . ':%') . ')';
            } else {
                $where_parent = 's.share_parents = ' . $this->_db->quote($parents);
            }
        } elseif (!$allLevels) {
            // No parents, and we only want the root.
            $where_parent = "(s.share_parents = '' OR s.share_parents IS NULL)";
        }

        if (empty($where_parent)) {
            $criteria[$key] = $query . ' WHERE ' . $where;
        } else {
            if (!empty($where)) {
                $criteria[$key] = $query . ' WHERE (' . $where . ') AND ' . $where_parent;
            } else {
                $criteria[$key] = $query . ' WHERE ' . $where_parent;
            }
        }

        return $criteria[$key];
    }

    /**
     * Return a list of users who have shares with the given permissions
     * for the current user.
     *
     * @param integer $perm       The level of permissions required.
     * @param mixed  $parent      The parent share to start looking in.
     *                            (Horde_Share_Object, share_id, or null)
     * @param boolean $allLevels  Return all levels, or just the direct
     *                            children of $parent? Defaults to all levels.
     * @param integer $from       The user to start listing at.
     * @param integer $count      The number of users to return.
     *
     * @return array  List of users.
     */
    public function listOwners($perm = Horde_Perms::SHOW, $parent = null, $allLevels = true,
                               $from = 0, $count = 0)
    {
        $sql = 'SELECT DISTINCT(s.share_owner) '
                . $this->getShareCriteria($GLOBALS['registry']->getAuth(), $perm, null,
                                           $parent, $allLevels);

        if ($count) {
            $this->_db->setLimit($count, $from);
        }

        $allowners = $this->_db->queryCol($sql);
        if ($allowners instanceof PEAR_Error) {
             Horde::logMessage($allowners, 'ERR');
             throw new Horde_Share_Exception($allowners->getMessage());
        }

        $owners = array();
        foreach ($allowners as $owner) {
            if ($this->countShares($GLOBALS['registry']->getAuth(), $perm, $owner, $parent,
                                   $allLevels)) {

                $owners[] = $owner;
            }
        }

        return $owners;
    }

    /**
     * Count the number of users who have shares with the given permissions
     * for the current user.
     *
     * @param integer $perm       The level of permissions required.
     * @param mixed $parent       The parent share to start looking in.
     *                            (Horde_Share_Object, share_id, or null).
     * @param boolean $allLevels  Return all levels, or just the direct
     *                            children of $parent?
     *
     * @return integer  Number of users.
     */
    public function countOwners($perm = Horde_Perms::SHOW, $parent = null, $allLevels = true)
    {
        $sql = 'SELECT COUNT(DISTINCT(s.share_owner)) '
               . $this->getShareCriteria($GLOBALS['registry']->getAuth(), $perm, null, $parent,
                                          $allLevels);

        return $this->_db->queryOne($sql);
    }

    /**
     * Returns a share's direct parent object.
     *
     * @param Horde_Share_Object $share  The share to get parent for.
     *
     * @return Horde_Share_Object The parent share, if it exists.
     */
    public function getParent($child)
    {
        $parents = $child->get('parents');

        // No parents, this is at the root.
        if (empty($parents)) {
            return null;
        }
        $parents = explode(':', $parents);
        return $this->getShareById(array_pop($parents));
    }

    /**
     * Returns a Horde_Share_Object object corresponding to the given unique
     * ID, with the details retrieved appropriately.
     *
     * @param string $cid  The id of the share to retrieve.
     *
     * @return Horde_Share_Object  The requested share.
     */
    public function getShareById($cid)
    {
        if (!isset($this->_cache[$cid])) {
            $share = &$this->_getShareById($cid);
            if ($share instanceof PEAR_Error) {
                throw new Horde_Share_Exception($share->getMessage());
            }
            $share->setShareOb($this);
            $this->_cache[$cid] = &$share;
        }

        return $this->_cache[$cid];
    }

    /**
     * Returns an array of Horde_Share_Object objects corresponding to the
     * given set of unique IDs, with the details retrieved appropriately.
     *
     * @param array $cids  The array of ids to retrieve.
     *
     * @return array  The requested shares keyed by share_id.
     * @throws Horde_Share_Exception
     */
    public function getShares($cids)
    {
        $all_shares = array();
        $missing_ids = array();
        foreach ($cids as $cid) {
            if (isset($this->_cache[$cid])) {
                $all_shares[] = &$this->_cache[$cid];
            } else {
                $missing_ids[] = $cid;
            }
        }

        if (count($missing_ids)) {
            $shares = &$this->_getShares($missing_ids);
            if ($shares instanceof PEAR_Error) {
                throw new Horde_Share_Exception($shares->getMessage());
            }

            foreach (array_keys($shares) as $key) {
                $this->_cache[$key] = &$shares[$key];
                $this->_cache[$key]->setShareOb($this);
                $all_shares[$key] = &$this->_cache[$key];
            }
        }

        return $all_shares;
    }

   /**
     * Removes a share from the shares system permanently. This will recursively
     * delete all child shares as well.
     *
     * @param Horde_Share_Object $share  The share to remove.
     * @throws Horde_Exception
     */
    public function removeShare(Horde_Share_Object $share)
    {
        try {
            Horde::callHook('share_remove', array($share));
        } catch (Horde_Exception_HookNotSet $e) {}

        /* Get the list of all $share's children */
        $children = $share->getChildren(null, true);

        /* Remove share from the caches. */
        $id = $share->getId();
        $this->_cache = array();
        $this->_listCache = array();

        foreach ($children as $child) {
            $result = $this->_removeShare($child);
            if ($result instanceof PEAR_Error) {
                throw new Horde_Share_Exception($result->getMessage());
            }
        }

        return $this->_removeShare($share);
    }

    /**
     * Returns an array of Horde_Share_Object_sql objects corresponding
     * to the given set of unique IDs, with the details retrieved
     * appropriately.
     *
     * @param array $cids  The array of ids to retrieve.
     *
     * @return array  The requested shares keyed by share_id.
     */
    protected function _getShares($ids)
    {
        $shares = array();
        $query = 'SELECT * FROM ' . $this->_table . ' WHERE share_id IN (' . implode(', ', $ids) . ')';
        $result = $this->_db->query($query);
        if ($result instanceof PEAR_Error) {
            Horde::logMessage($result, 'ERR');
            throw new Horde_Share_Exception($result->getMessage());
        } elseif (empty($result)) {
            return array();
        }

        $groups = array();
        $users = array();
        while ($share = $result->fetchRow(MDB2_FETCHMODE_ASSOC)) {
            $shares[(int)$share['share_id']] = $this->_fromDriverCharset($share);
            if ($this->_hasUsers($share)) {
                $users[] = (int)$share['share_id'];
            }
            if ($this->_hasGroups($share)) {
                $groups[] = (int)$share['share_id'];
            }
        }
        $result->free();

        // Get users permissions
        if (!empty($users)) {
            $query = 'SELECT share_id, user_uid, perm FROM ' . $this->_table . '_users '
                    . ' WHERE share_id IN (' . implode(', ', $users) . ')';
            $result = $this->_db->query($query);
            if ($result instanceof PEAR_Error) {
                Horde::logMessage($result, 'ERR');
                throw new Horde_Share_Exception($result->getMessage());
            } elseif (!empty($result)) {
                while ($share = $result->fetchRow(MDB2_FETCHMODE_ASSOC)) {
                    $shares[$share['share_id']]['perm']['users'][$share['user_uid']] = (int)$share['perm'];
                }
                $result->free();
            }
        }

        // Get groups permissions
        if (!empty($groups)) {
            $query = 'SELECT share_id, group_uid, perm FROM ' . $this->_table . '_groups'
                   . ' WHERE share_id IN (' . implode(', ', $groups) . ')';
            $result = $this->_db->query($query);
            if ($result instanceof PEAR_Error) {
                Horde::logMessage($result, 'ERR');
                throw new Horde_Share_Exception($result->getMessage());
            } elseif (!empty($result)) {
                while ($share = $result->fetchRow(MDB2_FETCHMODE_ASSOC)) {
                    $shares[$share['share_id']]['perm']['groups'][$share['group_uid']] = (int)$share['perm'];
                }
                $result->free();
            }
        }

        $sharelist = array();
        foreach ($shares as $id => $data) {
            $sharelist[$id] = new $this->_shareObject($data);
        }

        return $sharelist;
    }

    /**
     * Override the Horde_Share base class to avoid any confusion
     *
     * @throws Horde_Share_Exception
     */
    public function getShare($name)
    {
        throw new Horde_Share_Exception(_("Share names are not supported in this driver"));
    }

    /**
     * Returns the count of all shares that $userid has access to.
     *
     * @param string  $userid      The userid of the user to check access for.
     * @param integer $perm        The level of permissions required.
     * @param mixed   $attributes  Restrict the shares counted to those
     *                             matching $attributes. An array of
     *                             attribute/values pairs or a share owner
     *                             username.
     * @param mixed  $parent      The share to start searching from
     *                            (Horde_Share_Object, share_id, or null)
     * @param boolean $allLevels  Return all levels, or just the direct
     *                            children of $parent?
     *
     * @return integer  Number of shares the user has access to.
     */
    public function countShares($userid, $perm = Horde_Perms::SHOW, $attributes = null,
                         $parent = null, $allLevels = true)
    {
        $query = 'SELECT COUNT(DISTINCT s.share_id) '
                 . $this->getShareCriteria($userid, $perm, $attributes,
                                            $parent, $allLevels);

        return $this->_db->queryOne($query);
    }

}
