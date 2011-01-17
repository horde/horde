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
     * Returns a new share object.
     *
     * @param string $owner The share owner name.
     * @param string $name  The share's name.
     *
     * @return Horde_Share_Object_Sql_Hierarchical  A new share object.
     */
    protected function _newShare($owner, $name = '')
    {
        return $this->_createObject();
    }

    /**
     * Returns an array of all shares that $userid has access to.
     *
     * @param string $userid  The userid of the user to check access for.
     * @param array $params   Additional parameters for the search.
     *<pre>
     *  'perm'          Require this level of permissions. Horde_Perms constant.
     *  'attribtues'    Restrict shares to these attributes. A hash or username.
     *  'from'          Offset. Start at this share
     *  'count'         Limit.  Only return this many.
     *  'sort_by'       Sort by attribute.
     *  'direction'     Sort by direction.
     *  'parent'        Start at this share in the hierarchy. Either share_id or
     *                  Horde_Share_Object
     *  'all_levels'    List all levels or just the direct children of parent?
     *</pre>
     *
     * @return array  The shares the user has access to.
     * @throws Horde_Share_Exception
     */
    public function listShares($userid, array $params = array())
    {
        $params = array_merge(array('perm' => Horde_Perms::SHOW,
                                    'attributes' => null,
                                    'from' => 0,
                                    'count' => 0,
                                    'sort_by' => null,
                                    'direction' => 0,
                                    'parent' => null,
                                    'all_levels' => true),
                              $params);
        $key = md5(serialize(array($userid, $params)));
        if (!empty($this->_listcache[$key])) {
            return $this->_listcache[$key];
        }
        $shares = array();
        if (is_null($params['sort_by'])) {
            $sortfield = 's.share_id';
        } elseif ($params['sort_by'] == 'owner' || $params['sort_by'] == 'id') {
            $sortfield = 's.share_' . $params['sort_by'];
        } else {
            $sortfield = 's.attribute_' . $params['sort_by'];
        }

        $query = 'SELECT DISTINCT s.* '
            . $this->getShareCriteria($userid, $params['perm'], $params['attributes'], $params['parent'], $params['all_levels'])
            . ' ORDER BY ' . $sortfield
            . (($params['direction'] == 0) ? ' ASC' : ' DESC');

        $query = $this->_db->addLimitOffset($query, array('limit' => $params['count'], 'offset' => $params['from']));
        try {
            $rows = $this->_db->selectAll($query);
        } catch (Horde_Db_Exception $e) {
            throw new Horde_Share_Exception($e->getMessage());
        }

        $users = array();
        $groups = array();
        foreach ($rows as $share) {
            $shares[(int)$share['share_id']] = $this->_fromDriverCharset($share);
            if ($this->_hasUsers($share)) {
                $users[] = (int)$share['share_id'];
            }
            if ($this->_hasGroups($share)) {
                $groups[] = (int)$share['share_id'];
            }
        }

        // Get users permissions
        if (!empty($users)) {
            $query = 'SELECT share_id, user_uid, perm FROM ' . $this->_table
                     . '_users WHERE share_id IN (' . str_repeat('?,', count($users) - 1) . '?)';

            try {
                $rows = $this->_db->selectAll($query, $users);
            } catch (Horde_Db_Exception $e) {
                throw new Horde_Share_Exception($e->getMessage());
            }
            foreach ($rows as $share) {
                    $shares[$share['share_id']]['perm']['users'][$share['user_uid']] = (int)$share['perm'];
            }
        }

        // Get groups permissions
        if (!empty($groups)) {
            $query = 'SELECT share_id, group_uid, perm FROM ' . $this->_table
                     . '_groups WHERE share_id IN (' . str_repeat('?,', count($groups) - 1) . '?)';
            try {
                $rows = $this->_db->selectAll($query, $groups);
            } catch (Horde_Db_Exception $e) {
                throw new Horde_Share_Exception($e->getMessage());
            }
            foreach ($rows as $share) {
                $shares[$share['share_id']]['perm']['groups'][$share['group_uid']] = (int)$share['perm'];
            }
        }

        $sharelist = array();
        foreach ($shares as $id => $data) {
            $this->_getSharePerms($data);
            $sharelist[$id] = $this->_createObject($data);
        }
        unset($shares);

        // Run the results through the callback, if configured.
        if (!empty($this->_callbacks['list'])) {
            $sharelist = $this->runCallback('list', array($userid, $sharelist, $params));
        }

        $this->_listcache[$key] = $sharelist;

        return $sharelist;
    }

    /**
     * Returns an array of criteria for querying shares.
     *
     * @TODO:
     *        remove ignorePerms param, simply set perm to null for this
     *
     * @param string $userid      The userid of the user to check access for.
     * @param integer $perm       The level of permissions required. Set to null
     *                            to skip permission filtering.
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
    public function getShareCriteria($userid, $perm = Horde_Perms::SHOW,
                                     $attributes = null, $parent = null,
                                     $allLevels = true)
    {
        $query = $where = '';
        if (!is_null($perm)) {
            list($query, $where) = $this->_getUserAndGroupCriteria($userid, $perm);
        }
        $query = ' FROM ' . $this->_table . ' s ' . $query;

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
                if (is_array($value)) {
                    $value = array_map(array($this->_db, 'quote'), $value);
                    $where .= ' AND ' . $key . ' IN (' . implode(', ', $value) . ')';
                } else {
                    $where .= ' AND ' . $key . ' = ' . $this->_db->quote($value);
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

        if (!empty($where_parent)) {
            if (empty($where)) {
                $where = $where_parent;
            } else {
                $where = '(' . $where . ') AND ' . $where_parent;
            }
        }

        return $query . ' WHERE ' . $where;
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
     * @throws Horde_Share_Exception
     */
    public function listOwners($perm = Horde_Perms::SHOW, $parent = null, $allLevels = true,
                               $from = 0, $count = 0)
    {
        $sql = 'SELECT DISTINCT(s.share_owner) '
            . $this->getShareCriteria($this->_user, $perm, null, $parent, $allLevels);

        if ($count) {
            $sql = $this->_db->addLimitOffset($sql, array('limit' => $count, 'offset' => $from));
        }

        try {
            $allowners = $this->_db->selectValues($sql);
        } catch (Horde_Db_Exception $e) {
            throw new Horde_Share_Exception($e);
        }

        $owners = array();
        foreach ($allowners as $owner) {
            if ($this->countShares($this->_user, $perm, $owner, $parent, $allLevels)) {
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
     * @throws Horde_Share_Exception
     */
    public function countOwners($perm = Horde_Perms::SHOW, $parent = null, $allLevels = true)
    {
        $sql = 'SELECT COUNT(DISTINCT(s.share_owner)) '
            . $this->getShareCriteria($this->_user, $perm, null, $parent, $allLevels);

        try {
            $results = $this->_db->selectValue($sql);
        } catch (Horde_Db_Exception $e) {
            throw new Horde_Share_Exception($e);
        }

        return $results;
    }

    /**
     * Returns a share's direct parent object.
     *
     * @param Horde_Share_Object $child  The share to get parent for.
     *
     * @return Horde_Share_Object The parent share, if it exists.
     */
    public function getParent(Horde_Share_Object $child)
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
            $share = $this->_getShareById($cid);
            $this->_cache[$cid] = $share;
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
     */
    public function getShares(array $cids)
    {
        $all_shares = array();
        $missing_ids = array();
        foreach ($cids as $cid) {
            if (isset($this->_cache[$cid])) {
                $all_shares[] = $this->_cache[$cid];
            } else {
                $missing_ids[] = $cid;
            }
        }

        if (count($missing_ids)) {
            $shares = $this->_getShares($missing_ids);
            foreach (array_keys($shares) as $key) {
                $this->_cache[$key] = $shares[$key];
                $all_shares[$key] = $this->_cache[$key];
            }
        }

        return $all_shares;
    }

    /**
     * Removes a share from the shares system permanently. This will recursively
     * delete all child shares as well.
     *
     * @param Horde_Share_Object $share  The share to remove.
     *
     * @return boolean
     */
    public function removeShare(Horde_Share_Object $share)
    {
        /* Get the list of all $share's children */
        $children = $share->getChildren(null, null, true);

        /* Remove share from the caches. */
        $this->_cache = array();
        $this->_listCache = array();
        foreach ($children as $child) {
            $this->_removeShare($child);
        }

        $this->_removeShare($share);
    }

    /**
     * Returns an array of Horde_Share_Object objects corresponding
     * to the given set of unique IDs, with the details retrieved
     * appropriately.
     *
     * @param array $ids  The array of ids to retrieve.
     *
     * @return array  The requested shares keyed be share_id.
     * @throws Horde_Share_Exception
     */
    protected function _getShares(array $ids)
    {
        return parent::_getShares($ids, 'share_id');
    }

    /**
     * Override the Horde_Share base class to avoid any confusion
     *
     * @throws Horde_Share_Exception
     */
    public function getShare($name)
    {
        throw new Horde_Share_Exception(Horde_Share_Translation::t("Share names are not supported in this driver"));
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
     * @throws Horde_Share_Exception
     */
    public function countShares($userid, $perm = Horde_Perms::SHOW, $attributes = null,
                         $parent = null, $allLevels = true)
    {
        $query = 'SELECT COUNT(DISTINCT s.share_id) '
            . $this->getShareCriteria($userid, $perm, $attributes, $parent, $allLevels);

        try {
            $this->_db->selectValue($query);
        } catch (Horde_Db_Exception $e) {
            throw new Horde_Share_Exception($e);
        }

        return $this->_db->selectValue($query);
    }

    /**
     * Checks if a share exists in the system.
     *
     * @param string $share  The share to check.
     *
     * @return boolean  True if the share exists.
     * @throws Horde_Share_Exception
     */
    protected function _exists($share)
    {
        try {
            return (boolean)$this->_db->selectOne('SELECT 1 FROM ' . $this->_table . ' WHERE share_id = ?', array($share));
        } catch (Horde_Db_Exception $e) {
            throw new Horde_Share_Exception($e);
        }
    }

}
