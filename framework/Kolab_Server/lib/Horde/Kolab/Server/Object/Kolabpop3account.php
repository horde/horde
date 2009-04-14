<?php
/**
 * Represents external pop3 account information.
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
 * This class provides a representation of pop3 mail accounts.
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
class Horde_Kolab_Server_Object_Kolabpop3account extends Horde_Kolab_Server_Object
{
    /** Define attributes specific to this object type */

    /** The common name */
    const ATTRIBUTE_CN = 'cn';

    /** Server the account resides on */
    const ATTRIBUTE_SERVER = 'externalPop3AccountServer';

    /** User name for the account */
    const ATTRIBUTE_LOGINNAME = 'externalPop3AccountLoginName';

    /** Password for the account */
    const ATTRIBUTE_PASSWORD = 'externalPop3EncryptedAccountPassword';

    /** Description of the account */
    const ATTRIBUTE_DESCRIPTION = 'externalPop3AccountDescription';

    /** Mail address of the account */
    const ATTRIBUTE_MAIL = 'externalPop3AccountMail';

    /** Port to connect to */
    const ATTRIBUTE_PORT = 'externalPop3AccountPort';

    /** Use SSL when fetching mail from the account? */
    const ATTRIBUTE_USESSL = 'externalPop3AccountUseSSL';

    /** Use TLS when fetching mail from the account? */
    const ATTRIBUTE_USETLS = 'externalPop3AccountUseTLS';

    /** Login method for the external account */
    const ATTRIBUTE_LOGINMETHOD = 'externalPop3AccountLoginMethod';

    /** Validate the server certificate when connecting via SSL/TLS? */
    const ATTRIBUTE_CHECKCERTIFICATE = 'externalPop3AccountCheckServerCertificate';

    /** Should the fetched mail be deleted on the external account or not? */
    const ATTRIBUTE_KEEPMAILONSERVER = 'externalPop3AccountKeepMailOnServer';

    /** The uid of the owner of this account */
    const ATTRIBUTE_OWNERUID = 'externalPop3AccountOwnerUid';

    /** The specific object class of this object type */
    const OBJECTCLASS_KOLABEXTERNALPOP3ACCOUNT = 'kolabExternalPop3Account';

    /**
     * A structure to initialize the attribute structure for this class.
     *
     * @var array
     */
    static public $init_attributes = array(
        'defined' => array(
            self::ATTRIBUTE_CN,
            self::ATTRIBUTE_SERVER,
            self::ATTRIBUTE_LOGINNAME,
            self::ATTRIBUTE_PASSWORD,
            self::ATTRIBUTE_DESCRIPTION,
            self::ATTRIBUTE_MAIL,
            self::ATTRIBUTE_PORT,
            self::ATTRIBUTE_USESSL,
            self::ATTRIBUTE_USETLS,
            self::ATTRIBUTE_LOGINMETHOD,
            self::ATTRIBUTE_CHECKCERTIFICATE,
            self::ATTRIBUTE_KEEPMAILONSERVER,
        ),
        'derived' => array(
            self::ATTRIBUTE_OWNERUID => array(
                'method' => 'getParentUid',
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
            self::ATTRIBUTE_CN,
            self::ATTRIBUTE_SERVER,
            self::ATTRIBUTE_LOGINNAME,
            self::ATTRIBUTE_PASSWORD,
        ),
        'object_classes' => array(
            self::OBJECTCLASS_KOLABEXTERNALPOP3ACCOUNT,
        ),
    );

    /**
     * Generates an ID for the given information.
     *
     * @param array &$info The data of the object.
     *
     * @return string|PEAR_Error The ID.
     */
    public function generateId(&$info)
    {
        if (!isset($info[self::ATTRIBUTE_OWNERUID])) {
            $uid = $this->get(self::ATTRIBUTE_OWNERUID);
            if (empty($uid)) {
                throw new Horde_Kolab_Server_Exception(_("No parent object provided!"),
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
            throw new Horde_Kolab_Server_Exception(sprintf(_("The parent object %s does not exist!"),
                                                           $uid),
                                                   Horde_Kolab_Server_Exception::INVALID_INFORMATION);
        }
        if (!isset($info[self::ATTRIBUTE_SERVER])) {
            throw new Horde_Kolab_Server_Exception(_("No server name given!"),
                                                   Horde_Kolab_Server_Exception::INVALID_INFORMATION);
        }
        if (!isset($info[self::ATTRIBUTE_LOGINNAME])) {
            throw new Horde_Kolab_Server_Exception(_("No login name given!"),
                                                   Horde_Kolab_Server_Exception::INVALID_INFORMATION);
        }

        if (is_array($info[self::ATTRIBUTE_SERVER])) {
            $server = $info[self::ATTRIBUTE_SERVER][0];
        } else {
            $server = $info[self::ATTRIBUTE_SERVER];
        }
        if (is_array($info[self::ATTRIBUTE_LOGINNAME])) {
            $user = $info[self::ATTRIBUTE_LOGINNAME][0];
        } else {
            $user = $info[self::ATTRIBUTE_LOGINNAME];
        }

        $cn = $user . '@' . $server;

        $base = substr($uid, 0, strpos($uid, $this->server->getBaseUid()) - 1);

	unset($info[self::ATTRIBUTE_OWNERUID]);

        return self::ATTRIBUTE_CN . '=' . $this->server->structure->quoteForUid($cn) . ',' . $base;
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
        if (!$this->exists() && empty($info[self::ATTRIBUTE_CN])
            && !empty($info[self::ATTRIBUTE_SERVER])
            && !empty($info[self::ATTRIBUTE_LOGINNAME])) {
            $info[self::ATTRIBUTE_CN] = $info[self::ATTRIBUTE_LOGINNAME] . '@' . $info[self::ATTRIBUTE_SERVER];
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
        $searches = array(
            'pop3AccountsForMail',
        );
        return $searches;
    }

    /**
     * Returns the UIDs of the pop3 accounts for the user with the given mail
     * address.
     *
     * @param Horde_Kolab_Server $server The server to query.
     * @param string             $mail   Search objects with this mail alias.
     *
     * @return mixed The UIDs or false if there was no result.
     *
     * @throws Horde_Kolab_Server_Exception
     */
    static public function pop3AccountsForMail($server, $mail)
    {
        $uid = $server->uidForMail($mail, Horde_Kolab_Server_Object::RESULT_SINGLE);
        return self::objectsForUid($server, $uid, self::OBJECTCLASS_KOLABEXTERNALPOP3ACCOUNT);
    }

}