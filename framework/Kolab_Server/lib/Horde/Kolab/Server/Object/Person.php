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
 * Copyright 2009-2011 The Horde Project (http://www.horde.org/)
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
            ? $this->server->params['hashtype'] : 'sha1';
        switch ($type) {
        case 'plain':
            /**
             * Do not hash passwords. This is of course not recommended.
             */
            return $password;
        case 'sha256':
            /**
             * Hash passwords with sha256. Ensure your server actually supports this.
             */
            return $this->sha256($password, $this->gensalt());
        default:
            /**
             * Hash passwords with sha1. The default.
             */
            return $this->ssha($password, $this->gensalt());
        }
    }

    /**
     * Return a salted hashed string.
     *
     * @param string $string The string to be transformed to a hash.
     * @param string $salt   A salt string.
     *
     * @return string The salted hashed string.
     */
    protected function sha256($string, $salt)
    {
        return '{SSHA256}' . base64_encode(pack('H*', hash('sha256', $string . $salt)) . $salt);
    }

    /**
     * Return a salted hashed string.
     *
     * @param string $string The string to be transformed to a hash.
     * @param string $salt   A salt string.
     *
     * @return string The salted hashed string.
     */
    protected function ssha($string, $salt)
    {
        return '{SSHA}' . base64_encode(pack('H*', sha1($string . $salt)) . $salt);
    }

    /**
     * Return 4 random bytes.
     *
     * @return string 4 random bytes.
     */
    public function gensalt()
    {
        $salt = '';
        while (strlen($salt) < 4) {
            $salt = $salt . chr(mt_rand(0, 255));
        }
        return $salt;
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