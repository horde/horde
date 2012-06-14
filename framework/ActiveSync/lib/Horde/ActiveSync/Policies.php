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
    /* Policy configuration keys */
    const POLICY_PIN                            = 'pin';
    const POLICY_AEFVALUE                       = 'inactivity';
    const POLICY_WIPETHRESHOLD                  = 'wipethreshold';
    const POLICY_CODEFREQ                       = 'codewordfrequency';
    const POLICY_MINLENGTH                      = 'minimumlength';
    const POLICY_COMPLEXITY                     = 'complexity';
    // 12.0
    const POLICY_MAXLENGTH                      = 'maximumlength';
    const POLICY_PWDRECOVERY                    = 'passwordrecovery';
    const POLICY_PWDEXPIRATION                  = 'passwordexpiration';
    const POLICY_PWDHISTORY                     = 'passwordhistory';
    const POLICY_ENCRYPTION                     = 'encryption';
    const POLICY_ATC                            = 'attachments';
    const POLICY_MAXATCSIZE                     = 'maxattachmentsize';
    const POLICY_MAXFAILEDATTEMPTS              = 'maxdevicepasswordfailedattempts';

    /**
     * Default policy values used in both 12.0 and 12.1
     *
     * @var array
     */
    protected $_defaults = array(
        self::POLICY_PIN               => false,
        self::POLICY_AEFVALUE          => '0',
        self::POLICY_MAXFAILEDATTEMPTS => '5',
        self::POLICY_WIPETHRESHOLD     => '10',
        self::POLICY_CODEFREQ          => '0',
        self::POLICY_MINLENGTH         => '5',
        self::POLICY_COMPLEXITY        => '2',
        self::POLICY_MAXLENGTH         => '10',
        self::POLICY_PWDRECOVERY       => '0',
        self::POLICY_PWDEXPIRATION     => '0',
        self::POLICY_PWDHISTORY        => '0',
        self::POLICY_ENCRYPTION        => '0',
        self::POLICY_ATC               => '1',
        self::POLICY_MAXATCSIZE        => '5000000'
    );

    /**
     * Defaults used only in 12.1
     *
     * @var array
     */
    protected $_defaults_twelveone = array(

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
     * EAS version to support.
     *
     * @var long
     */
    protected $_version;

    /**
     * Const'r
     *
     * @param Horde_ActiveSync_Wbxml_Encoder $encoder  The output stream encoder
     * @param float $version                           The EAS Version.
     * @param array $policies                          The policy array.
     */
    public function __construct(
        Horde_ActiveSync_Wbxml_Encoder $encoder,
        $version = Horde_ActiveSync::VERSION_TWELVEONE,
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
            . '<parm name="4131" value="' . ($policies[self::POLICY_PIN] ? 0 : 1) . '"/>'
            . '</characteristic>';
        if ($policies[self::POLICY_PIN]) {
            $xml .= '<characteristic type="Registry">'
            .   '<characteristic type="HKLM\Comm\Security\Policy\LASSD\AE\{50C13377-C66D-400C-889E-C316FC4AB374}">'
            .   '<parm name="AEFrequencyType" value="' . (!empty($policies[self::POLICY_AEFVALUE]) ? 1 : 0) . '"/>'
            .   (!empty($policies[self::POLICY_AEFVALUE]) ? '<parm name="AEFrequencyValue" value="' . $policies[self::POLICY_AEFVALUE] . '"/>' : '')
            .   '</characteristic>';

            if (!empty($policies[self::POLICY_WIPETHRESHOLD])) {
                $xml .= '<characteristic type="HKLM\Comm\Security\Policy\LASSD"><parm name="DeviceWipeThreshold" value="' . $policies[self::POLICY_WIPETHRESHOLD] . '"/></characteristic>';
            }
            if (!empty($policies[self::POLICY_CODEFREQ])) {
                $xml .= '<characteristic type="HKLM\Comm\Security\Policy\LASSD"><parm name="CodewordFrequency" value="' . $policies[self::POLICY_CODEFREQ] . '"/></characteristic>';
            }
            if (!empty($policies[self::POLICY_MINLENGTH])) {
                $xml .= '<characteristic type="HKLM\Comm\Security\Policy\LASSD\LAP\lap_pw"><parm name="MinimumPasswordLength" value="' . $policies[self::POLICY_MINLENGTH] . '"/></characteristic>';
            }
            if ($policies[self::POLICY_COMPLEXITY] !== false) {
                $xml .= '<characteristic type="HKLM\Comm\Security\Policy\LASSD\LAP\lap_pw"><parm name="PasswordComplexity" value="' . $policies[self::POLICY_COMPLEXITY] . '"/></characteristic>';
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
        $this->_encoder->content($policies[self::POLICY_PIN] ? '1' : '0');
        $this->_encoder->endTag();

        if ($policies[self::POLICY_PIN]) {
            $this->_encoder->startTag('Provision:AlphanumericDevicePasswordRequired');
            $this->_encoder->content($policies[self::POLICY_COMPLEXITY] === 0 ? '1' : '0');
            $this->_encoder->endTag();

            $this->_encoder->startTag('Provision:PasswordRecoveryEnabled');
            $this->_encoder->content($policies[self::POLICY_PWDRECOVERY]);
            $this->_encoder->endTag();

            $this->_encoder->startTag('Provision:MinDevicePasswordLength');
            $this->_encoder->content($policies[self::POLICY_MINLENGTH]);
            $this->_encoder->endTag();

            $this->_encoder->startTag('Provision:MaxDevicePasswordFailedAttempts');
            $this->_encoder->content($policies[self::POLICY_MAXFAILEDATTEMPTS]);
            $this->_encoder->endTag();

            $this->_encoder->startTag('Provision:AllowSimpleDevicePassword');
            $this->_encoder->content($policies[self::POLICY_COMPLEXITY] >= 1 ? '1' : '0');
            $this->_encoder->endTag();

            $this->_encoder->startTag('Provision:DevicePasswordExpiration', false, true);

            $this->_encoder->startTag('Provision:DevicePasswordHistory');
            $this->_encoder->content($policies[self::POLICY_PWDHISTORY]);
            $this->_encoder->endTag();
        }

        $this->_encoder->startTag('Provision:DeviceEncryptionEnabled');
        $this->_encoder->content($policies[self::POLICY_ENCRYPTION]);
        $this->_encoder->endTag();

        $this->_encoder->startTag('Provision:AttachmentsEnabled');
        $this->_encoder->content($policies[self::POLICY_ATC]);
        $this->_encoder->endTag();

        $this->_encoder->startTag('Provision:MaxInactivityTimeDeviceLock');
        $this->_encoder->content($policies[self::POLICY_AEFVALUE]);
        $this->_encoder->endTag();

        $this->_encoder->startTag('Provision:MaxAttachmentSize');
        $this->_encoder->content($policies[self::POLICY_MAXATCSIZE]);
        $this->_encoder->endTag();

        $this->_encoder->endTag();
    }

}