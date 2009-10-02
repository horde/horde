<?php
/**
 * Representation of a group.
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
 * This class provides methods to deal with groups.
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
class Horde_Kolab_Server_Object_Groupofnames extends Horde_Kolab_Server_Object
{
    /** Define attributes specific to this object type */

    /** The common name */
    const ATTRIBUTE_CN     = 'cn';

    /** The members of this group */
    const ATTRIBUTE_MEMBER = 'member';

    /** The specific object class of this object type */
    const OBJECTCLASS_GROUPOFNAMES = 'groupOfNames';

    /**
     * A structure to initialize the attribute structure for this class.
     *
     * @var array
     */
    static public $init_attributes = array(
        'required' => array(
            self::ATTRIBUTE_CN,
            self::ATTRIBUTE_MEMBER,
        ),
        'defined' => array(
            self::ATTRIBUTE_CN,
            self::ATTRIBUTE_MEMBER,
        ),
        'object_classes' => array(
            self::OBJECTCLASS_GROUPOFNAMES,
        ),
    );

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
        return '(' . self::ATTRIBUTE_OC . '=' . self::OBJECTCLASS_GROUPOFNAMES . ')';
    }

    /**
     * Generates an ID for the given information.
     *
     * @param array &$info The data of the object.
     *
     * @static
     *
     * @return string|PEAR_Error The ID.
     */
    public function generateId(&$info)
    {
        $id = $info[self::ATTRIBUTE_CN];
        if (is_array($id)) {
            $id = $id[0];
        }
        return trim(self::ATTRIBUTE_CN . '=' . $id, " \t\n\r\0\x0B,");
    }

    /**
     * Retrieve the member list for this group.
     *
     * @return array|PEAR_Error The list of members in this group.
     */
    public function getMembers()
    {
        return $this->get(self::ATTRIBUTE_MEMBER, false);
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
        if (!in_array($member, $this->getMembers())) {
            $this->_cache[self::ATTRIBUTE_MEMBER][] = $member;
        } else {
            throw new Horde_Kolab_Server_Exception(_("The UID %s is already a member of the group %s!"),
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
        if (in_array($member, $members)) {
            //FIXME: As the member attribute is required we may not remove the last member
            $this->_cache[self::ATTRIBUTE_MEMBER] =
                array_diff($this->_cache[self::ATTRIBUTE_MEMBER],
                           array($member));
        } else {
            throw new Horde_Kolab_Server_Exception(_("The UID %s is no member of the group %s!"),
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
        if (!is_array($members)) {
            return $member == $members;
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
                        'test'  => self::OBJECTCLASS_GROUPOFNAMES);
        if (!empty($criteria)) {
            $criteria = array('AND' => array($groups, $criteria));
        } else {
            $criteria = array('AND' => array($groups));
        }
        return self::basicUidForSearch($server, $criteria, $restrict);
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
    static public function getGroups($server, $uid)
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
