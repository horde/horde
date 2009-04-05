<?php
/**
 * Representation of a Kolab user group.
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_Server
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Server
 */

/**
 * This class provides methods to deal with groups for Kolab.
 *
 * Copyright 2008-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category Kolab
 * @package  Kolab_Server
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Server
 */
class Horde_Kolab_Server_Object_Kolabgroupofnames extends Horde_Kolab_Server_Object_Inetorgperson
{

    const ATTRIBUTE_VISIBILITY   = 'visible';
    const ATTRIBUTE_MEMBER       = 'member';

    const OBJECTCLASS_KOLABGROUPOFNAMES = 'kolabGroupOfNames';

    /**
     * Attributes derived from the LDAP values.
     *
     * @var array
     */
    public $derived_attributes = array(
        self::ATTRIBUTE_ID,
        self::ATTRIBUTE_VISIBILITY,
    );

    /**
     * The ldap classes for this type of object.
     *
     * @var array
     */
    public $object_classes = array(
        self::OBJECTCLASS_TOP,
        self::OBJECTCLASS_INETORGPERSON,
        self::OBJECTCLASS_KOLABGROUPOFNAMES,
    );

    /**
     * Sort by this attributes (must be a LDAP attribute).
     *
     * @var string
     */
    public $sort_by = self::ATTRIBUTE_MAIL;

    /**
     * Return the filter string to retrieve this object type.
     *
     * @static
     *
     * @return string The filter to retrieve this object type from the server
     *                database.
     */
    public static function getFilter()
    {
        return '(objectClass=kolabGroupOfNames)';
    }

    /**
     * Derive an attribute value.
     *
     * @param string $attr The attribute to derive.
     *
     * @return mixed The value of the attribute.
     */
    protected function derive($attr)
    {
        switch ($attr) {
        case self::ATTRIBUTE_VISIBILITY:
            return strpos($this->_uid, 'cn=internal') === false;
        default:
            return parent::derive($attr);
        }
    }

    /**
     * Convert the object attributes to a hash.
     *
     * @param string $attrs The attributes to return.
     *
     * @return array|PEAR_Error The hash representing this object.
     */
    public function toHash($attrs = null)
    {
        if (!isset($attrs)) {
            $attrs = array(
                self::ATTRIBUTE_ID,
                self::ATTRIBUTE_MAIL,
                self::ATTRIBUTE_VISIBILITY,
            );
        }
        return parent::toHash($attrs);
    }

    /**
     * Generates an ID for the given information.
     *
     * @param array $info The data of the object.
     *
     * @static
     *
     * @return string|PEAR_Error The ID.
     */
    public static function generateId($info)
    {
        if (isset($info['mail'])) {
            return trim($info['mail'], " \t\n\r\0\x0B,");
        } else {
            return trim($info['cn'], " \t\n\r\0\x0B,");
        }
    }

    /**
     * Saves object information.
     *
     * @param array $info The information about the object.
     *
     * @return boolean|PEAR_Error True on success.
     */
    public function save($info)
    {
        if (!isset($info['cn'])) {
            if (!isset($info['mail'])) {
                throw new Horde_Kolab_Server_Exception('Either the mail address or the common name has to be specified for a group object!');
            } else {
                $info['cn'] = $info['mail'];
            }
        }
        return parent::save($info);
    }

    /**
     * Retrieve the member list for this group.
     *
     * @return array|PEAR_Error The list of members in this group.
     */
    public function getMembers()
    {
        return $this->_get(self::ATTRIBUTE_MEMBER, false);
    }

    /**
     * Add a member to this group.
     *
     * @param string $member The UID of the member to add.
     *
     * @return array|PEAR_Error True if successful.
     */
    public function addMember($member)
    {
        $members = $this->getMembers();
        if (is_a($members, 'PEAR_Error')) {
            return $members;
        }
        if (!in_array($member, $members)) {
            $this->_cache[self::ATTRIBUTE_MEMBER][] = $member;
        } else {
            return PEAR::raiseError(_("The UID %s is already a member of the group %s!"),
                                    $member, $this->_uid);
        }
        return $this->save($this->_cache);
    }

