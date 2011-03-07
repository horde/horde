<?php
/**
 * Handle Notify requests.
 * 
 * Logic adapted from Z-Push, original copyright notices below.
 *
 * Copyright 2009 - 2010 The Horde Project (http://www.horde.org)
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @package ActiveSync
 */
/**
 * Zarafa Deutschland GmbH, www.zarafaserver.de
 * This file is distributed under GPL v2.
 * Consult LICENSE file for details
 */
// AIRNOTIFY
define("SYNC_AIRNOTIFY_NOTIFY","AirNotify:Notify");
define("SYNC_AIRNOTIFY_NOTIFICATION","AirNotify:Notification");
define("SYNC_AIRNOTIFY_VERSION","AirNotify:Version");
define("SYNC_AIRNOTIFY_LIFETIME","AirNotify:Lifetime");
define("SYNC_AIRNOTIFY_DEVICEINFO","AirNotify:DeviceInfo");
define("SYNC_AIRNOTIFY_ENABLE","AirNotify:Enable");
define("SYNC_AIRNOTIFY_FOLDER","AirNotify:Folder");
define("SYNC_AIRNOTIFY_SERVERENTRYID","AirNotify:ServerEntryId");
define("SYNC_AIRNOTIFY_DEVICEADDRESS","AirNotify:DeviceAddress");
define("SYNC_AIRNOTIFY_VALIDCARRIERPROFILES","AirNotify:ValidCarrierProfiles");
define("SYNC_AIRNOTIFY_CARRIERPROFILE","AirNotify:CarrierProfile");
define("SYNC_AIRNOTIFY_STATUS","AirNotify:Status");
define("SYNC_AIRNOTIFY_REPLIES","AirNotify:Replies");
define("SYNC_AIRNOTIFY_VERSION='1.1'","AirNotify:Version='1.1'");
define("SYNC_AIRNOTIFY_DEVICES","AirNotify:Devices");
define("SYNC_AIRNOTIFY_DEVICE","AirNotify:Device");
define("SYNC_AIRNOTIFY_ID","AirNotify:Id");
define("SYNC_AIRNOTIFY_EXPIRY","AirNotify:Expiry");
define("SYNC_AIRNOTIFY_NOTIFYGUID","AirNotify:NotifyGUID");

class Horde_ActiveSync_Request_Notify extends Horde_ActiveSync_Request_Base
{
    /**
     * Handle request
     *
     * @return boolean
     */
    public function handle()
    {
         if (!$this->_decoder->getElementStartTag(SYNC_AIRNOTIFY_NOTIFY)) {
            return false;
        }

        if (!$this->_decoder->getElementStartTag(SYNC_AIRNOTIFY_DEVICEINFO)) {
            return false;
        }

        if (!$this->_decoder->getElementEndTag()) {
            return false;
        }

        if (!$this->_decoder->getElementEndTag()) {
            return false;
        }
        $this->_encoder->StartWBXML();
        $this->_encoder->startTag(SYNC_AIRNOTIFY_NOTIFY);
        $this->_encoder->startTag(SYNC_AIRNOTIFY_STATUS);
        $this->_encoder->content(1);
        $this->_encoder->endTag();
        $this->_encoder->startTag(SYNC_AIRNOTIFY_VALIDCARRIERPROFILES);
        $this->_encoder->endTag();
        $this->_encoder->endTag();

        return true;
    }
}