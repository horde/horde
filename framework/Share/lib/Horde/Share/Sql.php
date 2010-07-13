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
 * @package Horde_Share
 */

/**
 * @package Horde_Share
 */
class Horde_Share_Sql extends Horde_Share
{
    /* Share has user perms */
    const SQL_FLAG_USERS = 1;

    /* Share has group perms */
    const SQL_FLAG_GROUPS = 2;

    /**
     * Handle for the current database connection.
     * @TODO: port to Horde_Db
     *
     * @var MDB2
     */
    protected $_db;

    /**
     * Handle for the current database connection, used for writing. Defaults
     * to the same handle as $db if a separate write database is not required.
     *
     * @var MDB2
     */
    protected $_write_db;

    /**
     * SQL connection parameters
     */
    protected $_params = array();

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
     * Initializes the object.
     */
    public function __wakeup()
    {
        $this->_table = $this->_app . '_shares';
        $this->_connect();

        foreach (array_keys($this->_cache) as $name) {
            $this->_cache[$name]->setShareOb($this);
        }

        parent::__wakeup();
    }

    /**
     * Returns the properties that need to be serialized.
     *
     * @return array  List of serializable properties.
     */
    public function __sleep()
    {
        $properties = get_object_vars($this);
        unset($properties['_sortList'],
              $properties['_db'],
              $properties['_write_db']);
        return array_keys($properties);
    }

    /**
     * Get storage table
     */
    public function getTable()
    {
        return $this->_table;
    }

    /**
     * Refetence to write db
     */
    public function getWriteDb()
    {
        return $this->_write_db;
    }

    public function getReadDb()
    {
        return $this->_db;
    }

    /**
     * Finds out if the share has user set
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
        if ($this->_hasUsers($share)) {
            $stmt = $this->_db->prepare('SELECT user_uid, perm FROM ' . $this->_table . '_users WHERE share_id = ?');
            if ($stmt instanceof PEAR_Error) {
                Horde::logMessage($stmt, 'ERR');
                throw new Horde_Share_Exception($stmt->getMessage());
            }
            $result = $stmt->execute(array($share['share_id']));
            if ( $result instanceof PEAR_Error) {
                Horde::logMessage($result, 'ERR');
                throw new Horde_Share_Exception($result->getMessage());
            } elseif (!empty($result)) {
                while ($row = $result->fetchRow(MDB2_FETCHMODE_ASSOC)) {
                    $share['perm']['users'][$row['user_uid']] = (int)$row['perm'];
                }
            }
            $stmt->free();
            $result->free();
        }
    }

    /**
     * Get groups permissions
     *
     * @param array $share Share data array
     * @throws Horde_Share_Exception
     */
    protected function _getShareGroups(&$share)
    {
        if ($this->_hasGroups($share)) {
            // Get groups permissions
            $stmt = $this->_db->prepare('SELECT group_uid, perm FROM ' . $this->_table . '_groups WHERE share_id = ?');
            if ($stmt instanceof PEAR_Error) {
                Horde::logMessage($stmt, 'ERR');
                throw new Horde_Share_Exception($stmt->getMessage());
            }
            $result = $stmt->execute(array($share['share_id']));
            if ($result instanceof PEAR_Error) {
                Horde::logMessage($result, 'ERR');
                throw new Horde_Share_Exception($result->getMessage());
            } elseif (!empty($result)) {
                while ($row = $result->fetchRow(MDB2_FETCHMODE_ASSOC)) {
                    $share['perm']['groups'][$row['group_uid']] = (int)$row['perm'];
                }
            }
            $stmt->free();
            $result->free();
        }
    }

