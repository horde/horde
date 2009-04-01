<?php
/**
 * A bsaic object representation.
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
 * This class provides basic methods common to all Kolab server objects.
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
class Horde_Kolab_Server_Object_Inetorgperson extends Horde_Kolab_Server_Object_Organizationalperson
{

    const ATTRIBUTE_SID          = 'uid';
    const ATTRIBUTE_GIVENNAME    = 'givenName';
    const ATTRIBUTE_FN           = 'fn';
    const ATTRIBUTE_MAIL         = 'mail';
    const ATTRIBUTE_LNFN         = 'lnfn';
    const ATTRIBUTE_FNLN         = 'fnln';

    const OBJECTCLASS_INETORGPERSON      = 'inetOrgPerson';

    /**
     * The ldap classes for this type of object.
     *
     * @var array
     */
    protected $object_classes = array(
        self::OBJECTCLASS_TOP,
        self::OBJECTCLASS_INETORGPERSON
    );

    /**
     * The attributes supported by this class
     *
     * @var array
     */
/*     public $supported_attributes = array( */
/*         self::ATTRIBUTE_FBPAST, */
/*     ); */


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
        case self::ATTRIBUTE_ID:
            $result = split(',', $this->uid);
            if (substr($result[0], 0, 3) == 'cn=') {
                return substr($result[0], 3);
            } else {
                return $result[0];
            }
        default:
            return parent::derive($attr);
        }
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
        $id_mapfields = array('givenName', 'sn');
        $id_format    = '%s %s';

        $fieldarray = array();
        foreach ($id_mapfields as $mapfield) {
            if (isset($info[$mapfield])) {
                $fieldarray[] = $info[$mapfield];
            } else {
                $fieldarray[] = '';
            }
        }

        return trim(vsprintf($id_format, $fieldarray), " \t\n\r\0\x0B,");
    }
}