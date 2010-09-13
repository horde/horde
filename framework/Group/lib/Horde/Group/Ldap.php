<?php
/**
 * This class provides an LDAP backend for the Horde groups system.
 *
 * Copyright 2005-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Ben Chavet <ben@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @package  Group
 */
class Horde_Group_Ldap extends Horde_Group
{
    /**
     * LDAP connection handle
     */
    protected $_ds;

    /**
     * Local copy of the global $conf['group']['params'] array. Simply
     * for coding convenience.
     */
    protected $_params;

    /**
     * Generated LDAP filter based on the config parameters
     */
    protected $_filter;

    /**
     * Constructor.
     */
    public function __construct($params)
    {
        $this->_params = Horde::getDriverConfig('group', 'ldap');

        $this->_params['gid'] = Horde_String::lower($this->_params['gid']);
        $this->_params['memberuid'] = Horde_String::lower($this->_params['memberuid']);
        foreach ($this->_params['newgroup_objectclass'] as $key => $val) {
            $this->_params['newgroup_objectclass'][$key] = Horde_String::lower($val);
        }

        /* Generate LDAP search filter. */
        if (!empty($this->_params['filter'])) {
            $this->_filter = $this->_params['filter'];
        } elseif (!is_array($this->_params['objectclass'])) {
            $this->_filter = 'objectclass=' . $this->_params['objectclass'];
        } else {
            $this->_filter = '';
            foreach ($this->_params['objectclass'] as $objectclass) {
                $this->_filter = '(&' . $this->_filter;
                $this->_filter .= '(objectclass=' . $objectclass . '))';
            }
        }

        $this->_filter = Horde_String::lower($this->_filter);
    }

    /**
     * Connects to the LDAP server.
     *
     * @throws Horde_Group_Exception
     */
    protected function _connect()
    {
        /* Connect to the LDAP server. */
        $this->_ds = @ldap_connect($this->_params['hostspec']);
        if (!$this->_ds) {
            throw new Horde_Group_Exception('Could not reach the LDAP server');
        }

        if (!@ldap_set_option($this->_ds, LDAP_OPT_PROTOCOL_VERSION,
                              $this->_params['version'])) {
            Horde::logMessage(
                sprintf('Set LDAP protocol version to %d failed: [%d] %s',
                        $this->_params['version'],
                        @ldap_errno($this->_ds),
                        @ldap_error($this->_ds)),
                'ERR');
        }

        /* Start TLS if we're using it. */
        if (!empty($this->_params['tls'])) {
            if (!@ldap_start_tls($this->_ds)) {
                Horde::logMessage(
                    sprintf('STARTTLS failed: [%d] %s',
                            @ldap_errno($this->_ds),
                            @ldap_error($this->_ds)),
                    'ERR');
            }
        }

        if (isset($this->_params['binddn'])) {
            $bind = @ldap_bind($this->_ds, $this->_params['binddn'],
                               $this->_params['password']);
        } else {
            $bind = @ldap_bind($this->_ds);
        }

        if (!$bind) {
            throw new Horde_Group_Exception('Could not bind to LDAP server');
        }
    }

    /**
     * Recursively deletes $dn. $this->_ds MUST already be connected.
     *
     * @throws Horde_Group_Exception
     */
    protected function _recursive_delete($dn)
    {
        $search = @ldap_list($this->_ds, $dn, 'objectclass=*', array(''));
        if (!$search) {
            throw new Horde_Group_Exception('Could not reach the LDAP server');
        }

        $children = @ldap_get_entries($this->_ds, $search);
        for ($i = 0; $i < $children['count']; $i++) {
            $result = $this->_recursive_delete($children[$i]['dn']);
            if (!$result) {
                throw new Horde_Group_Exception(sprintf(__CLASS__ . ': Unable to delete group "%s". This is what the server said: %s', $this->getName($children[$i]['dn']), @ldap_error($this->_ds)));
            }
        }

        if (!@ldap_delete($this->_ds, $dn)) {
            throw new Horde_Group_Exception(sprintf(__CLASS__ . ': Unable to delete group "%s". This is what the server said: %s', $dn, @ldap_error($this->_ds)));
        }
    }

