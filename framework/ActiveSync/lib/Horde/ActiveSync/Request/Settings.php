<?php
/**
 * Horde_ActiveSync_Request_Settings::
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
 * @copyright 2012 Horde LLC (http://www.horde.org)
 * @author    Michael J Rubinsky <mrubinsk@horde.org>
 * @package   ActiveSync
 */
/**
 * Handle Settings requests.
 *
 * @license   http://www.horde.org/licenses/gpl GPLv2
 *            NOTE: According to sec. 8 of the GENERAL PUBLIC LICENSE (GPL),
 *            Version 2, the distribution of the Horde_ActiveSync module in or
 *            to the United States of America is excluded from the scope of this
 *            license.
 * @copyright 2009-2012 Horde LLC (http://www.horde.org)
 * @author    Michael J Rubinsky <mrubinsk@horde.org>
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

    const STATUS_SUCCESS                    = 1;
    const STATUS_ERROR                      = 2;

    const OOF_STATE_ENABLED                 = 1;
    const OOF_STATE_DISABLED                = 0;

    /**
     * Handle the request.
     *
     * @return boolean
     */
    protected function _handle()
    {
        if (!$this->_decoder->getElementStartTag(self::SETTINGS_SETTINGS)) {
            throw new Horde_ActiveSync_Exception('Protocol Errror');
        }

        $request = array();
        while (($reqtype = ($this->_decoder->getElementStartTag(self::SETTINGS_OOF) ? self::SETTINGS_OOF :
               ($this->_decoder->getElementStartTag(self::SETTINGS_DEVICEINFORMATION) ? self::SETTINGS_DEVICEINFORMATION :
               ($this->_decoder->getElementStartTag(self::SETTINGS_USERINFORMATION) ? self::SETTINGS_USERINFORMATION :
               ($this->_decoder->getElementStartTag(self::SETTINGS_DEVICEPASSWORD) ? self::SETTINGS_DEVICEPASSWORD :
               -1))))) != -1) {

            while (($querytype = ($this->_decoder->getElementStartTag(self::SETTINGS_GET) ? self::SETTINGS_GET :
                   ($this->_decoder->getElementStartTag(self::SETTINGS_SET) ? self::SETTINGS_SET :
                   -1))) != -1) {

                switch ($querytype) {
                case self::SETTINGS_GET:
                    switch ($reqtype) {
                    case self::SETTINGS_OOF:
                        if ($this->_decoder->getElementStartTag(self::SETTINGS_BODYTYPE)) {
                            if (($bodytype = $this->_decoder->getElementContent()) !== false) {
                                if (!$this->_decoder->getElementEndTag()) {
                                    throw new Horde_ActiveSync_Exception('Protocol Error'); // end self::SETTINGS BODYTYPE
                                }
                            }
                        }
                        if (!$this->_decoder->getElementEndTag()) {
                            throw new Horde_ActiveSync_Exception('Protocol Error'); // end self::SETTINGS_OOF
                        }
                        $request['get']['oof']['bodytype'] = $bodytype;
                        break;
                    case self::SETTINGS_USERINFORMATION:
                        $request['get']['userinformation'] = array();
                        break;
                    }
                    if (!$this->_decoder->getElementEndTag()) {
                        throw new Horde_ActiveSync_Exception('Protocol Error'); // end self::SETTINGS GET
                    }
                    break;
                case self::SETTINGS_SET:
                    switch ($reqtype) {
                    case self::SETTINGS_OOF:
                        while (($type = ($this->_decoder->getElementStartTag(self::SETTINGS_OOFSTATE) ? self::SETTINGS_OOFSTATE :
                               ($this->_decoder->getElementStartTag(self::SETTINGS_STARTTIME) ? self::SETTINGS_STARTTIME :
                               ($this->_decoder->getElementStartTag(self::SETTINGS_ENDTIME) ? self::SETTINGS_ENDTIME :
                               ($this->_decoder->getElementStartTag(self::SETTINGS_OOFMESSAGE) ? self::SETTINGS_OOFMESSAGE :
                               -1))))) != -1) {

                            switch ($type) {
                            case self::SETTINGS_OOFSTATE:
                                if (($oofstate = $this->_decoder->getElementContent()) !== false) {
                                    $this->_decoder->getElementEndTag();
                                }
                                $request['set']['oof']['oofstate'] = $oofstate;
                                break;
                            case self::SETTINGS_STARTTIME:
                                if (($starttime = $this->_decoder->getElementContent()) !== false) {
                                    $this->_decoder->getElementEndTag();
                                }
                                $request['set']['oof']['starttime'] = $starttime;
                                break;
                            case self::SETTINGS_ENDTIME:
                                if (($endtime = $this->_decoder->getElementContent()) !== false) {
                                    $this->_decoder->getElementEndTag();
                                }
                                $request['set']['oof']['endtime'] = $endtime;
                                break;
                            case self::SETTINGS_OOFMESSAGE:
                                while (($type = ($this->_decoder->getElementStartTag(self::SETTINGS_APPLIESTOINTERNAL) ? self::SETTINGS_APPLIESTOINTERNAL :
                                       ($this->_decoder->getElementStartTag(self::SETTINGS_APPLIESTOEXTERNALKNOWN) ? self::SETTINGS_APPLIESTOEXTERNALKNOWN :
                                       ($this->_decoder->getElementStartTag(self::SETTINGS_APPLIESTOEXTERNALUNKNOWN) ? self::SETTINGS_APPLIESTOEXTERNALUNKNOWN :
                                       -1)))) != -1) {
                                    $oof = array();
                                    $oof['appliesto'] = $type;
                                    while (($type = ($this->_decoder->getElementStartTag(self::SETTINGS_ENABLED) ? self::SETTINGS_ENABLED :
                                           ($this->_decoder->getElementStartTag(self::SETTINGS_REPLYMESSAGE) ? self::SETTINGS_REPLYMESSAGE :
                                           ($this->_decoder->getElementStartTag(self::SETTINGS_BODYTYPE) ? self::SETTINGS_BODYTYPE :
                                           -1)))) != -1) {

                                        switch ($type) {
                                        case self::SETTINGS_ENABLED:
                                            if (($oof['enabled'] = $this->_decoder->getElementContent()) !== false) {
                                                $this->_decoder->getElementEndTag(); // end self::SETTINGS_ENABLED
                                            }
                                            break;
                                        case self::SETTINGS_REPLYMESSAGE:
                                            if (($oof['replymessage'] = $this->_decoder->getElementContent()) !== false) {
                                                $this->_decoder->getElementEndTag(); // end self::SETTINGS_REPLYMESSAGE
                                            }
                                            break;
                                        case self::SETTINGS_BODYTYPE:
                                            if (($oof['bodytype'] = $this->_decoder->getElementContent()) != false) {
                                                $this->_decoder->getElementEndTag(); // end self::SETTINGS_BODYTYPE
                                            }
                                            break;
                                        }
                                    }
                                }
                                if (!isset($request['set']['oof']['oofmsgs'])) {
                                    $request['set']['oof']['oofmsgs'] = array();
                                }
                                $request['set']['oof']['oofmsgs'][] = $oof;
                                $this->_decoder->getElementEndTag(); // end self::SETTINGS_OOFMESSAGE
                                break;
                            }
                        }
                        $this->_decoder->getElementEndTag(); // end self::SETTINGS_OOF
                        break;
                    case self::SETTINGS_DEVICEINFORMATION :
                        while (($field = ($this->_decoder->getElementStartTag(self::SETTINGS_MODEL) ? self::SETTINGS_MODEL :
                               ($this->_decoder->getElementStartTag(self::SETTINGS_IMEI) ? self::SETTINGS_IMEI :
                               ($this->_decoder->getElementStartTag(self::SETTINGS_FRIENDLYNAME) ? self::SETTINGS_FRIENDLYNAME :
                               ($this->_decoder->getElementStartTag(self::SETTINGS_OS) ? self::SETTINGS_OS :
                               ($this->_decoder->getElementStartTag(self::SETTINGS_OSLANGUAGE) ? self::SETTINGS_OSLANGUAGE :
                               ($this->_decoder->getElementStartTag(self::SETTINGS_PHONENUMBER) ? self::SETTINGS_PHONENUMBER :
                               ($this->_decoder->getElementStartTag(self::SETTINGS_USERAGENT) ? self::SETTINGS_USERAGENT :
                               ($this->_decoder->getElementStartTag(self::SETTINGS_MOBILEOPERATOR) ? self::SETTINGS_MOBILEOPERATOR :
                               ($this->_decoder->getElementStartTag(self::SETTINGS_ENABLEOUTBOUNDSMS) ? self::SETTINGS_ENABLEOUTBOUNDSMS :
                               -1)))))))))) != -1) {
                            if (($deviceinfo[$field] = $this->_decoder->getElementContent()) !== false) {
                                $this->_decoder->getElementEndTag(); // end $field
                            }
                        }
                        $request['set']['deviceinformation'] = $deviceinfo;
                        $this->_decoder->getElementEndTag(); // end self::SETTINGS_DEVICEINFORMATION
                        break;
                    case self::SETTINGS_DEVICEPASSWORD :
                        $this->_decoder->getElementStartTag(self::SETTINGS_PASSWORD);
                        if (($password = $this->_decoder->getElementContent()) !== false) {
                            $this->_decoder->getElementEndTag(); // end $field
                        }
                        $request['set']['devicepassword'] = $password;
                        $this->_decoder->getElementEndTag(); // end self::SETTINGS_DEVICEPASSWORD
                        break;
                    }

                    // end self::SETTINGS_SET
                    if (!$this->_decoder->getElementEndTag()) {
                        throw new Horde_ActiveSync_Exception('Protocol Error');
                    }
                    break;
                }
            }
        }
        $this->_decoder->getElementEndTag(); // end self::SETTINGS_SETTINGS

        // Tell the backend
        if (isset($request['set'])) {
            $result['set'] = $this->_driver->setSettings($request['set'], $this->_device->id);
        }
        if (isset($request['get'])) {
            $result['get'] = $this->_driver->getSettings($request['get'], $this->_device->id);
        }

        // Output response
        $this->_encoder->startWBXML();
        $this->_encoder->startTag(self::SETTINGS_SETTINGS);
        $this->_encoder->startTag(self::SETTINGS_STATUS);
        $this->_encoder->content(self::STATUS_SUCCESS);
        $this->_encoder->endTag(); // end self::SETTINGS_STATUS
        if (isset($request['set']['oof'])) {
            $this->_encoder->startTag(self::SETTINGS_OOF);
            $this->_encoder->startTag(self::SETTINGS_STATUS);
            if (!isset($result['set']['oof'])) {
                $this->_encoder->content(0);
            } else {
                $this->_encoder->content($result['set']['oof']);
            }
            $this->_encoder->endTag(); // end self::SETTINGS_STATUS
            $this->_encoder->endTag(); // end self::SETTINGS_OOF
        }
        if (isset($request['set']['deviceinformation'])) {
            $this->_encoder->startTag(self::SETTINGS_DEVICEINFORMATION);
            $this->_encoder->startTag(self::SETTINGS_STATUS);
            if (!isset($result['set']['deviceinformation'])) {
                $this->_encoder->content(0);
            } else {
                $this->_encoder->content($result['set']['deviceinformation']);
            }
            $this->_encoder->endTag(); // end self::SETTINGS_STATUS
            $this->_encoder->endTag(); // end self::SETTINGS_DEVICEINFORMATION
        }
        if (isset($request['set']['devicepassword'])) {
            $this->_encoder->startTag(self::SETTINGS_DEVICEPASSWORD);
            $this->_encoder->startTag(self::SETTINGS_STATUS);
            if (!isset($result['set']['devicepassword'])) {
                $this->_encoder->content(0);
            } else {
                $this->_encoder->content($result['set']['devicepassword']);
            }
            $this->_encoder->endTag(); // end self::SETTINGS_STATUS
            $this->_encoder->endTag(); // end self::SETTINGS_DEVICEPASSWORD
        }
        if (isset($request['get']['userinformation'])) {
            $this->_encoder->startTag(self::SETTINGS_USERINFORMATION);
            $this->_encoder->startTag(self::SETTINGS_STATUS);
            $this->_encoder->content($result['get']['userinformation']['status']);
            $this->_encoder->endTag(); // end self::SETTINGS_STATUS
            $this->_encoder->startTag(self::SETTINGS_GET);
            $this->_encoder->startTag(self::SETTINGS_EMAILADDRESSES);
            foreach($result['get']['userinformation']['emailaddresses'] as $value) {
                $this->_encoder->startTag(self::SETTINGS_SMTPADDRESS);
                $this->_encoder->content($value);
                $this->_encoder->endTag(); // end self::SETTINGS_SMTPADDRESS
            }
            $this->_encoder->endTag(); // end self::SETTINGS_EMAILADDRESSES
            $this->_encoder->endTag(); // end self::SETTINGS_GET
            $this->_encoder->endTag(); // end self::SETTINGS_USERINFORMATION
        }
        if (isset($request['get']['oof'])) {
            $this->_encoder->startTag(self::SETTINGS_OOF);
            $this->_encoder->startTag(self::SETTINGS_STATUS);
            $this->_encoder->content(1);
            $this->_encoder->endTag(); // end self::SETTINGS_STATUS

            $this->_encoder->startTag(self::SETTINGS_GET);
            $this->_encoder->startTag(self::SETTINGS_OOFSTATE);
            $this->_encoder->content($result['get']['oof']['oofstate']);
            $this->_encoder->endTag(); // end self::SETTINGS_OOFSTATE
            // This we maybe need later on (OOFSTATE=2). It shows that OOF
            // Messages could be send depending on Time being set in here.
            // Unfortunately cannot proof it working on my device.
            if ($result['get']['oof']['oofstate'] == 2) {
                $this->_encoder->startTag(self::SETTINGS_STARTTIME);
                $this->_encoder->content(gmdate('Y-m-d\TH:i:s.000', $result['get']['oof']['starttime']));
                $this->_encoder->endTag(); // end self::SETTINGS_STARTTIME
                $this->_encoder->startTag(self::SETTINGS_ENDTIME);
                $this->_encoder->content(gmdate('Y-m-d\TH:i:s.000', $result['get']['oof']['endtime']));
                $this->_encoder->endTag(); // end self::SETTINGS_ENDTIME
            }
            foreach($result['get']['oof']['oofmsgs'] as $oofentry) {
                $this->_encoder->startTag(self::SETTINGS_OOFMESSAGE);
                $this->_encoder->startTag($oofentry['appliesto'],false,true);
                $this->_encoder->startTag(self::SETTINGS_ENABLED);
                $this->_encoder->content($oofentry['enabled']);
                $this->_encoder->endTag(); // end self::SETTINGS_ENABLED
                $this->_encoder->startTag(self::SETTINGS_REPLYMESSAGE);
                $this->_encoder->content($oofentry['replymessage']);
                $this->_encoder->endTag(); // end self::SETTINGS_REPLYMESSAGE
                $this->_encoder->startTag(self::SETTINGS_BODYTYPE);
                switch (strtolower($oofentry['bodytype'])) {
                case 'text':
                    $this->_encoder->content('Text');
                    break;
                case 'HTML':
                    $this->_encoder->content('HTML');
                }
                $this->_encoder->endTag(); // end self::SETTINGS_BODYTYPE
                $this->_encoder->endTag(); // end self::SETTINGS_OOFMESSAGE
            }
            $this->_encoder->endTag(); // end self::SETTINGS_GET
            $this->_encoder->endTag(); // end self::SETTINGS_OOF
        }

        $this->_encoder->endTag(); // end self::SETTINGS_SETTINGS

        return true;
    }

}