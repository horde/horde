<?php
/**
 * Horde_Share_Sqlng provides the next-generation SQL backend driver for the
 * Horde_Share library.
 *
 * Copyright 2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Jan Schneider <jan@horde.org>
 * @package Horde_Share
 */

/**
 * @package Horde_Share
 */
class Horde_Share_Sqlng extends Horde_Share_Sql
{
    /* Serializable version */
    const VERSION = 1;

    /**
     * The Horde_Share_Object subclass to instantiate objects as
     *
     * @var string
     */
    protected $_shareObject = 'Horde_Share_Object_Sqlng';

    /**
     * A list of available permission.
     *
     * This is necessary to unset certain permission when updating existing
     * share objects.
     *
     * @param array
     */
    protected $_availablePermissions = array();

    /**
     * Passes the available permissions to the share object.
     *
     * @param Horde_Share_Object $object
     */
    public function initShareObject(Horde_Share_Object $object)
    {
        parent::initShareObject($object);
        $object->availablePermissions = array_keys($this->_availablePermissions);
    }

    /**
     * Returns an array of all shares that $userid has access to.
     *
     * @param string $userid  The userid of the user to check access for.
     * @param array $params   Additional parameters for the search.
     *  - 'perm':       Require this level of permissions. Horde_Perms constant.
     *  - 'attributes': Restrict shares to these attributes. A hash or username.
     *  - 'from':       Offset. Start at this share
     *  - 'count':      Limit.  Only return this many.
     *  - 'sort_by':    Sort by attribute.
     *  - 'direction':  Sort by direction.
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
                                    'direction' => 0),
                              $params);

        $key = md5(serialize(array($userid, $params)));
        if (!empty($this->_listcache[$key])) {
            return $this->_listcache[$key];
        }

        $perms = $this->convertBitmaskToArray($params['perm']);
        $shareids = null;
        if (!empty($userid)) {
            list($users, $groups, $shareids) = $this->_getUserAndGroupShares($userid, $perms);
        }

        $shares = array();
        if (is_null($params['sort_by'])) {
            $sortfield = 'share_name';
        } elseif ($params['sort_by'] == 'owner' || $params['sort_by'] == 'id') {
            $sortfield = 'share_' . $params['sort_by'];
        } else {
            $sortfield = 'attribute_' . $params['sort_by'];
        }

        $query = 'SELECT DISTINCT * FROM ' . $this->_table . ' WHERE '
            . $this->_getShareCriteria($userid, $perms, $params['attributes'], $shareids)
            . ' ORDER BY ' . $sortfield
            . (($params['direction'] == 0) ? ' ASC' : ' DESC');

        $query = $this->_db->addLimitOffset($query, array('limit' => $params['count'], 'offset' => $params['from']));
        try {
            $rows = $this->_db->selectAll($query);
        } catch (Horde_Db_Exception $e) {
            throw new Horde_Share_Exception($e->getMessage());
        }
        foreach ($rows as $share) {
            $shares[(int)$share['share_id']] = $this->_fromDriverCharset($share);
        }

        if (!empty($userid)) {
            foreach ($users as $user) {
                if (isset($shares[$user['share_id']])) {
                    $shares[$user['share_id']]['perm']['users'] = $this->_buildPermsFromRow($user, 'user_uid');
                }
            }
            foreach ($groups as $group) {
                if (isset($shares[$group['share_id']])) {
                    $shares[$group['share_id']]['perm']['groups'] = $this->_buildPermsFromRow($group, 'group_uid');
                }
            }
        }

        $sharelist = array();
        foreach ($shares as $data) {
            $this->_getSharePerms($data);
            $sharelist[$data['share_name']] = $this->_createObject($data);
        }
        unset($shares);

        // Run the results through the callback, if configured.
        if (!empty($this->_callbacks['list'])) {
            $sharelist = $this->runCallback('list', array($userid, $sharelist, $params));
        }
        $this->_listcache[$key] = $sharelist;

        return $this->_listcache[$key];
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
     * @throws Horde_Share_Exception
     */
    public function countShares($userid, $perm = Horde_Perms::SHOW,
                                $attributes = null)
    {
        $perms = $this->convertBitmaskToArray($perm);
        $shareids = null;
        if (!empty($userid)) {
            list(, , $shareids) = $this->_getUserAndGroupShares($userid, $perms);
        }

        $query = 'SELECT COUNT(DISTINCT share_id) FROM '
            . $this->_table . ' WHERE '
            . $this->_getShareCriteria($userid, $perms, $attributes, $shareids);

        try {
            return $this->_db->selectValue($query);
        } catch (Horde_Db_Exception $e) {
            throw new Horde_Share_Exception($e);
        }
    }

    /**
     * Converts a bit mask number to a bit mask array.
     *
     * @param integer  A bit mask.
     *
     * @return array  The bit mask as an array.
     */
    static public function convertBitmaskToArray($perm)
    {
        $perms = array();
        for ($bit = 1; $perm; $bit *= 2, $perm >>= 1) {
            if ($perm % 2) {
                $perms[] = $bit;
            }
        }
        return $perms;
    }

