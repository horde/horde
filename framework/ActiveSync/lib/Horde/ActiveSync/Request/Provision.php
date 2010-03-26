<?php
/**
 * Handle PROVISION requests
 *
 * Logic adapted from Z-Push, original copyright notices below.
 *
 * Copyright 2009 - 2010 The Horde Project (http://www.horde.org)
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Horde_ActiveSync
 */
/**
 * Zarafa Deutschland GmbH, www.zarafaserver.de
 * This file is distributed under GPL v2.
 * Consult LICENSE file for details
 */
class Horde_ActiveSync_Request_Provision extends Horde_ActiveSync_Request_Base
{

    public function handle(Horde_ActiveSync $activeSync)
    {
        $policykey = $activeSync->getPolicyKey();

        $status = SYNC_PROVISION_STATUS_SUCCESS;
        $phase2 = true;
        if (!$this->_decoder->getElementStartTag(SYNC_PROVISION_PROVISION)) {
            return false;
        }

        //handle android remote wipe.
        if ($this->_decoder->getElementStartTag(SYNC_PROVISION_REMOTEWIPE)) {
            if (!$this->_decoder->getElementStartTag(SYNC_PROVISION_STATUS)) {
                return false;
            }

            $status = $this->_decoder->getElementContent();

            if (!$this->_decoder->getElementEndTag()) {
                return false;
            }

            if (!$this->_decoder->getElementEndTag()) {
                return false;
            }
        } else {
            if (!$this->_decoder->getElementStartTag(SYNC_PROVISION_POLICIES)) {
                return false;
            }

            if (!$this->_decoder->getElementStartTag(SYNC_PROVISION_POLICY)) {
                return false;
            }

            if (!$this->_decoder->getElementStartTag(SYNC_PROVISION_POLICYTYPE)) {
                return false;
            }

            $policytype = $this->_decoder->getElementContent();
            if ($policytype != 'MS-WAP-Provisioning-XML') {
                $status = SYNC_PROVISION_STATUS_SERVERERROR;
            }
            if (!$this->_decoder->getElementEndTag()) {//policytype
                return false;
            }

            if ($this->_decoder->getElementStartTag(SYNC_PROVISION_POLICYKEY)) {
                // This should be Phase 3 of the Provision conversation...
                // We get the intermediate policy key sent back from the client.
                // TODO: Still need to verify the key once we have some kind of
                // storage for it.
                $policykey = $this->_decoder->getElementContent();
                if (!$this->_decoder->getElementEndTag()) {
                    return false;
                }

                if (!$this->_decoder->getElementStartTag(SYNC_PROVISION_STATUS)) {
                    return false;
                }

                $status = $this->_decoder->getElementContent();
                //do status handling
                $status = SYNC_PROVISION_STATUS_SUCCESS;

                if (!$this->_decoder->getElementEndTag()) {
                    return false;
                }
                $phase2 = false;
            }

            if (!$this->_decoder->getElementEndTag()) {//policy
                return false;
            }

            if (!$this->_decoder->getElementEndTag()) {//policies
                return false;
            }

            if ($this->_decoder->getElementStartTag(SYNC_PROVISION_REMOTEWIPE)) {
                if (!$this->_decoder->getElementStartTag(SYNC_PROVISION_STATUS)) {
                    return false;
                }

                $status = $this->_decoder->getElementContent();
                if (!$this->_decoder->getElementEndTag()) {
                    return false;
                }

                if (!$this->_decoder->getElementEndTag()) {
                    return false;
                }
            }
        }

        if (!$this->_decoder->getElementEndTag()) {//provision
            return false;
        }
        $this->_encoder->StartWBXML();

        // End of Phase 3 - We create the "final" policy key, store it, then
        // send it to the client.
        if (!$phase2) {
            $policykey = $this->_driver->generatePolicyKey();
            $this->_driver->setPolicyKey($policykey, $this->_devId);
        }

        $this->_encoder->startTag(SYNC_PROVISION_PROVISION);

        $this->_encoder->startTag(SYNC_PROVISION_STATUS);
        $this->_encoder->content($status);
        $this->_encoder->endTag();

        $this->_encoder->startTag(SYNC_PROVISION_POLICIES);
        $this->_encoder->startTag(SYNC_PROVISION_POLICY);

        $this->_encoder->startTag(SYNC_PROVISION_POLICYTYPE);
        $this->_encoder->content($policytype);
        $this->_encoder->endTag();
        $this->_encoder->startTag(SYNC_PROVISION_STATUS);
        $this->_encoder->content($status);
        $this->_encoder->endTag();
        $this->_encoder->startTag(SYNC_PROVISION_POLICYKEY);
        $this->_encoder->content($policykey);
        $this->_encoder->endTag();
        if ($phase2) {
            // If we are in Phase 2, send the security policies.
            // TODO: Configure this!
            $this->_encoder->startTag(SYNC_PROVISION_DATA);
            if ($policytype == 'MS-WAP-Provisioning-XML') {
                // Set 4131 to 0 to require a PIN, 4133
                $this->_encoder->content('<wap-provisioningdoc><characteristic type="SecurityPolicy"><parm name="4131" value="1"/><parm name="4133" value="0"/></characteristic></wap-provisioningdoc>');
            } else {
                $this->_logger->err('Wrong policy type');
                return false;
            }
            $this->_encoder->endTag();//data
        }
        $this->_encoder->endTag();//policy
        $this->_encoder->endTag(); //policies
        $rwstatus = $this->_driver->getDeviceRWStatus($this->_devId);

        //wipe data if status is pending or wiped
        if ($rwstatus == SYNC_PROVISION_RWSTATUS_PENDING || $rwstatus == SYNC_PROVISION_RWSTATUS_WIPED) {
            $this->_encoder->startTag(SYNC_PROVISION_REMOTEWIPE, false, true);
            $this->_driver->setDeviceRWStatus($this->_devId, SYNC_PROVISION_RWSTATUS_WIPED);
            //$rwstatus = SYNC_PROVISION_RWSTATUS_WIPED;
        }

        $this->_encoder->endTag();//provision

        return true;
    }

}