    /**
     * Returns a Horde_Share_Object_sql object corresponding to the given
     * share name, with the details retrieved appropriately.
     *
     * @param string $name  The name of the share to retrieve.
     *
     * @return Horde_Share_Object_sql  The requested share.
     */
    protected function _getShare($name)
    {
        $stmt = $this->_db->prepare('SELECT * FROM ' . $this->_table . ' WHERE share_name = ?');
        if ($stmt instanceof PEAR_Error) {
            Horde::logMessage($stmt, 'ERR');
            throw new Horde_Share_Exception($stmt->getMessage());
        }
        $results = $stmt->execute(array($name));
        if ($results instanceof PEAR_Error) {
            Horde::logMessage($results, 'ERR');
            throw new Horde_Share_Exception($results->getMessage());
        }
        $data = $results->fetchRow(MDB2_FETCHMODE_ASSOC);
        if ($data instanceof PEAR_Error) {
            Horde::logMessage($data, 'ERR');
            throw new Horde_Share_Exception($data->getMessage());
        } elseif (empty($data)) {
            throw new Horde_Share_Exception(sprintf(_("Share \"%s\" does not exist."), $name));
        }
        $stmt->free();
        $results->free();

        // Convert charset
        $data = $this->_fromDriverCharset($data);

        // Populate the perms array
        $this->_loadPermissions($data);

        return new $this->_shareObject($data);
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
     * @param integer $cid  The id of the share to retrieve.
     *
     * @return Horde_Share_Object_sql  The requested share.
     * @throws Horde_Share_Exception
     */
    protected function _getShareById($id)
    {
        $params = array($id);
        $stmt = $this->_db->prepare('SELECT * FROM ' . $this->_table . ' WHERE share_id = ?');
        if ($stmt instanceof PEAR_Error) {
            Horde::logMessage($stmt, 'ERR');
            throw new Horde_Share_Exception($stmt->getMessage());
        }
        $results = $stmt->execute($params);
        if ($results instanceof PEAR_Error) {
            Horde::logMessage($results, 'ERR');
            throw new Horde_Share_Exception($results->getMessage());
        }
        $data = $results->fetchRow(MDB2_FETCHMODE_ASSOC);
        if ($data instanceof PEAR_Error) {
            Horde::logMessage($data, 'ERR');
            throw new Horde_Share_Exception($data->getMessage());
        } elseif (empty($data)) {
            throw new Horde_Share_Exception(sprintf(_("Share ID %d does not exist."), $id));
        }

        $stmt->free();
        $results->free();

        // Convert charset
        $data = $this->_fromDriverCharset($data);

        // Get permissions
        $this->_loadPermissions($data);

        return new $this->_shareObject($data);
    }

    /**
     * Returns an array of Horde_Share_Object_sql objects corresponding
     * to the given set of unique IDs, with the details retrieved
     * appropriately.
     *
     * @param array $cids  The array of ids to retrieve.
     *
     * @return array  The requested shares.
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
            $this->_getSharePerms($data);
            $sharelist[$data['share_name']] = new $this->_shareObject($data);
        }

        return $sharelist;
    }

    /**
     * Lists *all* shares for the current app/share, regardless of
     * permissions.
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
     * Lists *all* shares for the current app/share, regardless of
     * permissions.
     *
     * @return array  All shares for the current app/share.
     */
    protected function _listAllShares()
    {
        $shares = array();
        $query = 'SELECT * FROM ' . $this->_table . ' ORDER BY share_name ASC';
        $result = $this->_db->query($query);
        if ($result instanceof PEAR_Error) {
            Horde::logMessage($result, 'ERR');
            throw new Horde_Share_Exception($result->getMessage());
        } elseif (empty($result)) {
            return array();
        }

        while ($share = $result->fetchRow(MDB2_FETCHMODE_ASSOC)) {
            $shares[(int)$share['share_id']] = $this->_fromDriverCharset($share);
        }
        $result->free();

        // Get users permissions
        $query = 'SELECT share_id, user_uid, perm FROM ' . $this->_table . '_users';
        $result = $this->_db->query($query);
        if ($result instanceof PEAR_Error) {
            Horde::logMessage($result, 'ERR');
            return $result;
        } elseif (!empty($result)) {
            while ($share = $result->fetchRow(MDB2_FETCHMODE_ASSOC)) {
                $shares[$share['share_id']]['perm']['users'][$share['user_uid']] = (int)$share['perm'];
            }
            $result->free();
        }

        // Get groups permissions
        $query = 'SELECT share_id, group_uid, perm FROM ' . $this->_table . '_groups';
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

        $sharelist = array();
        foreach ($shares as $id => $data) {
            $this->_getSharePerms($data);
            $sharelist[$data['share_name']] = new $this->_shareObject($data);
            $sharelist[$data['share_name']]->setShareOb($this);
        }

        return $sharelist;
    }

    /**
     * Returns an array of all shares that $userid has access to.
     *
     * @param string $userid     The userid of the user to check access for.
     * @param integer $perm      The level of permissions required.
     * @param mixed $attributes  Restrict the shares counted to those
     *                           matching $attributes. An array of
     *                           attribute/values pairs or a share owner
     *                           username.
     *
     * @return array  The shares the user has access to.
     * @throws Horde_Share_Exception
     */
    public function listShares($userid, $perm = Horde_Perms::SHOW, $attributes = null,
                        $from = 0, $count = 0, $sort_by = null, $direction = 0)
    {
        $shares = array();
        if (is_null($sort_by)) {
            $sortfield = 's.share_name';
        } elseif ($sort_by == 'owner' || $sort_by == 'id') {
            $sortfield = 's.share_' . $sort_by;
        } else {
            $sortfield = 's.attribute_' . $sort_by;
        }

        $query = 'SELECT DISTINCT s.* '
            . $this->getShareCriteria($userid, $perm, $attributes)
            . ' ORDER BY ' . $sortfield
            . (($direction == 0) ? ' ASC' : ' DESC');
        if ($from > 0 || $count > 0) {
            $this->_db->setLimit($count, $from);
        }

        // Fix field names for sqlite. MDB2 tries to handle this with
        // MDB2_PORTABILITY_FIX_ASSOC_FIELD_NAMES, but it doesn't stick.
        if ($this->_db->phptype == 'sqlite') {
            $connection = $this->_db->getConnection();
            @sqlite_query('PRAGMA full_column_names=0', $connection);
            @sqlite_query('PRAGMA short_column_names=1', $connection);
        }

        Horde::logMessage(sprintf("SQL Query by Horde_Share_sql::listShares: %s", $query), 'DEBUG');
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
            $sharelist[$data['share_name']] = new $this->_shareObject($data);
            $sharelist[$data['share_name']]->setShareOb($this);
        }
        unset($shares);

        try {
            return Horde::callHook('share_list', array($userid, $perm, $attributes, $sharelist));
        } catch (Horde_Exception_HookNotSet $e) {}

        return $sharelist;
    }

