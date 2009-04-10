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
    /** Define attributes specific to this object type */

    /** The common name */
    const ATTRIBUTE_CN = 'cn';

    /** The surname */
    const ATTRIBUTE_SN = 'sn';

    /** A surname suffix */
    const ATTRIBUTE_SNSUFFIX = 'snsuffix';

    /** A password for this person */
    const ATTRIBUTE_USERPASSWORD = 'userPassword';

    /** A telephone number for this person */
    const ATTRIBUTE_TELNO = 'telephoneNumber';

    /** The specific object class of this object type */
    const OBJECTCLASS_PERSON = 'person';

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
            self::ATTRIBUTE_TELNO,
        ),
        'derived' => array(
            self::ATTRIBUTE_USERPASSWORD => array(
                'base' => self::ATTRIBUTE_USERPASSWORD,
            ),
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
     * Derive an attribute value.
     *
     * @param string $attr The attribute to derive.
     *
     * @return mixed The value of the attribute.
     */
    protected function derive($attr)
    {
        switch ($attr) {
        case self::ATTRIBUTE_USERPASSWORD:
            return '';
        default:
            return parent::derive($attr);
        }
    }

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
        return '{SHA256}' . base64_encode(pack('H*', hash('sha256', $string . $salt)) . $salt);
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
        return '(&(' . self::ATTRIBUTE_OC . '=' . self::OBJECTCLASS_PERSON . '))';
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
    public function generateId($info)
    {
        if (!empty($info[self::ATTRIBUTE_CN])) {
            $id = $info[self::ATTRIBUTE_CN];
        } else {
            $id = $info[self::ATTRIBUTE_SN];
        }
        if (is_array($id)) {
            $id = $id[0];
        }
        return self::ATTRIBUTE_CN . '=' . $this->server->structure->quoteForUid($id);
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
        if (!$this->exists()
            && empty($info[self::ATTRIBUTE_CN])
            && !empty($info[self::ATTRIBUTE_SN])) {
            $info[self::ATTRIBUTE_CN] = $info[self::ATTRIBUTE_SN];
        }

        if (!empty($info[self::ATTRIBUTE_USERPASSWORD])) {
            $info[self::ATTRIBUTE_USERPASSWORD] = $this->hashPassword($info[self::ATTRIBUTE_USERPASSWORD]);
        } else if (isset($info[self::ATTRIBUTE_USERPASSWORD])) {
            unset($info[self::ATTRIBUTE_USERPASSWORD]);
        }

        return parent::save($info);
    }

    /**
     * Returns the set of search operations supported by this object type.
     *
     * @return array An array of supported search operations.
     */
    static public function getSearchOperations()
    {
        return array(
            'uidForCn',
        );
    }

    /**
     * Identify the UID for the first object found with the given common name.
     *
     * @param string $cn       Search for objects with this common name.
     * @param int    $restrict A Horde_Kolab_Server::RESULT_* result restriction.
     *
     * @return mixed The UID or false if there was no result.
     *
     * @throws Horde_Kolab_Server_Exception
     */
    static public function uidForCn($server, $cn,
                                    $restrict = Horde_Kolab_Server_Object::RESULT_SINGLE)
    {
        $criteria = array('AND' => array(array('field' => self::ATTRIBUTE_CN,
                                               'op'    => '=',
                                               'test'  => $cn),
                          ),
        );
        return self::basicUidForSearch($server, $criteria, $restrict);
    }

}