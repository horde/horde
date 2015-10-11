<?php
/**
 * Horde_ActiveSync_Device::
 *
 * @license   http://www.horde.org/licenses/gpl GPLv2
 *            NOTE: According to sec. 8 of the GENERAL PUBLIC LICENSE (GPL),
 *            Version 2, the distribution of the Horde_ActiveSync module in or
 *            to the United States of America is excluded from the scope of this
 *            license.
 * @copyright 2013-2015 Horde LLC (http://www.horde.org)
 * @author    Michael J Rubinsky <mrubinsk@horde.org>
 * @package   ActiveSync
 */
/**
 * Horde_ActiveSync_Device:: Wraps all functionality related to device data.
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
 * @property string   $id               The device id.
 * @property string   $deviceType       The device type string.
 * @property string   $clientType       The client name, if available.
 * @property integer  $rwstatus         The RemoteWipe status - a
 *                                      Horde_ActiveSync::RWSTATUS_* constant.
 * @property string   $userAgent        The device's user agent string.
 * @property string   $user             The userid for the current device account.
 * @property array    $supported        The SUPPORTED data sent from this device.
 * @property string   $policykey        The current policykey, if provisioned.
 * @property array    $properties       The device properties, sent in DEVICEINFO,
 *                                      along with any custom properties set.
 * @property string   $announcedVersion The most last EAS supported versions
 *                                      announced to the device.
 * @property integer  $multiplex        Bitmask describing collections that this
 *                                      device does not support user created
 *                                      folders for, therefore all sources must
 *                                      be multiplexed together. Masks are
 *                                      the MULTIPLEX_* constants.
 * @property boolean   $blocked         True if device has been marked as blocked.
 *
 */
class Horde_ActiveSync_Device
{
    const MODEL             = 'Settings:Model';
    const IMEI              = 'Settings:IMEI';
    const NAME              = 'Settings:FriendlyName';
    const OS                = 'Settings:OS';
    const OS_LANGUAGE       = 'Settings:OSLanguage';
    const PHONE_NUMBER      = 'Settings:PhoneNumber';
    const OPERATOR          = 'Settings:MobileOperator';
    const VERSION           = 'version';
    const MULTIPLEX         = 'multiplex';
    const ANNOUNCED_VERSION = 'announcedVersion';
    const BLOCKED           = 'blocked';


    // Bitwise constants for flagging device must use multiplexed collections.
    // @since 2.9.0
    const MULTIPLEX_CONTACTS = 1;
    const MULTIPLEX_CALENDAR = 2;
    const MULTIPLEX_TASKS    = 4;
    const MULTIPLEX_NOTES    = 8;

    const TYPE_IPHONE          = 'iphone';
    const TYPE_IPOD            = 'ipod';
    const TYPE_IPAD            = 'ipad';
    const TYPE_WEBOS           = 'webos';
    const TYPE_ANDROID         = 'android';
    const TYPE_BLACKBERRY      = 'blackberry';
    const TYPE_WP              = 'windowsphone';
    const TYPE_TOUCHDOWN       = 'touchdown';
    const TYPE_UNKNOWN         = 'unknown';
    const TYPE_NINE            = 'nine';

    /**
     * Quirk to specify if the client fails to property ghost the
     * POOMCONTACTS:Picture field. If this quirk is present, it means we should
     * add the POOMCONTACTS:Picture field to the SUPPORTED array for this client.
     */
    const QUIRK_NEEDS_SUPPORTED_PICTURE_TAG = 1;

    /**
     * Device properties.
     *
     * @var array
     */
    protected $_properties = array();

    /**
     * State handler
     *
     * @var Horde_ActiveSync_State_Base
     */
    protected $_state;

    /**
     * Dirty flag
     *
     * @var array
     */
    protected $_dirty = array();

    /**
     * Flag to indicate self::multiplex was set externally.
     *
     * @var boolean
     */
    protected $_multiplexSet = false;

    /**
     * Local override/cache of detected clientType.
     *
     * @var string
     */
    protected $_clientType;

