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
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @package  Group
 */
class Horde_Group_Ldap extends Horde_Group
{
    /**
     * LDAP object.
     *
     * @var Horde_Ldap
     */
    protected $_ldap;

    /**
     * Local copy of the global $conf['group']['params'] array. Simply
     * for coding convenience.
     *
     * @var array
     */
    protected $_params;

    /**
     * LDAP filter based on the config parameters.
     *
     * @var Horde_Ldap_Filter
     */
    protected $_filter;

    /**
     * Local cache for already retrieved group objects, indexed by DN.
     *
     * @var array
     */
    protected $_dnCache = array();

    /**
     * Local cache for already retrieved group objects, indexed by group name.
     *
     * @var array
     */
    protected $_nameCache = array();

    /**
     * Local cache for already retrieved group names, indexed by DN.
     *
     * @var array|boolean
     */
    protected $_listCache = false;

    /**
     * Local cache for already retrieved group memberships, indexed by user
     * name.
     *
     * @var array
     */
    protected $_userCache = array();

    /**
     * Local cache for already retrieved group users, indexed by group name.
     *
     * @var array
     */
    protected $_groupCache = array();

    /**
     * Constructor.
     */
    public function __construct($params)
    {
        $this->_params = $params;
        $this->_params['gid'] = Horde_String::lower($this->_params['gid']);
        $this->_params['memberuid'] = Horde_String::lower($this->_params['memberuid']);
        foreach ($this->_params['newgroup_objectclass'] as &$val) {
            $val = Horde_String::lower($val);
        }

        /* Generate LDAP search filter. */
        $this->_filter = Horde_Ldap_Filter::build($this->_params);

        /* Connect to server. */
        $this->_ldap = new Horde_Ldap($this->_params);
    }

    /**
     * Searches existing groups for the highest gidnumber, and returns one
     * higher.
     *
     * @return integer
     *
     * @throws Horde_Group_Exception
     */
    protected function _nextGid()
    {
        try {
            $search = $this->_ldap->search($this->_params['basedn'], $this->_filter, array('attributes' => array('gidnumber')));
        } catch (Horde_Ldap_Exception $e) {
            throw new Horde_Group_Exception($e);
        }

        if (!$search->count()) {
            return 1;
        }

        $nextgid = 0;
        foreach ($search as $entry) {
            $nextgid = max($nextgid, $entry->getValue('gidnumber', 'single'));
        }

        return $nextgid + 1;
    }

    /**
     * Returns a new group object.
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
     * Returns a group object corresponding to the named group, with the users
     * and other data retrieved appropriately.
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
     * Returns a group object corresponding to the given DN, with the users and
     * other data retrieved appropriately.
     *
     * @param string $dn  The DN of the group to retrieve.
     *
     * @return Horde_Group_LdapObject  The requested group.
     * @throws Horde_Group_Exception
     * @throws Horde_Exception_NotFound
     */
    public function getGroupById($dn)
    {
        if (isset($this->_dnCache[$dn])) {
            return $this->_dnCache[$dn];
        }

        try {
            $entry = $this->_ldap->getEntry($dn);
        } catch (Horde_Ldap_Exception $e) {
            throw new Horde_Group_Exception($e);
        }

        $group = new Horde_Group_LdapObject($this->getGroupName($dn));
        $group->setEntry($entry);
        $group->setGroupOb($this);
        $this->_dnCache[$dn] = $group;

        return $group;
    }

    /**
     * Returns a globally unique ID for a group.
     *
     * This really just returns the DN for the group, but is included for
     * compatibility with the Group class.
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
     * Adds a group to the groups system.
     *
     * The group must first be created with Horde_Group_Ldap::newGroup(), and
     * have any initial users added to it, before this function is called.
     *
     * @param Horde_Group_LdapObject $group  The new group object.
     *
     * @throws Horde_Group_Exception
     */
    public function addGroup(Horde_Group_LdapObject $group)
    {
        $dn = $group->get('dn');
        $entry = Horde_Ldap_Entry::createFresh($dn, $group->toAttributes());
        try {
            $this->_ldap->add($entry);
        } catch (Horde_Ldap_Exception $e) {
            throw new Horde_Group_Exception($e);
        }
    }

    /**
     * Stores updated data - users, etc. - of a group to the backend system.
     *
     * @param Horde_Group_LdapObject $group  The group to update.
     *
     * @throws Horde_Group_Exception
     * @throws Horde_History_Exception
     * @throws InvalidArgumentException
     */
    public function updateGroup(Horde_Group_LdapObject $group)
    {
        $entry = $group->getEntry();
        $attributes = $group->toAttributes();
        // Do not attempt to change an LDAP object's objectClasses
        unset($attributes['objectclass']);

        try {
            $entry->replace($attributes);
            $entry->update();
        } catch (Horde_Ldap_Exception $e) {
            throw new Horde_Group_Exception($e);
        }

        /* Log the update of the group users on the history log. */
        $history = $GLOBALS['injector']->getInstance('Horde_History');
        $guid = $this->getGUID($group);
        foreach ($group->getAuditLog() as $userId => $action) {
            $history->log($guid, array('action' => $action, 'user' => $userId), true);
        }
        $group->clearAuditLog();

        /* Log the group modification. */
        $history->log($guid, array('action' => 'modify'), true);
    }

    /**
     * Removes a group from the groups system permanently.
     *
     * @param Horde_Group_LdapObject $group  The group to remove.
     * @param boolean $force     Recursively delete children groups if true.
     *
     * @throws Horde_Group_Exception
     */
    public function removeGroup(Horde_Group_DataTreeObject $group,
                                $force = false)
    {
        try {
            $this->_ldap->delete($group->getId(), $force);
        } catch (Horde_Ldap_Exception $e) {
            throw new Horde_Group_Exception($e);
        }
    }

