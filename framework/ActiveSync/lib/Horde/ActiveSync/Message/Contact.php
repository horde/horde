<?php
/**
 * Horde_ActiveSync_Message_Contact::
 *
 * Portions of this class were ported from the Z-Push project:
 *   File      :   wbxml.php
 *   Project   :   Z-Push
 *   Descr     :   WBXML mapping file
 *
 *   Created   :   01.10.2007
 *
 *   � Zarafa Deutschland GmbH, www.zarafaserver.de
 *   This file is distributed under GPL-2.0.
 *   Consult COPYING file for details
 *
 * @license   http://www.horde.org/licenses/gpl GPLv2
 *            NOTE: According to sec. 8 of the GENERAL PUBLIC LICENSE (GPL),
 *            Version 2, the distribution of the Horde_ActiveSync module in or
 *            to the United States of America is excluded from the scope of this
 *            license.
 * @copyright 2010-2015 Horde LLC (http://www.horde.org)
 * @author    Michael J Rubinsky <mrubinsk@horde.org>
 * @package   ActiveSync
 */
/**
 * Horde_ActiveSync_Message_Contact::
 *
 * @license   http://www.horde.org/licenses/gpl GPLv2
 *            NOTE: According to sec. 8 of the GENERAL PUBLIC LICENSE (GPL),
 *            Version 2, the distribution of the Horde_ActiveSync module in or
 *            to the United States of America is excluded from the scope of this
 *            license.
 * @copyright 2010-2015 Horde LLC (http://www.horde.org)
 * @author    Michael J Rubinsky <mrubinsk@horde.org>
 * @package   ActiveSync
 *
 * @property Horde_Date   $anniversary
 * @property string   $assistantname
 * @property string   $assistnamephonenumber
 * @property Horde_Date   $birthday
 * @property string   $business2phonenumber
 * @property string   $businesscity
 * @property string   $businesscountry
 * @property string   $businesspostalcode
 * @property string   $businessstate
 * @property string   $businessstreet
 * @property string   $businessfaxnumber
 * @property string   $businessphonenumber
 * @property string   $carphonenumber
 * @property array   $categories
 * @property array   $children
 * @property string   $companyname
 * @property string   $department
 * @property string   $email1address
 * @property string   $email2address
 * @property string   $email3address
 * @property string   $fileas
 * @property string   $firstname
 * @property string   $home2phonenumber
 * @property string   $homecity
 * @property string   $homecountry
 * @property string   $homepostalcode
 * @property string   $homestate
 * @property string   $homestreet
 * @property string   $homefaxnumber
 * @property string   $homephonenumber
 * @property string   $jobtitle
 * @property string   $lastname
 * @property string   $middlename
 * @property string   $mobilephonenumber
 * @property string   $officelocation
 * @property string   $othercity
 * @property string   $othercountry
 * @property string   $otherpostalcode
 * @property string   $otherstate
 * @property string   $otherstreet
 * @property string   $pagernumber
 * @property string   $radiophonenumber
 * @property string   $spouse
 * @property string   $suffix
 * @property string   $title
 * @property string   $webpage
 * @property string   $yomicompanyname
 * @property string   $yomifirstname
 * @property string   $yomilastname
 * @property string   $picture
 * @property string   $customerid
 * @property string   $governmentid
 * @property string   $imaddress
 * @property string   $imaddress2
 * @property string   $imaddress3
 * @property string   $managername
 * @property string   $companymainphone
 * @property string   $accountname
 * @property string   $nickname
 * @property string   $mms
 * @property string   $alias (EAS >= 14.0 only)
 * @property string   $weightedrank (EAS >= 14.0 only)
 * @property string   $body (EAS 2.5 only)
 * @property integer   $bodysize (EAS 2.5 only)
 * @property integer   $bodytruncated (EAS 2.5 only)
 * @property integer   $rtf (EAS 2.5 only)
 * @property Horde_ActiveSync_Message_AirSyncBaseBody   $airsyncbasebody (EAS >= 12.0 only)
 */