    /**
     * Cache of OS version.
     *
     * @var string
     */
    protected $_iOSVersion;

    /**
     * Const'r
     *
     * @param Horde_ActiveSync_State_Base $state  The state driver.
     * @param array $data                         The current device data.
     */
    public function __construct(Horde_ActiveSync_State_Base $state, array $data = array())
    {
        $this->_state = $state;
        $this->_properties = $data;
    }

    /**
     * Getter
     */
    public function &__get($property)
    {
        switch ($property) {
        case self::MULTIPLEX:
            if (!$this->_multiplexSet && empty($this->_properties['properties'][self::MULTIPLEX])) {
                $this->_sniffMultiplex();
                $this->multiplex = $this->_properties['properties'][self::MULTIPLEX];
                $this->save();
            }
        case self::ANNOUNCED_VERSION:
        case self::BLOCKED:
            return $this->_properties['properties'][$property];
        case 'clientType':
            if (!isset($this->_clientType)) {
                $this->_clientType = $this->_getClientType();
            }
            return $this->_clientType;
        case self::VERSION:
            if (isset($this->_properties['properties'][self::VERSION])) {
                return $this->_properties['properties'][self::VERSION];
            }
            break;
        case self::OS:
            if (isset($this->_properties['properties'][self::OS])) {
                return $this->_properties['properties'][self::OS];
            }
            break;
        case 'properties':
            if (!isset($this->_properties['properties'])) {
                $return = array();
                return $return;
            }
            // Fall through.
        default:
            if (isset($this->_properties[$property])) {
                return $this->_properties[$property];
            }
        }

        $return = null;
        return $return;
    }

    /**
     * Setter
     */
    public function __set($property, $value)
    {
        switch ($property) {
        case self::MULTIPLEX:
            $this->_multiplexSet = true;
            // fallthrough
        case self::ANNOUNCED_VERSION:
        case self::BLOCKED:
        case self::VERSION:
        case self::OS:
            $properties = $this->properties;
            if (empty($properties)) {
                $properties = array();
            }
            $properties[$property] = $value;
            $this->setDeviceProperties($properties);
            break;
        case 'clientType':
            $this->_clientType = $value;
            break;

        default:
            if (!isset($this->_properties[$property]) || $value != $this->_properties[$property]) {
                $this->_dirty[$property] = true;
                $this->_properties[$property] = $value;
            }
        }
    }

    /**
     * Magic isset
     */
    public function __isset($property)
    {
        return !empty($this->_properties[$property]);
    }

    /**
     * Indicates if we need to announce new EAS version string to the client.
     * If the property is empty, we don't send it since we are sending the
     * EAS-Version header anyway and this is a new device.
     *
     * @param string $supported  The current EAS-Version header.
     *
     * @return boolean  True if we need to send the MS-RP header, otherwise false.
     */
    public function needsVersionUpdate($supported)
    {
        if (empty($this->properties[self::ANNOUNCED_VERSION])) {
            $properties = $this->properties;
            $properties[self::ANNOUNCED_VERSION] = $supported;
            $this->setDeviceProperties($properties);
            return false;
        }
        if ($this->properties[self::ANNOUNCED_VERSION] != $supported) {
            $properties = $this->properties;
            $properties[self::ANNOUNCED_VERSION] = $supported;
            $this->setDeviceProperties($properties);
            return true;
        }

        return false;
    }

    /**
     * Returns if the current device is an expected non-provisionable device.
     * I.e., the client does not support provisioning at all, but should still
     * be allowed to connect to a server that has provisioning set to Force.
     * Currently, this only applies to Windows Communication Apps (Outlook 2013).
     *
     * @return boolean  True if the device should be allowed to connect to a
     *                  Forced provision server. False if not.
     */
    public function isNonProvisionable()
    {
        // Outlook? The specs say that "Windows Communication Apps" should
        // provide the 'OS' parameter of the ITEMSETTINGS data equal to 'Windows',
        // but Outlook 2013 doesn't even send the ITEMSETTINGS command, so we
        // need to check the userAgent header. Early versions used Microsoft.Outlook,
        // but after some update it was changed to 'Outlook/15.0'
        if (strpos($this->deviceType, 'MicrosoftOutlook') !== false ||
            strpos($this->userAgent, 'Outlook') !== false) {
            return true;
        }

        return false;
    }