    /**
     * Delete a member from this group.
     *
     * @param string $member The UID of the member to delete.
     *
     * @return array|PEAR_Error True if successful.
     */
    public function deleteMember($member)
    {
        $members = $this->getMembers();
        if (is_a($members, 'PEAR_Error')) {
            return $members;
        }
        if (in_array($member, $members)) {
            $this->_cache[self::ATTRIBUTE_MEMBER] =
                array_diff($this->_cache[self::ATTRIBUTE_MEMBER],
                           array($member));
        } else {
            return PEAR::raiseError(_("The UID %s is no member of the group %s!"),
                                    $member, $this->_uid);

        }
        return $this->save($this->_cache);
    }

    /**
     * Is the specified UID member of this group?
     *
     * @param string $member The UID of the member to check.
     *
     * @return boolean|PEAR_Error True if the UID is a member of the group,
     *                            false otherwise.
     */
    public function isMember($member)
    {
        $members = $this->getMembers();
        if (is_a($members, 'PEAR_Error') || !is_array($members)) {
            return $members;
        }
        if (!in_array($member, $members)) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * Returns the set of search operations supported by this object type.
     *
     * @return array An array of supported search operations.
     */
    static public function getSearchOperations()
    {
        $searches = array(
            'gidForSearch',
            'gidForMail',
            'memberOfGroupAddress',
            'getGroups',
        );
        return $searches;
    }

    /**
     * FIXME: This method belongs somewhere where we are aware of groups
     * Identify the GID for the first group found using the specified
     * search criteria
     *
     * @param array $criteria The search parameters as array.
     * @param int   $restrict A Horde_Kolab_Server::RESULT_* result restriction.
     *
     * @return boolean|string|array The GID(s) or false if there was no result.
     *
     * @throws Horde_Kolab_Server_Exception
     */
    static public function gidForSearch($server, $criteria,
                                        $restrict = Horde_Kolab_Server_Object::RESULT_SINGLE)
    {
        $groups = array('field' => self::ATTRIBUTE_OC,
                        'op'    => '=',
                        'test'  => self::OBJECTCLASS_KOLABGROUPOFNAMES);
        if (!empty($criteria)) {
            $criteria = array('AND' => array($groups, $criteria));
        } else {
            $criteria = array('AND' => array($groups));
        }
        return self::basicUidForSearch($server, $criteria, $restrict);
    }

    /**
     * Identify the GID for the first group found with the given mail.
     *
     * @param string $mail     Search for groups with this mail address.
     * @param int    $restrict A Horde_Kolab_Server::RESULT_* result restriction.
     *
     * @return mixed The GID or false if there was no result.
     *
     * @throws Horde_Kolab_Server_Exception
     */
    static public function gidForMail($server, $mail,
                                      $restrict = Horde_Kolab_Server_Object::RESULT_SINGLE)
    {
        $criteria = array('AND' => array(array('field' => self::ATTRIBUTE_MEMBER,
                                               'op'    => '=',
                                               'test'  => $mail),
                         ),
        );
        return self::gidForSearch($server, $criteria, $restrict);
    }

    /**
     * Is the given UID member of the group with the given mail address?
     *
     * @param string $uid  UID of the user.
     * @param string $mail Search the group with this mail address.
     *
     * @return boolean True in case the user is in the group, false otherwise.
     *
     * @throws Horde_Kolab_Server_Exception
     */
    static public function memberOfGroupAddress($server, $uid, $mail)
    {
        $criteria = array('AND' =>
                          array(
                              array('field' => self::ATTRIBUTE_MAIL,
                                    'op'    => '=',
                                    'test'  => $mail),
                              array('field' => self::ATTRIBUTE_MEMBER,
                                    'op'    => '=',
                                    'test'  => $uid),
                          ),
        );

        $result = self::gidForSearch($server, $criteria,
                                      self::RESULT_SINGLE);
        return !empty($result);
    }

    /**
     * Get the groups for this object.
     *
     * @param string $uid The UID of the object to fetch.
     *
     * @return array An array of group ids.
     *
     * @throws Horde_Kolab_Server_Exception
     */
    public function getGroups($server, $uid)
    {
        $criteria = array('AND' =>
                          array(
                              array('field' => self::ATTRIBUTE_MEMBER,
                                    'op'    => '=',
                                    'test'  => $uid),
                          ),
        );

        $result = self::gidForSearch($server, $criteria, self::RESULT_MANY);
        if (empty($result)) {
            return array();
        }
        return $result;
    }

}
