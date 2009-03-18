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
class Horde_Kolab_Server_Object_address extends Horde_Kolab_Server_Object
{

    /**
     * The LDAP filter to retrieve this object type
     *
     * @var string
     */
    public static $filter = '(&(objectclass=inetOrgPerson)(!(uid=*))(sn=*))';

    /**
     * Attributes derived from the LDAP values.
     *
     * @var array
     */
    public $derived_attributes = array(
        Horde_Kolab_Server_Object::ATTRIBUTE_LNFN,
        Horde_Kolab_Server_Object::ATTRIBUTE_FNLN,
    );

    /**
     * The ldap classes for this type of object.
     *
     * @var array
     */
    protected $object_classes = array(
        Horde_Kolab_Server_Object::OBJECTCLASS_TOP,
        Horde_Kolab_Server_Object::OBJECTCLASS_INETORGPERSON,
        Horde_Kolab_Server_Object::OBJECTCLASS_KOLABINETORGPERSON,
    );

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
                Horde_Kolab_Server_Object::ATTRIBUTE_LNFN,
            );
        }
        return parent::toHash($attrs);
    }
}
