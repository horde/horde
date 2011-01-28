<?php
/**
 * A standard Kolab user.
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
 * This class provides methods to deal with Kolab users stored in
 * the Kolab db.
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
class Horde_Kolab_Server_Object_Kolab_User extends Horde_Kolab_Server_Object_Kolabinetorgperson
{

    /** Define attributes specific to this object type */

    /** The user type */
    const ATTRIBUTE_USERTYPE = 'usertype';

    /** The first name */
    const ATTRIBUTE_FN = 'fn';

    /** Define the possible Kolab user types */
    const USERTYPE_STANDARD = 0;
    const USERTYPE_INTERNAL = 1;
    const USERTYPE_GROUP    = 2;
    const USERTYPE_RESOURCE = 3;

    /**
     * A structure to initialize the attribute structure for this class.
     *
     * @var array
     */
/*     static public $init_attributes = array( */
/*         'derived' => array( */
/*             self::ATTRIBUTE_USERTYPE => array(), */
/*             self::ATTRIBUTE_FN => array(), */
/*         ), */
/*         'required' => array( */
/*             self::ATTRIBUTE_USERPASSWORD, */
/*         ), */
/*     ); */

    /**
     * Return the filter string to retrieve this object type.
     *
     * @return string The filter to retrieve this object type from the server
     *                database.
     */
    public static function getFilter()
    {
        $criteria = array('AND' => array(
                              array('field' => self::ATTRIBUTE_SN,
                                    'op'    => 'any'),
                              array('field' => self::ATTRIBUTE_MAIL,
                                    'op'    => 'any'),
                              array('field' => self::ATTRIBUTE_SID,
                                    'op'    => 'any'),
                              array('field' => self::ATTRIBUTE_OC,
                                    'op'    => '=',
                                    'test'  => self::OBJECTCLASS_KOLABINETORGPERSON),
                          ),
        );
        return $criteria;
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
        case self::ATTRIBUTE_USERTYPE:
            if (strpos($this->_uid, 'cn=internal')) {
                return self::USERTYPE_INTERNAL;
            } else if (strpos($this->_uid, 'cn=group')) {
                return self::USERTYPE_GROUP;
            } else if (strpos($this->_uid, 'cn=resource')) {
                return self::USERTYPE_RESOURCE;
            } else {
                return self::USERTYPE_STANDARD;
            }
        case self::ATTRIBUTE_FN:
            return $this->getFn();
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
                self::ATTRIBUTE_SID,
                self::ATTRIBUTE_FN,
                self::ATTRIBUTE_MAIL,
                self::ATTRIBUTE_USERTYPE,
            );
        }
        return parent::toHash($attrs);
    }

    /**
     * Get the "first name" attribute of this object
     *
     * @todo: This should get refactored to be combined with the Id value.
     *
     * @return string the "first name" of this object
     */
    protected function getFn()
    {
        $sn = $this->_get(self::ATTRIBUTE_SN, true);
        $cn = $this->_get(self::ATTRIBUTE_CN, true);
        return trim(substr($cn, 0, strlen($cn) - strlen($sn)));
    }

    /**
     * Get the groups for this object
     *
     * @return mixed|PEAR_Error An array of group ids, false if no groups were
     *                          found.
     */
    public function getGroups()
    {
        return $this->server->getGroups($this->uid);
    }

    /**
     * Get the group mail addresses for this object
     *
     * @return mixed|PEAR_Error An array of group addresses, false if no groups were
     *                          found.
     */
    function getGroupAddresses()
    {
        return $this->server->getGroupAddresses($this->uid);
    }
};