    /**
     * Check if we should enforce provisioning on this device.
     *
     * @return boolean
     */
    public function enforceProvisioning()
    {
        if (strpos($this->userAgent, 'Android') !== false) {
            if (preg_match('@EAS[/-]{0,1}([.0-9]{2,})@', $this->userAgent, $matches)) {
                return $matches[1] > 1.2;
            }
            return false;
        }

        return !$this->isNonProvisionable();
    }

    /**
     * Set the device's DEVICEINFO data.
     *
     * @param array $data  The data array sent from the device.
     */
    public function setDeviceProperties(array $data)
    {
        $data = array_merge($this->properties, $data);
        if (empty($data['userAgent']) && !empty($this->_properties['userAgent'])) {
            $data['userAgent'] = $this->_properties['userAgent'];
        }
        $this->properties = $data;
        $this->_dirty['properties'] = true;
    }

    /**
     * Return an array of DEVICEINFO data, with keys suitable for displaying.
     *
     * @return array
     */
    public function getFormattedDeviceProperties()
    {
        $data = array(
            _("Id") => $this->id,
            _("Policy Key") => $this->policykey,
            _("User Agent") => $this->userAgent
        );

        if (!empty($this->properties[self::MODEL])) {
            $data[_("Model")] = $this->properties[self::MODEL];
        }
        if (!empty($this->properties[self::IMEI])) {
            $data[_("IMEI")] = $this->properties[self::IMEI];
        }
        if (!empty($this->properties[self::NAME])) {
            $data[_("Common Name")] = $this->properties[self::NAME];
        }
        if (!empty($this->properties[self::OPERATOR])) {
            $data[_("Mobile Operator")] = $this->properties[self::OPERATOR];
        }
        if (!empty($this->properties[self::OS])) {
            $data[_("OS")] = $this->properties[self::OS];
        }
        if (!empty($this->properties[self::OS_LANGUAGE])) {
            $data[_("OS Language")] = $this->properties[self::OS_LANGUAGE];
        }
        if (!empty($this->properties[self::PHONE_NUMBER])) {
            $data[_("Phone Number")] = $this->properties[self::PHONE_NUMBER];
        }
        if (!empty($this->properties[self::VERSION])) {
            $data[_("EAS Version")] = $this->properties[self::VERSION];
        }
        if (!empty($this->properties[self::MULTIPLEX])) {
            $data[_("Forced Multiplexed Bitmask")] = $this->properties[self::MULTIPLEX];
        }

        return $data;
    }

    /**
     * Return the last time the device issued a SYNC request.
     *
     * @return integer  The timestamp.
     */
    public function getLastSyncTimestamp()
    {
        return $this->_state->getLastSyncTimestamp($this->id, $this->user);
    }

    /**
     * Save the dirty device info data.
     *
     * @param boolean $all  If true, save all properties (deviceInfo and
     *                      deviceProperties). Otherwise, just save dirty
     *                      deviceProperties. @since 2.16.0
     * @todo For 3.0, make it clearer that deviceInfo is per-user and
     *       deviceProperties is per-device.
     */
    public function save($all = true)
    {
        if ($all) {
            $this->_state->setDeviceInfo($this, $this->_dirty);
        }
        if (!empty($this->_dirty['properties'])) {
            $this->_state->setDeviceProperties($this->properties, $this->id);
        }
        $this->_dirty = array();
    }

