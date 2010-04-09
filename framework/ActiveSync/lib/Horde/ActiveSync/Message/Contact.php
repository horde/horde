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
    public $categories = array();
    public $children = array();


    /* POOMCONTACTS */
    const ANNIVERSARY = "POOMCONTACTS:Anniversary";
    const ASSISTANTNAME = "POOMCONTACTS:AssistantName";
    const ASSISTNAMEPHONENUMBER = "POOMCONTACTS:AssistnamePhoneNumber";
    const BIRTHDAY = "POOMCONTACTS:Birthday";
    const BODY = "POOMCONTACTS:Body";
    const BODYSIZE = "POOMCONTACTS:BodySize";
    const BODYTRUNCATED = "POOMCONTACTS:BodyTruncated";
    const BUSINESS2PHONENUMBER = "POOMCONTACTS:Business2PhoneNumber";
    const BUSINESSCITY = "POOMCONTACTS:BusinessCity";
    const BUSINESSCOUNTRY = "POOMCONTACTS:BusinessCountry";
    const BUSINESSPOSTALCODE = "POOMCONTACTS:BusinessPostalCode";
    const BUSINESSSTATE = "POOMCONTACTS:BusinessState";
    const BUSINESSSTREET = "POOMCONTACTS:BusinessStreet";
    const BUSINESSFAXNUMBER = "POOMCONTACTS:BusinessFaxNumber";
    const BUSINESSPHONENUMBER = "POOMCONTACTS:BusinessPhoneNumber";
    const CARPHONENUMBER = "POOMCONTACTS:CarPhoneNumber";
    const CATEGORIES = "POOMCONTACTS:Categories";
    const CATEGORY = "POOMCONTACTS:Category";
    const CHILDREN = "POOMCONTACTS:Children";
    const CHILD = "POOMCONTACTS:Child";
    const COMPANYNAME = "POOMCONTACTS:CompanyName";
    const DEPARTMENT = "POOMCONTACTS:Department";
    const EMAIL1ADDRESS = "POOMCONTACTS:Email1Address";
    const EMAIL2ADDRESS = "POOMCONTACTS:Email2Address";
    const EMAIL3ADDRESS = "POOMCONTACTS:Email3Address";
    const FILEAS = "POOMCONTACTS:FileAs";
    const FIRSTNAME = "POOMCONTACTS:FirstName";
    const HOME2PHONENUMBER = "POOMCONTACTS:Home2PhoneNumber";
    const HOMECITY = "POOMCONTACTS:HomeCity";
    const HOMECOUNTRY = "POOMCONTACTS:HomeCountry";
    const HOMEPOSTALCODE = "POOMCONTACTS:HomePostalCode";
    const HOMESTATE = "POOMCONTACTS:HomeState";
    const HOMESTREET = "POOMCONTACTS:HomeStreet";
    const HOMEFAXNUMBER = "POOMCONTACTS:HomeFaxNumber";
    const HOMEPHONENUMBER = "POOMCONTACTS:HomePhoneNumber";
    const JOBTITLE = "POOMCONTACTS:JobTitle";
    const LASTNAME = "POOMCONTACTS:LastName";
    const MIDDLENAME = "POOMCONTACTS:MiddleName";
    const MOBILEPHONENUMBER = "POOMCONTACTS:MobilePhoneNumber";
    const OFFICELOCATION = "POOMCONTACTS:OfficeLocation";
    const OTHERCITY = "POOMCONTACTS:OtherCity";
    const OTHERCOUNTRY = "POOMCONTACTS:OtherCountry";
    const OTHERPOSTALCODE = "POOMCONTACTS:OtherPostalCode";
    const OTHERSTATE = "POOMCONTACTS:OtherState";
    const OTHERSTREET = "POOMCONTACTS:OtherStreet";
    const PAGERNUMBER = "POOMCONTACTS:PagerNumber";
    const RADIOPHONENUMBER = "POOMCONTACTS:RadioPhoneNumber";
    const SPOUSE = "POOMCONTACTS:Spouse";
    const SUFFIX = "POOMCONTACTS:Suffix";
    const TITLE = "POOMCONTACTS:Title";
    const WEBPAGE = "POOMCONTACTS:WebPage";
    const YOMICOMPANYNAME = "POOMCONTACTS:YomiCompanyName";
    const YOMIFIRSTNAME = "POOMCONTACTS:YomiFirstName";
    const YOMILASTNAME = "POOMCONTACTS:YomiLastName";
    const RTF = "POOMCONTACTS:Rtf";
    const PICTURE = "POOMCONTACTS:Picture";

    /* POOMCONTACTS2 */
    const CUSTOMERID = "POOMCONTACTS2:CustomerId";
    const GOVERNMENTID = "POOMCONTACTS2:GovernmentId";
    const IMADDRESS = "POOMCONTACTS2:IMAddress";
    const IMADDRESS2 = "POOMCONTACTS2:IMAddress2";
    const IMADDRESS3 = "POOMCONTACTS2:IMAddress3";
    const MANAGERNAME = "POOMCONTACTS2:ManagerName";
    const COMPANYMAINPHONE = "POOMCONTACTS2:CompanyMainPhone";
    const ACCOUNTNAME = "POOMCONTACTS2:AccountName";
    const NICKNAME = "POOMCONTACTS2:NickName";
    const MMS = "POOMCONTACTS2:MMS";

    /**
     * Const'r
     *
     * @param array $params
     *
     * @return Horde_ActiveSync_Message_Contact
     */
    public function __construct($params = array())
    {
        $mapping = array (
            self::ANNIVERSARY => array (Horde_ActiveSync_Message_Base::KEY_ATTRIBUTE =>  'anniversary', Horde_ActiveSync_Message_Base::KEY_TYPE => Horde_ActiveSync_Message_Base::TYPE_DATE_DASHES),
            self::ASSISTANTNAME => array (Horde_ActiveSync_Message_Base::KEY_ATTRIBUTE => 'assistantname'),
            self::ASSISTNAMEPHONENUMBER => array (Horde_ActiveSync_Message_Base::KEY_ATTRIBUTE => 'assistnamephonenumber'),
            self::BIRTHDAY => array (Horde_ActiveSync_Message_Base::KEY_ATTRIBUTE => 'birthday', Horde_ActiveSync_Message_Base::KEY_TYPE => Horde_ActiveSync_Message_Base::TYPE_DATE_DASHES),
            self::BODY => array (Horde_ActiveSync_Message_Base::KEY_ATTRIBUTE => 'body'),
            self::BODYSIZE => array (Horde_ActiveSync_Message_Base::KEY_ATTRIBUTE => 'bodysize'),
            self::BODYTRUNCATED => array (Horde_ActiveSync_Message_Base::KEY_ATTRIBUTE => 'bodytruncated'),
            self::BUSINESS2PHONENUMBER => array (Horde_ActiveSync_Message_Base::KEY_ATTRIBUTE => 'business2phonenumber'),
            self::BUSINESSCITY => array (Horde_ActiveSync_Message_Base::KEY_ATTRIBUTE => 'businesscity'),
            self::BUSINESSCOUNTRY => array (Horde_ActiveSync_Message_Base::KEY_ATTRIBUTE => 'businesscountry'),
            self::BUSINESSPOSTALCODE => array (Horde_ActiveSync_Message_Base::KEY_ATTRIBUTE => 'businesspostalcode'),
            self::BUSINESSSTATE => array (Horde_ActiveSync_Message_Base::KEY_ATTRIBUTE => 'businessstate'),
            self::BUSINESSSTREET => array (Horde_ActiveSync_Message_Base::KEY_ATTRIBUTE => 'businessstreet'),
            self::BUSINESSFAXNUMBER => array (Horde_ActiveSync_Message_Base::KEY_ATTRIBUTE => 'businessfaxnumber'),
            self::BUSINESSPHONENUMBER => array (Horde_ActiveSync_Message_Base::KEY_ATTRIBUTE => 'businessphonenumber'),
            self::CARPHONENUMBER => array (Horde_ActiveSync_Message_Base::KEY_ATTRIBUTE => 'carphonenumber'),
            self::CHILDREN => array (Horde_ActiveSync_Message_Base::KEY_ATTRIBUTE => 'children', Horde_ActiveSync_Message_Base::KEY_VALUES => self::CHILD),
            self::COMPANYNAME => array (Horde_ActiveSync_Message_Base::KEY_ATTRIBUTE => 'companyname'),
            self::DEPARTMENT => array (Horde_ActiveSync_Message_Base::KEY_ATTRIBUTE => 'department'),
            self::EMAIL1ADDRESS => array (Horde_ActiveSync_Message_Base::KEY_ATTRIBUTE => 'email1address'),
            self::EMAIL2ADDRESS => array (Horde_ActiveSync_Message_Base::KEY_ATTRIBUTE => 'email2address'),
            self::EMAIL3ADDRESS => array (Horde_ActiveSync_Message_Base::KEY_ATTRIBUTE => 'email3address'),
            self::FILEAS => array (Horde_ActiveSync_Message_Base::KEY_ATTRIBUTE => 'fileas'),
            self::FIRSTNAME => array (Horde_ActiveSync_Message_Base::KEY_ATTRIBUTE => 'firstname'),
            self::HOME2PHONENUMBER => array (Horde_ActiveSync_Message_Base::KEY_ATTRIBUTE => 'home2phonenumber'),
            self::HOMECITY => array (Horde_ActiveSync_Message_Base::KEY_ATTRIBUTE => 'homecity'),
            self::HOMECOUNTRY => array (Horde_ActiveSync_Message_Base::KEY_ATTRIBUTE => 'homecountry'),
            self::HOMEPOSTALCODE => array (Horde_ActiveSync_Message_Base::KEY_ATTRIBUTE => 'homepostalcode'),
            self::HOMESTATE => array (Horde_ActiveSync_Message_Base::KEY_ATTRIBUTE => 'homestate'),
            self::HOMESTREET => array (Horde_ActiveSync_Message_Base::KEY_ATTRIBUTE => 'homestreet'),
            self::HOMEFAXNUMBER => array (Horde_ActiveSync_Message_Base::KEY_ATTRIBUTE => 'homefaxnumber'),
            self::HOMEPHONENUMBER => array (Horde_ActiveSync_Message_Base::KEY_ATTRIBUTE => 'homephonenumber'),
            self::JOBTITLE => array (Horde_ActiveSync_Message_Base::KEY_ATTRIBUTE => 'jobtitle'),
            self::LASTNAME => array (Horde_ActiveSync_Message_Base::KEY_ATTRIBUTE => 'lastname'),
            self::MIDDLENAME => array (Horde_ActiveSync_Message_Base::KEY_ATTRIBUTE => 'middlename'),
            self::MOBILEPHONENUMBER => array (Horde_ActiveSync_Message_Base::KEY_ATTRIBUTE => 'mobilephonenumber'),
            self::OFFICELOCATION => array (Horde_ActiveSync_Message_Base::KEY_ATTRIBUTE => 'officelocation'),
            self::OTHERCITY => array (Horde_ActiveSync_Message_Base::KEY_ATTRIBUTE => 'othercity'),
            self::OTHERCOUNTRY => array (Horde_ActiveSync_Message_Base::KEY_ATTRIBUTE => 'othercountry'),
            self::OTHERPOSTALCODE => array (Horde_ActiveSync_Message_Base::KEY_ATTRIBUTE => 'otherpostalcode'),
            self::OTHERSTATE => array (Horde_ActiveSync_Message_Base::KEY_ATTRIBUTE => 'otherstate'),
            self::OTHERSTREET => array (Horde_ActiveSync_Message_Base::KEY_ATTRIBUTE => 'otherstreet'),
            self::PAGERNUMBER => array (Horde_ActiveSync_Message_Base::KEY_ATTRIBUTE => 'pagernumber'),
            self::RADIOPHONENUMBER => array (Horde_ActiveSync_Message_Base::KEY_ATTRIBUTE => 'radiophonenumber'),
            self::SPOUSE => array (Horde_ActiveSync_Message_Base::KEY_ATTRIBUTE => 'spouse'),
            self::SUFFIX => array (Horde_ActiveSync_Message_Base::KEY_ATTRIBUTE => 'suffix'),
            self::TITLE => array (Horde_ActiveSync_Message_Base::KEY_ATTRIBUTE => 'title'),
            self::WEBPAGE => array (Horde_ActiveSync_Message_Base::KEY_ATTRIBUTE => 'webpage'),
            self::YOMICOMPANYNAME => array (Horde_ActiveSync_Message_Base::KEY_ATTRIBUTE => 'yomicompanyname'),
            self::YOMIFIRSTNAME => array (Horde_ActiveSync_Message_Base::KEY_ATTRIBUTE => 'yomifirstname'),
            self::YOMILASTNAME => array (Horde_ActiveSync_Message_Base::KEY_ATTRIBUTE => 'yomilastname'),
            self::RTF => array (Horde_ActiveSync_Message_Base::KEY_ATTRIBUTE => 'rtf'),
            self::PICTURE => array (Horde_ActiveSync_Message_Base::KEY_ATTRIBUTE => 'picture'),
            self::CATEGORIES => array (Horde_ActiveSync_Message_Base::KEY_ATTRIBUTE => 'categories', Horde_ActiveSync_Message_Base::KEY_VALUES => self::CATEGORY),
        );

        /* Additional mappings for AS versions >= 2.5 */
        if (isset($params['protocolversion']) && $params['protocolversion'] >= 2.5) {
            $mapping += array(
                self::CUSTOMERID => array (Horde_ActiveSync_Message_Base::KEY_ATTRIBUTE => 'customerid'),
                self::GOVERNMENTID => array (Horde_ActiveSync_Message_Base::KEY_ATTRIBUTE => 'governmentid'),
                self::IMADDRESS => array (Horde_ActiveSync_Message_Base::KEY_ATTRIBUTE => 'imaddress'),
                self::IMADDRESS2 => array (Horde_ActiveSync_Message_Base::KEY_ATTRIBUTE => 'imaddress2'),
                self::IMADDRESS3 => array (Horde_ActiveSync_Message_Base::KEY_ATTRIBUTE => 'imaddress3'),
                self::MANAGERNAME => array (Horde_ActiveSync_Message_Base::KEY_ATTRIBUTE => 'managername'),
                self::COMPANYMAINPHONE => array (Horde_ActiveSync_Message_Base::KEY_ATTRIBUTE => 'companymainphone'),
                self::ACCOUNTNAME => array (Horde_ActiveSync_Message_Base::KEY_ATTRIBUTE => 'accountname'),
                self::NICKNAME => array (Horde_ActiveSync_Message_Base::KEY_ATTRIBUTE => 'nickname'),
                self::MMS => array (Horde_ActiveSync_Message_Base::KEY_ATTRIBUTE => 'mms'),
            );
        }

        parent::__construct($mapping, $params);
    }

    public function getClass()
    {
        return 'Contacts';
    }

}