    /**
     * Returns an array of all system shares.
     *
     * @return array  All system shares.
     * @throws Horde_Share_Exception
     */
    public function listSystemShares()
    {
        // Fix field names for sqlite. MDB2 tries to handle this with
        // MDB2_PORTABILITY_FIX_ASSOC_FIELD_NAMES, but it doesn't stick.
        if ($this->_db->phptype == 'sqlite') {
            $connection = $this->_db->getConnection();
            @sqlite_query('PRAGMA full_column_names=0', $connection);
            @sqlite_query('PRAGMA short_column_names=1', $connection);
        }

        $query = 'SELECT * FROM ' . $this->_table . ' WHERE share_owner IS NULL';
        Horde::logMessage('SQL Query by Horde_Share_sql::listSystemShares: ' . $query, 'DEBUG');
        $result = $this->_db->query($query);
        if ($result instanceof PEAR_Error) {
            Horde::logMessage($result, 'ERR');
            throw new Horde_Share_Exception($result->getMessage());;
        } elseif (empty($result)) {
            return array();
        }

        $sharelist = array();
        while ($share = $result->fetchRow(MDB2_FETCHMODE_ASSOC)) {
            $data = $this->_fromDriverCharset($share);
            $this->_getSharePerms($data);
            $sharelist[$data['share_name']] = new $this->_shareObject($data);
            $sharelist[$data['share_name']]->setShareOb($this);
        }
        $result->free();

        return $sharelist;
    }

    /**
     * Returns the number of shares that $userid has access to.
     *
     * @param string $userid     The userid of the user to check access for.
     * @param integer $perm      The level of permissions required.
     * @param mixed $attributes  Restrict the shares counted to those
     *                           matching $attributes. An array of
     *                           attribute/values pairs or a share owner
     *                           username.
     *
     * @return integer  The number of shares
     */
    protected function _countShares($userid, $perm = Horde_Perms::SHOW,
                                    $attributes = null)
    {
        $query = $this->getShareCriteria($userid, $perm, $attributes);
        $query = 'SELECT COUNT(DISTINCT s.share_id) ' . $query;
        Horde::logMessage(sprintf("SQL Query by Horde_Share_sql::_countShares: %s", $query), 'DEBUG');
        return $this->_db->queryOne($query);
    }

    /**
     * Returns a new share object.
     *
     * @param string $name  The share's name.
     *
     * @return Horde_Share_Object_sql  A new share object.
     */
    protected function _newShare($name)
    {
        return new $this->_shareObject(array('share_name' => $name));
    }

