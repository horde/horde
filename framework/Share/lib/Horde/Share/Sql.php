<?php
/**
 * Horde_Share_sql:: provides the sql backend for the horde share
 * driver.
 *
 * Copyright 2008-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Duck <duck@obala.net>
 * @author  Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Horde_Share
 */

/**
 * @package Horde_Share
 */
class Horde_Share_Sql extends Horde_Share_Base
{
    /* Share has user perms */
    const SQL_FLAG_USERS = 1;

    /* Share has group perms */
    const SQL_FLAG_GROUPS = 2;

    /* Serializable version */
    const VERSION = 1;

    /**
     * Handle for the current database connection.
     *
     * @var Horde_Db_Adapter
     */
    protected $_db;

    /**
     * Main share table for the current scope.
     *
     * @var string
     */
    protected $_table;

    /**
     * The Horde_Share_Object subclass to instantiate objects as
     *
     * @var string
     */
    protected $_shareObject = 'Horde_Share_Object_Sql';

    /**
     *
     * @see Horde_Share_Base::__construct()
     */
    public function __construct($app, $user, Horde_Perms $perms, Horde_Group $groups)
    {
        parent::__construct($app, $user, $perms, $groups);
        $this->_table = $this->_app . '_shares';
    }

    /**
     * Set the SQL table name to use for the current scope's share storage.
     *
     * @var string $table  The table name
     */
    public function setTable($table)
    {
        $this->_table = $table;
    }

    /**
     * Get storage table
     *
     * @return string
     */
    public function getTable()
    {
        return $this->_table;
    }

    public function setStorage(Horde_Db_Adapter $db)
    {
        $this->_db = $db;
    }

    /**
     *
     * @return Horde_Db_Adapter
     */
    public function getStorage()
    {
        return $this->_db;
    }

    /**
     * Finds out if the share has user set
     * @TODO: fix method prefix or make protected
     * @param boolean
     */
    public function _hasUsers($share)
    {
        return $share['share_flags'] & self::SQL_FLAG_USERS;
    }

    /**
     * Finds out if the share has user set
     */
    public function _hasGroups($share)
    {
        return $share['share_flags'] & self::SQL_FLAG_GROUPS;
    }

    /**
     * Get users permissions
     *
     * @param array $share Share data array
     *
     * @throws Horde_Share_Exception
     */
    protected function _getShareUsers(&$share)
    {
        if (!$this->_hasUsers($share)) {
            return;
        }

        try {
            $rows = $this->_db->selectAll('SELECT * FROM ' . $this->_table . '_users WHERE share_id = ?', array($share['share_id']));
        } catch (Horde_Db_Exception $e) {
            throw new Horde_Share_Exception($e->getMessage());
        }

        foreach ($rows as $row) {
            $share['perm']['users'] = $this->_buildPermsFromRow($row, 'user_uid');
        }
    }

    /**
     * Get groups permissions
     *
     * @param array $share Share data array
     *
     * @throws Horde_Share_Exception
     */
    protected function _getShareGroups(&$share)
    {
        if (!$this->_hasGroups($share)) {
            return;
        }

        try {
            $rows = $this->_db->selectAll('SELECT * FROM ' . $this->_table . '_groups WHERE share_id = ?', array($share['share_id']));
        } catch (Horde_Db_Exception $e) {
            throw new Horde_Share_Exception($e->getMessage());
        }

        foreach ($rows as $row) {
            $share['perm']['groups'] = $this->_buildPermsFromRow($row, 'group_uid');
        }
    }

    /**
     * Returns a Horde_Share_Object_sql object corresponding to the given
     * share name, with the details retrieved appropriately.
     *
     * @param string $name  The name of the share to retrieve.
     *
     * @return Horde_Share_Object  The requested share.
     * @throws Horde_Exception_NotFound
     * @throws Horde_Share_Exception
     */
    protected function _getShare($name)
    {
        try {
            $results = $this->_db->selectOne('SELECT * FROM ' . $this->_table . ' WHERE share_name = ?', array($name));
        } catch (Horde_Db_Exception $e) {
            throw new Horde_Share_Exception($e->getMessage());
        }
        if (!$results) {
            $this->_logger->err(sprintf('Share name %s not found', $name));
            throw new Horde_Exception_NotFound();
        }
        $data = $this->_fromDriverCharset($results);
        $this->_loadPermissions($data);

        return $this->_createObject($data);
    }

