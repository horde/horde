<?php
/**
 * Horde_ActiveSync_Request_Settings
 *
 * PHP Version 5
 *
 * Contains portions of code from ZPush
 * Zarafa Deutschland GmbH, www.zarafaserver.de
 *
 * @license   http://www.horde.org/licenses/gpl GPLv2
 * @copyright 2012 Horde LLC (http://www.horde.org/)
 * @author    Michael J Rubinsky <mrubinsk@horde.org>
 * @link      http://pear.horde.org/index.php?package=ActiveSync
 * @package   ActiveSync
 */
/**
 * ActiveSync Handler for SETTING requests
 *
 * @license   http://www.horde.org/licenses/gpl GPLv2
 * @copyright 2012 Horde LLC (http://www.horde.org/)
 * @author    Michael J Rubinsky <mrubinsk@horde.org>
 * @link      http://pear.horde.org/index.php?package=ActiveSync
 * @package   ActiveSync
 */

class Horde_ActiveSync_Request_Settings extends Horde_ActiveSync_Request_Base
{

    const SETTINGS_SETTINGS                 = 'Settings:Settings';
    const SETTINGS_STATUS                   = 'Settings:Status';
    const SETTINGS_GET                      = 'Settings:Get';
    const SETTINGS_SET                      = 'Settings:Set';
    const SETTINGS_OOF                      = 'Settings:Oof';
    const SETTINGS_OOFSTATE                 = 'Settings:OofState';
    const SETTINGS_STARTTIME                = 'Settings:StartTime';
    const SETTINGS_ENDTIME                  = 'Settings:EndTime';
    const SETTINGS_OOFMESSAGE               = 'Settings:OofMessage';
    const SETTINGS_APPLIESTOINTERNAL        = 'Settings:AppliesToInternal';
    const SETTINGS_APPLIESTOEXTERNALKNOWN   = 'Settings:AppliesToExternalKnown';
    const SETTINGS_APPLIESTOEXTERNALUNKNOWN = 'Settings:AppliesToExternalUnknown';
    const SETTINGS_ENABLED                  = 'Settings:Enabled';
    const SETTINGS_REPLYMESSAGE             = 'Settings:ReplyMessage';
    const SETTINGS_BODYTYPE                 = 'Settings:BodyType';
    const SETTINGS_DEVICEPASSWORD           = 'Settings:DevicePassword';
    const SETTINGS_PASSWORD                 = 'Settings:Password';
    const SETTINGS_DEVICEINFORMATION        = 'Settings:DeviceInformation';
    const SETTINGS_MODEL                    = 'Settings:Model';
    const SETTINGS_IMEI                     = 'Settings:IMEI';
    const SETTINGS_FRIENDLYNAME             = 'Settings:FriendlyName';
    const SETTINGS_OS                       = 'Settings:OS';
    const SETTINGS_OSLANGUAGE               = 'Settings:OSLanguage';
    const SETTINGS_PHONENUMBER              = 'Settings:PhoneNumber';
    const SETTINGS_USERINFORMATION          = 'Settings:UserInformation';
    const SETTINGS_EMAILADDRESSES           = 'Settings:EmailAddresses';
    const SETTINGS_SMTPADDRESS              = 'Settings:SmtpAddress';
    const SETTINGS_USERAGENT                = 'Settings:UserAgent';
    const SETTINGS_ENABLEOUTBOUNDSMS        = 'Settings:EnableOutboundSMS';
    const SETTINGS_MOBILEOPERATOR           = 'Settings:MobileOperator';

    /**
     * Handle the request.
     *
     * @return boolean
     */
    protected function _handle()
    {

    }

}