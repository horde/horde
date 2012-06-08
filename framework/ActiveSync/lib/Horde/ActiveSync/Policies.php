<?php
/**
 * Horde_ActiveSync_Policies::
 *
 * @license   http://www.horde.org/licenses/gpl GPLv2
 *            NOTE: According to sec. 8 of the GENERAL PUBLIC LICENSE (GPL),
 *            Version 2, the distribution of the Horde_ActiveSync module in or
 *            to the United States of America is excluded from the scope of this
 *            license.
 * @copyright 2010-2012 Horde LLC (http://www.horde.org)
 * @author    Michael J Rubinsky <mrubinsk@horde.org>
 * @package   ActiveSync
 */
/**
 * Horde_ActiveSync_Policies:: Wraps all functionality related to generating
 * the XML or WBXML for EAS Policies.
 *
 * @license   http://www.horde.org/licenses/gpl GPLv2
 *            NOTE: According to sec. 8 of the GENERAL PUBLIC LICENSE (GPL),
 *            Version 2, the distribution of the Horde_ActiveSync module in or
 *            to the United States of America is excluded from the scope of this
 *            license.
 * @copyright 2010-2012 Horde LLC (http://www.horde.org)
 * @author    Michael J Rubinsky <mrubinsk@horde.org>
 * @package   ActiveSync
 */
class Horde_ActiveSync_Policies
{

    /**
     * Default policy values.
     *
     * @var array
     */
    protected $_defaults = array(
        Horde_ActiveSync::POLICY_PIN               => true,
        Horde_ActiveSync::POLICY_AEFVALUE          => '5',
        Horde_ActiveSync::POLICY_MAXFAILEDATTEMPTS => '5',
        Horde_ActiveSync::POLICY_WIPETHRESHOLD     => '10',
        Horde_ActiveSync::POLICY_CODEFREQ          => '0',
        Horde_ActiveSync::POLICY_MINLENGTH         => '5',
        Horde_ActiveSync::POLICY_COMPLEXITY        => '2',
        Horde_ActiveSync::POLICY_MAXLENGTH         => '10',
        Horde_ActiveSync::POLICY_PWDRECOVERY       => '0',
        Horde_ActiveSync::POLICY_PWDEXPIRATION     => '0',
        Horde_ActiveSync::POLICY_PWDHISTORY        => '0',
        Horde_ActiveSync::POLICY_ENCRYPTION        => '0',
        Horde_ActiveSync::POLICY_ATC               => '1',
        Horde_ActiveSync::POLICY_MAXATCSIZE        => '5000000'
    );

    /**
     * Explicitly set policies.
     *
     * @var array
     */
    protected $_overrides;

    /**
     * Output stream
     *
     * @var Horde_ActiveSync_Wbxml_Encoder
     */
    protected $_encoder;

    /**
     * Const'r
     *
     * @param Horde_ActiveSync_Wbxml_Encoder $encoder  The output stream encoder
     * @param array $policies                          The policy array.
     */
    public function __construct(
        Horde_ActiveSync_Wbxml_Encoder $encoder,
        array $policies = array())
    {
        $this->_encoder = $encoder;
        $this->_overrides = $policies;
    }

    /**
     * Output the policies as XML. Only used in EAS Version 2.5. This method
     * only outputs the 2.5 compatible policies.
     *
     * @throws Horde_ActiveSync_Exception
     */
    public function toXml()
    {
        if (empty($this->_encoder)) {
            throw new Horde_ActiveSync_Exception('No output stream');
        }

        $policies = array_merge($this->_defaults, $this->_overrides);

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
     * Output the policies as WBXML. Used in EAS Versions >= 12.0
     */
    public function toWbxml()
    {
        if (empty($this->_encoder)) {
            throw new Horde_ActiveSync_Exception('No output stream');
        }

        $policies = array_merge($this->_defaults, $this->_overrides);

        $this->_encoder->startTag('Provision:EASProvisionDoc');

        $this->_encoder->startTag('Provision:DevicePasswordEnabled');
        $this->_encoder->content($policies[Horde_ActiveSync::POLICY_PIN] ? '1' : '0');
        $this->_encoder->endTag();

        if ($policies[Horde_ActiveSync::POLICY_PIN]) {
            $this->_encoder->startTag('Provision:AlphanumericDevicePasswordRequired');
            $this->_encoder->content($policies[Horde_ActiveSync::POLICY_COMPLEXITY] === 0 ? '1' : '0');
            $this->_encoder->endTag();

            $this->_encoder->startTag('Provision:PasswordRecoveryEnabled');
            $this->_encoder->content($policies[Horde_ActiveSync::POLICY_PWDRECOVERY]);
            $this->_encoder->endTag();

            $this->_encoder->startTag('Provision:MinDevicePasswordLength');
            $this->_encoder->content($policies[Horde_ActiveSync::POLICY_MINLENGTH]);
            $this->_encoder->endTag();

            $this->_encoder->startTag('Provision:MaxDevicePasswordFailedAttempts');
            $this->_encoder->content($policies[Horde_ActiveSync::POLICY_MAXFAILEDATTEMPTS]);
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

}