    /**
     * Adds a share to the shares system.
     *
     * The share must first be created with
     * Horde_Share_sql::_newShare(), and have any initial details added
     * to it, before this function is called.
     *
     * @param Horde_Share_Object_sql $share  The new share object.
     */
    protected function _addShare($share)
    {
        return $share->save();
    }

    /**
     * Removes a share from the shares system permanently.
     *
     * @param Horde_Share_Object_sql $share  The share to remove.
     *
     * @return boolean
     * @throws Horde_Share_Exception
     */
    protected function _removeShare($share)
    {
        $params = array($share->getId());
        $tables = array($this->_table,
                        $this->_table . '_users',
                        $this->_table . '_groups');
        foreach ($tables as $table) {

            /* Remove the share entry */
            $stmt = $this->_write_db->prepare('DELETE FROM ' . $table . ' WHERE share_id = ?', null, MDB2_PREPARE_MANIP);
            if ($stmt instanceof PEAR_Error) {
                Horde::logMessage($stmt, 'ERR');
                throw new Horde_Share_Exception($stmt->getMessage());
            }
            $result = $stmt->execute($params);
            if ($result instanceof PEAR_Error) {
                Horde::logMessage($result, 'ERR');
                throw new Horde_Share_Exception($result->getMessage());
            }
            $stmt->free();
        }

        return true;
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
        $stmt = $this->_db->prepare('SELECT 1 FROM ' . $this->_table
                . ' WHERE share_name = ?');

        if ($stmt instanceof PEAR_Error) {
            Horde::logMessage($stmt, 'ERR');
            throw new Horde_Share_Exception($stmt->getMessage());
        }
        $result = $stmt->execute(array($share));
        if ($result instanceof PEAR_Error) {
            Horde::logMessage($result, 'ERR');
            throw new Horde_Share_Exception($result->getMessage());
        }

        $exists = (bool)$result->fetchOne();
        $stmt->free();
        $result->free();

        return $exists;
    }

    /**
     * Returns an array of criteria for querying shares.
     * @access protected
     *
     * @param string  $userid      The userid of the user to check access for.
     * @param integer $perm        The level of permissions required.
     * @param mixed   $attributes  Restrict the shares returned to those who
     *                             have these attribute values.
     *
     * @return string  The criteria string for fetching this user's shares.
     */
    public function getShareCriteria($userid, $perm = Horde_Perms::SHOW,
                               $attributes = null)
    {
        $query = ' FROM ' . $this->_table . ' s ';
        $where = '';

        if (!empty($userid)) {
            // (owner == $userid)
            $where .= 's.share_owner = ' . $this->_write_db->quote($userid);

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
            } catch (Horde_Group_Exception $e) {
                Horde::logMessage($e, 'ERR');
            }
        } else {
            $where = '(' . Horde_SQL::buildClause($this->_db, 's.perm_guest', '&', $perm) . ')';
        }

        $attributes = $this->_toDriverKeys($attributes);
        $attributes = $this->_toDriverCharset($attributes);

        if (is_array($attributes)) {
            // Build attribute/key filter.
            $where = ' (' . $where . ') ';
            foreach ($attributes as $key => $value) {
                $where .= ' AND ' . $key . ' = ' . $this->_db->quote($value);
            }
        } elseif (!empty($attributes)) {
            // Restrict to shares owned by the user specified in the
            // $attributes string.
            $where = ' (' . $where . ') AND s.share_owner = ' . $this->_db->quote($attributes);
        }

