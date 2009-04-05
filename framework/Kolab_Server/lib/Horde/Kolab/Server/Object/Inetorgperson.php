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

    /** Middle names */
    const ATTRIBUTE_MIDDLENAMES = 'middleNames';

    /**
     * A structure to initialize the attribute structure for this class.
     *
     * @var array
     */
    static public $init_attributes = array(
        /**
         * Derived attributes are calculated based on other attribute values.
         */
        'derived' => array(
            self::ATTRIBUTE_GIVENNAME => array(
                'desc' => 'Given name.',
            ),
            self::ATTRIBUTE_MIDDLENAMES => array(
                'desc' => 'Additional names separated from the given name by whitespace.',
            ),
        ),
        /**
         * Default values for attributes without a value.
         */
        'defaults' => array(
        ),
        /**
         * Locked attributes. These are fixed after the object has been stored
         * once. They may not be modified again.
         */
        'locked' => array(
        ),
        /**
         * The object classes representing this object.
         */
        'object_classes' => array(
            self::OBJECTCLASS_INETORGPERSON,
        ),
    );

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
        case self::ATTRIBUTE_GIVENNAME:
        case self::ATTRIBUTE_MIDDLENAMES:
            $gn = $this->_get(self::ATTRIBUTE_GIVENNAME);
            if (empty($gn)) {
                return;
            }
            list($a[self::ATTRIBUTE_GIVENNAME],
                 $a[self::ATTRIBUTE_MIDDLENAMES]) = explode(' ', $gn, 2);
            if (empty($a[$attr])) {
                return;
            }
            return $a[$attr];
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
    protected function collapse($attr, &$info)
    {
        switch ($attr) {
        case self::ATTRIBUTE_GIVENNAME:
        case self::ATTRIBUTE_MIDDLENAMES:
            if (!isset($info[self::ATTRIBUTE_MIDDLENAMES])
                && !isset($info[self::ATTRIBUTE_GIVENNAME])) {
                return;
            }

            if (isset($info[self::ATTRIBUTE_MIDDLENAMES])) {
                $givenname = isset($info[self::ATTRIBUTE_GIVENNAME]) ? $info[self::ATTRIBUTE_GIVENNAME] : '';
                $info[self::ATTRIBUTE_GIVENNAME] = $givenname . isset($info[self::ATTRIBUTE_MIDDLENAMES]) ? ' ' . $info[self::ATTRIBUTE_MIDDLENAMES] : '';
                unset($info[self::ATTRIBUTE_MIDDLENAMES]);
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