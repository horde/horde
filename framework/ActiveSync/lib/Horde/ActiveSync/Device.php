<?php
/**
 * Horde_ActiveSync_Device::
 *
 * @license   http://www.horde.org/licenses/gpl GPLv2
 *            NOTE: According to sec. 8 of the GENERAL PUBLIC LICENSE (GPL),
 *            Version 2, the distribution of the Horde_ActiveSync module in or
 *            to the United States of America is excluded from the scope of this
 *            license.
 * @copyright 2013-2014 Horde LLC (http://www.horde.org)
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
 * @copyright 2010-2014 Horde LLC (http://www.horde.org)
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
            $type = $this->_getClientType();
            return $type;
        case self::VERSION:
            if (isset($this->_properties['properties'][self::VERSION])) {
                return $this->_properties['properties'][self::VERSION];
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
            } else {
                $return = null;
                return $return;
            }
        }
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
            $properties = $this->properties;
            if (empty($properties)) {
                $properties = array();
            }
            $properties[$property] = $value;
            $this->setDeviceProperties($properties);
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
        // but Outlook 2013 doesn't even sent the ITEMSETTINGS command, so we
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
     * @todo For 3.0, make it clearer taht deviceInfo is per-user and
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

    public function getMajorVersion()
    {
        switch (strtolower($this->clientType)) {
            case self::TYPE_BLACKBERRY:
                if (preg_match('/(.+)\/(.+)/', $this->userAgent, $matches)) {
                    return $matches[2];
                }
                break;
            case self::TYPE_IPOD:
            case self::TYPE_IPAD:
                if (preg_match('/(\d+)\.(\d+)/', $this->properties[self::OS], $matches)) {
                    return $matches[1];
                }
                break;
            case self::TYPE_IPHONE:
                if (preg_match('/(.+)\/(\d+)\.(\d+)/', $this->userAgent, $matches)) {
                    return $matches[2];
                }
                break;
            case self::TYPE_ANDROID:
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
     * Return the number of hours to offset a POOMCONTACTS:BIRTHDAY
     * or ANNIVERSARY field in an attempt to work around a bug in the
     * protocol - which doesn't define a standard time for birthdays to occur.
     *
     * @param Horde_Date $date  The date.
     * @param boolean $toEas    Convert from local to device if true.
     *
     * @return Horde_Date  The date of the birthday/anniversary, in UTC, with
     *                     any fixes applied for the current device.
     */
    public function normalizePoomContactsDates($date, $toEas = false)
    {
        // WP devices seem to send the birthdays at the entered date, with
        // a time of 00:00:00 UTC.
        //
        // iOS seems different based on version. iOS 5+, at least seems to send
        // the birthday as midnight at the entered date in the device's timezone
        // then converted to UTC. Some minor issues with offsets being off an
        // hour or two for some timezones though.
        //
        // iOS < 5 sends the birthday time part as the time the birthday
        // was entered/edited on the device, converted to UTC, so it can't be
        // trusted at all. The best we can do here is transform the date to
        // midnight on date_default_timezone() converted to UTC.
        //
        // Native Android 4 ALWAYS sends it as 08:00:00 UTC
        //
        // BB 10+ expects it at 12:00:00 UTC
        switch (strtolower($this->clientType)) {
        case self::TYPE_WP:
        case 'wp8': // Legacy. Remove in H6.
        case 'wp':  // Legacy. Remove in H6.
            if ($toEas) {
                return new Horde_Date($date->format('Y-m-d'), 'UTC');
            } else {
                return new Horde_Date($date->format('Y-m-d'));
            }

        case self::TYPE_ANDROID:
            if ($this->getMajorVersion() >= 4) {
                if ($toEas) {
                    return new Horde_Date($date->format('Y-m-d 08:00:00'), 'UTC');
                } else {
                    return new Horde_Date($date->format('Y-m-d'));
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
                return $date;
            } else {
                if ($toEas) {
                    return new Horde_Date($date->format('Y-m-d'), 'UTC');
                } else {
                    return new Horde_Date($date->format('Y-m-d'));
                }
            }

        case self::TYPE_BLACKBERRY:
            if ($toEas) {
                return new Horde_Date($date->format('Y-m-d 12:00:00'), 'UTC');
            } else {
                return new Horde_Date($date->format('Y-m-d'));
            }

        case self::TYPE_TOUCHDOWN:
        case self::TYPE_UNKNOWN:
        default:
            return $date;
        }
    }

    /**
     * Attempt to determine the *client* application as opposed to the device,
     * which may or may not be the client.
     *
     * @return string  The client name, or self::TYPE_UNKNOWN if unable to
     *                 determine.
     */
    protected function _getClientType()
    {
        // Differentiate between the deviceType and the client app.
        if ((!empty($this->properties[self::OS]) &&
             stripos($this->properties[self::OS], 'Android') !== false) ||
             strtolower($this->deviceType) == self::TYPE_ANDROID) {

            // We can detect native android and TouchDown so far.
            // Moxier does not distinguish itself, so we can't sniff it.
            if (strpos($this->userAgent, 'TouchDown') !== false) {
                return self::TYPE_TOUCHDOWN;
            } else if (stripos($this->userAgent, 'Android') !== false) {
                return $this->deviceType;
            } else {
                return self::TYPE_UNKNOWN;
            }
       } else {
            return $this->deviceType;
       }
    }

    /**
     * Basic sniffing for determining if devices can support non-multiplexed
     * collections.
     */
    protected function _sniffMultiplex()
    {
        $clientType = strtolower($this->clientType);
        if (strpos($this->userAgent, 'iOS') === 0 || in_array($clientType, array(self::TYPE_IPAD, self::TYPE_IPOD, self::TYPE_IPHONE))) {
            // iOS seems to support multiple collections for everything except Notes.
            $this->_properties['properties'][self::MULTIPLEX] = Horde_ActiveSync_Device::MULTIPLEX_NOTES;
        } else if ($clientType == self::TYPE_ANDROID) {
            // All android before 4.4 KitKat requires multiplex. KitKat supports
            // non-multiplexed calendars only.
            if (!empty($this->properties[self::OS]) &&
                preg_match('/(\d+\.\d+\.\d+)/', $this->properties[self::OS], $matches) &&
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
        } else if (strpos($this->userAgent, 'MSFT-WP/8.0') !== false || $this->deviceType == 'WP8') {
            // Windows Phone. For the devices I've tested, it seems that
            // only multiple tasklists are accepted. The rest must be
            // multiplexed.
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
        } else {
            $this->_properties['properties']['multiplex'] = 0;
        }
    }

}
