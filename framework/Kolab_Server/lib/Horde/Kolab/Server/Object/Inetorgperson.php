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
    const ATTRIBUTE_MAIL         = 'mail';
    const ATTRIBUTE_FN           = 'fn';
    const ATTRIBUTE_LNFN         = 'lnfn';
    const ATTRIBUTE_FNLN         = 'fnln';

    const OBJECTCLASS_INETORGPERSON      = 'inetOrgPerson';

    /** Middle names */
    const ATTRIBUTE_MIDDLENAMES = 'middleNames';

    /**
     * A structure to initialize the attribute structure for this class.
     *
     * @var array
     */
    static public $init_attributes = array(
        'defined' => array(
            self::ATTRIBUTE_SID,
            self::ATTRIBUTE_GIVENNAME,
            self::ATTRIBUTE_MAIL,
        ),
        'derived' => array(
            self::ATTRIBUTE_GIVENNAME => array(
                'base' => self::ATTRIBUTE_GIVENNAME,
                'order' => 0,
                'desc' => 'Given name.',
            ),
            self::ATTRIBUTE_MIDDLENAMES => array(
                'base' => self::ATTRIBUTE_GIVENNAME,
                'order' => 1,
                'desc' => 'Additional names separated from the given name by whitespace.',
            ),
            self::ATTRIBUTE_FNLN => array(
                'base' => array(self::ATTRIBUTE_GIVENNAME,
                                self::ATTRIBUTE_SN),
                'readonly' => true,
            ),
            self::ATTRIBUTE_LNFN => array(
                'base' => array(self::ATTRIBUTE_GIVENNAME,
                                self::ATTRIBUTE_SN),
                'readonly' => true,
            ),
        ),
        'locked' => array(
            self::ATTRIBUTE_MAIL,
        ),
        'object_classes' => array(
            self::OBJECTCLASS_INETORGPERSON,
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
        return '(&(' . self::ATTRIBUTE_OC . '=' . self::OBJECTCLASS_INETORGPERSON . '))';
    }

    /**
     * Derive an attribute value.
     *
     * @param string $attr The attribute to derive.
     *
     * @return mixed The value of the attribute.
     */
    protected function derive($attr, $separator = '$')
    {
        switch ($attr) {
        case self::ATTRIBUTE_GIVENNAME:
        case self::ATTRIBUTE_MIDDLENAMES:
            return $this->getField($attr, ' ', 2);
        case self::ATTRIBUTE_LNFN:
            $gn = $this->get(self::ATTRIBUTE_GIVENNAME, true);
            $sn = $this->get(self::ATTRIBUTE_SN, true);
            return sprintf('%s, %s', $sn, $gn);
        case self::ATTRIBUTE_FNLN:
            $gn = $this->get(self::ATTRIBUTE_GIVENNAME, true);
            $sn = $this->get(self::ATTRIBUTE_SN, true);
            return sprintf('%s %s', $gn, $sn);
        default:
            return parent::derive($attr);
        }
    }

    /**
     * Collapse derived values back into the main attributes.
     *
     * @param string $attr The attribute to collapse.
     * @param array  $info The information currently working on.
     *
     * @return mixed The value of the attribute.
     */
    protected function collapse($key, $attributes, &$info, $separator = '$')
    {
        switch ($key) {
        case self::ATTRIBUTE_GIVENNAME:
            parent::collapse($key, $attributes, $info, ' ');
            break;
        default:
            parent::collapse($key, $attributes, $info, $separator);
            break;
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
        $id_mapfields = array(self::ATTRIBUTE_GIVENNAME,
                              self::ATTRIBUTE_SN);
        $id_format    = self::ATTRIBUTE_CN . '=' . '%s %s';

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