        return $query . ' WHERE ' . $where;
    }

    /**
     * Resets the current database name so that MDB2 is always selecting the
     * database before sending a query.
     *
     * @TODO: This needs to be public since it's used as a callback in MDB2.
     *        Remove when refactored to use Horde_Db
     */
    public function _selectDB($db, $scope, $message, $is_manip = null)
    {
        if ($scope == 'query') {
            $db->connected_database_name = '';
        }
    }

    /**
     * Set the SQL table name to use for the current scope's share storage.
     *
     * @var string $table  The table name
     */
    public function setShareTable($table) {
        $this->_table = $table;
    }

    /**
     * Attempts to open a connection to the sql server.
     *
     * @return boolean  True on success.
     * @throws Horde_Share_Exception
     */
    protected function _connect()
    {
        $this->_params = $GLOBALS['conf']['sql'];
        if (!isset($this->_params['database'])) {
            $this->_params['database'] = '';
        }
        if (!isset($this->_params['username'])) {
            $_params['username'] = '';
        }
        if (!isset($this->_params['hostspec'])) {
            $this->_params['hostspec'] = '';
        }

        /* Connect to the sql server using the supplied parameters. */
        $params = $this->_params;
        unset($params['charset']);
        $this->_write_db = &MDB2::factory($params);
        if ($this->_write_db instanceof PEAR_Error) {
            throw new Horde_Share_Exception($this->_write_db->getMessage());
        }

        /* Attach debug handler. */
        $this->_write_db->setOption('debug', true);
        $this->_write_db->setOption('debug_handler', array($this, '_selectDB'));
        $this->_write_db->setOption('seqcol_name', 'id');

        /* Set DB portability options. */
        switch ($this->_write_db->phptype) {
        case 'mssql':
            $this->_write_db->setOption('field_case', CASE_LOWER);
            $this->_write_db->setOption('portability', MDB2_PORTABILITY_FIX_CASE | MDB2_PORTABILITY_ERRORS | MDB2_PORTABILITY_RTRIM | MDB2_PORTABILITY_FIX_ASSOC_FIELD_NAMES);
            break;

        case 'pgsql':
            /* The debug handler breaks PostgreSQL. In most cases it shouldn't
             * be necessary, but this may mean we simply can't support use of
             * multiple Postgres databases right now. See
             * http://bugs.horde.org/ticket/7825 */
            $this->_write_db->setOption('debug', false);
            // Fall through

        default:
            $this->_write_db->setOption('field_case', CASE_LOWER);
            $this->_write_db->setOption('portability', MDB2_PORTABILITY_FIX_CASE | MDB2_PORTABILITY_ERRORS | MDB2_PORTABILITY_FIX_ASSOC_FIELD_NAMES);
        }

        /* Check if we need to set up the read DB connection seperately. */
        if (!empty($this->_params['splitread'])) {
            $params = array_merge($params, $this->_params['read']);
            unset($params['charset']);
            $this->_db = &MDB2::singleton($params);
            if ($this->_db instanceof PEAR_Error) {
                throw new Horde_Share_Exception($this->_db);
            }

            $this->_db->setOption('seqcol_name', 'id');
            /* Set DB portability options. */
            switch ($this->_db->phptype) {
            case 'mssql':
                $this->_db->setOption('field_case', CASE_LOWER);
                $this->_db->setOption('portability', MDB2_PORTABILITY_FIX_CASE | MDB2_PORTABILITY_ERRORS | MDB2_PORTABILITY_RTRIM | MDB2_PORTABILITY_FIX_ASSOC_FIELD_NAMES);
                break;

            case 'pgsql':
                /* The debug handler breaks PostgreSQL. In most cases it shouldn't
                 * be necessary, but this may mean we simply can't support use of
                 * multiple Postgres databases right now. See
                 * http://bugs.horde.org/ticket/7825 */
                $this->_write_db->setOption('debug', false);
                // Fall through

            default:
                $this->_db->setOption('field_case', CASE_LOWER);
                $this->_db->setOption('portability', MDB2_PORTABILITY_FIX_CASE | MDB2_PORTABILITY_ERRORS | MDB2_PORTABILITY_FIX_ASSOC_FIELD_NAMES);
            }
        } else {
            /* Default to the same DB handle as the writer for reading too */
            $this->_db = $this->_write_db;
        }

        return true;
    }

    /**
     * Utility function to convert from the SQL server's charset.
     */
    protected function _fromDriverCharset($data)
    {
        foreach ($data as $key => $value) {
            if (substr($key, 0, 9) == 'attribute') {
                $data[$key] = Horde_String::convertCharset(
                    $data[$key], $this->_params['charset']);
            }
        }

        return $data;
    }

    /**
     * Utility function to convert TO the SQL server's charset.
     *
     * @TODO: This needs to be public since it's called by the share object.
     * Look at making this outright public or maybe moving it to the object.
     */
    public function _toDriverCharset($data)
    {
        if (!is_array($data)) {
            return $data;
        }

        foreach ($data as $key => $value) {
            if (substr($key, 0, 9) == 'attribute') {
                $data[$key] = Horde_String::convertCharset(
                    $data[$key], $GLOBALS['registry']->getCharset(), $this->_params['charset']);
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
