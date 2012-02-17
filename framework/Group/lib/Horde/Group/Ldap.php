<?php
/**
 * This class provides an LDAP driver for the Horde group system.
 *
 * Copyright 2005-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Ben Chavet <ben@horde.org>
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Group
 */
class Horde_Group_Ldap extends Horde_Group_Base
{
    /**
     * Handle for the current LDAP connection.
     *
     * @var Horde_Ldap
     */
    protected $_ldap;

    /**
     * Any additional parameters for the driver.
     *
     * @var array
     */
    protected $_params;

    /**
     * LDAP filter for searching groups.
     *
     * @var Horde_Ldap_Filter
     */
    protected $_filter;

    /**
     * Constructor.
     *
     * @throws Horde_Group_Exception
     */
    public function __construct($params)
    {
        $params = array_merge(
            array('binddn'               => '',
                  'bindpw'               => '',
                  'gid'                  => 'cn',
                  'memberuid'            => 'memberUid',
                  'objectclass'          => array('posixGroup'),
                  'newgroup_objectclass' => array('posixGroup')),
            $params
        );

        /* Check mandatory parameters. */
        foreach (array('ldap', 'basedn') as $param) {
            if (!isset($params[$param])) {
                throw new Horde_Group_Exception('The \'' . $param . '\' parameter is missing.');
            }
        }

        /* Set Horde_Ldap object. */
        $this->_ldap = $params['ldap'];
        unset($params['ldap']);

        /* Lowercase attribute names. */
        $params['gid']       = Horde_String::lower($params['gid']);
        $params['memberuid'] = Horde_String::lower($params['memberuid']);
        if (!is_array($params['newgroup_objectclass'])) {
            $params['newgroup_objectclass'] = array($params['newgroup_objectclass']);
        }
        foreach ($params['newgroup_objectclass'] as &$objectClass) {
            $objectClass = Horde_String::lower($objectClass);
        }

        /* Generate LDAP search filter. */
        try {
            $this->_filter = Horde_Ldap_Filter::build($params['search']);
        } catch (Horde_Ldap_Exception $e) {
            throw new Horde_Group_Exception($e);
        }

        $this->_params = $params;
    }

    /**
     * Returns whether the group backend is read-only.
     *
     * @return boolean
     */
    public function readOnly()
    {
        return !isset($this->_params['writedn']) ||
               !isset($this->_params['writepw']);
    }

    /**
     * Returns whether groups can be renamed.
     *
     * @return boolean
     */
    public function renameSupported()
    {
        return false;
    }

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
        if ($this->readOnly()) {
            throw new Horde_Group_Exception('This group backend is read-only.');
        }

        $attributes = array(
            $this->_params['gid'] => $name,
            'objectclass'         => $this->_params['newgroup_objectclass'],
            'gidnumber'           => $this->_nextGid());
        if (!empty($email)) {
            $attributes['mail'] = $email;
        }