class Horde_ActiveSync_Message_Contact extends Horde_ActiveSync_Message_Base
{
    /* POOMCONTACTS */
    const ANNIVERSARY           = 'POOMCONTACTS:Anniversary';
    const ASSISTANTNAME         = 'POOMCONTACTS:AssistantName';
    const ASSISTNAMEPHONENUMBER = 'POOMCONTACTS:AssistnamePhoneNumber';
    const BIRTHDAY              = 'POOMCONTACTS:Birthday';
    const BODY                  = 'POOMCONTACTS:Body';
    const BODYSIZE              = 'POOMCONTACTS:BodySize';
    const BODYTRUNCATED         = 'POOMCONTACTS:BodyTruncated';
    const BUSINESS2PHONENUMBER  = 'POOMCONTACTS:Business2PhoneNumber';
    const BUSINESSCITY          = 'POOMCONTACTS:BusinessCity';
    const BUSINESSCOUNTRY       = 'POOMCONTACTS:BusinessCountry';
    const BUSINESSPOSTALCODE    = 'POOMCONTACTS:BusinessPostalCode';
    const BUSINESSSTATE         = 'POOMCONTACTS:BusinessState';
    const BUSINESSSTREET        = 'POOMCONTACTS:BusinessStreet';
    const BUSINESSFAXNUMBER     = 'POOMCONTACTS:BusinessFaxNumber';
    const BUSINESSPHONENUMBER   = 'POOMCONTACTS:BusinessPhoneNumber';
    const CARPHONENUMBER        = 'POOMCONTACTS:CarPhoneNumber';
    const CATEGORIES            = 'POOMCONTACTS:Categories';
    const CATEGORY              = 'POOMCONTACTS:Category';
    const CHILDREN              = 'POOMCONTACTS:Children';
    const CHILD                 = 'POOMCONTACTS:Child';
    const COMPANYNAME           = 'POOMCONTACTS:CompanyName';
    const DEPARTMENT            = 'POOMCONTACTS:Department';
    const EMAIL1ADDRESS         = 'POOMCONTACTS:Email1Address';
    const EMAIL2ADDRESS         = 'POOMCONTACTS:Email2Address';
    const EMAIL3ADDRESS         = 'POOMCONTACTS:Email3Address';
    const FILEAS                = 'POOMCONTACTS:FileAs';
    const FIRSTNAME             = 'POOMCONTACTS:FirstName';
    const HOME2PHONENUMBER      = 'POOMCONTACTS:Home2PhoneNumber';
    const HOMECITY              = 'POOMCONTACTS:HomeCity';
    const HOMECOUNTRY           = 'POOMCONTACTS:HomeCountry';
    const HOMEPOSTALCODE        = 'POOMCONTACTS:HomePostalCode';
    const HOMESTATE             = 'POOMCONTACTS:HomeState';
    const HOMESTREET            = 'POOMCONTACTS:HomeStreet';
    const HOMEFAXNUMBER         = 'POOMCONTACTS:HomeFaxNumber';
    const HOMEPHONENUMBER       = 'POOMCONTACTS:HomePhoneNumber';
    const JOBTITLE              = 'POOMCONTACTS:JobTitle';
    const LASTNAME              = 'POOMCONTACTS:LastName';
    const MIDDLENAME            = 'POOMCONTACTS:MiddleName';
    const MOBILEPHONENUMBER     = 'POOMCONTACTS:MobilePhoneNumber';
    const OFFICELOCATION        = 'POOMCONTACTS:OfficeLocation';
    const OTHERCITY             = 'POOMCONTACTS:OtherCity';
    const OTHERCOUNTRY          = 'POOMCONTACTS:OtherCountry';
    const OTHERPOSTALCODE       = 'POOMCONTACTS:OtherPostalCode';
    const OTHERSTATE            = 'POOMCONTACTS:OtherState';
    const OTHERSTREET           = 'POOMCONTACTS:OtherStreet';
    const PAGERNUMBER           = 'POOMCONTACTS:PagerNumber';
    const RADIOPHONENUMBER      = 'POOMCONTACTS:RadioPhoneNumber';
    const SPOUSE                = 'POOMCONTACTS:Spouse';
    const SUFFIX                = 'POOMCONTACTS:Suffix';
    const TITLE                 = 'POOMCONTACTS:Title';
    const WEBPAGE               = 'POOMCONTACTS:WebPage';
    const YOMICOMPANYNAME       = 'POOMCONTACTS:YomiCompanyName';
    const YOMIFIRSTNAME         = 'POOMCONTACTS:YomiFirstName';
    const YOMILASTNAME          = 'POOMCONTACTS:YomiLastName';
    const RTF                   = 'POOMCONTACTS:Rtf';
    const PICTURE               = 'POOMCONTACTS:Picture';

