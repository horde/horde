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

    /* Status Constants */
    const STATUS_SUCCESS = 1;
    const STATUS_PROTERROR = 2;  // Global status
    const STATUS_NOTDEFINED = 2; // Policy status

    const STATUS_SERVERERROR = 3; // Global
    const STATUS_POLICYUNKNOWN = 3; // Policy

    const STATUS_DEVEXTMANAGED = 4; // Global
    const STATUS_POLICYCORRUPT = 4; // Policy

    const STATUS_POLKEYMISM = 5;

    /* Client -> Server Status */
    const STATUS_CLIENT_SUCCESS = 1;
    const STATUS_CLIENT_PARTIAL = 2; // Only pin was enabled.
    const STATUS_CLIENT_FAILED = 3; // No policies applied at all.
    const STATUS_CLIENT_THIRDPARTY = 4; // Client provisioned by 3rd party?

    const RWSTATUS_NA = 0;
    const RWSTATUS_OK = 1;
    const RWSTATUS_PENDING = 2;
    const RWSTATUS_WIPED = 3;

    const POLICYTYPE_XML = 'MS-WAP-Provisioning-XML';
    const POLICYTYPE_WBXML = 'MS-EAS-Provisioning-WBXML';

    /**
     * Handle the Provision request. This is a 3-phase process. Phase 1 is
     * actually the enforcement, when the server rejects a request and forces
     * the client to perform this PROVISION request...so we are handling phase
     * 2 (download policies) and 3 (acknowledge policies) here.
     *
     * @param Horde_ActiveSync $activeSync  The activesync object.
     *
     * @return boolean
     * @throws Horde_ActiveSync_Exception
     */
    public function handle(Horde_ActiveSync $activeSync, $devId)
    {
        parent::handle($activeSync, $devId);

        /* Get the policy key if it was sent */
        $policykey = $activeSync->getPolicyKey();

        $this->_logger->debug('PIM PolicyKey: ' . $policykey);
        /* Be optimistic */
        $status = self::STATUS_SUCCESS;
        $policyStatus = self::STATUS_SUCCESS;

        /* Start by assuming we are in stage 2 */
        $phase2 = true;
        if (!$this->_decoder->getElementStartTag(SYNC_PROVISION_PROVISION)) {
            return $this->_globalError(self::STATUS_PROTERROR);
        }

        /* Handle android remote wipe */
        if ($this->_decoder->getElementStartTag(SYNC_PROVISION_REMOTEWIPE)) {
            if (!$this->_decoder->getElementStartTag(SYNC_PROVISION_STATUS)) {
                return $this->_globalError(self::STATUS_PROTERROR);
            }
            // TODO: Look at $status here...
            $status = $this->_decoder->getElementContent();
            if (!$this->_decoder->getElementEndTag() ||
                !$this->_decoder->getElementEndTag()) {
                return $this->_globalError(self::STATUS_PROTERROR);
            }
        } else {
            if (!$this->_decoder->getElementStartTag(SYNC_PROVISION_POLICIES) ||
                !$this->_decoder->getElementStartTag(SYNC_PROVISION_POLICY) ||
                !$this->_decoder->getElementStartTag(SYNC_PROVISION_POLICYTYPE)) {

                return $this->_globalError(self::STATUS_PROTERROR);
            }

            $policytype = $this->_decoder->getElementContent();
            if ($policytype != self::POLICYTYPE_XML) {
                $policyStatus = self::STATUS_POLICYUNKNOWN;
            }
            if (!$this->_decoder->getElementEndTag()) {//policytype
                return $this->_globalError(self::STATUS_PROTERROR);
            }

            /* Check to be sure that we *need* to PROVISION */
            if ($this->_provisioning === false) {
                $policyStatus = self::STATUS_NOTDEFINED;
            }

            /* POLICYKEY is only sent by client in phase 3 */
            if ($this->_decoder->getElementStartTag(SYNC_PROVISION_POLICYKEY)) {
                $policykey = $this->_decoder->getElementContent();
                $this->_logger->debug('PHASE 3 policykey sent from PIM: ' . $policykey);
                if (!$this->_decoder->getElementEndTag() ||
                    !$this->_decoder->getElementStartTag(SYNC_PROVISION_STATUS)) {

                    return $this->_globalError(self::STATUS_PROTERROR);
                }
                if ($this->_decoder->getElementContent() != self::STATUS_SUCCESS) {
                    $policyStatus = self::STATUS_POLICYCORRUPT;
                }

                if (!$this->_decoder->getElementEndTag()) {
                    return $this->_globalError(self::STATUS_PROTERROR);
                }
                $phase2 = false;
            }

            if (!$this->_decoder->getElementEndTag() ||
                !$this->_decoder->getElementEndTag()) {

                return $this->_globalError(self::STATUS_PROTERROR);
            }

            /* Handle remote wipe for other devices */
            if ($this->_decoder->getElementStartTag(SYNC_PROVISION_REMOTEWIPE)) {
                if (!$this->_decoder->getElementStartTag(SYNC_PROVISION_STATUS)) {
                    return $this->_globalError(self::STATUS_PROTERROR);
                }
                // @TODO: look at status here??
                $status = $this->_decoder->getElementContent();
                if (!$this->_decoder->getElementEndTag() ||
                    !$this->_decoder->getElementEndTag()) {

                    return $this->_globalError(self::STATUS_PROTERROR);
                }
            }
        }

        if (!$this->_decoder->getElementEndTag()) {//provision
            return $this->_globalError(self::STATUS_PROTERROR);
        }

        /* Start handling request and sending output */
        $this->_encoder->StartWBXML();

        // End of Phase 3 - We create the "final" policy key, store it, then
        // send it to the client.
        $state = $this->_driver->getStateObject();
        if (!$phase2) {
            /* Verify intermediate key */
            if ($state->getPolicyKey($this->_devId) != $policykey) {
                $policyStatus = self::STATUS_POLKEYMISM;
            } else {
                /* Set the final key */
                $policykey = $state->generatePolicyKey();
                $state->setPolicyKey($this->_devId, $policykey);
            }
        } elseif (empty($policykey)) {
            // This is phase2 - we need to set the intermediate key
            $policykey = $state->generatePolicyKey();
            $state->setPolicyKey($this->_devId, $policykey);
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
        $this->_encoder->content($policyStatus);
        $this->_encoder->endTag();
        $this->_encoder->startTag(SYNC_PROVISION_POLICYKEY);
        $this->_encoder->content($policykey);
        $this->_encoder->endTag();

        /* Send security policies - configure this/move to it's own method...*/
        if ($phase2 && $status == self::STATUS_SUCCESS && $policyStatus == self::STATUS_SUCCESS) {
            $this->_encoder->startTag(SYNC_PROVISION_DATA);
            if ($policytype == self::POLICYTYPE_XML) {
                $this->_encoder->content($this->_driver->getCurrentPolicy(self::POLICYTYPE_XML));
            } else {
                // TODO wbxml for 12.0
            }
            $this->_encoder->endTag();//data
        }
        $this->_encoder->endTag();//policy
        $this->_encoder->endTag(); //policies
        $rwstatus = $state->getDeviceRWStatus($this->_devId);

        //wipe data if status is pending or wiped
        if ($rwstatus == self::RWSTATUS_PENDING || $rwstatus == self::RWSTATUS_WIPED) {
            $this->_encoder->startTag(SYNC_PROVISION_REMOTEWIPE, false, true);
            $state->setDeviceRWStatus($this->_devId, self::RWSTATUS_WIPED);
        }

        $this->_encoder->endTag();//provision

        return true;
    }

    private function _globalError($status)
    {
        $this->_encoder->StartWBXML();
        $this->_encoder->startTag(SYNC_PROVISION_PROVISION);
        $this->_encoder->startTag(SYNC_PROVISION_STATUS);
        $this->_encoder->content($status);
        $this->_encoder->endTag();
        $this->_encoder->endTag();

        return false;
    }

}