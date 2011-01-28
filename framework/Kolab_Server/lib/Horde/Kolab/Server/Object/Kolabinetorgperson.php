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
class Horde_Kolab_Server_Object_Kolabinetorgperson extends Horde_Kolab_Server_Object_Inetorgperson
{
    /** The specific object class of this object type */
    const OBJECTCLASS_KOLABINETORGPERSON = 'kolabInetOrgPerson';

    /** Define attributes specific to this object type */

    /**
     * The attributes defined for this class.
     *
     * @var array
     */
    static public $attributes = array(
        'alias', 'kolabHomeServer', 'kolabFreebusyHost'
/*         'kolabDelegate', 'kolabDeleteFlag', 'kolabFreeBusyFuture', */
/*         , , 'kolabImapServer', */
/*         'kolabInvitationPolicy', 'kolabSalutation', 'gender', */
/*         'kolabMaritalStatus', 'homeFacsimileTelephoneNumber', 'germanTaxId', */
/*         'c', 'cyrus-userquota', 'kolabAllowSMTPRecipient', 'kolabAllowSMTPFrom', */
/*         'apple-birthday', 'apple-birthdayDate', 'birthPlace', 'birthName', */
/*         'pseudonym', 'countryOfCitizenship', 'legalForm', */
/*         'tradeRegisterRegisteredCapital', 'bylawURI', 'dateOfIncorporation', */
/*         'legalRepresentative', 'commercialProcuration', */
/*         'legalRepresentationPolicy', 'actingDeputy', 'VATNumber', */
/*         'otherLegalRelationship', 'inLiquidation', 'tradeRegisterType', */
/*         'tradeRegisterLocation', 'tradeRegisterIdentifier', 'tradeRegisterURI', */
/*         'tradeRegisterLastChangedDate', 'domainComponent', */
    );


    /**
     * A structure to initialize the attribute structure for this class.
     *
     * @var array
     */
/*     static public $init_attributes = array( */
/*         'defined' => array( */
/*             self::ATTRIBUTE_DELEGATE, */
/*             self::ATTRIBUTE_DELETED, */
/*             self::ATTRIBUTE_FBFUTURE, */
/*             self::ATTRIBUTE_HOMESERVER, */
/*             self::ATTRIBUTE_FREEBUSYHOST, */
/*             self::ATTRIBUTE_IMAPHOST, */
/*             self::ATTRIBUTE_IPOLICY, */
/*             self::ATTRIBUTE_SALUTATION, */
/*             self::ATTRIBUTE_GENDER, */
/*             self::ATTRIBUTE_MARITALSTATUS, */
/*             self::ATTRIBUTE_HOMEFAX, */
/*             self::ATTRIBUTE_GERMANTAXID, */
/*             self::ATTRIBUTE_COUNTRY, */
/*             self::ATTRIBUTE_QUOTA, */
/*             self::ATTRIBUTE_ALLOWEDRECIPIENTS, */
/*             self::ATTRIBUTE_ALLOWEDFROM, */
/*             self::ATTRIBUTE_DATEOFBIRTH, */
/*             self::ATTRIBUTE_PLACEOFBIRTH, */
/*             self::ATTRIBUTE_BIRTHNAME, */
/*             self::ATTRIBUTE_PSEUDONYM, */
/*             self::ATTRIBUTE_COUNTRYCITIZENSHIP, */
/*             self::ATTRIBUTE_LEGALFORM, */
/*             self::ATTRIBUTE_REGISTEREDCAPITAL, */
/*             self::ATTRIBUTE_BYLAWURI, */
/*             self::ATTRIBUTE_DATEOFINCORPORATION, */
/*             self::ATTRIBUTE_LEGALREPRESENTATIVE, */
/*             self::ATTRIBUTE_COMMERCIALPROCURATION, */
/*             self::ATTRIBUTE_LEGALREPRESENTATIONPOLICY, */
/*             self::ATTRIBUTE_ACTINGDEPUTY, */
/*             self::ATTRIBUTE_VATNUMBER, */
/*             self::ATTRIBUTE_OTHERLEGAL, */
/*             self::ATTRIBUTE_INLIQUIDATION, */
/*             self::ATTRIBUTE_TRTYPE, */
/*             self::ATTRIBUTE_TRLOCATION, */
/*             self::ATTRIBUTE_TRIDENTIFIER, */
/*             self::ATTRIBUTE_TRURI, */
/*             self::ATTRIBUTE_TRLASTCHANGED, */
/*             self::ATTRIBUTE_DC, */
/*         ), */
/*         'locked' => array( */
/*             self::ATTRIBUTE_MAIL, */
/*         ), */
/*         /\** */
/*          * Derived attributes are calculated based on other attribute values. */
/*          *\/ */
/*         'derived' => array( */
/*             self::ATTRDATE_DATEOFBIRTH => array( */
/*                 'method' => 'getDate', */
/*                 'args' => array( */
/*                     self::ATTRIBUTE_DATEOFBIRTH, */
/*                 ), */
/*             ), */
/*         ), */
/*         'object_classes' => array( */
/*             self::OBJECTCLASS_KOLABINETORGPERSON, */
/*         ), */
/*     ); */

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
        $filter = new Horde_Kolab_Server_Query_Element_Equals(
            'Objectclass',
            self::OBJECTCLASS_KOLABINETORGPERSON
        );
        return $filter;
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