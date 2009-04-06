<?php
/**
 * A person (objectclass 2.5.6.6).
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
 * This class provides methods for the person objectclass.
 *
 * Copyright 2009 The Horde Project (http://www.horde.org/)
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
class Horde_Kolab_Server_Object_Person extends Horde_Kolab_Server_Object
{

    const ATTRIBUTE_CN           = 'cn';
    const ATTRIBUTE_SN           = 'sn';
    const ATTRIBUTE_USERPASSWORD = 'userPassword';

    const OBJECTCLASS_PERSON     = 'person';

    /**
     * A structure to initialize the attribute structure for this class.
     *
     * @var array
     */
    static public $init_attributes = array(
        'defined' => array(
            self::ATTRIBUTE_CN,
            self::ATTRIBUTE_SN,
            self::ATTRIBUTE_USERPASSWORD,
        ),
        'required' => array(
            self::ATTRIBUTE_CN,
            self::ATTRIBUTE_SN,
        ),
        'object_classes' => array(
            self::OBJECTCLASS_PERSON
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