    /**
     * Searches existing groups for the highest gidnumber, and returns
     * one higher.
     *
     * @return integer
     *
     * @throws Horde_Group_Exception
     */
    protected function _nextGid()
    {
        /* Connect to the LDAP server. */
        $this->_connect();

        $search = @ldap_search($this->_ds, $this->_params['basedn'], $this->_filter);
        if (!$search) {
            throw new Horde_Group_Exception('Could not reach the LDAP server');
        }

        $result = @ldap_get_entries($this->_ds, $search);
        @ldap_close($this->_ds);

        if (!is_array($result) || (count($result) <= 1)) {
            return 1;
        }

        $nextgid = 0;
        for ($i = 0; $i < $result['count']; $i++) {
            if ($result[$i]['gidnumber'][0] > $nextgid) {
                $nextgid = $result[$i]['gidnumber'][0];
            }
        }

        return $nextgid + 1;
    }

    /**
     * Return a new group object.
     *
     * @param string $name    The group's name.
     * @param string $parent  The group's parent's ID (DN).
     *
     * @return Horde_Group_LdapObject  A new group object.
     * @throws Horde_Exception
     */
    public function newGroup($name, $parent = null)
    {
        try {
            $entry = Horde::callHook('groupldap', array($name, $parent));
        } catch (Horde_Exception_HookNotSet $e) {
            // Try this simple default and hope it works.
            $entry[$this->_params['gid']] = $name;
            $entry['objectclass'] = $this->_params['newgroup_objectclass'];
            $entry['gidnumber'] = $this->_nextGid();
        }

        $group = new Horde_Group_LdapObject($name, $parent);
        $group->fromAttributes($entry);
        $group->setGroupOb($this);

        return $group;
    }

    /**
     * Return a group object corresponding to the named group, with the
     * users and other data retrieved appropriately.
     *
     * @param string $name  The name of the group to retrieve.
     *
     * @return Horde_Group_LdapObject  The requested group.
     * @throws Horde_Group_Exception
     */
    public function getGroup($name)
    {
        return $this->getGroupById($this->getGroupId($name));
    }

    /**
     * Return a group object corresponding to the given dn, with the
     * users and other data retrieved appropriately.
     *
     * @param string $dn  The dn of the group to retrieve.
     *
     * @return Horde_Group_LdapObject  The requested group.
     * @throws Horde_Group_Exception
     */
    public function getGroupById($dn)
    {
        static $cache = array();

        if (!isset($cache[$dn])) {
            /* Connect to the LDAP server. */
            $this->_connect();

            $search = @ldap_search($this->_ds, $dn, $this->_filter);
            if (!$search) {
                throw new Horde_Group_Exception('Could not reach the LDAP server');
            }

            $result = @ldap_get_entries($this->_ds, $search);
            @ldap_close($this->_ds);
            if (!is_array($result) || (count($result) <= 1)) {
                throw new Horde_Group_Exception('Empty result');
            }

            $attributes = array();
            for ($i = 0; $i < $result[0]['count']; $i++) {
                if ($result[0][$result[0][$i]]['count'] > 1) {
                    $attributes[$result[0][$i]] = array();
                    for ($j = 0; $j < $result[0][$result[0][$i]]['count']; $j++) {
                        $attributes[$result[0][$i]][] = $result[0][$result[0][$i]][$j];
                    }
                } else {
                    $attributes[$result[0][$i]] = $result[0][$result[0][$i]][0];
                }
            }
            $attributes['dn'] = $result[0]['dn'];

            $group = new Horde_Group_LdapObject($this->getGroupName($dn));
            $group->fromAttributes($attributes);
            $group->setGroupOb($this);
            $cache[$dn] = $group;
        }

        return $cache[$dn];
    }

    /**
     * Get a globally unique ID for a group.  This really just returns the dn
     * for the group, but is included for compatibility with the Group class.
     *
     * @param Horde_Group_LdapObject $group  The group.
     *
     * @return string  A GUID referring to $group.
     */
    public function getGUID($group)
    {
        return $group->get('dn');
    }

