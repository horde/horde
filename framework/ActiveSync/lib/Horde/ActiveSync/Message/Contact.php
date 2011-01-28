<?php
/**
 * Horde_ActiveSync_Message_Contact class represents a single ActiveSync
 * Contact object.
 *
 * @copyright 2010-2011 The Horde Project (http://www.horde.org)
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Horde_ActiveSync
 */
class Horde_ActiveSync_Message_Contact extends Horde_ActiveSync_Message_Base
{
    /* Workaround for issues with arrays from __get() */
    public $categories = array();
    public $children = array();
    public $bodytruncated = 0;

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
        /* Mappings for the encoder */
        $this->_mapping = array (
            self::ANNIVERSARY => array (self::KEY_ATTRIBUTE =>  'anniversary', self::KEY_TYPE => self::TYPE_DATE_DASHES),
            self::ASSISTANTNAME => array (self::KEY_ATTRIBUTE => 'assistantname'),
            self::ASSISTNAMEPHONENUMBER => array (self::KEY_ATTRIBUTE => 'assistnamephonenumber'),
            self::BIRTHDAY => array (self::KEY_ATTRIBUTE => 'birthday', self::KEY_TYPE => self::TYPE_DATE_DASHES),
            self::BODY => array (self::KEY_ATTRIBUTE => 'body'),
            self::BODYSIZE => array (self::KEY_ATTRIBUTE => 'bodysize'),
            self::BODYTRUNCATED => array (self::KEY_ATTRIBUTE => 'bodytruncated'),
            self::BUSINESS2PHONENUMBER => array (self::KEY_ATTRIBUTE => 'business2phonenumber'),
            self::BUSINESSCITY => array (self::KEY_ATTRIBUTE => 'businesscity'),
            self::BUSINESSCOUNTRY => array (self::KEY_ATTRIBUTE => 'businesscountry'),
            self::BUSINESSPOSTALCODE => array (self::KEY_ATTRIBUTE => 'businesspostalcode'),
            self::BUSINESSSTATE => array (self::KEY_ATTRIBUTE => 'businessstate'),
            self::BUSINESSSTREET => array (self::KEY_ATTRIBUTE => 'businessstreet'),
            self::BUSINESSFAXNUMBER => array (self::KEY_ATTRIBUTE => 'businessfaxnumber'),
            self::BUSINESSPHONENUMBER => array (self::KEY_ATTRIBUTE => 'businessphonenumber'),
            self::CARPHONENUMBER => array (self::KEY_ATTRIBUTE => 'carphonenumber'),
            self::CHILDREN => array (self::KEY_ATTRIBUTE => 'children', self::KEY_VALUES => self::CHILD),
            self::COMPANYNAME => array (self::KEY_ATTRIBUTE => 'companyname'),
            self::DEPARTMENT => array (self::KEY_ATTRIBUTE => 'department'),
            self::EMAIL1ADDRESS => array (self::KEY_ATTRIBUTE => 'email1address'),
            self::EMAIL2ADDRESS => array (self::KEY_ATTRIBUTE => 'email2address'),
            self::EMAIL3ADDRESS => array (self::KEY_ATTRIBUTE => 'email3address'),
            self::FILEAS => array (self::KEY_ATTRIBUTE => 'fileas'),
            self::FIRSTNAME => array (self::KEY_ATTRIBUTE => 'firstname'),
            self::HOME2PHONENUMBER => array (self::KEY_ATTRIBUTE => 'home2phonenumber'),
            self::HOMECITY => array (self::KEY_ATTRIBUTE => 'homecity'),
            self::HOMECOUNTRY => array (self::KEY_ATTRIBUTE => 'homecountry'),
            self::HOMEPOSTALCODE => array (self::KEY_ATTRIBUTE => 'homepostalcode'),
            self::HOMESTATE => array (self::KEY_ATTRIBUTE => 'homestate'),
            self::HOMESTREET => array (self::KEY_ATTRIBUTE => 'homestreet'),
            self::HOMEFAXNUMBER => array (self::KEY_ATTRIBUTE => 'homefaxnumber'),
            self::HOMEPHONENUMBER => array (self::KEY_ATTRIBUTE => 'homephonenumber'),
            self::JOBTITLE => array (self::KEY_ATTRIBUTE => 'jobtitle'),
            self::LASTNAME => array (self::KEY_ATTRIBUTE => 'lastname'),
            self::MIDDLENAME => array (self::KEY_ATTRIBUTE => 'middlename'),
            self::MOBILEPHONENUMBER => array (self::KEY_ATTRIBUTE => 'mobilephonenumber'),
            self::OFFICELOCATION => array (self::KEY_ATTRIBUTE => 'officelocation'),
            self::OTHERCITY => array (self::KEY_ATTRIBUTE => 'othercity'),
            self::OTHERCOUNTRY => array (self::KEY_ATTRIBUTE => 'othercountry'),
            self::OTHERPOSTALCODE => array (self::KEY_ATTRIBUTE => 'otherpostalcode'),
            self::OTHERSTATE => array (self::KEY_ATTRIBUTE => 'otherstate'),
            self::OTHERSTREET => array (self::KEY_ATTRIBUTE => 'otherstreet'),
            self::PAGERNUMBER => array (self::KEY_ATTRIBUTE => 'pagernumber'),
            self::RADIOPHONENUMBER => array (self::KEY_ATTRIBUTE => 'radiophonenumber'),
            self::SPOUSE => array (self::KEY_ATTRIBUTE => 'spouse'),
            self::SUFFIX => array (self::KEY_ATTRIBUTE => 'suffix'),
            self::TITLE => array (self::KEY_ATTRIBUTE => 'title'),
            self::WEBPAGE => array (self::KEY_ATTRIBUTE => 'webpage'),
            self::YOMICOMPANYNAME => array (self::KEY_ATTRIBUTE => 'yomicompanyname'),
            self::YOMIFIRSTNAME => array (self::KEY_ATTRIBUTE => 'yomifirstname'),
            self::YOMILASTNAME => array (self::KEY_ATTRIBUTE => 'yomilastname'),
            self::RTF => array (self::KEY_ATTRIBUTE => 'rtf'),
            self::PICTURE => array (self::KEY_ATTRIBUTE => 'picture'),
            self::CATEGORIES => array (self::KEY_ATTRIBUTE => 'categories', self::KEY_VALUES => self::CATEGORY),
        );

