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
    const ATTRIBUTE_SNSUFFIX     = 'snsuffix';
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
        'derived' => array(
            self::ATTRIBUTE_SN => array(
                'base' => self::ATTRIBUTE_SN,
                'order' => 0,
            ),
            self::ATTRIBUTE_SNSUFFIX => array(
                'base' => self::ATTRIBUTE_SN,
                'order' => 1,
            ),
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
     * Generates an ID for the given information.
     *
     * @param array $info The data of the object.
     *
     * @static
     *
     * @return string The ID.
     */
    public static function generateId($info)
    {
        if (!empty($info[self::ATTRIBUTE_CN])) {
            return self::ATTRIBUTE_CN . '=' . $info[self::ATTRIBUTE_CN];
        }
        return self::ATTRIBUTE_CN . '=' . $info[self::ATTRIBUTE_SN];
    }

    /**
     * Saves object information. This may either create a new entry or modify an
     * existing entry.
     *
     * Please note that fields with multiple allowed values require the callee
     * to provide the full set of values for the field. Any old values that are
     * not resubmitted will be considered to be deleted.
     *
     * @param array $info The information about the object.
     *
     * @return boolean|PEAR_Error True on success.
     */
    public function save($info)
    {
        if (empty($info[self::ATTRIBUTE_CN])
            && !empty($info[self::ATTRIBUTE_SN])) {
            $info[self::ATTRIBUTE_CN] = $info[self::ATTRIBUTE_SN];
        }
        return parent::save($info);
    }
}