    /**
     * Retrieves the name of a group.
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
     * Returns the ID of the given group.
     *
     * NOTE: If given a group name, this function can be unreliable if more
     * than one group exists with the same name.
     *
     * @param Horde_Group_LdapObject|string $group  Group object, or a group name.
     *
     * @return string  The group's ID.
     * @throws Horde_Group_Exception
     */
    public function getGroupId($group)
    {
        if ($group instanceof Horde_Group_LdapObject) {
            return $group->get('dn');
        }

        if (isset($this->_nameCache[$group])) {
            return $this->_nameCache[$group];
        }

        try {
            $search = $this->_ldap->search(
                $this->_params['basedn'],
                Horde_Ldap_Filter::create($this->_params['gid'], 'equals', $group),
                array('attributes' => array($this->_params['gid'])));
        } catch (Horde_Ldap_Exception $e) {
            throw new Horde_Group_Exception($e);
        }
        if (!$search->count()) {
            throw new Horde_Group_Exception('Empty result');
        }
        try {
            $this->_nameCache[$group] = $search->shiftEntry()->dn();
        } catch (Horde_Ldap_Exception $e) {
            throw new Horde_Group_Exception($e);
        }

        return $this->_nameCache[$group];
    }

    /**
     * Returns whether a group exists in the system.
     *
     * @param string $group  The group name to check for.
     *
     * @return boolean  True if the group exists, False otherwise.
     * @throws Horde_Group_Exception
     */
    public function exists($group)
    {
        try {
            $ldapGroup = $this->getGroup($group);
        } catch (Horde_Exception_NotFound $e) {
            return false;
        }

        return $ldapGroup->getName() == $group->getName();
    }

    /**
     * Returns a list of the parents of a child group.
     *
     * @param string $dn  The fully qualified group DN.
     *
     * @return array  Nested array of parents.
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
     * Returns the parent of the given group.
     *
     * @param string $dn  The DN of the child group.
     *
     * @return string  The DN of the parent group.
     * @throws Horde_Group_Exception
     */
    public function getGroupParent($dn)
    {
        $result = @ldap_explode_dn($dn, 0);
        if ($result === false) {
            throw new Horde_Group_Exception('Invalid group ID passed (bad DN syntax)');
        }

        unset($result['count'], $result[0]);
        $parent_dn = implode(',', $result);

        return (Horde_String::lower($parent_dn) == Horde_String::lower($GLOBALS['conf']['group']['params']['basedn']))
            ? DATATREE_ROOT
            : $parent_dn;
    }

    /**
     * Returns a list of parents all the way up to the root object for the
     * given group.
     *
     * @param string $dn  The DN of the group.
     *
     * @return array  A flat list of all of the parents of the given group,
     *                hashed in $dn => $name format.
     * @throws Horde_Group_Exception
     */
    public function getGroupParentList($dn)
    {
        $result = @ldap_explode_dn($dn, 0);
        if ($result === false) {
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
     * Returns a list of every group, in the format dn => groupname.
     *
     * @param boolean $refresh  If true, the cached value is ignored and the
     *                          group list is refreshed from the group backend.
     *
     * @return array  dn => groupname hash.
     * @throws Horde_Group_Exception
     */
    public function listGroups($refresh = false)
    {
        if ($this->_listCache !== false) {
            return $this->_listCache;
        }

        $this->_listCache = array();
        try {
            $search = $this->_ldap->search($this->_params['basedn'], $this->_filter, array($this->_params['gid']));
        } catch (Horde_Ldap_Exception $e) {
            throw new Horde_Group_Exception($e);
        }

        foreach ($search->sortedAsArray(array($this->_params['gid'])) as $entry) {
            $this->_listCache[$entry['dn']] = $this->getGroupName($entry['dn']);
        }

        return $this->_listCache;
    }

    /**
     * Returns a list of every user that is part of the specified group and any
     * of its subgroups.
     *
     * @param string $dn  The DN of the parent group.
     *
     * @return array  The complete user list.
     * @throws Horde_Group_Exception
     */
    public function listAllUsers($dn)
    {
        if (isset($this->_groupCache[$dn])) {
            return $this->_groupCache[$dn];
        }

        try {
            $search = $this->_ldap->search($dn, $this->_filter);
        } catch (Horde_Ldap_Exception $e) {
            throw new Horde_Group_Exception($e);
        }

        $users = array();
        foreach ($search as $dn => $entry) {
            $users = array_merge($users, $this->listUsers($dn));
        }

        return $this->_groupCache[$dn] = array_keys(array_flip($users));
    }

    /**
     * Returns a list of every group that the given user is a member of.
     *
     * @param string  $user          The user to get groups for.
     * @param boolean $parentGroups  Also return the parents of any groups?
     *
     * @return array  An array of all groups the user is in.
     * @throws Horde_Group_Exception
     */
    public function getGroupMemberships($user, $parentGroups = false)
    {
        if (isset($this->_userCache[$user])) {
            return $this->_userCache[$user];
        }

        // Set up search filter.
        // TODO/FIXME/WTH?
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
        try {
            $search = $this->_ldap->search($this->_params['basedn'], $filter);
        } catch (Horde_Ldap_Exception $e) {
            throw new Horde_Group_Exception($e);
        }

        $this->_userCache[$user] = array();
        $current_charset = $GLOBALS['registry']->getCharset();
        foreach ($search as $dn => $entry) {
            $utf8_dn = Horde_String::convertCharset($dn, 'UTF-8', $current_charset);
            $this->_userCache[$user][$utf8_dn] = $this->getGroupName($utf8_dn);
        }

        return $this->_userCache[$user];
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
