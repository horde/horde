<?php
/**
 * Represents german bank account information.
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
 * This class provides a representation of german bank account
 * information.
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
class Horde_Kolab_Server_Object_Kolabgermanbankarrangement extends Horde_Kolab_Server_Object_Top
{
    /** Define attributes specific to this object type */

    /** The number of the account */
    const ATTRIBUTE_NUMBER = 'kolabGermanBankAccountNumber';

    /** The numeric ID of the bank */
    const ATTRIBUTE_BANKCODE = 'kolabGermanBankCode';

    /** Account holder */
    const ATTRIBUTE_HOLDER = 'kolabGermanBankAccountHolder';

    /** Name of the bank */
    const ATTRIBUTE_BANKNAME = 'kolabGermanBankName';

    /** Additional information */
    const ATTRIBUTE_INFO = 'kolabGermanBankAccountInfo';

    /** The uid of the owner of this account */
    const ATTRIBUTE_OWNERUID = 'kolabGermanBankAccountOwnerUid';

    /** The specific object class of this object type */
    const OBJECTCLASS_KOLABGERMANBANKARRANGEMENT = 'kolabGermanBankArrangement';

    /**
     * A structure to initialize the attribute structure for this class.
     *
     * @var array
     */
    static public $init_attributes = array(
        'defined' => array(
            self::ATTRIBUTE_NUMBER,
            self::ATTRIBUTE_BANKCODE,
            self::ATTRIBUTE_HOLDER,
            self::ATTRIBUTE_BANKNAME,
            self::ATTRIBUTE_INFO,
        ),
        'derived' => array(
            self::ATTRIBUTE_OWNERUID => array(
                'method' => 'getParentUid',
                'args' => array(
                    2,
                ),
            ),
        ),
        'collapsed' => array(
            self::ATTRIBUTE_OWNERUID => array(
                'base' => array(
                    self::ATTRIBUTE_OWNERUID
                ),
                'method' => 'removeAttribute',
            ),
        ),
        'required' => array(
            self::ATTRIBUTE_NUMBER,
            self::ATTRIBUTE_BANKCODE,
        ),
        'object_classes' => array(
            self::OBJECTCLASS_KOLABGERMANBANKARRANGEMENT,
        ),
    );

    /**
     * Generates an ID for the given information.
     *
     * @param array &$info The data of the object.
     *
     * @static
     *
     * @return string|PEAR_Error The ID.
     */
    public function generateId(array &$info)
    {
        if (!isset($info[self::ATTRIBUTE_OWNERUID])) {
            $uid = $this->get(self::ATTRIBUTE_OWNERUID);
            if (empty($uid)) {
                throw new Horde_Kolab_Server_Exception("No parent object provided!",
                                                       Horde_Kolab_Server_Exception::INVALID_INFORMATION);
            }
        } else {
            if (is_array($info[self::ATTRIBUTE_OWNERUID])) {
                $uid = $info[self::ATTRIBUTE_OWNERUID][0];
            } else {
                $uid = $info[self::ATTRIBUTE_OWNERUID];
            }
        }

        $object = $this->server->fetch($uid);
        if (!$object->exists()) {
            throw new Horde_Kolab_Server_Exception(sprintf("The parent object %s does not exist!",
                                                           $uid),
                                                   Horde_Kolab_Server_Exception::INVALID_INFORMATION);
        }

        if (!isset($info[self::ATTRIBUTE_NUMBER])) {
            $number = $this->get(self::ATTRIBUTE_NUMBER);
            if (empty($number)) {
                throw new Horde_Kolab_Server_Exception("No account number given!",
                                                       Horde_Kolab_Server_Exception::INVALID_INFORMATION);
            }
        } else {
            if (is_array($info[self::ATTRIBUTE_NUMBER])) {
                $number = $info[self::ATTRIBUTE_NUMBER][0];
            } else {
                $number = $info[self::ATTRIBUTE_NUMBER];
            }
        }

        if (!isset($info[self::ATTRIBUTE_BANKCODE])) {
            $bankcode = $this->get(self::ATTRIBUTE_BANKCODE);
            if (empty($bankcode)) {
                throw new Horde_Kolab_Server_Exception("No bankcode given!",
                                                       Horde_Kolab_Server_Exception::INVALID_INFORMATION);
            }
        } else {
            if (is_array($info[self::ATTRIBUTE_BANKCODE])) {
                $bankcode = $info[self::ATTRIBUTE_BANKCODE][0];
            } else {
                $bankcode = $info[self::ATTRIBUTE_BANKCODE];
            }
        }

        $base = substr($uid, 0, strpos($uid, $this->server->getBaseUid()) - 1);

        unset($info[self::ATTRIBUTE_OWNERUID]);

        return self::ATTRIBUTE_NUMBER . '=' . $this->server->structure->quoteForUid($number) . ',' 
            . self::ATTRIBUTE_BANKCODE . '=' . $this->server->structure->quoteForUid($bankcode) . ','
            . $base;
    }

    /**
     * Returns the set of search operations supported by this object type.
     *
     * @return array An array of supported search operations.
     */
    static public function getSearchOperations()
    {
        $searches = array(
/*             'accountsForMail', */
        );
        return $searches;
    }

    /**
     * Returns the UIDs of the bank accounts for the user with the given mail
     * address.
     *
     * @param Horde_Kolab_Server $server The server to query.
     * @param string             $mail   Search objects with this mail alias.
     *
     * @return mixed The UIDs or false if there was no result.
     *
     * @throws Horde_Kolab_Server_Exception
     */
    static public function accountsForMail($server, $mail)
    {
        $uid = $server->uidForMail($mail, Horde_Kolab_Server_Object::RESULT_SINGLE);
        return self::objectsForUid($server, $uid, self::OBJECTCLASS_KOLABGERMANBANKARRANGEMENT);
    }

}