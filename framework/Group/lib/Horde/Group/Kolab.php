<?php
/**
 * This class provides a Kolab backend for the Horde groups system.
 *
 * FIXME: A better solution would be to let this class rely on
 *        Horde/Kolab/LDAP.php.
 *
 * Copyright 2005-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @package  Group
 */
class Horde_Group_Kolab extends Horde_Group_Ldap
{
    /**
     * Constructor.
     *
     * @throws Horde_Group_Exception
     */
    public function __construct($params)
    {
        if (!function_exists('ldap_connect')) {
            throw new Horde_Group_Exception('The Kolab group driver requires LDAP support.');
        }

        $this->_params = array(
            'hostspec' => $GLOBALS['conf']['kolab']['ldap']['server'],
            'basedn' => $GLOBALS['conf']['kolab']['ldap']['basedn'],
            'binddn' => $GLOBALS['conf']['kolab']['ldap']['phpdn'],
            'password' => $GLOBALS['conf']['kolab']['ldap']['phppw'],
            'version' => 3,
            'gid' => 'cn',
            'memberuid' => 'member',
            'attrisdn' => true,
            'filter_type' => 'objectclass',
            'objectclass' => 'kolabGroupOfNames',
            'newgroup_objectclass' => 'kolabGroupOfNames'
        );

        $this->_filter = 'objectclass=' . $this->_params['objectclass'];

        $this->__wakeup();
    }

    /**
     * Initializes the object.
     */
    public function __wakeup()
    {
        foreach (array_keys($this->_groupCache) as $name) {
            $this->_groupCache[$name]->setGroupOb($this);
        }
    }

    /**
     * Returns the properties that need to be serialized.
     *
     * @return array  List of serializable properties.
     */
    public function __sleep()
    {
        return array_diff(array_keys(get_class_vars(__CLASS__)), array('_datatree', '_ds'));
    }

    /**
     * Returns a new group object.
     *
     * @param string $name    The group's name.
     * @param string $parent  The group's parent's name.
     *
     * @return Horde_Group_KolabObject  A new group object.
     * @throws Horde_Group_Exception
     */
    public function newGroup($name)
    {
        throw new Horde_Group_Exception('Unsupported.');
    }

    /**
     * Adds a group to the groups system. The group must first be created with
     * newGroup(), and have any initial users added to it, before this
     * function is called.
     *
     * @param Horde_Group_KolabObject $group  The new group object.
     * @throws Horde_Group_Exception
     */
    public function addGroup($group)
    {
        throw new Horde_Group_Exception('Unsupported.');
    }

    /**
     * Stores updated data - users, etc. - of a group to the backend system.
     *
     * @param Horde_Group_KolabObject $group  The group to update.
     *
     * @throws Horde_Group_Exception
     */
    public function updateGroup($group)
    {
        throw new Horde_Group_Exception('Unsupported.');
    }

    /**
     * Removes a group from the groups system permanently.
     *
     * @param Horde_Group_KolabObject $group  The group to remove.
     * @param boolean $force      Force to remove every child.
     *
     * @throws Horde_Group_Exception
     */
    public function removeGroup($group, $force = false)
    {
        throw new Horde_Group_Exception('Unsupported.');
    }

    /**
     * Return a Horde_Group_KolabObject corresponding to the given dn, with the
     * users and other data retrieved appropriately.
     *
     * @param string $dn  The dn of the group to retrieve.
     *
     * @return Horde_Group_KolabObject  The requested group.
     * @throws Horde_Group_Exception
     */
    public function getGroupById($dn)
    {
        static $cache = array();

        if (!isset($cache[$dn])) {
            /* Connect to the LDAP server. */
            $success = $this->_connect();

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

            $group = new Horde_Group_KolabObject($this->getGroupName($dn));
            $group->_fromAttributes($attributes);
            $group->setGroupOb($this);
            $cache[$dn] = $group;
        }

        return $cache[$dn];
    }


    /**
     * Retrieve the ID of the given group.
     *
     * NOTE: If given a group name, this function can be unreliable if more
     * than one group exists with the same name.
     *
     * @param mixed $group  LDAP_Group object, or a group name (string)
     *
     * @return string  The group's ID.
     * @throws Horde_Group_Exception
     */
    public function getGroupId($group)
    {
        static $cache = array();

        if ($group instanceof Horde_Group_KolabObject) {
            return $group->getDn();
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
     * Get a list of the parents of a child group.
     *
     * @param string $dn  The fully qualified group dn
     *
     * @return array  Nested array of parents
     */
    public function getGroupParents($dn)
    {
        return array();
    }

    /**
     * Get the parent of the given group.
     *
     * @param string $dn  The dn of the child group.
     *
     * @return string  The dn of the parent group.
     */
    public function getGroupParent($dn)
    {
        return null;
    }

    /**
     * Get a list of parents all the way up to the root object for the given
     * group.
     *
     * @param string $dn  The dn of the group.
     *
     * @return array  A flat list of all of the parents of the given group,
     *                hashed in $dn => $name format.
     */
    public function getGroupParentList($dn)
    {
        return array();
    }

    /**
     * Tries to find a DN for a given kolab mail address.
     *
     * @param string $mail  The mail address to search for.
     *
     * @return string  The corresponding dn or false.
     * @throws Horde_Group_Exception
     */
    public function dnForMail($mail)
    {
        $filter = '(&(objectclass=kolabInetOrgPerson)(mail=' . Horde_Ldap::quote($mail) . '))';
        $search = @ldap_search($this->_ds, $this->_params['basedn'], $filter);
        if (!$search) {
            throw new Horde_Group_Exception('Could not reach the LDAP server');
        }
        $dn = @ldap_first_entry($this->_ds, $search);
        if ($dn) {
            return ldap_get_dn($this->_ds, $dn);
        }

        throw new Horde_Group_Exception(sprintf('Error searching for user with the email address "%s"!', $mail));
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
            $success = $this->_connect();
            $dn = $this->dnForMail($user);

            // Set up search filter
            $filter = '(' . $this->_params['memberuid'] . '=' . $dn . ')';

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
            $current_charset = 'UTF-8';
            for ($i = 0; $i < $result['count']; $i++) {
                $utf8_dn = Horde_String::convertCharset($result[$i]['dn'], 'UTF-8', $current_charset);
                $groups[$utf8_dn] = $this->getGroupName($utf8_dn);
            }

            $cache[$user] = $groups;
        }

        return $cache[$user];
    }

}