        return $this->_create($name, $attributes);
    }

    /**
     * Creates a new group.
     *
     * @param string $name       A group name.
     * @param array $attributes  The group's attributes.
     *
     * @return mixed  The ID of the created group.
     * @throws Horde_Group_Exception
     */
    protected function _create($name, array $attributes)
    {
        $dn = Horde_Ldap::quoteDN(array(array($this->_params['gid'], $name))) . ',' . $this->_params['basedn'];
        try {
            $entry = Horde_Ldap_Entry::createFresh($dn, $attributes);
            $this->_rebind(true);
            $this->_ldap->add($entry);
            $this->_rebind(false);
            return $dn;
        } catch (Horde_Ldap_Exception $e) {
            throw new Horde_Group_Exception($e);
        }
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
        throw new Horde_Group_Exception('Renaming groups is not supported with the LDAP driver.');
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
        if ($this->readOnly()) {
            throw new Horde_Group_Exception('This group backend is read-only.');
        }

        try {
            $this->_rebind(true);
            $this->_ldap->delete($gid);
            $this->_rebind(false);
        } catch (Horde_Ldap_Exception $e) {
            throw new Horde_Group_Exception($e);
        }
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
        try {
            return $this->_ldap->exists($gid);
        } catch (Horde_Ldap_Exception $e) {
            throw new Horde_Group_Exception($e);
        }
    }

    /**
     * Returns a group name.
     *
     * @param mixed $gid  A group ID.
     *
     * @return string  The group's name.
     * @throws Horde_Group_Exception
     * @throws Horde_Exception_NotFound
     */
    public function getName($gid)
    {
        try {
            $entry = $this->_ldap->getEntry($gid);
            return $entry->getValue($this->_params['gid'], 'single');
        } catch (Horde_Ldap_Exception $e) {
            throw new Horde_Group_Exception($e);
        }
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
        try {
            $entry = $this->_ldap->getEntry($gid);
            $attributes = $entry->getValues();
        } catch (Horde_Ldap_Exception $e) {
            throw new Horde_Group_Exception($e);
        }
        $data = array();
        foreach ($attributes as $attribute => $value) {
            switch ($attribute) {
            case $this->_params['gid']:
                $attribute = 'name';
                break;
            case 'mail':
                $attribute = 'email';
                break;
            }
            $data[$attribute] = $value;
        }
        return $data;
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
        if ($this->readOnly()) {
            throw new Horde_Group_Exception('This group backend is read-only.');
        }

        $attributes = is_array($attribute)
            ? $attribute
            : array($attribute => $value);
        try {
            $entry = $this->_ldap->getEntry($gid);
            foreach ($attributes as $attribute => $value) {
                switch ($attribute) {
                case 'name':
                    $attribute = $this->_params['gid'];
                    break;
                case 'email':
                    $attribute = 'mail';
                    break;
                }
                $entry->replace(array($attribute => $value));
            }
            $this->_rebind(true);
            $entry->update();
            $this->_rebind(false);
        } catch (Horde_Ldap_Exception $e) {
            throw new Horde_Group_Exception($e);
        }
    }

    /**
     * Returns a list of all groups a user may see, with IDs as keys and names
     * as values.
     *
     * @param string $member  Only return groups that this user is a member of.
     *
     * @return array  All existing groups.
     * @throws Horde_Group_Exception
     */
    public function listAll($member = null)
    {
        if (!is_null($member)) {
            return $this->listGroups($member);
        }

        $attr = $this->_params['gid'];
        try {
            $search = $this->_ldap->search($this->_params['basedn'],
                                           $this->_filter,
                                           array($attr));
        } catch (Horde_Ldap_Exception $e) {
            throw new Horde_Group_Exception($e);
        }

        $entries = array();
        foreach ($search->sortedAsArray(array($attr)) as $entry) {
            $entries[$entry['dn']] = $entry[$attr][0];
        }
        return $entries;
    }

    /**
     * Returns a list of users in a group.
     *
     * @param mixed $gid  A group ID.
     *
     * @return array  List of group users.
     * @throws Horde_Group_Exception
     * @throws Horde_Exception_NotFound
     */
    public function listUsers($gid)
    {
        $attr = $this->_params['memberuid'];
        try {
            $entry = $this->_ldap->getEntry($gid, array($attr));
            if (!$entry->exists($attr)) {
                return array();
            }

            if (empty($this->_params['attrisdn'])) {
                return $entry->getValue($attr, 'all');
            }

            $users = array();
            foreach ($entry->getValue($attr, 'all') as $user) {
                $dn = Horde_Ldap_Util::explodeDN($user,
                                                 array('onlyvalues' => true));
                // Very simplified approach: assume the first element of the DN
                // contains the user ID.
                $user = $dn[0];
                // Check for multi-value RDNs.
                if (is_array($element)) {
                    $user = $element[0];
                }
                $users[] = $user;
            }
            return $users;
        } catch (Horde_Ldap_Exception $e) {
            throw new Horde_Group_Exception($e);
        }
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
        $attr = $this->_params['gid'];
        try {
            if (!empty($this->_params['attrisdn'])) {
                $user =  $this->_ldap->findUserDN($user);
            }
            $filter = Horde_Ldap_Filter::create($this->_params['memberuid'],
                                                'equals', $user);
            $filter = Horde_Ldap_Filter::combine('and', array($this->_filter, $filter));
            $search = $this->_ldap->search($this->_params['basedn'], $filter,
                                           array($attr));
        } catch (Horde_Ldap_Exception $e) {
            throw new Horde_Group_Exception($e);
        }
        $entries = array();
        foreach ($search->sortedAsArray(array($attr)) as $entry) {
            $entries[$entry['dn']] = $entry[$attr][0];
        }
        return $entries;
    }

    /**
     * Add a user to a group.
     *
     * @param mixed $gid    A group ID.
     * @param string $user  A user name.
     *
     * @throws Horde_Group_Exception
     * @throws Horde_Exception_NotFound
     */
    public function addUser($gid, $user)
    {
        if ($this->readOnly()) {
            throw new Horde_Group_Exception('This group backend is read-only.');
        }

        $attr = $this->_params['memberuid'];
        try {
            if (!empty($this->_params['attrisdn'])) {
                $user =  $this->_ldap->findUserDN($user);
            }
            $entry = $this->_ldap->getEntry($gid, array($attr));
            $entry->add(array($attr => $user));
            $this->_rebind(true);
            $entry->update();
            $this->_rebind(false);
        } catch (Horde_Ldap_Exception $e) {
            throw new Horde_Group_Exception($e);
        }
    }

    /**
     * Removes a user from a group.
     *
     * @param mixed $gid    A group ID.
     * @param string $user  A user name.
     *
     * @throws Horde_Group_Exception
     * @throws Horde_Exception_NotFound
     */
    public function removeUser($gid, $user)
    {
        $attr = $this->_params['memberuid'];
        try {
            if (!empty($this->_params['attrisdn'])) {
                $user =  $this->_ldap->findUserDN($user);
            }
            $entry = $this->_ldap->getEntry($gid, array($attr));
            $entry->delete(array($attr => $user));
            $this->_rebind(true);
            $entry->update();
            $this->_rebind(false);
        } catch (Horde_Ldap_Exception $e) {
            throw new Horde_Group_Exception($e);
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
        $attr = $this->_params['gid'];
        try {
            $result = $this->_ldap->search(
                $this->_params['basedn'],
                Horde_Ldap_Filter::create($attr, 'contains', $name),
                array($attr));
        } catch (Horde_Ldap_Exception $e) {
            throw new Horde_Group_Exception($e);
        }
        $entries = array();
        foreach ($result->sortedAsArray(array($attr)) as $entry) {
            $entries[$entry['dn']] = $entry[$attr][0];
        }
        return $entries;
    }

    /**
     * Searches existing groups for the highest gidnumber, and returns one
     * higher.
     *
     * @return integer  The next group ID.
     *
     * @throws Horde_Group_Exception
     */
    protected function _nextGid()
    {
        try {
            $search = $this->_ldap->search(
                $this->_params['basedn'],
                $this->_filter,
                array('attributes' => array('gidnumber')));
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
     * Rebinds to the LDAP server.
     *
     * @param boolean $write  Whether to rebind for write access. Use false
     *                        after finishing write actions.
     *
     * @throws Horde_Ldap_Exception
     */
    protected function _rebind($write)
    {
        if ($write) {
            $this->_ldap->bind($this->_params['writedn'], $this->_params['writepw']);
        } else {
            $this->_ldap->bind();
        }
    }
}