    /* POOMCONTACTS2 */
    const CUSTOMERID            = 'POOMCONTACTS2:CustomerId';
    const GOVERNMENTID          = 'POOMCONTACTS2:GovernmentId';
    const IMADDRESS             = 'POOMCONTACTS2:IMAddress';
    const IMADDRESS2            = 'POOMCONTACTS2:IMAddress2';
    const IMADDRESS3            = 'POOMCONTACTS2:IMAddress3';
    const MANAGERNAME           = 'POOMCONTACTS2:ManagerName';
    const COMPANYMAINPHONE      = 'POOMCONTACTS2:CompanyMainPhone';
    const ACCOUNTNAME           = 'POOMCONTACTS2:AccountName';
    const NICKNAME              = 'POOMCONTACTS2:NickName';
    const MMS                   = 'POOMCONTACTS2:MMS';

    /* EAS 14 (Only used in Recipient Information Cache responses) */
    const ALIAS                 = 'POOMCONTACTS:Alias';
    const WEIGHTEDRANK          = 'POOMCONTACTS:WeightedRank';

    public $categories = array();

    /**
     * Property mapping.
     *
     * @var array
     */
    protected $_mapping = array(
        self::ANNIVERSARY           => array(self::KEY_ATTRIBUTE => 'anniversary', self::KEY_TYPE => self::TYPE_DATE_DASHES),
        self::ASSISTANTNAME         => array(self::KEY_ATTRIBUTE => 'assistantname'),
        self::ASSISTNAMEPHONENUMBER => array(self::KEY_ATTRIBUTE => 'assistnamephonenumber'),
        self::BIRTHDAY              => array(self::KEY_ATTRIBUTE => 'birthday', self::KEY_TYPE => self::TYPE_DATE_DASHES),
        self::BUSINESS2PHONENUMBER  => array(self::KEY_ATTRIBUTE => 'business2phonenumber'),
        self::BUSINESSCITY          => array(self::KEY_ATTRIBUTE => 'businesscity'),
        self::BUSINESSCOUNTRY       => array(self::KEY_ATTRIBUTE => 'businesscountry'),
        self::BUSINESSPOSTALCODE    => array(self::KEY_ATTRIBUTE => 'businesspostalcode'),
        self::BUSINESSSTATE         => array(self::KEY_ATTRIBUTE => 'businessstate'),
        self::BUSINESSSTREET        => array(self::KEY_ATTRIBUTE => 'businessstreet'),
        self::BUSINESSFAXNUMBER     => array(self::KEY_ATTRIBUTE => 'businessfaxnumber'),
        self::BUSINESSPHONENUMBER   => array(self::KEY_ATTRIBUTE => 'businessphonenumber'),
        self::CARPHONENUMBER        => array(self::KEY_ATTRIBUTE => 'carphonenumber'),
        self::CHILDREN              => array(self::KEY_ATTRIBUTE => 'children', self::KEY_VALUES => self::CHILD),
        self::COMPANYNAME           => array(self::KEY_ATTRIBUTE => 'companyname'),
        self::DEPARTMENT            => array(self::KEY_ATTRIBUTE => 'department'),
        self::EMAIL1ADDRESS         => array(self::KEY_ATTRIBUTE => 'email1address'),
        self::EMAIL2ADDRESS         => array(self::KEY_ATTRIBUTE => 'email2address'),
        self::EMAIL3ADDRESS         => array(self::KEY_ATTRIBUTE => 'email3address'),
        self::FILEAS                => array(self::KEY_ATTRIBUTE => 'fileas'),
        self::FIRSTNAME             => array(self::KEY_ATTRIBUTE => 'firstname'),
        self::HOME2PHONENUMBER      => array(self::KEY_ATTRIBUTE => 'home2phonenumber'),
        self::HOMECITY              => array(self::KEY_ATTRIBUTE => 'homecity'),
        self::HOMECOUNTRY           => array(self::KEY_ATTRIBUTE => 'homecountry'),
        self::HOMEPOSTALCODE        => array(self::KEY_ATTRIBUTE => 'homepostalcode'),
        self::HOMESTATE             => array(self::KEY_ATTRIBUTE => 'homestate'),
        self::HOMESTREET            => array(self::KEY_ATTRIBUTE => 'homestreet'),
        self::HOMEFAXNUMBER         => array(self::KEY_ATTRIBUTE => 'homefaxnumber'),
        self::HOMEPHONENUMBER       => array(self::KEY_ATTRIBUTE => 'homephonenumber'),
        self::JOBTITLE              => array(self::KEY_ATTRIBUTE => 'jobtitle'),
        self::LASTNAME              => array(self::KEY_ATTRIBUTE => 'lastname'),
        self::MIDDLENAME            => array(self::KEY_ATTRIBUTE => 'middlename'),
        self::MOBILEPHONENUMBER     => array(self::KEY_ATTRIBUTE => 'mobilephonenumber'),
        self::OFFICELOCATION        => array(self::KEY_ATTRIBUTE => 'officelocation'),
        self::OTHERCITY             => array(self::KEY_ATTRIBUTE => 'othercity'),
        self::OTHERCOUNTRY          => array(self::KEY_ATTRIBUTE => 'othercountry'),
        self::OTHERPOSTALCODE       => array(self::KEY_ATTRIBUTE => 'otherpostalcode'),
        self::OTHERSTATE            => array(self::KEY_ATTRIBUTE => 'otherstate'),
        self::OTHERSTREET           => array(self::KEY_ATTRIBUTE => 'otherstreet'),
        self::PAGERNUMBER           => array(self::KEY_ATTRIBUTE => 'pagernumber'),
        self::RADIOPHONENUMBER      => array(self::KEY_ATTRIBUTE => 'radiophonenumber'),
        self::SPOUSE                => array(self::KEY_ATTRIBUTE => 'spouse'),
        self::SUFFIX                => array(self::KEY_ATTRIBUTE => 'suffix'),
        self::TITLE                 => array(self::KEY_ATTRIBUTE => 'title'),
        self::WEBPAGE               => array(self::KEY_ATTRIBUTE => 'webpage'),
        self::YOMICOMPANYNAME       => array(self::KEY_ATTRIBUTE => 'yomicompanyname'),
        self::YOMIFIRSTNAME         => array(self::KEY_ATTRIBUTE => 'yomifirstname'),
        self::YOMILASTNAME          => array(self::KEY_ATTRIBUTE => 'yomilastname'),
        self::PICTURE               => array(self::KEY_ATTRIBUTE => 'picture'),
        self::CATEGORIES            => array(self::KEY_ATTRIBUTE => 'categories', self::KEY_VALUES => self::CATEGORY),

        // POOMCONTACTS2
        self::CUSTOMERID            => array(self::KEY_ATTRIBUTE => 'customerid'),
        self::GOVERNMENTID          => array(self::KEY_ATTRIBUTE => 'governmentid'),
        self::IMADDRESS             => array(self::KEY_ATTRIBUTE => 'imaddress'),
        self::IMADDRESS2            => array(self::KEY_ATTRIBUTE => 'imaddress2'),
        self::IMADDRESS3            => array(self::KEY_ATTRIBUTE => 'imaddress3'),
        self::MANAGERNAME           => array(self::KEY_ATTRIBUTE => 'managername'),
        self::COMPANYMAINPHONE      => array(self::KEY_ATTRIBUTE => 'companymainphone'),
        self::ACCOUNTNAME           => array(self::KEY_ATTRIBUTE => 'accountname'),
        self::NICKNAME              => array(self::KEY_ATTRIBUTE => 'nickname'),
        self::MMS                   => array(self::KEY_ATTRIBUTE => 'mms'),
    );

