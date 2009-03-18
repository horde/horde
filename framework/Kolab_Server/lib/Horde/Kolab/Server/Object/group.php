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
class Horde_Kolab_Server_Object_group extends Horde_Kolab_Server_Object
{

    /**
     * The LDAP filter to retrieve this object type
     *
     * @var string
     */
    public static $filter = '(objectClass=kolabGroupOfNames)';

    /**
     * Attributes derived from the LDAP values.
     *
     * @var array
     */
    public $derived_attributes = array(
        Horde_Kolab_Server_Object::ATTRIBUTE_ID,
        Horde_Kolab_Server_Object::ATTRIBUTE_VISIBILITY,
    );

    /**
     * The ldap classes for this type of object.
     *
     * @var array
     */
    protected $object_classes = array(
        Horde_Kolab_Server_Object::OBJECTCLASS_TOP,
        Horde_Kolab_Server_Object::OBJECTCLASS_INETORGPERSON,
        Horde_Kolab_Server_Object::OBJECTCLASS_KOLABGROUPOFNAMES,
    );

    /**
     * Sort by this attributes (must be a LDAP attribute).
     *
     * @var string
     */
    public $sort_by = Horde_Kolab_Server_Object::ATTRIBUTE_MAIL;

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
                Horde_Kolab_Server_Object::ATTRIBUTE_ID,
                Horde_Kolab_Server_Object::ATTRIBUTE_MAIL,
                Horde_Kolab_Server_Object::ATTRIBUTE_VISIBILITY,
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
        return $this->_get(Horde_Kolab_Server_Object::ATTRIBUTE_MEMBER, false);
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
            $this->_cache[Horde_Kolab_Server_Object::ATTRIBUTE_MEMBER][] = $member;
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
            $this->_cache[Horde_Kolab_Server_Object::ATTRIBUTE_MEMBER] =
                array_diff($this->_cache[Horde_Kolab_Server_Object::ATTRIBUTE_MEMBER],
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

}