    /**
     * Return the major version number of the OS (or client app) as reported
     * by the client.
     *
     * @return integer  The version number.
     */
    public function getMajorVersion()
    {
        switch (Horde_String::lower($this->clientType)) {
            case self::TYPE_BLACKBERRY:
                if (preg_match('/(.+)\/(.+)/', $this->userAgent, $matches)) {
                    return $matches[2];
                }
                break;
            case self::TYPE_IPOD:
            case self::TYPE_IPAD:
            case self::TYPE_IPHONE:
                if (empty($this->_iOSVersion)) {
                    $this->_getIosVersion();
                }
                if (preg_match('/(\d+)\.(\d+)/', $this->_iOSVersion, $matches)) {
                    return $matches[1];
                }
                break;
            case self::TYPE_ANDROID:
            case self::TYPE_NINE:
                // Most newer Android clients send self::OS, so check that first
                if (!empty($this->properties[self::OS]) && preg_match('/(\d+)\.(\d+)/', $this->properties[self::OS], $matches)) {
                    return $matches[1];
                }
                // Some newer devices send userAgent like Android/4.3.3-EAS-1.3
                if (preg_match('/Android\/(\d+)\.(\d+)/', $this->userAgent, $matches)) {
                    return $matches[1];
                }
                // Older Android/0.3 type userAgent strings.
                if (preg_match('/(.+)\/(\d+)\.(\d+)/', $this->userAgent, $matches)) {
                    return $matches[2];
                }
                break;
            case self::TYPE_TOUCHDOWN:
                 if (preg_match('/(.+)\/(\d+)\.(\d+)/', $this->userAgent, $matches)) {
                    return $matches[2];
                }
                break;
        }

        return 0;
    }

    /**
     * Detects the iOS version in M.m format and caches locally.
     */
    protected function _getIosVersion()
    {
        // First see if we have a newer client that sends the OS version
        // Newer iOS sends e.g., "iOS 8.2.2" in OS field.
        if (!empty($this->properties[self::OS]) &&
            preg_match('/\d+\.\d+\.?\d+?/', $this->properties[self::OS], $matches)) {
            if (!empty($matches[0])) {
                $this->_iOSVersion = $matches[0];
                return;
            }
        }
        // Match to a known UserAgent string version.
        foreach (Horde_ActiveSync_Device_Ios::$VERSION_MAP as $userAgent => $version) {
            if (preg_match('/\w+\/(' . $userAgent . ')$/', $this->userAgent, $matches)) {
                $this->_iOSVersion = $version;
                return;
            }
        }
    }

    /**
     * Return the minor version number of the OS (or client app) as reported
     * by the client.
     *
     * @return integer  The version number.
     */
    public function getMinorVersion()
    {
        switch (Horde_String::lower($this->clientType)) {
            case self::TYPE_BLACKBERRY:
                if (preg_match('/(.+)\/(.+)/', $this->userAgent, $matches)) {
                    return $matches[2];
                }
                break;
            case self::TYPE_IPOD:
            case self::TYPE_IPAD:
            case self::TYPE_IPHONE:
                if (empty($this->_iOSVersion)) {
                    $this->_getIosVersion();
                }
                if (preg_match('/(\d+)\.(\d+)/', $this->_iOSVersion, $matches)) {
                    return $matches[2];
                }
                break;
            case self::TYPE_ANDROID:
                // Most newer Android clients send self::OS, so check that first
                if (!empty($this->properties[self::OS]) && preg_match('/(\d+)\.(\d+)/', $this->properties[self::OS], $matches)) {
                    return $matches[2];
                }
                // Some newer devices send userAgent like Android/4.3.3-EAS-1.3
                if (preg_match('/Android\/(\d+)\.(\d+)/', $this->userAgent, $matches)) {
                    return $matches[2];
                }
                // Older Android/0.3 type userAgent strings.
                if (preg_match('/(.+)\/(\d+)\.(\d+)/', $this->userAgent, $matches)) {
                    return $matches[3];
                }
                break;
            case self::TYPE_TOUCHDOWN:
                 if (preg_match('/(.+)\/(\d+)\.(\d+)/', $this->userAgent, $matches)) {
                    return $matches[3];
                }
                break;
        }

        return 0;
    }