    /**
     * Property values.
     *
     * @var array
     */
    protected $_properties = array(
        'anniversary'           => false,
        'assistantname'         => false,
        'assistnamephonenumber' => false,
        'birthday'              => false,
        'business2phonenumber'  => false,
        'businesscity'          => false,
        'businesscountry'       => false,
        'businesspostalcode'    => false,
        'businessstate'         => false,
        'businessstreet'        => false,
        'businessfaxnumber'     => false,
        'businessphonenumber'   => false,
        'carphonenumber'        => false,
        'children'              => array(),
        'companyname'           => false,
        'department'            => false,
        'email1address'         => false,
        'email2address'         => false,
        'email3address'         => false,
        'fileas'                => false,
        'firstname'             => false,
        'home2phonenumber'      => false,
        'homecity'              => false,
        'homecountry'           => false,
        'homepostalcode'        => false,
        'homestate'             => false,
        'homestreet'            => false,
        'homefaxnumber'         => false,
        'homephonenumber'       => false,
        'jobtitle'              => false,
        'lastname'              => false,
        'middlename'            => false,
        'mobilephonenumber'     => false,
        'officelocation'        => false,
        'othercity'             => false,
        'othercountry'          => false,
        'otherpostalcode'       => false,
        'otherstate'            => false,
        'otherstreet'           => false,
        'pagernumber'           => false,
        'radiophonenumber'      => false,
        'spouse'                => false,
        'suffix'                => false,
        'title'                 => false,
        'webpage'               => false,
        'yomicompanyname'       => false,
        'yomifirstname'         => false,
        'yomilastname'          => false,
        'picture'               => false,
        'categories'            => false,

        // POOMCONTACTS2
        'customerid'            => false,
        'governmentid'          => false,
        'imaddress'             => false,
        'imaddress2'            => false,
        'imaddress3'            => false,
        'managername'           => false,
        'companymainphone'      => false,
        'accountname'           => false,
        'nickname'              => false,
        'mms'                   => false,
    );

