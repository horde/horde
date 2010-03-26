<?php
/**
 * Horde_ActiveSync_Message_Contact class represents a single ActiveSync
 * Contact object.
 *
 * @copyright 2010 The Horde Project (http://www.horde.org)
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Horde_ActiveSync
 */
class Horde_ActiveSync_Message_Contact extends Horde_ActiveSync_Message_Base
{
    public $anniversary;
    public $assistantname;
    public $assistnamephonenumber;
    public $birthday;
    public $body;
    public $bodysize;
    public $bodytruncated;
    public $business2phonenumber;
    public $businesscity;
    public $businesscountry;
    public $businesspostalcode;
    public $businessstate;
    public $businessstreet;
    public $businessfaxnumber;
    public $businessphonenumber;
    public $carphonenumber;
    public $categories = array();
    public $children = array();
    public $companyname;
    public $department;
    public $email1address;
    public $email2address;
    public $email3address;
    public $fileas;
    public $firstname;
    public $home2phonenumber;
    public $homecity;
    public $homecountry;
    public $homepostalcode;
    public $homestate;
    public $homestreet;
    public $homefaxnumber;
    public $homephonenumber;
    public $jobtitle;
    public $lastname;
    public $middlename;
    public $mobilephonenumber;
    public $officelocation;
    public $othercity;
    public $othercountry;
    public $otherpostalcode;
    public $otherstate;
    public $otherstreet;
    public $pagernumber;
    public $radiophonenumber;
    public $spouse;
    public $suffix;
    public $title;
    public $webpage;
    public $yomicompanyname;
    public $yomifirstname;
    public $yomilastname;
    public $rtf;
    public $picture;
    public $nickname;