    protected function _createObject(array $data = array())
    {
        $object = new $this->_shareObject($data);
        $this->initShareObject($object);

        return $object;
    }

    /**
     * Helper function to load the permissions data into the share data
     *
     * @param array $data  Array of share attributes
     */
    protected function _loadPermissions(&$data)
    {
        $this->_getShareUsers($data);
        $this->_getShareGroups($data);
        $this->_getSharePerms($data);
    }

    protected function _getSharePerms(&$data)
    {
        $data['perm']['type'] = 'matrix';
        $data['perm']['default'] = isset($data['perm_default']) ? (int)$data['perm_default'] : 0;
        $data['perm']['guest'] = isset($data['perm_guest']) ? (int)$data['perm_guest'] : 0;
        $data['perm']['creator'] = isset($data['perm_creator']) ? (int)$data['perm_creator'] : 0;
        unset($data['perm_creator'], $data['perm_guest'], $data['perm_default']);
    }

    /**
     * Returns a Horde_Share_Object_sql object corresponding to the given
     * unique ID, with the details retrieved appropriately.
     *
     * @param integer $id  The id of the share to retrieve.
     *
     * @return Horde_Share_Object_sql  The requested share.
     * @throws Horde_Share_Exception, Horde_Exception_NotFound
     */
    protected function _getShareById($id)
    {
        try {
            $results = $this->_db->selectOne('SELECT * FROM ' . $this->_table . ' WHERE share_id = ?', array($id));
        } catch (Horde_Db_Exception $e) {
            throw new Horde_Share_Exception($e->getMessage());
        }
        if (!$results) {
            $this->_logger->err(sprintf('Share id %s not found', $id));
            throw new Horde_Exception_NotFound();
        }
        $data = $this->_fromDriverCharset($results);
        $this->_loadPermissions($data);

        return $this->_createObject($data);
    }

    /**
     * Returns an array of Horde_Share_Object objects corresponding
     * to the given set of unique IDs, with the details retrieved
     * appropriately.
     *
     * @param array $ids   The array of ids to retrieve.
     * @param string $key  The column name that should for the list keys.
     *
     * @return array  The requested shares.
     * @throws Horde_Share_Exception
     */
    protected function _getShares(array $ids)
    {
        try {
            $rows = $this->_db->selectAll('SELECT * FROM ' . $this->_table . ' WHERE share_id IN (' . str_repeat('?, ', count($ids) - 1) . '?)', $ids);
        } catch (Horde_Db_Exception $e) {
            throw new Horde_Share_Exception($e->getMessage());
        }

        $sharelist = array();
        foreach ($rows as $share) {
            $this->_loadPermissions($share);
            $sharelist[$share['share_name']] = $this->_createObject($share);
        }

        return $sharelist;
    }

    /**
     * Lists *all* shares for the current app/share, regardless of permissions.
     *
     * This is for admin functionality and scripting tools, and shouldn't be
     * called from user-level code!
     *
     * @return array  All shares for the current app/share.
     * @throws Horde_Share_Exception
     */
    public function listAllShares()
    {
        return $this->_listAllShares();
    }

