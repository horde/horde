<?php
/**
 * Horde_ActiveSync_Request_Provision::
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
 * @copyright 2009-2012 Horde LLC (http://www.horde.org)
 * @author    Michael J Rubinsky <mrubinsk@horde.org>
 * @package   ActiveSync
 */
/**
 * Hanlde Provision requests.
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
class Horde_ActiveSync_Request_Provision extends Horde_ActiveSync_Request_Base
{

    /* Status Constants */
    const STATUS_SUCCESS           = 1;
    const STATUS_PROTERROR         = 2; // Global status
    const STATUS_NOTDEFINED        = 2; // Policy status

    const STATUS_SERVERERROR       = 3; // Global
    const STATUS_POLICYUNKNOWN     = 3; // Policy

    const STATUS_DEVEXTMANAGED     = 4; // Global
    const STATUS_POLICYCORRUPT     = 4; // Policy

    const STATUS_POLKEYMISM        = 5;

    /* Client -> Server Status */
    const STATUS_CLIENT_SUCCESS    = 1;
    const STATUS_CLIENT_PARTIAL    = 2; // Only pin was enabled.
    const STATUS_CLIENT_FAILED     = 3; // No policies applied at all.
    const STATUS_CLIENT_THIRDPARTY = 4; // Client provisioned by 3rd party?

    /**
     * Handle the Provision request. This is a 3-phase process. Phase 1 is
     * actually the enforcement, when the server rejects a request and forces
     * the client to perform this PROVISION request...so we are handling phase
     * 2 (download policies) and 3 (acknowledge policies) here.
     *
     * @return boolean
     * @throws Horde_ActiveSync_Exception
     */
    protected function _handle()
    {
        // Be optimistic
        $status = self::STATUS_SUCCESS;
        $policyStatus = self::STATUS_SUCCESS;

        // Start by assuming we are in stage 2
        $phase2 = true;
        if (!$this->_decoder->getElementStartTag(Horde_ActiveSync::PROVISION_PROVISION)) {
            return $this->_globalError(self::STATUS_PROTERROR);
        }

        // Handle android remote wipe
        if ($this->_decoder->getElementStartTag(Horde_ActiveSync::PROVISION_REMOTEWIPE)) {
            if (!$this->_decoder->getElementStartTag(Horde_ActiveSync::PROVISION_STATUS)) {
                return $this->_globalError(self::STATUS_PROTERROR);
            }
            $status = $this->_decoder->getElementContent();
            if (!$this->_decoder->getElementEndTag() ||
                !$this->_decoder->getElementEndTag()) {
                return $this->_globalError(self::STATUS_PROTERROR);
            }
            if ($status == self::STATUS_CLIENT_SUCCESS) {
                $this->_stateDriver->setDeviceRWStatus($this->_devId, Horde_ActiveSync::RWSTATUS_WIPED);
            }
            $policytype = Horde_ActiveSync::POLICYTYPE_XML;
        } else {
            if (!$this->_decoder->getElementStartTag(Horde_ActiveSync::PROVISION_POLICIES) ||
                !$this->_decoder->getElementStartTag(Horde_ActiveSync::PROVISION_POLICY)) {

                return $this->_globalError(self::STATUS_PROTERROR);
            }

            // iOS (at least 5.0.1) incorrectly sends a STATUS tag before the
            // REMOTEWIPE response.
            if (!$this->_decoder->getElementStartTag(Horde_ActiveSync::PROVISION_POLICYTYPE)) {
                if ($this->_decoder->getElementStartTag(Horde_ActiveSync::PROVISION_STATUS)) {
                    $this->_decoder->getElementContent();
                    $this->_decoder->getElementEndTag(); // status
                }
            } else {
                $policytype = $this->_decoder->getElementContent();
                if ($this->_version < Horde_ActiveSync::VERSION_TWELVE && $policytype != Horde_ActiveSync::POLICYTYPE_XML) {
                    $policyStatus = self::STATUS_POLICYUNKNOWN;
                }
                if ($this->_version >= Horde_ActiveSync::VERSION_TWELVE && $policytype != Horde_ActiveSync::POLICYTYPE_WBXML) {
                    $policyStatus = self::STATUS_POLICYUNKNOWN;
                }
                if (!$this->_decoder->getElementEndTag()) {//policytype
                    return $this->_globalError(self::STATUS_PROTERROR);
                }
            }

            // POLICYKEY is only sent by client in phase 3
            if ($this->_decoder->getElementStartTag(Horde_ActiveSync::PROVISION_POLICYKEY)) {
                $policykey = $this->_decoder->getElementContent();
                $this->_logger->debug('[' . $this->_device->id .'] PHASE 3 policykey sent from PIM: ' . $policykey);
                if (!$this->_decoder->getElementEndTag() ||
                    !$this->_decoder->getElementStartTag(Horde_ActiveSync::PROVISION_STATUS)) {

                    return $this->_globalError(self::STATUS_PROTERROR);
                }
                if ($this->_decoder->getElementContent() != self::STATUS_SUCCESS) {
                    $this->_logger->err('Policy not accepted by device: ' . $this->_device->id);

                    if ($this->_provisioning == Horde_ActiveSync::PROVISIONING_LOOSE) {
                        // Loose provisioning, don't error out, just don't reqiure provision.
                        $this->_sendNoProvisionNeededResponse($status);
                        return true;
                    }

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

            // Handle remote wipe for other devices
            if ($this->_decoder->getElementStartTag(Horde_ActiveSync::PROVISION_REMOTEWIPE)) {
                if (!$this->_decoder->getElementStartTag(Horde_ActiveSync::PROVISION_STATUS)) {
                    return $this->_globalError(self::STATUS_PROTERROR);
                }
                $status = $this->_decoder->getElementContent();
                if (!$this->_decoder->getElementEndTag() ||
                    !$this->_decoder->getElementEndTag()) {
                    return $this->_globalError(self::STATUS_PROTERROR);
                }
                if ($status == self::STATUS_CLIENT_SUCCESS) {
                    $this->_stateDriver->setDeviceRWStatus($this->_device->id, Horde_ActiveSync::RWSTATUS_WIPED);
                }
            }
        }

        if (!$this->_decoder->getElementEndTag()) { //provision
            return $this->_globalError(self::STATUS_PROTERROR);
        }

        // Check to be sure that we *need* to PROVISION
        if ($this->_provisioning === false) {
            $this->_sendNoProvisionNeededResponse($status);
            return true;
        }

        // Start handling request and sending output
        $this->_encoder->StartWBXML();

        // End of Phase 3 - We create the "final" policy key, store it, then
        // send it to the client.
        if (!$phase2) {
            // Verify intermediate key
            if ($this->_stateDriver->getPolicyKey($this->_device->id) != $policykey) {
                $policyStatus = self::STATUS_POLKEYMISM;
            } else {
                // Set the final key
                $policykey = $this->_stateDriver->generatePolicyKey();
                $this->_stateDriver->setPolicyKey($this->_device->id, $policykey);
                $this->_stateDriver->setDeviceRWStatus($this->_device->id, Horde_ActiveSync::RWSTATUS_OK);
            }
            $this->_cleanUpAfterPairing();
        } elseif (empty($policykey)) {
            // This is phase2 - we need to set the intermediate key
            $policykey = $this->_stateDriver->generatePolicyKey();
            $this->_stateDriver->setPolicyKey($this->_device->id, $policykey);
        }

        $this->_encoder->startTag(Horde_ActiveSync::PROVISION_PROVISION);
        $this->_encoder->startTag(Horde_ActiveSync::PROVISION_STATUS);
        $this->_encoder->content($status);
        $this->_encoder->endTag();

        // Wipe data if status is pending or wiped
        $this->_encoder->startTag(Horde_ActiveSync::PROVISION_POLICIES);
        $this->_encoder->startTag(Horde_ActiveSync::PROVISION_POLICY);
        $this->_encoder->startTag(Horde_ActiveSync::PROVISION_POLICYTYPE);
        $this->_encoder->content($policytype);
        $this->_encoder->endTag();
        $this->_encoder->startTag(Horde_ActiveSync::PROVISION_STATUS);
        $this->_encoder->content($policyStatus);
        $this->_encoder->endTag();
        $this->_encoder->startTag(Horde_ActiveSync::PROVISION_POLICYKEY);
        $this->_encoder->content($policykey);
        $this->_encoder->endTag();

        // Send security policies.
        if ($phase2 && $status == self::STATUS_SUCCESS && $policyStatus == self::STATUS_SUCCESS) {
            $this->_encoder->startTag(Horde_ActiveSync::PROVISION_DATA);
            if ($policytype == Horde_ActiveSync::POLICYTYPE_XML) {
                $this->_sendXml($this->_driver->getCurrentPolicy());
            } else {
                $this->_sendWbxml($this->_driver->getCurrentPolicy());
            }
            $this->_encoder->endTag(); //data
        }
        $this->_encoder->endTag();     //policy
        $this->_encoder->endTag();     //policies
        $rwstatus = $this->_stateDriver->getDeviceRWStatus($this->_device->id);
        if ($rwstatus == Horde_ActiveSync::RWSTATUS_PENDING || $rwstatus == Horde_ActiveSync::RWSTATUS_WIPED) {
            $this->_encoder->startTag(Horde_ActiveSync::PROVISION_REMOTEWIPE, false, true);
            $this->_stateDriver->setDeviceRWStatus($this->_device->id, Horde_ActiveSync::RWSTATUS_WIPED);
        }
        $this->_encoder->endTag();     //provision

        return true;
    }

    /**
     * Sends a WBXML style provision response to the output stream.
     *
     * @param array $policies  An array of policy values.
     */
    protected function _sendWbxml(array $policies)
    {
        $policies = array_merge(array(
            Horde_ActiveSync::POLICY_PIN           => true,
            Horde_ActiveSync::POLICY_AEFVALUE      => '5',
            Horde_ActiveSync::POLICY_WIPETHRESHOLD => '10',
            Horde_ActiveSync::POLICY_CODEFREQ      => '0',
            Horde_ActiveSync::POLICY_MINLENGTH     => '5',
            Horde_ActiveSync::POLICY_COMPLEXITY    => '2',
            Horde_ActiveSync::POLICY_MAXLENGTH     => '10',
            Horde_ActiveSync::POLICY_PWDRECOVERY   => '0',
            Horde_ActiveSync::POLICY_PWDEXPIRATION => '0',
            Horde_ActiveSync::POLICY_PWDHISTORY    => '0',
            Horde_ActiveSync::POLICY_ENCRYPTION    => '0',
            Horde_ActiveSync::POLICY_ATC           => '1',
            Horde_ActiveSync::POLICY_MAXATCSIZE    => '5000000'
            ), $policies
        );
        $this->_encoder->startTag('Provision:EASProvisionDoc');

        $this->_encoder->startTag('Provision:DevicePasswordEnabled');
        $this->_encoder->content($policies[Horde_ActiveSync::POLICY_PIN] ? '1' : '0');
        $this->_encoder->endTag();

        if ($policies[Horde_ActiveSync::POLICY_PIN]) {
            $this->_encoder->startTag('Provision:AlphanumericDevicePasswordRequired');
            $this->_encoder->content($policies[Horde_ActiveSync::POLICY_COMPLEXITY === 0] ? '1' : '0');
            $this->_encoder->endTag();

            $this->_encoder->startTag('Provision:PasswordRecoveryEnabled');
            $this->_encoder->content($policies[Horde_ActiveSync::POLICY_PWDRECOVERY]);
            $this->_encoder->endTag();

            $this->_encoder->startTag('Provision:MinDevicePasswordLength');
            $this->_encoder->content($policies[Horde_ActiveSync::POLICY_MINLENGTH]);
            $this->_encoder->endTag();

            $this->_encoder->startTag('Provision:MaxDevicePasswordFailedAttempts');
            $this->_encoder->content('5');
            $this->_encoder->endTag();

            $this->_encoder->startTag('Provision:AllowSimpleDevicePassword');
            $this->_encoder->content($policies[Horde_ActiveSync::POLICY_COMPLEXITY] >= 1 ? '1' : '0');
            $this->_encoder->endTag();

            $this->_encoder->startTag('Provision:DevicePasswordExpiration', false, true);

            $this->_encoder->startTag('Provision:DevicePasswordHistory');
            $this->_encoder->content($policies[Horde_ActiveSync::POLICY_PWDHISTORY]);
            $this->_encoder->endTag();
        }

        $this->_encoder->startTag('Provision:DeviceEncryptionEnabled');
        $this->_encoder->content($policies[Horde_ActiveSync::POLICY_ENCRYPTION]);
        $this->_encoder->endTag();

        $this->_encoder->startTag('Provision:AttachmentsEnabled');
        $this->_encoder->content($policies[Horde_ActiveSync::POLICY_ATC]);
        $this->_encoder->endTag();

        $this->_encoder->startTag('Provision:MaxInactivityTimeDeviceLock');
        $this->_encoder->content($policies[Horde_ActiveSync::POLICY_AEFVALUE]);
        $this->_encoder->endTag();

        $this->_encoder->startTag('Provision:MaxAttachmentSize');
        $this->_encoder->content($policies[Horde_ActiveSync::POLICY_MAXATCSIZE]);
        $this->_encoder->endTag();

        $this->_encoder->endTag();
    }

    /**
     * Send a XML style policy value to the output stream.
     *
     * @param array  An array of policy settings, keyed by
     *               Horde_ActiveSync::POLICY_ constants.
     */
    protected function _sendXml(array $policies)
    {
        $xml = '<wap-provisioningdoc><characteristic type="SecurityPolicy">'
            . '<parm name="4131" value="' . ($policies[Horde_ActiveSync::POLICY_PIN] ? 0 : 1) . '"/>'
            . '</characteristic>';
        if ($policies[Horde_ActiveSync::POLICY_PIN]) {
            $xml .= '<characteristic type="Registry">'
            .   '<characteristic type="HKLM\Comm\Security\Policy\LASSD\AE\{50C13377-C66D-400C-889E-C316FC4AB374}">'
            .   '<parm name="AEFrequencyType" value="' . (!empty($policies[Horde_ActiveSync::POLICY_AEFVALUE]) ? 1 : 0) . '"/>'
            .   (!empty($policies[Horde_ActiveSync::POLICY_AEFVALUE]) ? '<parm name="AEFrequencyValue" value="' . $policies[Horde_ActiveSync::POLICY_AEFVALUE] . '"/>' : '')
            .   '</characteristic>';

            if (!empty($policies[Horde_ActiveSync::POLICY_WIPETHRESHOLD])) {
                $xml .= '<characteristic type="HKLM\Comm\Security\Policy\LASSD"><parm name="DeviceWipeThreshold" value="' . $policies[Horde_ActiveSync::POLICY_WIPETHRESHOLD] . '"/></characteristic>';
            }
            if (!empty($policies[Horde_ActiveSync::POLICY_CODEFREQ])) {
                $xml .= '<characteristic type="HKLM\Comm\Security\Policy\LASSD"><parm name="CodewordFrequency" value="' . $policies[Horde_ActiveSync::POLICY_CODEFREQ] . '"/></characteristic>';
            }
            if (!empty($policies[Horde_ActiveSync::POLICY_MINLENGTH])) {
                $xml .= '<characteristic type="HKLM\Comm\Security\Policy\LASSD\LAP\lap_pw"><parm name="MinimumPasswordLength" value="' . $policies[Horde_ActiveSync::POLICY_MINLENGTH] . '"/></characteristic>';
            }
            if ($policies[Horde_ActiveSync::POLICY_COMPLEXITY] !== false) {
                $xml .= '<characteristic type="HKLM\Comm\Security\Policy\LASSD\LAP\lap_pw"><parm name="PasswordComplexity" value="' . $policies[Horde_ActiveSync::POLICY_COMPLEXITY] . '"/></characteristic>';
            }
            $xml .= '</characteristic>';
        }
        $xml .= '</wap-provisioningdoc>';

        $this->_encoder->content($xml);
    }

    /**
     * Send a WBXML response to the output stream indicating that no
     * provision requests are necessary.
     *
     * @param integer $status  The status code to send along with the response.
     */
    protected function _sendNoProvisionNeededResponse($status)
    {
        $this->_encoder->startWBXML();
        $this->_encoder->startTag(Horde_ActiveSync::PROVISION_PROVISION);
        $this->_encoder->startTag(Horde_ActiveSync::PROVISION_STATUS);
        $this->_encoder->content($status);
        $this->_encoder->endTag();
        $this->_encoder->startTag(Horde_ActiveSync::PROVISION_POLICIES);
        $this->_encoder->startTag(Horde_ActiveSync::PROVISION_POLICY);
        $this->_encoder->startTag(Horde_ActiveSync::PROVISION_STATUS);
        $this->_encoder->content(self::STATUS_NOTDEFINED);
        $this->_encoder->endTag();
        $this->_encoder->endTag();
        $this->_encoder->endTag();
        $this->_encoder->endTag();
    }

    /**
     * Handle global provision request errors, and send the output to the
     * output stream.
     *
     * @param integer $status  The status code to send.
     */
    protected function _globalError($status)
    {
        $this->_encoder->StartWBXML();
        $this->_encoder->startTag(Horde_ActiveSync::PROVISION_PROVISION);
        $this->_encoder->startTag(Horde_ActiveSync::PROVISION_STATUS);
        $this->_encoder->content($status);
        $this->_encoder->endTag();
        $this->_encoder->endTag();

        return false;
    }

}