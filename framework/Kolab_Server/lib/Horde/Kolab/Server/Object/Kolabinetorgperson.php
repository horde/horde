<?php
/**
 * A representation of a Kolab entity.
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
class Horde_Kolab_Server_Object_Kolabinetorgperson extends Horde_Kolab_Server_Object_Inetorgperson
{
    /** Define attributes specific to this object type */

    /** Alias mail addresses */
    const ATTRIBUTE_ALIAS = 'alias';

    /** Delegates for this person */
    const ATTRIBUTE_DELEGATE = 'kolabDelegate';

    /** Marker for a deleted object */
    const ATTRIBUTE_DELETED = 'kolabDeleteFlag';

    /** How many days of the free/busy future should be calculated in advance? */
    const ATTRIBUTE_FBFUTURE = 'kolabFreeBusyFuture';

    /**
     * The home server of this person. It identifies the correct machine in a
     * master/slave setup.
     */
    const ATTRIBUTE_HOMESERVER = 'kolabHomeServer';

    /** The free/busy server of this person */
    const ATTRIBUTE_FREEBUSYHOST = 'kolabFreeBusyServer';

    /** The host that keeps the IMAP mail store of this person */
    const ATTRIBUTE_IMAPHOST = 'kolabImapServer';

    /** The invitation policy for this person */
    const ATTRIBUTE_IPOLICY = 'kolabInvitationPolicy';

    /** The salutation of this person. */
    const ATTRIBUTE_SALUTATION = 'kolabSalutation';

    /** Persons gender */
    const ATTRIBUTE_GENDER = 'gender';

    /** The marital status */
    const ATTRIBUTE_MARITALSTATUS = 'kolabMaritalStatus';

    /** The home fax number */
    const ATTRIBUTE_HOMEFAX = 'homeFacsimileTelephoneNumber';

    /** The german tax ID */
    const ATTRIBUTE_GERMANTAXID = 'germanTaxId';

    /** The country of residence */
    const ATTRIBUTE_COUNTRY = 'c';

    /** The IMAP quota */
    const ATTRIBUTE_QUOTA = 'cyrus-userquota';

    /** Allowed recipients for this person */
    const ATTRIBUTE_ALLOWEDRECIPIENTS = 'kolabAllowSMTPRecipient';

    /** Allowed senders for this person */
    const ATTRIBUTE_ALLOWEDFROM = 'kolabAllowSMTPFrom';

    /** The date of birth */
    const ATTRIBUTE_DATEOFBIRTH = 'apple-birthday';

    /** The date of birth as Horde_Date */
    const ATTRDATE_DATEOFBIRTH = 'apple-birthdayDate';

    /** The place of birth */
    const ATTRIBUTE_PLACEOFBIRTH = 'birthPlace';

    /** Birth name */
    const ATTRIBUTE_BIRTHNAME = 'birthName';

    /** Pseudonym */
    const ATTRIBUTE_PSEUDONYM = 'pseudonym';

    /** Country of citizenship */
    const ATTRIBUTE_COUNTRYCITIZENSHIP = 'countryOfCitizenship';

    /** Legal form (if the person is a legal entity) */
    const ATTRIBUTE_LEGALFORM = 'legalForm';

    /** Registered capital (if the person is a legal entity) */
    const ATTRIBUTE_REGISTEREDCAPITAL = 'tradeRegisterRegisteredCapital';

    /** URI for bylaw (if the person is a legal entity) */
    const ATTRIBUTE_BYLAWURI = 'bylawURI';

    /** Date of incorporation (if the person is a legal entity) */
    const ATTRIBUTE_DATEOFINCORPORATION = 'dateOfIncorporation';

    /** Legal representative (if the person is a legal entity) */
    const ATTRIBUTE_LEGALREPRESENTATIVE = 'legalRepresentative';

    /** Commercial procuration (if the person is a legal entity) */
    const ATTRIBUTE_COMMERCIALPROCURATION = 'commercialProcuration';

    /** Legal representation policy (if the person is a legal entity) */
    const ATTRIBUTE_LEGALREPRESENTATIONPOLICY = 'legalRepresentationPolicy';

    /** Acting deputy (if the person is a legal entity) */
    const ATTRIBUTE_ACTINGDEPUTY = 'actingDeputy';

    /** VAT number (if the person is a legal entity) */
    const ATTRIBUTE_VATNUMBER = 'VATNumber';

    /** Additional legal relationships (if the person is a legal entity) */
    const ATTRIBUTE_OTHERLEGAL = 'otherLegalRelationship';

    /** Is this entity in liquidation? (if the person is a legal entity) */
    const ATTRIBUTE_INLIQUIDATION = 'inLiquidation';

    /** Type of entity as given by the trade register (if the person is a legal entity) */
    const ATTRIBUTE_TRTYPE = 'tradeRegisterType';

    /** Location of entity as given by the trade register (if the person is a legal entity) */
    const ATTRIBUTE_TRLOCATION = 'tradeRegisterLocation';

    /** Identifier of entity as given by the trade register (if the person is a legal entity) */
    const ATTRIBUTE_TRIDENTIFIER = 'tradeRegisterIdentifier';

    /** URI of entity as given by the trade register (if the person is a legal entity) */
    const ATTRIBUTE_TRURI = 'tradeRegisterURI';

    /** Date of last change in the trade register (if the person is a legal entity) */
    const ATTRIBUTE_TRLASTCHANGED = 'tradeRegisterLastChangedDate';

    /** Subdomain for this person */
    const ATTRIBUTE_DC = 'domainComponent';

    /** The specific object class of this object type */
    const OBJECTCLASS_KOLABINETORGPERSON = 'kolabInetOrgPerson';

    /**
     * A structure to initialize the attribute structure for this class.
     *
     * @var array
     */
    static public $init_attributes = array(
        'defined' => array(
            self::ATTRIBUTE_ALIAS,
            self::ATTRIBUTE_DELEGATE,
            self::ATTRIBUTE_DELETED,
            self::ATTRIBUTE_FBFUTURE,
            self::ATTRIBUTE_HOMESERVER,
            self::ATTRIBUTE_FREEBUSYHOST,
            self::ATTRIBUTE_IMAPHOST,
            self::ATTRIBUTE_IPOLICY,
            self::ATTRIBUTE_SALUTATION,
            self::ATTRIBUTE_GENDER,
            self::ATTRIBUTE_MARITALSTATUS,
            self::ATTRIBUTE_HOMEFAX,
            self::ATTRIBUTE_GERMANTAXID,
            self::ATTRIBUTE_COUNTRY,
            self::ATTRIBUTE_QUOTA,
            self::ATTRIBUTE_ALLOWEDRECIPIENTS,
            self::ATTRIBUTE_ALLOWEDFROM,
            self::ATTRIBUTE_DATEOFBIRTH,
            self::ATTRIBUTE_PLACEOFBIRTH,
            self::ATTRIBUTE_BIRTHNAME,
            self::ATTRIBUTE_PSEUDONYM,
            self::ATTRIBUTE_COUNTRYCITIZENSHIP,
            self::ATTRIBUTE_LEGALFORM,
            self::ATTRIBUTE_REGISTEREDCAPITAL,
            self::ATTRIBUTE_BYLAWURI,
            self::ATTRIBUTE_DATEOFINCORPORATION,
            self::ATTRIBUTE_LEGALREPRESENTATIVE,
            self::ATTRIBUTE_COMMERCIALPROCURATION,
            self::ATTRIBUTE_LEGALREPRESENTATIONPOLICY,
            self::ATTRIBUTE_ACTINGDEPUTY,
            self::ATTRIBUTE_VATNUMBER,
            self::ATTRIBUTE_OTHERLEGAL,
            self::ATTRIBUTE_INLIQUIDATION,
            self::ATTRIBUTE_TRTYPE,
            self::ATTRIBUTE_TRLOCATION,
            self::ATTRIBUTE_TRIDENTIFIER,
            self::ATTRIBUTE_TRURI,
            self::ATTRIBUTE_TRLASTCHANGED,
            self::ATTRIBUTE_DC,
        ),
        'locked' => array(
            self::ATTRIBUTE_MAIL,
        ),
        /**
         * Derived attributes are calculated based on other attribute values.
         */
        'derived' => array(
            self::ATTRDATE_DATEOFBIRTH => array(
                'method' => 'getDate',
                'args' => array(
                    self::ATTRIBUTE_DATEOFBIRTH,
                ),
            ),
        ),
        'object_classes' => array(
            self::OBJECTCLASS_KOLABINETORGPERSON,
        ),
    );

    /**
     * Generates an ID for the given information.
     *
     * @param array $info The data of the object.
     *
     * @static
     *
     * @return string|PEAR_Error The ID.
     */
    public function generateId(array &$info)
    {
        /**
         * Never rename the object, even if the components of the CN attribute
         * changed
         */
        if ($this->exists()) {
            return false;
        }
        return self::ATTRIBUTE_CN . '=' . $this->generateCn($info);
    }

    /**
     * Generates the common name for the given information.
     *
     * @param array $info The data of the object.
     *
     * @return string The common name.
     */
    public function generateCn($info)
    {
        global $conf;

        /** The fields that should get mapped into the user ID. */
        if (isset($conf['kolab']['server']['params']['user_cn_mapfields'])) {
            $id_mapfields = $conf['kolab']['server']['params']['user_cn_mapfields'];
        } else {
            $id_mapfields = array(self::ATTRIBUTE_GIVENNAME,
                                  self::ATTRIBUTE_SN);
        }

        /** The user ID format. */
        if (isset($conf['kolab']['server']['params']['user_cn_format'])) {
            $id_format = $conf['kolab']['server']['params']['user_cn_format'];
        } else {
            $id_format = '%s %s';
        }

        $fieldarray = array();
        foreach ($id_mapfields as $mapfield) {
            if (isset($info[$mapfield])) {
                $id = $info[$mapfield];
                if (is_array($id)) {
                    $id = $id[0];
                }
                $fieldarray[] = $this->server->structure->quoteForUid($id);
            } else {
                $fieldarray[] = '';
            }
        }
        return trim(vsprintf($id_format, $fieldarray), " \t\n\r\0\x0B,");
    }

    /**
     * Distill the server side object information to save.
     *
     * @param array $info The information about the object.
     *
     * @return NULL.
     *
     * @throws Horde_Kolab_Server_Exception If the given information contains errors.
     */
    public function prepareObjectInformation(array &$info)
    {
        if (!$this->exists()) {
            if (!isset($info[self::ATTRIBUTE_CN])) {
                if (!isset($info[self::ATTRIBUTE_SN]) || !isset($info[self::ATTRIBUTE_GIVENNAME])) {
                    throw new Horde_Kolab_Server_Exception("Either the last name or the given name is missing!");
                } else {
                    $info[self::ATTRIBUTE_CN] = $this->generateCn($info);
                }
            }
        }
    }

    /**
     * Return the filter string to retrieve this object type.
     *
     * @return string The filter to retrieve this object type from the server
     *                database.
     */
    public static function getFilter()
    {
        $criteria = array('AND' => array(array('field' => self::ATTRIBUTE_OC,
                                               'op'    => '=',
                                               'test'  => self::OBJECTCLASS_KOLABINETORGPERSON),
                          ),
        );
        return $criteria;
    }

    /**
     * List the external pop3 accounts of this object.
     *
     * @return array The data of the pop3 accounts.
     */
    public function getExternalAccounts()
    {
        $result = array();
        $account_uids = $this->objectsForUid($this->server, $this->uid, Horde_Kolab_Server_Object_Kolabpop3account::OBJECTCLASS_KOLABEXTERNALPOP3ACCOUNT);
        if ($account_uids !== false) {
            foreach ($account_uids as $account_uid) {
                $account = $this->server->fetch($account_uid, 'Horde_Kolab_Server_Object_Kolabpop3account');
                $result[] = $account->toHash();
            }
        }
        return $result;
    }

    /**
     * Create/update an external pop3 accounts of this object.
     *
     * @param array $account The account data.
     *
     * @return NULL
     */
    public function saveExternalAccount($account)
    {
        $account[Horde_Kolab_Server_Object_Kolabpop3account::ATTRIBUTE_OWNERUID] = $this->getUid();
        $object = &Horde_Kolab_Server_Object::factory('Horde_Kolab_Server_Object_Kolabpop3account',
                                                      null, $this->server, $account);
        $object->save();
    }

    /**
     * Delete an external account.
     *
     * @param string $mail The mail address of the pop3 account.
     *
     * @return NULL
     */
    public function deleteExternalAccount($mail)
    {
        $account[Horde_Kolab_Server_Object_Kolabpop3account::ATTRIBUTE_OWNERUID] = $this->getUid();
        $account[Horde_Kolab_Server_Object_Kolabpop3account::ATTRIBUTE_MAIL] = $mail;
        $object = &Horde_Kolab_Server_Object::factory('Horde_Kolab_Server_Object_Kolabpop3account',
                                                      null, $this->server, $account);
        $object->delete();
    }

    /**
     * Returns the set of search operations supported by this object type.
     *
     * @return array An array of supported search operations.
     */
    static public function getSearchOperations()
    {
        $searches = array(
            'uidForSearch',
            'uidForId',
            'uidForMail',
            'uidForIdOrMail',
            'uidForAlias',
            'uidForMailOrAlias',
            'uidForIdOrMailOrAlias',
            'mailForIdOrMail',
            'addrsForIdOrMail',
        );
        return $searches;
    }

    /**
     * Identify the kolab UID for the first object found using the specified
     * search criteria.
     *
     * @param Horde_Kolab_Server $server   The server to query.
     * @param array              $criteria The search parameters as array.
     * @param int                $restrict A Horde_Kolab_Server::RESULT_* result restriction.
     *
     * @return boolean|string|array The UID(s) or false if there was no result.
     *
     * @throws Horde_Kolab_Server_Exception
     */
    static public function uidForSearch($server, $criteria,
                                        $restrict = Horde_Kolab_Server_Object::RESULT_SINGLE)
    {
        $users = array('field' => self::ATTRIBUTE_OC,
                       'op'    => '=',
                       'test'  => self::OBJECTCLASS_KOLABINETORGPERSON);
        if (!empty($criteria)) {
            $criteria = array('AND' => array($users, $criteria));
        } else {
            $criteria = array('AND' => array($users));
        }
        return self::basicUidForSearch($server, $criteria, $restrict);
    }

    /**
     * Identify the UID for the first object found with the given ID.
     *
     * @param Horde_Kolab_Server $server   The server to query.
     * @param string             $id       Search for objects with this ID.
     * @param int                $restrict A Horde_Kolab_Server::RESULT_* result restriction.
     *
     * @return mixed The UID or false if there was no result.
     *
     * @throws Horde_Kolab_Server_Exception
     */
    static public function uidForId($server, $id,
                                    $restrict = Horde_Kolab_Server_Object::RESULT_SINGLE)
    {
        $criteria = array('AND' => array(array('field' => self::ATTRIBUTE_SID,
                                               'op'    => '=',
                                               'test'  => $id),
                          ),
        );
        return self::uidForSearch($server, $criteria, $restrict);
    }

    /**
     * Identify the UID for the first user found with the given mail.
     *
     * @param Horde_Kolab_Server $server   The server to query.
     * @param string             $mail     Search for users with this mail address.
     * @param int                $restrict A Horde_Kolab_Server::RESULT_* result restriction.
     *
     * @return mixed The UID or false if there was no result.
     *
     * @throws Horde_Kolab_Server_Exception
     */
    static public function uidForMail($server, $mail,
                                      $restrict = Horde_Kolab_Server_Object::RESULT_SINGLE)
    {
        $criteria = array('AND' => array(array('field' => self::ATTRIBUTE_MAIL,
                                               'op'    => '=',
                                               'test'  => $mail),
                          ),
        );
        return self::uidForSearch($server, $criteria, $restrict);
    }

    /**
     * Identify the UID for the first object found with the given ID or mail.
     *
     * @param Horde_Kolab_Server $server The server to query.
     * @param string             $id     Search for objects with this uid/mail.
     *
     * @return string|boolean The UID or false if there was no result.
     *
     * @throws Horde_Kolab_Server_Exception
     */
    static public function uidForIdOrMail($server, $id)
    {
        $criteria = array('OR' =>
                         array(
                             array('field' => self::ATTRIBUTE_SID,
                                   'op'    => '=',
                                   'test'  => $id),
                             array('field' => self::ATTRIBUTE_MAIL,
                                   'op'    => '=',
                                   'test'  => $id),
                         ),
        );
        return self::uidForSearch($server, $criteria);
    }

    /**
     * Identify the UID for the first object found with the given alias.
     *
     * @param Horde_Kolab_Server $server   The server to query.
     * @param string             $mail     Search for objects with this mail alias.
     * @param int                $restrict A Horde_Kolab_Server::RESULT_* result restriction.
     *
     * @return mixed The UID or false if there was no result.
     *
     * @throws Horde_Kolab_Server_Exception
     */
    static public function uidForAlias($server, $mail,
                                       $restrict = Horde_Kolab_Server_Object::RESULT_SINGLE)
    {
        $criteria = array('AND' => array(array('field' => self::ATTRIBUTE_ALIAS,
                                               'op'    => '=',
                                               'test'  => $mail),
                          ),
        );
        return self::uidForSearch($server, $criteria, $restrict);
    }


    /**
     * Identify the UID for the first object found with the given mail
     * address or alias.
     *
     * @param Horde_Kolab_Server $server The server to query.
     * @param string             $mail   Search for objects with this mail address
     * or alias.
     *
     * @return string|boolean The UID or false if there was no result.
     *
     * @throws Horde_Kolab_Server_Exception
     */
    static public function uidForMailOrAlias($server, $mail)
    {
        $criteria = array('OR' =>
                         array(
                             array('field' => self::ATTRIBUTE_ALIAS,
                                   'op'    => '=',
                                   'test'  => $mail),
                             array('field' => self::ATTRIBUTE_MAIL,
                                   'op'    => '=',
                                   'test'  => $mail),
                         )
        );
        return self::uidForSearch($server, $criteria);
    }

    /**
     * Identify the UID for the first object found with the given ID,
     * mail or alias.
     *
     * @param Horde_Kolab_Server $server The server to query.
     * @param string             $id     Search for objects with this ID/mail/alias.
     *
     * @return string|boolean The UID or false if there was no result.
     *
     * @throws Horde_Kolab_Server_Exception
     */
    static public function uidForIdOrMailOrAlias($server, $id)
    {
        $criteria = array('OR' =>
                         array(
                             array('field' => self::ATTRIBUTE_ALIAS,
                                   'op'    => '=',
                                   'test'  => $id),
                             array('field' => self::ATTRIBUTE_MAIL,
                                   'op'    => '=',
                                   'test'  => $id),
                             array('field' => self::ATTRIBUTE_SID,
                                   'op'    => '=',
                                   'test'  => $id),
                         ),
        );
        return self::uidForSearch($server, $criteria);
    }

    /**
     * Identify the primary mail attribute for the first object found
     * with the given ID or mail.
     *
     * @param Horde_Kolab_Server $server The server to query.
     * @param string             $id     Search for objects with this ID/mail.
     *
     * @return mixed The mail address or false if there was no result.
     *
     * @throws Horde_Kolab_Server_Exception
     */
    static public function mailForIdOrMail($server, $id)
    {
        $criteria = array('AND' =>
                         array(
                             array('field' => self::ATTRIBUTE_OC,
                                   'op'    => '=',
                                   'test'  => self::OBJECTCLASS_KOLABINETORGPERSON),
                             array('OR' =>
                                   array(
                                       array('field' => self::ATTRIBUTE_SID,
                                             'op'    => '=',
                                             'test'  => $id),
                                       array('field' => self::ATTRIBUTE_MAIL,
                                             'op'    => '=',
                                             'test'  => $id),
                                   ),
                             ),
                         ),
        );

        $data = self::attrsForSearch($server, $criteria, array(self::ATTRIBUTE_MAIL),
                                     self::RESULT_STRICT);
        if (!empty($data)) {
            return $data[self::ATTRIBUTE_MAIL][0];
        } else {
            return false;
        }
    }

    /**
     * Returns a list of allowed email addresses for the given user.
     *
     * @param Horde_Kolab_Server $server The server to query.
     * @param string             $id     Search for objects with this ID/mail.
     *
     * @return array An array of allowed mail addresses.
     *
     * @throws Horde_Kolab_Server_Exception
     */
    static public function addrsForIdOrMail($server, $id)
    {
        $criteria = array('AND' =>
                         array(
                             array('field' => self::ATTRIBUTE_OC,
                                   'op'    => '=',
                                   'test'  => self::OBJECTCLASS_KOLABINETORGPERSON),
                             array('OR' =>
                                   array(
                                       array('field' => self::ATTRIBUTE_SID,
                                             'op'    => '=',
                                             'test'  => $id),
                                       array('field' => self::ATTRIBUTE_MAIL,
                                             'op'    => '=',
                                             'test'  => $id),
                                   ),
                             ),
                         ),
        );

        $result = self::attrsForSearch($server, $criteria,
                                       array(self::ATTRIBUTE_MAIL,
                                             self::ATTRIBUTE_ALIAS),
                                       self::RESULT_STRICT);
        if (isset($result[self::ATTRIBUTE_ALIAS])) {
            $addrs = array_merge((array) $result[self::ATTRIBUTE_MAIL],
                                 (array) $result[self::ATTRIBUTE_ALIAS]);
        } else {
            $addrs = $result[self::ATTRIBUTE_MAIL];
        }

        if (empty($result)) {
            return array();
        }
        $criteria = array('AND' =>
                         array(
                             array('field' => self::ATTRIBUTE_OC,
                                   'op'    => '=',
                                   'test'  => self::OBJECTCLASS_KOLABINETORGPERSON),
                             array('field' => self::ATTRIBUTE_DELEGATE,
                                   'op'    => '=',
                                   'test'  => $result[self::ATTRIBUTE_MAIL][0]),
                         ),
        );

        $result = self::attrsForSearch($server, $criteria,
                                       array(self::ATTRIBUTE_MAIL,
                                             self::ATTRIBUTE_ALIAS),
                                       self::RESULT_MANY);
        if (!empty($result)) {
            foreach ($result as $adr) {
                if (isset($adr[self::ATTRIBUTE_MAIL])) {
                    $addrs = array_merge((array) $addrs, (array) $adr[self::ATTRIBUTE_MAIL]);
                }
                if (isset($adr[self::ATTRIBUTE_ALIAS])) {
                    $addrs = array_merge((array) $addrs, (array) $adr[self::ATTRIBUTE_ALIAS]);
                }
            }
        }

        $addrs = array_map('strtolower', $addrs);

        return $addrs;
    }

    /**
     * Returns the server url of the given type for this user.
     *
     * This method is used to encapsulate multidomain support.
     *
     * @param string $server_type The type of server URL that should be returned.
     *
     * @return string The server url or empty on error.
     */
    public function getServer($server_type)
    {
        global $conf;

        switch ($server_type) {
        case 'freebusy':
            $server = $this->get(self::ATTRIBUTE_FREEBUSYHOST);
            if (!empty($server)) {
                return $server;
            }
            if (isset($conf['kolab']['freebusy']['server'])) {
                return $conf['kolab']['freebusy']['server'];
            }
            $server = $this->getServer('homeserver');
            if (empty($server)) {
                $server = $_SERVER['SERVER_NAME'];
            }
            if (isset($conf['kolab']['server']['freebusy_url_format'])) {
                return sprintf($conf['kolab']['server']['freebusy_url_format'],
                               $server);
            } else {
                return 'https://' . $server . '/freebusy';
            }
        case 'imap':
            $server = $this->get(self::ATTRIBUTE_IMAPHOST);
            if (!empty($server)) {
                return $server;
            }
        case 'homeserver':
        default:
            $server = $this->get(self::ATTRIBUTE_HOMESERVER);
            if (empty($server)) {
                $server = $_SERVER['SERVER_NAME'];
            }
            return $server;
        }
    }
}