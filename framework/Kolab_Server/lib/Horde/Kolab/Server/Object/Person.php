<?php
/**
 * A person (objectclass 2.5.6.6).
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_Server
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Kolab_Server
 */

/**
 * This class provides methods for the person objectclass.
 *
 * Copyright 2009-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category Kolab
 * @package  Kolab_Server
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Kolab_Server
 */
class Horde_Kolab_Server_Object_Person extends Horde_Kolab_Server_Object_Top
{
    /** The specific object class of this object type */
    const OBJECTCLASS_PERSON = 'person';

    /**
     * A structure to initialize the attribute structure for this class.
     *
     * @var array
     */
    static public $init_attributes = array(
        'Cn', 'Sn', 'Userpassword', 'Userpasswordraw',
/*         'Telephonenumber' */

/*         'defined' => array( */
/*             self::ATTRIBUTE_CN, */
/*             self::ATTRIBUTE_SN, */
/*             self::ATTRIBUTE_USERPASSWORD, */
/*             self::ATTRIBUTE_TELNO, */
/*         ), */
/*         'derived' => array( */
/*             self::ATTRIBUTE_USERPASSWORD => array( */
/*                 'base' => array( */
/*                     self::ATTRIBUTE_USERPASSWORD */
/*                 ), */
/*                 'method' => 'getEmpty', */
/*             ), */
/*             self::ATTRIBUTE_USERPASSWORDRAW => array( */
/*                 'base' => array( */
/*                     self::ATTRIBUTE_USERPASSWORD */
/*                 ), */
/*                 'method' => '_get', */
/*                 'args' => array( */
/*                     self::ATTRIBUTE_USERPASSWORD, */
/*                 ), */
/*             ), */
/*         ), */
/*         'required' => array( */
/*             self::ATTRIBUTE_CN, */
/*             self::ATTRIBUTE_SN, */
/*         ), */
/*         'object_classes' => array( */
/*             self::OBJECTCLASS_PERSON */
/*         ), */
    );

    /**
     * Salt and hash the password.
     *
     * @param string $password The password.
     *
     * @return string The salted hashed password.
     */
    protected function hashPassword($password)
    {
        $type = isset($this->server->params['hashtype'])
            ? $this->server->params['hashtype'] : 'ssha';
        return Horde_Auth::getCryptedPassword($password, '', $type, true);
    }

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
        return new Horde_Kolab_Server_Query_Element_Equals(
            Horde_Kolab_Server_Object_Attribute_Objectclass::NAME,
            self::OBJECTCLASS_PERSON
        );
    }

    /**
     * Generates an ID for the given information.
     *
     * @param array $info The data of the object.
     *
     * @static
     *
     * @return string The ID.
     */
    public function generateId(array &$info)
    {
        $cn = Horde_Kolab_Server_Object_Attribute_Cn::NAME;
        $sn = Horde_Kolab_Server_Object_Attribute_Sn::NAME;
        if ($this->exists()) {
            if (!isset($info[$cn])
                && !isset($info[$sn])) {
                return $this->getGuid();
            }
            if (!isset($info[$cn])) {
                $old = $this->getInternal($cn);
                if (!empty($old)) {
                    return $this->getGuid();
                }
            }            
        }

        if (!empty($info[$cn])) {
            $id = $info[$cn];
        } else {
            $id = $info[$sn];
        }
        if (is_array($id)) {
            $id = $id[0];
        }
        return $cn . '=' . $this->server->structure->quoteForUid($id);
    }

    /**
     * Distill the server side object information to save.
     *
     * @param array $info The information about the object.
     *
     * @return array The set of information.
     *
     * @throws Horde_Kolab_Server_Exception If the given information contains errors.
     */
    public function prepareObjectInformation(array &$info)
    {
        $cn = Horde_Kolab_Server_Object_Attribute_Cn::NAME;
        $sn = Horde_Kolab_Server_Object_Attribute_Sn::NAME;
        if (!$this->exists() && empty($info[$cn]) && !empty($info[$sn])) {
            $info[$cn] = $info[$sn];
        }

        if (!empty($info[self::ATTRIBUTE_USERPASSWORD])) {
            $info[self::ATTRIBUTE_USERPASSWORD] = $this->hashPassword($info[self::ATTRIBUTE_USERPASSWORD]);
        } else if (isset($info[self::ATTRIBUTE_USERPASSWORD])) {
            unset($info[self::ATTRIBUTE_USERPASSWORD]);
        }
    }
}