    public function __construct($params = array())
    {
        $mapping = array (
            SYNC_POOMCONTACTS_ANNIVERSARY => array (Horde_ActiveSync_Message_Base::KEY_ATTRIBUTE =>  'anniversary', Horde_ActiveSync_Message_Base::KEY_TYPE => Horde_ActiveSync_Message_Base::TYPE_DATE_DASHES  ),
            SYNC_POOMCONTACTS_ASSISTANTNAME => array (Horde_ActiveSync_Message_Base::KEY_ATTRIBUTE => 'assistantname'),
            SYNC_POOMCONTACTS_ASSISTNAMEPHONENUMBER => array (Horde_ActiveSync_Message_Base::KEY_ATTRIBUTE => 'assistnamephonenumber'),
            SYNC_POOMCONTACTS_BIRTHDAY => array (Horde_ActiveSync_Message_Base::KEY_ATTRIBUTE => 'birthday', Horde_ActiveSync_Message_Base::KEY_TYPE => Horde_ActiveSync_Message_Base::TYPE_DATE_DASHES  ),
            SYNC_POOMCONTACTS_BODY => array (Horde_ActiveSync_Message_Base::KEY_ATTRIBUTE => 'body'),
            SYNC_POOMCONTACTS_BODYSIZE => array (Horde_ActiveSync_Message_Base::KEY_ATTRIBUTE => 'bodysize'),
            SYNC_POOMCONTACTS_BODYTRUNCATED => array (Horde_ActiveSync_Message_Base::KEY_ATTRIBUTE => 'bodytruncated'),
            SYNC_POOMCONTACTS_BUSINESS2PHONENUMBER => array (Horde_ActiveSync_Message_Base::KEY_ATTRIBUTE => 'business2phonenumber'),
            SYNC_POOMCONTACTS_BUSINESSCITY => array (Horde_ActiveSync_Message_Base::KEY_ATTRIBUTE => 'businesscity'),
            SYNC_POOMCONTACTS_BUSINESSCOUNTRY => array (Horde_ActiveSync_Message_Base::KEY_ATTRIBUTE => 'businesscountry'),
            SYNC_POOMCONTACTS_BUSINESSPOSTALCODE => array (Horde_ActiveSync_Message_Base::KEY_ATTRIBUTE => 'businesspostalcode'),
            SYNC_POOMCONTACTS_BUSINESSSTATE => array (Horde_ActiveSync_Message_Base::KEY_ATTRIBUTE => 'businessstate'),
            SYNC_POOMCONTACTS_BUSINESSSTREET => array (Horde_ActiveSync_Message_Base::KEY_ATTRIBUTE => 'businessstreet'),
            SYNC_POOMCONTACTS_BUSINESSFAXNUMBER => array (Horde_ActiveSync_Message_Base::KEY_ATTRIBUTE => 'businessfaxnumber'),
            SYNC_POOMCONTACTS_BUSINESSPHONENUMBER => array (Horde_ActiveSync_Message_Base::KEY_ATTRIBUTE => 'businessphonenumber'),
            SYNC_POOMCONTACTS_CARPHONENUMBER => array (Horde_ActiveSync_Message_Base::KEY_ATTRIBUTE => 'carphonenumber'),
            SYNC_POOMCONTACTS_CHILDREN => array (Horde_ActiveSync_Message_Base::KEY_ATTRIBUTE => 'children', Horde_ActiveSync_Message_Base::KEY_VALUES => SYNC_POOMCONTACTS_CHILD ),
            SYNC_POOMCONTACTS_COMPANYNAME => array (Horde_ActiveSync_Message_Base::KEY_ATTRIBUTE => 'companyname'),
            SYNC_POOMCONTACTS_DEPARTMENT => array (Horde_ActiveSync_Message_Base::KEY_ATTRIBUTE => 'department'),
            SYNC_POOMCONTACTS_EMAIL1ADDRESS => array (Horde_ActiveSync_Message_Base::KEY_ATTRIBUTE => 'email1address'),
            SYNC_POOMCONTACTS_EMAIL2ADDRESS => array (Horde_ActiveSync_Message_Base::KEY_ATTRIBUTE => 'email2address'),
            SYNC_POOMCONTACTS_EMAIL3ADDRESS => array (Horde_ActiveSync_Message_Base::KEY_ATTRIBUTE => 'email3address'),
            SYNC_POOMCONTACTS_FILEAS => array (Horde_ActiveSync_Message_Base::KEY_ATTRIBUTE => 'fileas'),
            SYNC_POOMCONTACTS_FIRSTNAME => array (Horde_ActiveSync_Message_Base::KEY_ATTRIBUTE => 'firstname'),
            SYNC_POOMCONTACTS_HOME2PHONENUMBER => array (Horde_ActiveSync_Message_Base::KEY_ATTRIBUTE => 'home2phonenumber'),
            SYNC_POOMCONTACTS_HOMECITY => array (Horde_ActiveSync_Message_Base::KEY_ATTRIBUTE => 'homecity'),
            SYNC_POOMCONTACTS_HOMECOUNTRY => array (Horde_ActiveSync_Message_Base::KEY_ATTRIBUTE => 'homecountry'),
            SYNC_POOMCONTACTS_HOMEPOSTALCODE => array (Horde_ActiveSync_Message_Base::KEY_ATTRIBUTE => 'homepostalcode'),
            SYNC_POOMCONTACTS_HOMESTATE => array (Horde_ActiveSync_Message_Base::KEY_ATTRIBUTE => 'homestate'),
            SYNC_POOMCONTACTS_HOMESTREET => array (Horde_ActiveSync_Message_Base::KEY_ATTRIBUTE => 'homestreet'),
            SYNC_POOMCONTACTS_HOMEFAXNUMBER => array (Horde_ActiveSync_Message_Base::KEY_ATTRIBUTE => 'homefaxnumber'),
            SYNC_POOMCONTACTS_HOMEPHONENUMBER => array (Horde_ActiveSync_Message_Base::KEY_ATTRIBUTE => 'homephonenumber'),
            SYNC_POOMCONTACTS_JOBTITLE => array (Horde_ActiveSync_Message_Base::KEY_ATTRIBUTE => 'jobtitle'),
            SYNC_POOMCONTACTS_LASTNAME => array (Horde_ActiveSync_Message_Base::KEY_ATTRIBUTE => 'lastname'),
            SYNC_POOMCONTACTS_MIDDLENAME => array (Horde_ActiveSync_Message_Base::KEY_ATTRIBUTE => 'middlename'),
            SYNC_POOMCONTACTS_MOBILEPHONENUMBER => array (Horde_ActiveSync_Message_Base::KEY_ATTRIBUTE => 'mobilephonenumber'),
            SYNC_POOMCONTACTS_OFFICELOCATION => array (Horde_ActiveSync_Message_Base::KEY_ATTRIBUTE => 'officelocation'),
            SYNC_POOMCONTACTS_OTHERCITY => array (Horde_ActiveSync_Message_Base::KEY_ATTRIBUTE => 'othercity'),
            SYNC_POOMCONTACTS_OTHERCOUNTRY => array (Horde_ActiveSync_Message_Base::KEY_ATTRIBUTE => 'othercountry'),
            SYNC_POOMCONTACTS_OTHERPOSTALCODE => array (Horde_ActiveSync_Message_Base::KEY_ATTRIBUTE => 'otherpostalcode'),
            SYNC_POOMCONTACTS_OTHERSTATE => array (Horde_ActiveSync_Message_Base::KEY_ATTRIBUTE => 'otherstate'),
            SYNC_POOMCONTACTS_OTHERSTREET => array (Horde_ActiveSync_Message_Base::KEY_ATTRIBUTE => 'otherstreet'),
            SYNC_POOMCONTACTS_PAGERNUMBER => array (Horde_ActiveSync_Message_Base::KEY_ATTRIBUTE => 'pagernumber'),
            SYNC_POOMCONTACTS_RADIOPHONENUMBER => array (Horde_ActiveSync_Message_Base::KEY_ATTRIBUTE => 'radiophonenumber'),
            SYNC_POOMCONTACTS_SPOUSE => array (Horde_ActiveSync_Message_Base::KEY_ATTRIBUTE => 'spouse'),
            SYNC_POOMCONTACTS_SUFFIX => array (Horde_ActiveSync_Message_Base::KEY_ATTRIBUTE => 'suffix'),
            SYNC_POOMCONTACTS_TITLE => array (Horde_ActiveSync_Message_Base::KEY_ATTRIBUTE => 'title'),
            SYNC_POOMCONTACTS_WEBPAGE => array (Horde_ActiveSync_Message_Base::KEY_ATTRIBUTE => 'webpage'),
            SYNC_POOMCONTACTS_YOMICOMPANYNAME => array (Horde_ActiveSync_Message_Base::KEY_ATTRIBUTE => 'yomicompanyname'),
            SYNC_POOMCONTACTS_YOMIFIRSTNAME => array (Horde_ActiveSync_Message_Base::KEY_ATTRIBUTE => 'yomifirstname'),
            SYNC_POOMCONTACTS_YOMILASTNAME => array (Horde_ActiveSync_Message_Base::KEY_ATTRIBUTE => 'yomilastname'),
            SYNC_POOMCONTACTS_RTF => array (Horde_ActiveSync_Message_Base::KEY_ATTRIBUTE => 'rtf'),
            SYNC_POOMCONTACTS_PICTURE => array (Horde_ActiveSync_Message_Base::KEY_ATTRIBUTE => 'picture'),
            SYNC_POOMCONTACTS_CATEGORIES => array (Horde_ActiveSync_Message_Base::KEY_ATTRIBUTE => 'categories', Horde_ActiveSync_Message_Base::KEY_VALUES => SYNC_POOMCONTACTS_CATEGORY ),
        );

        /* Additional mappings for AS versions >= 2.5 */
        if (isset($params['protocolversion']) && $params['protocolversion'] >= 2.5) {
            $mapping += array(
                SYNC_POOMCONTACTS2_CUSTOMERID => array (Horde_ActiveSync_Message_Base::KEY_ATTRIBUTE => 'customerid'),
                SYNC_POOMCONTACTS2_GOVERNMENTID => array (Horde_ActiveSync_Message_Base::KEY_ATTRIBUTE => 'governmentid'),
                SYNC_POOMCONTACTS2_IMADDRESS => array (Horde_ActiveSync_Message_Base::KEY_ATTRIBUTE => 'imaddress'),
                SYNC_POOMCONTACTS2_IMADDRESS2 => array (Horde_ActiveSync_Message_Base::KEY_ATTRIBUTE => 'imaddress2'),
                SYNC_POOMCONTACTS2_IMADDRESS3 => array (Horde_ActiveSync_Message_Base::KEY_ATTRIBUTE => 'imaddress3'),
                SYNC_POOMCONTACTS2_MANAGERNAME => array (Horde_ActiveSync_Message_Base::KEY_ATTRIBUTE => 'managername'),
                SYNC_POOMCONTACTS2_COMPANYMAINPHONE => array (Horde_ActiveSync_Message_Base::KEY_ATTRIBUTE => 'companymainphone'),
                SYNC_POOMCONTACTS2_ACCOUNTNAME => array (Horde_ActiveSync_Message_Base::KEY_ATTRIBUTE => 'accountname'),
                SYNC_POOMCONTACTS2_NICKNAME => array (Horde_ActiveSync_Message_Base::KEY_ATTRIBUTE => 'nickname'),
                SYNC_POOMCONTACTS2_MMS => array (Horde_ActiveSync_Message_Base::KEY_ATTRIBUTE => 'mms'),
            );
        }

        parent::__construct($mapping, $params);
    }

    public function getClass()
    {
        return 'Contacts';
    }
}
