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
    const POLICY_PIN                            = 'DevicePasswordEnabled';
    const POLICY_AEFVALUE                       = 'MaxInactivityTimeDeviceLock';
    const POLICY_CODEFREQ                       = 'codewordfrequency';
    const POLICY_MINLENGTH                      = 'MinDevicePasswordLength';
    const POLICY_COMPLEXITY                     = 'AlphanumericDevicePasswordRequired';
    // 12.0
    //const POLICY_PWDRECOVERY                    = 'passwordrecovery';
    //const POLICY_PWDEXPIRATION                  = 'passwordexpiration';
    //const POLICY_PWDHISTORY                     = 'passwordhistory';
    const POLICY_ENCRYPTION                     = 'DeviceEncryptionEnabled';
    const POLICY_ATC                            = 'AttachmentsEnabled';
    const POLICY_MAXATCSIZE                     = 'MaxAttachmentSize';
    const POLICY_MAXFAILEDATTEMPTS              = 'MaxDevicePasswordFailedAttempts';
    // 12.1
    const POLICY_ALLOW_SDCARD                   = 'AllowStorageCard';
    const POLICY_ALLOW_CAMERA                   = 'AllowCamera';
    const POLICY_ALLOW_SMS                      = 'AllowTextMessaging';
    const POLICY_ALLOW_WIFI                     = 'AllowWiFi';
    const POLICY_ALLOW_BLUETOOTH                = 'AllowBluetooth';
    const POLICY_ALLOW_POPIMAP                  = 'AllowPOPIMAPEmail';
    const POLICY_ALLOW_BROWSER                  = 'AllowBrowser';
    const POLICY_REQUIRE_SMIME_SIGNED           = 'RequireSignedSMIMEMessages';
    const POLICY_REQUIRE_SMIME_ENCRYPTED        = 'RequireDeviceEncryption';
    const POLICY_DEVICE_ENCRYPTION              = 'RequireDeviceEncryption';
    const POLICY_ALLOW_HTML                     = 'AllowHTMLEmail';
    const POLICY_MAX_EMAIL_AGE                  = 'MaxEmailAgeFilter';
    //const POLICY_MAX_EMAIL_TRUNCATION           = 'maxemailtruncation';
    //const POLICY_MAX_HTMLEMAIL_TRUNCATION       = 'maxhtmlemailtruncation';
    const POLICY_ROAMING_NOPUSH                 = 'RequireManualSyncWhenRoaming';

    /**
     * Default policy values used in both 12.0 and 12.1
     *
     * @var array
     */
    protected $_defaults = array(
        self::POLICY_PIN               => false,
        self::POLICY_AEFVALUE          => '0',
        self::POLICY_MAXFAILEDATTEMPTS => '5',
        self::POLICY_CODEFREQ          => '0',
        self::POLICY_MINLENGTH         => '5',
        self::POLICY_COMPLEXITY        => '2',
        //self::POLICY_PWDRECOVERY       => '0',
        //self::POLICY_PWDEXPIRATION     => '0',
        //self::POLICY_PWDHISTORY        => '0',
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
        // 1 == Allow/Yes, 0 == Disallow/No.
        self::POLICY_ALLOW_SDCARD            => '1',
        self::POLICY_ALLOW_CAMERA            => '1',
        self::POLICY_ALLOW_SMS               => '1',
        self::POLICY_ALLOW_WIFI              => '1',
        self::POLICY_ALLOW_BLUETOOTH         => '1',
        self::POLICY_ALLOW_POPIMAP           => '1',
        self::POLICY_ALLOW_BROWSER           => '1',
        self::POLICY_REQUIRE_SMIME_ENCRYPTED => '0',
        self::POLICY_REQUIRE_SMIME_SIGNED    => '0',
        self::POLICY_DEVICE_ENCRYPTION       => '0',
        self::POLICY_ALLOW_HTML              => '1',
        self::POLICY_MAX_EMAIL_AGE           => '0',
        self::POLICY_ROAMING_NOPUSH          => '0',
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
        if ($version > Horde_ActiveSync::VERSION_TWELVE) {
            $this->_defaults = array_merge($this->_defaults, $this->_defaults_twelveone);
        }

        $this->_version = $version;
        $this->_overrides = $policies;
    }

    /**
     * Return a list of all configurable policy names.
     *
     * @return array
     */
    public function getAvailablePolicies()
    {
        return array_keys($this->_defaults);
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

            if (!empty($policies[self::POLICY_MAXFAILEDATTEMPTS])) {
                $xml .= '<characteristic type="HKLM\Comm\Security\Policy\LASSD"><parm name="DeviceWipeThreshold" value="' . $policies[self::POLICY_MAXFAILEDATTEMPTS] . '"/></characteristic>';
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

        $this->_sendPolicy(self::POLICY_PIN, $policies[self::POLICY_PIN] ? '1' : '0');
        if ($policies[self::POLICY_PIN]) {
            $this->_sendPolicy(self::POLICY_COMPLEXITY, $policies[self::POLICY_COMPLEXITY]);
            $this->_sendPolicy(self::POLICY_MINLENGTH, $policies[self::POLICY_MINLENGTH]);
            $this->_sendPolicy(self::POLICY_MAXFAILEDATTEMPTS, $policies[self::POLICY_MAXFAILEDATTEMPTS]);
            $this->_sendPolicy(self::POLICY_COMPLEXITY, $policies[self::POLICY_COMPLEXITY] >= 1 ? '1' : '0');
        }
        $this->_sendPolicy(self::POLICY_ENCRYPTION, $policies[self::POLICY_ENCRYPTION]);
        $this->_sendPolicy(self::POLICY_ATC, $policies[self::POLICY_ATC]);
        $this->_sendPolicy(self::POLICY_AEFVALUE, $policies[self::POLICY_AEFVALUE], true);
        $this->_sendPolicy(self::POLICY_MAXATCSIZE, $policies[self::POLICY_MAXATCSIZE]);
        if ($this->_version > Horde_ActiveSync::VERSION_TWELVE) {
            $this->_sendPolicy(self::POLICY_ALLOW_SDCARD, $policies[self::POLICY_ALLOW_SDCARD], true);
            $this->_sendPolicy(self::POLICY_ALLOW_CAMERA, $policies[self::POLICY_ALLOW_CAMERA], true);
            $this->_sendPolicy(self::POLICY_DEVICE_ENCRYPTION, $policies[self::POLICY_DEVICE_ENCRYPTION], true);
            $this->_sendPolicy(self::POLICY_ALLOW_WIFI, $policies[self::POLICY_ALLOW_WIFI], true);
            $this->_sendPolicy(self::POLICY_ALLOW_SMS, $policies[self::POLICY_ALLOW_SMS], true);
            $this->_sendPolicy(self::POLICY_ALLOW_POPIMAP, $policies[self::POLICY_ALLOW_POPIMAP], true);
            $this->_sendPolicy(self::POLICY_ALLOW_BLUETOOTH, $policies[self::POLICY_ALLOW_BLUETOOTH], true);
            $this->_sendPolicy(self::POLICY_ROAMING_NOPUSH, $policies[self::POLICY_ROAMING_NOPUSH], true);
            $this->_sendPolicy(self::POLICY_ALLOW_HTML, $policies[self::POLICY_ALLOW_HTML], true);
            $this->_sendPolicy(self::POLICY_MAX_EMAIL_AGE, $policies[self::POLICY_MAX_EMAIL_AGE], true);
            $this->_sendPolicy(self::POLICY_REQUIRE_SMIME_SIGNED, $policies[self::POLICY_REQUIRE_SMIME_SIGNED], true);
            $this->_sendPolicy(self::POLICY_REQUIRE_SMIME_ENCRYPTED, $policies[self::POLICY_REQUIRE_SMIME_ENCRYPTED], true);
            $this->_sendPolicy(self::POLICY_ALLOW_BROWSER, $policies[self::POLICY_ALLOW_BROWSER], true);
        }

        $this->_encoder->endTag();
    }

    /**
     * Output a single policy value
     *
     * @param string $policy      The policy name
     * @param mixed $value        The policy value
     * @param boolean $nodefault  Don't send the policy if the value is default.
     */
    protected function _sendPolicy($policy, $value, $nodefault = false)
    {
        if ($nodefault && $value == $this->_defaults[$policy]) {
            return;
        }
        $this->_encoder->startTag('Provision:' . $policy);
        $this->_encoder->content($value);
        $this->_encoder->endTag();
    }

}