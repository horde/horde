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
 * Copyright 2008-2011 The Horde Project (http://www.horde.org/)
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
class Horde_Kolab_Server_Object_Groupofnames extends Horde_Kolab_Server_Object_Top
{
    /** The specific object class of this object type */
    const OBJECTCLASS_GROUPOFNAMES = 'groupOfNames';

    /** Define attributes specific to this object type */

    /** The common name */
    const ATTRIBUTE_CN     = 'cn';

    /** The members of this group */
    const ATTRIBUTE_MEMBER = 'member';

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
    public function generateId(array &$info)
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
            throw new Horde_Kolab_Server_Exception("The UID %s is already a member of the group %s!",
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
            //@todo: As the member attribute is required we may not remove the last member
            $this->_cache[self::ATTRIBUTE_MEMBER] =
                array_diff($this->_cache[self::ATTRIBUTE_MEMBER],
                           array($member));
        } else {
            throw new Horde_Kolab_Server_Exception("The UID %s is no member of the group %s!",
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
}