        /* Accepted property values */
        $this->_properties = array(
            'anniversary' => false,
            'assistantname' => false,
            'assistnamephonenumber' => false,
            'birthday' => false,
            'body' => false,
            'bodysize' => false,
            'bodytruncated' => false,
            'business2phonenumber' => false,
            'businesscity' => false,
            'businesscountry' => false,
            'businesspostalcode' => false,
            'businessstate' => false,
            'businessstreet' => false,
            'businessfaxnumber' => false,
            'businessphonenumber' => false,
            'carphonenumber' => false,
            'children' => false,
            'companyname' => false,
            'department' => false,
            'email1address' => false,
            'email2address' => false,
            'email3address' => false,
            'fileas' => false,
            'firstname' => false,
            'home2phonenumber' => false,
            'homecity' => false,
            'homecountry' => false,
            'homepostalcode' => false,
            'homestate' => false,
            'homestreet' => false,
            'homefaxnumber' => false,
            'homephonenumber' => false,
            'jobtitle' => false,
            'lastname' => false,
            'middlename' => false,
            'mobilephonenumber' => false,
            'officelocation' => false,
            'othercity' => false,
            'othercountry' => false,
            'otherpostalcode' => false,
            'otherstate' => false,
            'otherstreet' => false,
            'pagernumber' => false,
            'radiophonenumber' => false,
            'spouse' => false,
            'suffix' => false,
            'title' => false,
            'webpage' => false,
            'yomicompanyname' => false,
            'yomifirstname' => false,
            'yomilastname' => false,
            'rtf' => false,
            'picture' => false,
            'categories' => false,
        );

        /* Additional mappings for AS versions >= 2.5 */
        if (isset($params['protocolversion']) && $params['protocolversion'] >= 2.5) {
            $this->_mapping += array(
                self::CUSTOMERID => array (self::KEY_ATTRIBUTE => 'customerid'),
                self::GOVERNMENTID => array (self::KEY_ATTRIBUTE => 'governmentid'),
                self::IMADDRESS => array (self::KEY_ATTRIBUTE => 'imaddress'),
                self::IMADDRESS2 => array (self::KEY_ATTRIBUTE => 'imaddress2'),
                self::IMADDRESS3 => array (self::KEY_ATTRIBUTE => 'imaddress3'),
                self::MANAGERNAME => array (self::KEY_ATTRIBUTE => 'managername'),
                self::COMPANYMAINPHONE => array (self::KEY_ATTRIBUTE => 'companymainphone'),
                self::ACCOUNTNAME => array (self::KEY_ATTRIBUTE => 'accountname'),
                self::NICKNAME => array (self::KEY_ATTRIBUTE => 'nickname'),
                self::MMS => array (self::KEY_ATTRIBUTE => 'mms'),
            );

            $this->_properties += array(
                'customerid' => false,
                'governmentid' => false,
                'imaddress' => false,
                'imaddress2' => false,
                'imaddress3' => false,
                'managername' => false,
                'companymainphone' => false,
                'accountname' => false,
                'nickname' => false,
                'mms' => false,
            );
        }

        parent::__construct($params);
    }

    public function getClass()
    {
        return 'Contacts';
    }

    protected function _checkSendEmpty($tag)
    {
        if ($tag == self::BODYTRUNCATED && $this->bodysize > 0) {
            return true;
        }

        return false;
    }

}