    /**
     * Add a group to the groups system.  The group must first be created with
     * Group_ldap::newGroup(), and have any initial users added to it, before
     * this function is called.
     *
     * @param Horde_Group_LdapObject $group  The new group object.
     *
     * @throws Horde_Group_Exception
     */
    public function addGroup(Horde_Group_DataTreeObject $group)
    {
        /* Connect to the LDAP server. */
        $this->_connect();

        $dn = $group->get('dn');

        $entry = $group->toAttributes();
        $success = @ldap_add($this->_ds, $dn, $entry);

        if (!$success) {
            throw new Horde_Group_Exception(sprintf(__CLASS__ . ': Unable to add group "%s". This is what the server said: ', $group->getName()) . @ldap_error($this->_ds));
        }

        @ldap_close($this->_ds);
    }

    /**
     * Store updated data - users, etc. - of a group to the backend system.
     *
     * @param Horde_Group_LdapObject $group  The group to update
     *
     * @throws Horde_Group_Exception
     * @throws Horde_History_Exception
     * @throws InvalidArgumentException
     */
    public function updateGroup(Horde_Group_DataTreeObject $group)
    {
        $entry = $group->toAttributes();

        /* Connect to the LDAP server. */
        $this->_connect();

        // Do not attempt to change an LDAP object's objectClasses
        unset($entry['objectclass']);

        $result = @ldap_modify($this->_ds, $group->getId(), $entry);
        if (!$result) {
            throw new Horde_Group_Exception(sprintf(__CLASS__ . ': Unable to update group "%s". This is what the server said: %s', $group->getName(), @ldap_error($this->_ds)));
        }

        @ldap_close($this->_ds);

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
     * Remove a group from the groups system permanently.
     *
     * @param Horde_Group_LdapObject $group  The group to remove.
     * @param boolean $force     Recursively delete children groups if true.
     *
     * @throws Horde_Group_Exception
     */
    public function removeGroup(Horde_Group_DataTreeObject $group,
                                $force = false)
    {
        $dn = $group->getId();

        /* Connect to the LDAP server. */
        $this->_connect();

        if ($force) {
            return $this->_recursive_delete($dn);
        }

        if (!@ldap_delete($this->_ds, $dn)) {
            throw new Horde_Group_Exception(sprintf(__CLASS__ . ': Unable to delete group "%s". This is what the server said: %s', $dn, @ldap_error($this->_ds)));
        }
    }

    /**
     * Retrieve the name of a group.
     *
     * @param string $dn  The dn of the group to retrieve the name for.
     *
     * @return string  The group's name.
     * @throws Horde_Group_Exception
     */
    public function getGroupName($dn)
    {
        $dn = Horde_String::convertCharset($dn, $GLOBALS['registry']->getCharset(), 'UTF-8');
        $result = @ldap_explode_dn($dn, 1);
        if ($result === false) {
            throw new Horde_Group_Exception('Invalid group ID passed (bad DN syntax)');
        }

        return $result[0];
    }

    /**
     * DataTreeObject full names include references to parents, but LDAP does
     * not have this concept.  This function simply returns the $group
     * parameter and is included for compatibility with the Group class.
     *
     * @param string $group  Group name.
     *
     * @return string  $group.
     */
    public function getGroupShortName($group)
    {
        return $group;
    }

    /**
     * Retrieve the ID of the given group.
     *
     * NOTE: If given a group name, this function can be unreliable if more
     * than one group exists with the same name.
     *
     * @param mixed $group  Group object, or a group name (string).
     *
     * @return string  The group's ID.
     * @throws Horde_Group_Exception
     */
    public function getGroupId($group)
    {
        static $cache = array();

        if ($group instanceof Horde_Group_LdapObject) {
            return $group->get('dn');
        }

        if (!isset($cache[$group])) {
            $this->_connect();
            $search = @ldap_search($this->_ds, $this->_params['basedn'],
                                   $this->_params['gid'] . '=' . $group,
                                   array($this->_params['gid']));
            if (!$search) {
                throw new Horde_Group_Exception('Could not reach the LDAP server');
            }

            $result = @ldap_get_entries($this->_ds, $search);
            @ldap_close($this->_ds);
            if (!is_array($result) || (count($result) <= 1)) {
                throw new Horde_Group_Exception('Empty result');
            }
            $cache[$group] = $result[0]['dn'];
        }

        return $cache[$group];
    }

    /**
     * Check if a group exists in the system.
     *
     * @param string $group  The group name to check for.
     *
     * @return boolean  True if the group exists, False otherwise.
     * @throws Horde_Group_Exception
     */
    public function exists($group)
    {
        static $cache = array();

        if (!isset($cache[$group])) {
            /* Connect to the LDAP server. */
            $this->_connect();

            $groupDN = $this->getGroupId($group);
            $group = $this->getGroupShortName($group);

            $res = @ldap_compare($this->_ds, $groupDN, $this->_params['gid'], $group);
            if ($res === false) {
                throw new Horde_Group_Exception(sprintf('Internal Error: An attribute must ALWAYS match itself: %s', @ldap_error($this->_ds)));
            }
            // $res is True if the group exists, -1 if not, false never
            $cache[$group] = ($res === true);
        }

        return $cache[$group];
    }

    /**
     * Get a list of the parents of a child group.
     *
     * @param string $dn  The fully qualified group dn
     *
     * @return array  Nested array of parents
     */
    public function getGroupParents($dn)
    {
        $parent = $this->getGroupParent($dn);
        $parents = array(DATATREE_ROOT => 1);
        while ($parent != DATATREE_ROOT) {
            $parents = array($parent => $parents);
            $parent = $this->getGroupParent($parent);
        }
        return $parents;
    }

    /**
     * Get the parent of the given group.
     *
     * @param string $dn  The dn of the child group.
     *
     * @return string  The dn of the parent group.
     * @throws Horde_Group_Exception
     */
    public function getGroupParent($dn)
    {
        if (@ldap_explode_dn($dn, 0) === false) {
            throw new Horde_Group_Exception('Invalid group ID passed (bad DN syntax)');
        }

        unset($result['count'], $result[0]);
        $parent_dn = implode(',', $result);

        return (Horde_String::lower($parent_dn) == Horde_String::lower($GLOBALS['conf']['group']['params']['basedn']))
            ? DATATREE_ROOT
            : $parent_dn;
    }

    /**
     * Get a list of parents all the way up to the root object for the given
     * group.
     *
     * @param string $dn  The dn of the group.
     *
     * @return array  A flat list of all of the parents of the given group,
     *                hashed in $dn => $name format.
     * @throws Horde_Group_Exception
     */
    public function getGroupParentList($dn)
    {
        if (@ldap_explode_dn($dn, 0) === false) {
            throw new Horde_Group_Exception('Invalid group ID passed (bad DN syntax)');
        }

        $num = $result['count'];
        unset($result['count'], $result[0]);

        $count = 0;
        $parents = array();
        $parent_dn = implode(',', $result);
        while ($parent_dn != $this->_params['basedn'] && $count++ != $num) {
            $parents[$parent_dn] = $this->getGroupName($parent_dn);
            unset($result[$count]);
            $parent_dn = implode(',', $result);
        }
        $parents[DATATREE_ROOT] = DATATREE_ROOT;

        return $parents;
    }

    /**
     * Get a list of every group, in the format dn => groupname.
     *
     * @param boolean $refresh  If true, the cached value is ignored and the
     *                          group list is refreshed from the group backend.
     *
     * @return array  dn => groupname hash.
     * @throws Horde_Group_Exception
     */
    public function listGroups($refresh = false)
    {
        static $groups;

        if ($refresh || is_null($groups)) {
            /* Connect to the LDAP server. */
            $this->_connect();

            $search = @ldap_search($this->_ds, $this->_params['basedn'], $this->_filter, array($this->_params['gid']));
            if (!$search) {
                throw new Horde_Group_Exception('Could not reach the LDAP server');
            }

            @ldap_sort($this->_ds, $search, $this->_params['gid']);

            $result = @ldap_get_entries($this->_ds, $search);
            @ldap_close($this->_ds);
            if (!is_array($result) || (count($result) <= 1)) {
                return array();
            }

            $groups = array();
            for ($i = 0; $i < $result['count']; $i++) {
                $groups[$result[$i]['dn']] = $this->getGroupName($result[$i]['dn']);
            }
        }

        return $groups;
    }

    /**
     * Get a list of every user that is part of the specified group and any
     * of its subgroups.
     *
     * @param string $dn  The dn of the parent group.
     *
     * @return array  The complete user list.
     * @throws Horde_Group_Exception
     */
    public function listAllUsers($dn)
    {
        static $cache = array();

        if (!isset($cache[$dn])) {
            $this->_connect();

            $search = @ldap_search($this->_ds, $dn, $this->_filter);
            if (!$search) {
                throw new Horde_Group_Exception(sprintf('Could not reach the LDAP server: %s', @ldap_error($this->_ds)));
            }

            $result = @ldap_get_entries($this->_ds, $search);
            @ldap_close($this->_ds);
            if (!is_array($result) || (count($result) <= 1)) {
                // Not an error, we just don't have any users in this group.
                return array();
            }

            $users = array();
            for ($i = 0; $i < $result['count']; $i++) {
                $users = array_merge($users, $this->listUsers($result[$i]['dn']));
            }

            $cache[$dn] = array_keys(array_flip($users));
        }

        return $cache[$dn];
    }

    /**
     * Get a list of every group that the given user is a member of.
     *
     * @param string  $user          The user to get groups for.
     * @param boolean $parentGroups  Also return the parents of any groups?
     *
     * @return array  An array of all groups the user is in.
     * @throws Horde_Group_Exception
     */
    public function getGroupMemberships($user, $parentGroups = false)
    {
        static $cache = array();

        if (empty($cache[$user])) {
            /* Connect to the LDAP server. */
            $this->_connect();

            // Set up search filter
            $filter = '(' . $this->_params['memberuid'] . '=';
            if ($GLOBALS['conf']['group']['params']['attrisdn']) {
                $filter .= $GLOBALS['conf']['auth']['params']['uid'] . '=';
            }
            $filter .= $user;
            if ($GLOBALS['conf']['group']['params']['attrisdn']) {
                $filter .= ',' . $GLOBALS['conf']['auth']['params']['basedn'];
            }
            $filter .= ')';

            // Perform search
            $search = @ldap_search($this->_ds, $this->_params['basedn'], $filter);
            if (!$search) {
                throw new Horde_Group_Exception('Could not reach the LDAP server');
            }

            $result = @ldap_get_entries($this->_ds, $search);
            @ldap_close($this->_ds);
            if (!is_array($result) || (count($result) <= 1)) {
                return array();
            }

            $groups = array();
            $current_charset = $GLOBALS['registry']->getCharset();
            for ($i = 0; $i < $result['count']; $i++) {
                $utf8_dn = Horde_String::convertCharset($result[$i]['dn'], 'UTF-8', $current_charset);
                $groups[$utf8_dn] = $this->getGroupName($utf8_dn);
            }

            $cache[$user] = $groups;
        }

        return $cache[$user];
    }

    /**
     * Returns the tree depth of the given group, relative to the base dn.
     * 0 is returned for any object directly below the base dn.
     *
     * @param string $dn  The dn of the object.
     *
     * @return intenger  The tree depth of the group.
     * @throws Horde_Group_Exception
     */
    public function getLevel($dn)
    {
        $base = @ldap_explode_dn($this->_params['basedn'], 0);
        if ($base === false) {
            throw new Horde_Group_Exception('Invalid basedn configured');
        }

        $group = @ldap_explode_dn($dn, 0);
        if ($group === false) {
            throw new Horde_Group_Exception('Invalid group ID passed (bad DN syntax)');
        }

        return $group['count'] - $base['count'] - 1;
    }

}