    /**
     * Const'r
     *
     * @see Horde_ActiveSync_Message_Base::__construct()
     */
    public function __construct(array $options = array())
    {
        parent::__construct($options);
        if ($this->_version < Horde_ActiveSync::VERSION_TWELVE) {
            $this->_mapping += array(
                self::BODY                  => array(self::KEY_ATTRIBUTE => 'body'),
                self::BODYSIZE              => array(self::KEY_ATTRIBUTE => 'bodysize'),
                self::BODYTRUNCATED         => array(self::KEY_ATTRIBUTE => 'bodytruncated'),
                self::RTF                   => array(self::KEY_ATTRIBUTE => 'rtf'),
            );

            $this->_properties += array(
                'body'                  => false,
                'bodysize'              => false,
                'bodytruncated'         => 0,
                'rtf'                   => false
            );
        } else {
            $this->_mapping += array(
                Horde_ActiveSync::AIRSYNCBASE_BODY => array(self::KEY_ATTRIBUTE => 'airsyncbasebody', self::KEY_TYPE => 'Horde_ActiveSync_Message_AirSyncBaseBody')
            );
            $this->_properties += array(
                'airsyncbasebody' => false
            );
            if ($this->_version > Horde_ActiveSync::VERSION_TWELVEONE) {
                $this->_mapping += array(
                    self::ALIAS => array(self::KEY_ATTRIBUTE => 'alias'),
                    self::WEIGHTEDRANK => array(self::KEY_ATTRIBUTE  => 'weightedrank')
                );
                $this->_properties += array(
                    'alias' => false,
                    'weightedrank' => false
                );
            }
        }
    }

    /**
     * Return message type
     *
     * @return string
     */
    public function getClass()
    {
        return 'Contacts';
    }

    /**
     * Check if we should send a specific property even if it's empty.
     *
     * @param string $tag  The property tag.
     *
     * @return boolean
     */
    protected function _checkSendEmpty($tag)
    {
        if ($tag == self::BODYTRUNCATED && $this->bodysize > 0) {
            return true;
        }

        return false;
    }

    /**
     * Override parent class so we can normalize the Date object before
     * returning it.
     *
     * @param string $ts  The timestamp
     *
     * @return Horde_Date|boolean  The Horde_Date object (UTC) or false if
     *     unable to parse the date.
     */
    protected function _parseDate($ts)
    {
        $date = parent::_parseDate($ts);

        // Since some clients send the date as YYYY-MM-DD only, the best we can
        // do is assume that it is in the same timezone as the user's default
        // timezone - so convert it to UTC and be done with it.
        if ($date->timezone != 'UTC') {
            $date->setTimezone('UTC');
        }
        // @todo: Remove this in H6.
        if (empty($this->_device)) {
            return $date;
        }

        return $this->_device->normalizePoomContactsDates($date);
    }

    /**
     * Format a date string for sending to the EAS client.
     *
     * @param Horde_Date $dt  The Horde_Date object to format
     *                        (should normally be in local tz).
     * @param integer $type   The type to format as (TYPE_DATE or TYPE_DATE_DASHES)
     *
     * @return string  The formatted date
     */
    protected function _formatDate(Horde_Date $dt, $type)
    {
        if (empty($this->_device)) {
            $date = $dt;
        } else {
            $date = $this->_device->normalizePoomContactsDates($dt, true);
        }
        return parent::_formatDate($date, $type);
    }

    /**
     * Determines if the property specified has been ghosted by the client.
     * A ghosted property 1) IS listed in the supported list and 2) NOT
     * present in the current message. If it's IN the supported list and NOT
     * in the current message, then it IS ghosted and the server should keep
     * the field's current value when performing any change action due to this
     * message.
     *
     * @param string $property  The property to check
     *
     * @return boolean
     */
    public function isGhosted($property)
    {
        $isGhosted = parent::isGhosted($property);
        if (!$isGhosted &&
            $property == $this->_mapping[self::PICTURE][self::KEY_ATTRIBUTE] &&
            $this->_device->hasQuirk(Horde_ActiveSync_Device::QUIRK_NEEDS_SUPPORTED_PICTURE_TAG)) {
            return true;
        }

        return $isGhosted;
    }

}