    /**
     * Return the number of hours to offset a POOMCONTACTS:BIRTHDAY
     * or ANNIVERSARY field in an attempt to work around a bug in the
     * protocol - which doesn't define a standard time for birthdays to occur.
     *
     *  WP:
     *     Devices seem to send the birthdays at the entered date, with
     *     a time of 00:00:00 UTC during standard time and with 01:00:00 UTC
     *     during DST if the client's configured timezone observes it. No idea
     *     what purpose this serves since no timezone data is transmitted for
     *     birthday values.
     *
     *   iOS:
     *     Seems different based on version. iOS 5+, at least seems to send
     *     the birthday as midnight at the entered date in the device's timezone
     *     then converted to UTC. Some minor issues with offsets being off an
     *     hour or two for some timezones though.
     *
     *     iOS < 5 sends the birthday time part as the time the birthday
     *     was entered/edited on the device, converted to UTC, so it can't be
     *     trusted at all. The best we can do here is transform the date to
     *     midnight on date_default_timezone() converted to UTC.
     *
     *   Android:
     *     For contacts originating on the SERVER, the following is true:
     *
     *     Stock 4.3 Takes the down-synched bday value which is assumed to be
     *     UTC, does some magic to it (converts to milliseconds, creates a
     *     gregorian calendar object, then converts to YYYY-MM-DD). When
     *     sending the bday value up, it sends it up as-is. No conversion
     *     to/from UTC or local is done.
     *
     *     Stock 4.4.x does the above, but before sending the bday value,
     *     validates that it's in a correct format for sending to the server.
     *     This really only affects date data originally entered on the device
     *     for non-stock android clients.
     *
     *     There is some strange bit of code in Android that adds 1 to the
     *     DAY_OF_MONTH when HOUR_OF_DAY >= 12 in an attempt to "fix"
     *     birthday handling for GMT+n users. See:
     *     https://android.googlesource.com/platform/packages/apps/Exchange/+/32daacdd71b9de8fd5e3f59c37934e3e4a9fa972%5E!/exchange2/src/com/android/exchange/adapter/ContactsSyncAdapter.java
     *     Not sure what to make of it, or why it's not just converted to
     *     local tz when displaying but this probably breaks birthday handling
     *     for people in a few timezones.
     *
     *     For contacts originating on the CLIENT, the datetime is sent as
     *     08:00:00 UTC, and this seems to be regardless of the timezone set
     *     in the Android system.
     *
     *     Given all of this, it makes sense to me to ALWAYS send birthday
     *     data as occuring at 08:00:00 UTC for *native* Android clients.
     *
     *   BB 10+ expects it at 12:00:00 UTC
     *
     * @param Horde_Date $date  The date. This should normally be in the local
     *                          timezone if encoding the date for the client.
     *                          If decoding the date from the client, it will
     *                          normally be in UTC.
     * @param boolean $toEas    Convert from local to device if true.
     *                          DEFAULT: false
     *
     * @return Horde_Date  The date of the birthday/anniversary, with
     *                     any fixes applied for the current device. The
     *                     timezone set in the object will depend on the
     *                     client detected, and whether the date is being
     *                     encoding or decoding.
     */
    public function normalizePoomContactsDates($date, $toEas = false)
    {
        switch (Horde_String::lower($this->clientType)) {
        case self::TYPE_WP:
        case 'wp8': // Legacy. Remove in H6.
        case 'wp':  // Legacy. Remove in H6.
            if ($toEas) {
                return new Horde_Date($date->format('Y-m-d'), 'UTC');
            } else {
                $date = new Horde_Date($date->format('Y-m-d'));
                return $date->setTimezone('UTC');
            }

        case self::TYPE_ANDROID:
            // Need to protect against clients that don't send the actual Android
            // version in the OS field.
            if (stripos($this->deviceType, 'samsung') === 0) {
                // Samsung's native Contacts app works differently than stock
                // Android, always sending as 00:00:00
                if ($toEas) {
                    return new Horde_Date($date->format('Y-m-d'), 'UTC');
                }
                $date = new Horde_Date($date->format('Y-m-d'));
                return $date->setTimezone('UTC');
            }
            if ($this->getMajorVersion() >= 4 && $this->getMajorVersion() <= 10) {
                if ($toEas) {
                    return new Horde_Date($date->format('Y-m-d 08:00:00'), 'UTC');
                } else {
                    $date = new Horde_Date($date->format('Y-m-d'));
                    return $date->setTimezone('UTC');
                }
            } else {
                // POOMCONTACTS:BIRTHDAY not really supported in early Android
                // versions. Return as is.
                return $date;
            }

        case self::TYPE_IPAD:
        case self::TYPE_IPHONE:
        case self::TYPE_IPOD:
            if ($this->getMajorVersion() >= 5) {
                // iOS >= 5 handles it correctly more or less.
                if ($toEas) {
                    return new Horde_Date($date->format('Y-m-d 00:00:00'));
                } else {
                    return $date;
                }
            } else {
                if ($toEas) {
                    return new Horde_Date($date->format('Y-m-d'), 'UTC');
                } else {
                    return new Horde_Date($date->format('Y-m-d'));
                }
            }

        case self::TYPE_NINE:
                if ($toEas) {
                    return new Horde_Date($date->format('Y-m-d 00:00:00'));
                } else {
                    return $date;
                }

        case self::TYPE_BLACKBERRY:
            if ($toEas) {
                return new Horde_Date($date->format('Y-m-d 12:00:00'), 'UTC');
            } else {
                $date = new Horde_Date($date->format('Y-m-d'));
                return $date->setTimezone('UTC');
            }

        case self::TYPE_TOUCHDOWN:
        case self::TYPE_UNKNOWN:
        default:
            return $date;
        }
    }