    /**
     * Lists *all* shares for the current app/share, regardless of permissions.
     *
     * @return array  All shares for the current app/share.
     * @throws Horde_Share_Exception
     */
    protected function _listAllShares()
    {
        $shares = array();

        try {
            $rows = $this->_db->selectAll('SELECT * FROM ' . $this->_table . ' ORDER BY share_name ASC');
        } catch (Horde_Db_Exception $e) {
            throw new Horde_Share_Exception($e->getMessage());
        }

        foreach ($rows as $share) {
            $shares[(int)$share['share_id']] = $this->_fromDriverCharset($share);
        }

        // Get users permissions
        try {
            $rows = $this->_db->selectAll('SELECT * FROM ' . $this->_table . '_users');
        } catch (Horde_Db_Exception $e) {
            throw new Horde_Share_Exception($e);
        }
        foreach ($rows as $row) {
            $shares[$row['share_id']]['perm']['users'] = $this->_buildPermsFromRow($row, 'user_uid');
        }

        // Get groups permissions
        try {
            $rows = $this->_db->selectAll('SELECT * FROM ' . $this->_table . '_groups');
        } catch (Horde_Db_Exception $e) {
            throw new Horde_Share_Exception($e->getMessage());
        }
        foreach ($rows as $row) {
            $shares[$row['share_id']]['perm']['groups'] = $this->_buildPermsFromRow($row, 'group_uid');
        }

        $sharelist = array();
        foreach ($shares as $data) {
            $this->_getSharePerms($data);
            $sharelist[$data['share_name']] = $this->_createObject($data);
        }

        return $sharelist;
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
            $sharelist[$data['share_name']] = $this->_createObject($data);
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
     * Returns an array of all shares that $userid has access to.
     *
     * @param string $userid     The userid of the user to check access for.
     * @param array  $params     See listShares().
     *
     * @return array  The shares the user has access to.
     */
    protected function _listShares($userid, array $params = array())
    {
        // We overwrite listShares(), this method is only implemented because
        // it's abstract in the base class.
    }

    /**
     * Returns an array of all system shares.
     *
     * @return array  All system shares.
     * @throws Horde_Share_Exception
     */
    public function listSystemShares()
    {
        $query = 'SELECT * FROM ' . $this->_table . ' WHERE share_owner IS NULL';
        try {
            $rows = $this->_db->selectAll($query);
        } catch (Horde_Db_Exception $e) {
            throw new Horde_Share_Exception($e->getMessage());;
        }

        $sharelist = array();
        foreach ($rows as $share) {
            $data = $this->_fromDriverCharset($share);
            $this->_getSharePerms($data);
            $sharelist[$data['share_name']] = $this->_createObject($data);
        }

        return $sharelist;
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
    public function countShares($userid, $perm = Horde_Perms::SHOW,
        $attributes = null, $parent = null, $allLevels = true)
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
     * Returns a new share object.
     *
     * @param string $name   The share's name.
     *
     * @return Horde_Share_Object  A new share object
     * @throws InvalidArgumentException
     */
    protected function _newShare($name)
    {
        if (empty($name)) {
            throw new InvalidArgumentException('Share names must be non-empty');
        }

        return $this->_createObject(array('share_name' => $name));
    }

    /**
     * Adds a share to the shares system.
     *
     * The share must first be created with
     * Horde_Share_sql::_newShare(), and have any initial details added
     * to it, before this function is called.
     *
     * @param Horde_Share_Object $share  The new share object.
     */
    protected function _addShare(Horde_Share_Object $share)
    {
        $share->save();
    }

    /**
     * Removes a share from the shares system permanently.
     *
     * @param Horde_Share_Object $share  The share to remove.
     *
     * @throws Horde_Share_Exception
     */
    public function removeShare(Horde_Share_Object $share)
    {
        // First Remove Children
        foreach ($share->getChildren(null, null, true) as $child) {
            $this->removeShare($child);
        }

        // Run the results through the callback, if configured.
        $this->runCallback('remove', array($share));

        /* Remove share from the caches. */
        $id = $share->getId();
        unset($this->_shareMap[$id]);
        unset($this->_cache[$share->getName()]);

        /* Reset caches that depend on unknown criteria. */
        $this->expireListCache();

        $this->_removeShare($share);
    }

    /**
     * Removes a share from the shares system permanently.
     *
     * @param Horde_Share_Object $share  The share to remove.
     *
     * @return boolean
     * @throws Horde_Share_Exception
     */
    protected function _removeShare(Horde_Share_Object $share)
    {
        $params = array($share->getId());
        $tables = array($this->_table,
                        $this->_table . '_users',
                        $this->_table . '_groups');
        foreach ($tables as $table) {
            try {
                $this->_db->delete('DELETE FROM ' . $table . ' WHERE share_id = ?', $params);
            } catch (Horde_Db_Exception $e) {
                throw new Horde_Share_Exception($e->getMessage());
            }
        }
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
            return (boolean)$this->_db->selectOne('SELECT 1 FROM ' . $this->_table . ' WHERE share_name = ?', array($share));
        } catch (Horde_Db_Exception $e) {
            throw new Horde_Share_Exception($e);
        }
    }

    /**
     * Check that a share id exists in the system.
     *
     * @param integer $id  The share id
     *
     * @return boolean True if the share exists.
     */
    protected function _idExists($id)
    {
        try {
            return (boolean)$this->_db->selectOne('SELECT 1 FROM ' . $this->_table . ' WHERE share_id = ?', array($id));
        } catch (Horde_Db_Exception $e) {
            throw new Horde_Share_Exception($e);
        }
    }

    /**
     * Returns an array of criteria for querying shares.
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
     * Returns criteria statement fragments for querying shares.
     *
     * @todo: Horde_SQL:: stuff should be refactored/removed when it's ported
     *        to Horde_Db
     *
     * @param string  $userid  The userid of the user to check access for.
     * @param integer $perm    The level of permissions required.
     *
     * @return array  An array with query and where string fragments.
     */
    protected function _getUserAndGroupCriteria($userid,
                                                $perm = Horde_Perms::SHOW)
    {
        $query = $where = '';

        if (empty($userid)) {
            $where = '(' . Horde_SQL::buildClause($this->_db, 's.perm_guest', '&', $perm) . ')';
        } else {
            // (owner == $userid)
            $where .= 's.share_owner = ' . $this->_db->quote($userid);

            // (name == perm_creator and val & $perm)
            $where .= ' OR (' . Horde_SQL::buildClause($this->_db, 's.perm_creator', '&', $perm) . ')';

            // (name == perm_creator and val & $perm)
            $where .= ' OR (' . Horde_SQL::buildClause($this->_db, 's.perm_default', '&', $perm) . ')';

            // (name == perm_users and key == $userid and val & $perm)
            $query .= ' LEFT JOIN ' . $this->_table . '_users u ON u.share_id = s.share_id';
            $where .= ' OR ( u.user_uid = ' .  $this->_db->quote($userid)
            . ' AND (' . Horde_SQL::buildClause($this->_db, 'u.perm', '&', $perm) . '))';

            // If the user has any group memberships, check for those also.
            try {
                $groups = $this->_groups->getGroupMemberships($userid, true);
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
            } catch (Horde_Group_Exception $e) {
                $this->_logger->err($e);
            }
        }

        return array($query, $where);
    }

    /**
     * Builds a list of permission bit masks from the "perm" column.
     *
     * @param array $row     A data row including permission columns.
     * @param string $index  Name of the column that should be used as the key
     *                       for the permissions list.
     *
     * @return array  A permission hash.
     */
    protected function _buildPermsFromRow($row, $index)
    {
        return array($row[$index] => (int)$row['perm']);
    }

    /**
     * Utility function to convert from the SQL server's charset.
     */
    protected function _fromDriverCharset($data)
    {
        foreach ($data as $key => &$value) {
            if (substr($key, 0, 9) == 'attribute') {
                $value = Horde_String::convertCharset(
                    $value, $this->_db->getOption('charset'), 'UTF-8');
            }
        }

        return $data;
    }

    /**
     * Utility function to convert TO the SQL server's charset.
     *
     * @see Horde_Share#toDriverCharset
     */
    public function toDriverCharset($data)
    {
        if (!is_array($data)) {
            return $data;
        }

        foreach ($data as $key => &$value) {
            if (substr($key, 0, 9) == 'attribute') {
                $value = Horde_String::convertCharset(
                    $value, 'UTF-8', $this->_db->getOption('charset'));
            }
        }

        return $data;
    }

    /**
     * Convert an array keyed on client keys to an array keyed on the driver
     * keys.
     *
     * @param array  $data  The client code keyed array.
     *
     * @return array  The driver keyed array.
     */
    protected function _toDriverKeys($data)
    {
        if (!is_array($data)) {
            return $data;
        }

        $driver_keys = array();
        foreach ($data as $key => $value) {
            if ($key == 'owner') {
                $driver_keys['share_owner'] = $value;
            } else {
                $driver_keys['attribute_' . $key] = $value;
            }
        }

        return $driver_keys;
    }

}
