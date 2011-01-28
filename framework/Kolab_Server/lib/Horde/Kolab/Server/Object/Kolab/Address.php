<?php
/**
 * An entry in the global addressbook.
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
 * This class provides methods to deal with global address book
 * entries for Kolab.
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
class Horde_Kolab_Server_Object_Kolab_Address extends Horde_Kolab_Server_Object_Kolabinetorgperson
{

    /**
     * Attributes derived from the LDAP values.
     *
     * @var array
     */
    public $derived_attributes = array(
        self::ATTRIBUTE_LNFN,
        self::ATTRIBUTE_FNLN,
    );

    /**
     * The ldap classes for this type of object.
     *
     * @var array
     */
    static public $object_classes = array(
        self::OBJECTCLASS_TOP,
        self::OBJECTCLASS_INETORGPERSON,
        self::OBJECTCLASS_KOLABINETORGPERSON,
    );

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
                              array('field' => self::ATTRIBUTE_OC,
                                    'op'    => '=',
                                    'test'  => self::OBJECTCLASS_INETORGPERSON),
                              array('NOT' => array(
                                        array('field' => self::ATTRIBUTE_SID,
                                              'op'    => 'any'),
                                    ),
                              ),
                          ),
        );
        return $criteria;
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
                self::ATTRIBUTE_LNFN,
            );
        }
        return parent::toHash($attrs);
    }
}