    /**
     * Return if this client has the described quirk.
     *
     * @param integer $quirk  The specified quirk to check for.
     *
     * @return boolean  True if quirk is present.
     */
    public function hasQuirk($quirk)
    {
        switch ($quirk) {
            case self::QUIRK_NEEDS_SUPPORTED_PICTURE_TAG:
                if ($this->isIos() && $this->getMajorVersion() == 4) {
                    return true;
                }
                return false;
                break;
            default:
                return false;
        }
    }

    /**
     * Attempt to determine the *client* application as opposed to the device,
     * which may or may not be the client.
     *
     * @return string  The client name.
     */
    protected function _getClientType()
    {
        // Differentiate between the deviceType and the client app.
        if ((!empty($this->properties[self::OS]) &&
             stripos($this->properties[self::OS], 'Android') !== false) ||
             Horde_String::lower($this->deviceType) == self::TYPE_ANDROID) {

            // We can detect native Android, TouchDown, and Nine.
            // Moxier does not distinguish itself, so we can't sniff it.
            if (strpos($this->userAgent, 'TouchDown') !== false) {
                return self::TYPE_TOUCHDOWN;
            } else if ($this->_isNine()) {
                return self::TYPE_NINE;
            } else if (stripos($this->userAgent, 'Android') !== false) {
                return $this->deviceType;
            } else {
                return self::TYPE_ANDROID;
            }
       } else {
            return $this->deviceType;
       }
    }

    /**
     * Helper method to sniff out the 9Folders client, "Nine".
     * @see https://ninefolders.plan.io/track/7048/46b213 for the discussion on
     * how to sniff out the Nine client. Not the best solution, but it's the one
     * they decided to use.
     *
     * @return boolean  True if client is thought to be "Nine".
     */
    protected function _isNine()
    {
        if (!ctype_xdigit($this->id)) {
            return false;
        }
        return stripos(pack('H*', $this->id), 'nine') === 0;
    }