    /**
     * Builds a list of permission bit masks from all columns in a data row
     * prefixed with "perm_".
     *
     * @param array $row     A data row including permission columns.
     * @param string $index  Name of the column that should be used as the key
     *                       for the permissions list.
     *
     * @return array  A permission hash.
     */
    protected function _buildPermsFromRow($row, $index)
    {
        $perms = array();
        foreach ($row as $column => $value) {
            if (substr($column, 0, 5) != 'perm_') {
                continue;
            }
            if (!isset($perms[$row[$index]])) {
                $perms[$row[$index]] = 0;
            }
            $perm = (int)substr($column, 5);
            $this->_availablePermissions[$perm] = true;
            if ($value) {
                $perms[$row[$index]] |= $perm;
            }
        }
        return $perms;
    }

    /**
     * Converts the permissions from the database table format into the
     * Horde_Share format.
     *
     * @param array $data  The share object data to convert.
     */
    protected function _getSharePerms(&$data)
    {
        $data['perm']['type'] = 'matrix';
        $data['perm']['default'] = $data['perm']['guest'] = $data['perm']['creator'] = 0;
        foreach ($data as $column => $value) {
            $perm = explode('_', $column, 3);
            if ($perm[0] != 'perm' || count($perm) != 3) {
                continue;
            }
            $permvalue = (int)$perm[2];
            $this->_availablePermissions[$permvalue] = true;
            if ($value) {
                $data['perm'][$perm[1]] |= $permvalue;
            }
            unset($data[$column]);
        }
    }

    /**
     * Returns the records and share IDs from the user and group tables that
     * match the search criteria.
     *
     * @param string $userid     The userid of the user to check access for.
     * @param array $perms       The level of permissions required.
     *
     * @return array  A set of user, groups, and shareids.
     */
    protected function _getUserAndGroupShares($userid, array $perms)
    {
        $shareids = array();

        // Get users permissions.
        $query = 'SELECT * FROM ' . $this->_table
            . '_users WHERE user_uid = ' .  $this->_db->quote($userid)
            . ' AND (' . $this->_getPermsCriteria('perm', $perms) . ')';
        try {
            $users = $this->_db->selectAll($query);
        } catch (Horde_Db_Exception $e) {
            throw new Horde_Share_Exception($e->getMessage());
        }
        foreach ($users as $user) {
            $shareids[] = $user['share_id'];
        }

        // Get groups permissions.
        $groups = array();
        try {
            $groupNames = $this->_groups->getGroupMemberships($userid, true);
            if ($groupNames) {
                $group_ids = array();
                foreach (array_keys($groupNames) as $id) {
                    $group_ids[] = $this->_db->quote((string)$id);
                }
                $query = 'SELECT * FROM ' . $this->_table
                    . '_groups WHERE group_uid IN ('
                    . implode(',', $group_ids) . ')' . ' AND ('
                    . $this->_getPermsCriteria('perm', $perms) . ')';
                try {
                    $groups = $this->_db->selectAll($query);
                } catch (Horde_Db_Exception $e) {
                    throw new Horde_Share_Exception($e->getMessage());
                }
                foreach ($groups as $group) {
                    $shareids[] = $group['share_id'];
                }
            }
        } catch (Horde_Group_Exception $e) {
            $this->_logger->err($e);
        }

        return array($users, $groups, array_unique($shareids));
    }

    /**
     * Returns a criteria statement for querying shares.
     *
     * @param string $userid     The userid of the user to check access for.
     * @param array $perms       The level of permissions required.
     * @param array $attributes  Restrict the shares returned to those who
     *                           have these attribute values.
     * @param array $shareids    Additional share IDs from user and group
     *                           permissions.
     *
     * @return string  The criteria string for fetching this user's shares.
     */
    protected function _getShareCriteria($userid, array $perms, $attributes,
                                         $shareids = null)
    {
        /* Convert to driver's keys */
        $attributes = $this->_toDriverKeys($attributes);

        /* ...and to driver charset */
        $attributes = $this->toDriverCharset($attributes);

        $where = '';
        if (empty($userid)) {
            $where = $this->_getPermsCriteria('perm_guest', $perms);
        } else {
            // (owner == $userid)
            $where .= 'share_owner = ' . $this->_db->quote($userid);

            // (name == perm_creator and val & $perm)
            $where .= ' OR ' . $this->_getPermsCriteria('perm_creator', $perms);

            // (name == perm_creator and val & $perm)
            $where .= ' OR ' . $this->_getPermsCriteria('perm_default', $perms);

            if ($shareids) {
                $where .= ' OR share_id IN (' . implode(',', $shareids) . ')';
            }
        }

        if (is_array($attributes)) {
            // Build attribute/key filter.
            $where = '(' . $where . ') ';
            foreach ($attributes as $key => $value) {
                if (is_array($value)) {
                    $value = array_map(array($this->_db, 'quote'), $value);
                    $where .= ' AND ' . $key . ' IN (' . implode(', ', $value) . ')';
                } else {
                    $where .= ' AND ' . $key . ' = ' . $this->_db->quote($value);
                }
            }
        } elseif (!empty($attributes)) {
            // Restrict to shares owned by the user specified in the
            // $attributes string.
            $where = '(' . $where . ') AND share_owner = ' . $this->_db->quote($attributes);
        }

        return $where;
    }

    /**
     * Builds an ANDed criteria snippet for a set or permissions.
     *
     * @param string $base  A column name prefix.
     * @param array $perms  A list of permissions.
     *
     * @return string  The generated criteria string.
     */
    protected function _getPermsCriteria($base, $perms)
    {
        $criteria = array();
        foreach ($perms as $perm) {
            $criteria[] = $base . '_' . $perm . ' = ' . $this->_db->quoteTrue();
        }
        return implode(' OR ', $criteria);
    }
}