    /**
     * Basic sniffing for determining if devices can support non-multiplexed
     * collections.
     */
    protected function _sniffMultiplex()
    {
        $clientType = Horde_String::lower($this->clientType);
        if ($this->_isIos()) {
            // iOS seems to support multiple collections for everything except Notes.
            $this->_properties['properties'][self::MULTIPLEX] = Horde_ActiveSync_Device::MULTIPLEX_NOTES;
        } else if ($clientType == self::TYPE_ANDROID) {
            // Special cases: These clients don't support non-multiplexed
            // collections. Samsung's native client and HTCOnemini2.
            if (stripos($this->deviceType, 'samsung') === 0 ||
                stripos($this->model, 'HTCOnemini2') === 0 ||
                $this->deviceType == 'HTCOnemini2') {
                $this->_properties['properties'][self::MULTIPLEX] =
                    Horde_ActiveSync_Device::MULTIPLEX_CONTACTS |
                    Horde_ActiveSync_Device::MULTIPLEX_CALENDAR |
                    Horde_ActiveSync_Device::MULTIPLEX_NOTES |
                    Horde_ActiveSync_Device::MULTIPLEX_TASKS;

                return;
            }

            // All android before 4.4 KitKat requires multiplex. KitKat and
            // Android 5 native supports non-multiplexed calendars only.
            if (!empty($this->properties[self::OS]) &&
                preg_match('/(\d+\.\d+(\.\d+)*)/', $this->properties[self::OS], $matches) &&
                version_compare($matches[0], '4.4.0') >= 0) {
                $this->_properties['properties'][self::MULTIPLEX] =
                    Horde_ActiveSync_Device::MULTIPLEX_NOTES |
                    Horde_ActiveSync_Device::MULTIPLEX_CONTACTS |
                    Horde_ActiveSync_Device::MULTIPLEX_TASKS;
            } else {
                $this->_properties['properties'][self::MULTIPLEX] =
                    Horde_ActiveSync_Device::MULTIPLEX_CONTACTS |
                    Horde_ActiveSync_Device::MULTIPLEX_CALENDAR |
                    Horde_ActiveSync_Device::MULTIPLEX_NOTES |
                    Horde_ActiveSync_Device::MULTIPLEX_TASKS;
            }
        } else if (strpos($this->userAgent, 'MSFT-WP/8.10') !== false) {
            // Windows Phone 8.10 supports multiple calendars and tasks, but
            // no contacts.
            $this->_properties['properties'][self::MULTIPLEX] =
                Horde_ActiveSync_Device::MULTIPLEX_CONTACTS;
        } else if (strpos($this->userAgent, 'MSFT-WP/8.0') !== false || $this->deviceType == 'WP8') {
            // Windows Phone 8.0 seems that only multiple tasklists are
            // supported. The rest must be multiplexed.
            $this->_properties['properties'][self::MULTIPLEX] =
                Horde_ActiveSync_Device::MULTIPLEX_CONTACTS |
                Horde_ActiveSync_Device::MULTIPLEX_CALENDAR;
        } else if (strpos($this->userAgent, 'MSFT-PPC') !== false || $this->deviceType == 'PocketPC') {
            // PocketPC versions seem to not support any user defined
            // collections at all, though I've only tested on a single HTC device.
            $this->_properties['properties'][self::MULTIPLEX] =
                Horde_ActiveSync_Device::MULTIPLEX_CONTACTS |
                Horde_ActiveSync_Device::MULTIPLEX_CALENDAR |
                Horde_ActiveSync_Device::MULTIPLEX_NOTES |
                Horde_ActiveSync_Device::MULTIPLEX_TASKS;
        } else if (strpos($this->userAgent, 'Outlook/15.0') !== false) {
            // OL2013 Doesn't support multiple contact lists.
            $this->_properties['properties'][self::MULTIPLEX] = Horde_ActiveSync_Device::MULTIPLEX_CONTACTS;
        } else {
            $this->_properties['properties']['multiplex'] = 0;
        }
    }

    /**
     * Return if this client is an iOS device. Different versions require
     * different checks.
     *
     * @return boolean [description]
     */
    protected function _isIos()
    {
        // Compare in order of likelyhood / most recent to least recent versions.
        if (strpos($this->{self::OS}, 'iOS') === 0 ||
            strpos($this->userAgent, 'iOS') === 0 ||
            in_array(Horde_String::lower($this->clientType), array(self::TYPE_IPAD, self::TYPE_IPOD, self::TYPE_IPHONE)) ||
            strpos($this->userAgent, 'Apple-') === 0) {

            return true;
        }
        return false;
    }

}
