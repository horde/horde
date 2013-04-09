<?php
/**
 * Horde_ActiveSync_Device::
 *
 * @license   http://www.horde.org/licenses/gpl GPLv2
 *            NOTE: According to sec. 8 of the GENERAL PUBLIC LICENSE (GPL),
 *            Version 2, the distribution of the Horde_ActiveSync module in or
 *            to the United States of America is excluded from the scope of this
 *            license.
 * @copyright 2013 Horde LLC (http://www.horde.org)
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
 * @copyright 2010-2013 Horde LLC (http://www.horde.org)
 * @author    Michael J Rubinsky <mrubinsk@horde.org>
 * @package   ActiveSync
 *
 * @property string id          The device id.
 * @property string deviceType  The device type string.
 * @property integer rwstatus   The RemoteWipe status - a
 *                              Horde_ActiveSync::RWSTATUS_* constant.
 * @property string userAgent   The device's user agent string.
 * @property string user        The user id for the current device account.
 * @property array supported    The SUPPORTED data sent from this device.
 * @property string policykey   The current policykey, if provisioned.
 * @property array properties   The device properties, as sent in DEVICEINFO.
 *
 */
class Horde_ActiveSync_Device
{
    const MODEL        = 'Settings:Model';
    const IMEI         = 'Settings:IMEI';
    const NAME         = 'Settings:FriendlyName';
    const OS           = 'Settings:OS';
    const OS_LANGUAGE  = 'Settings:OSLanguage';
    const PHONE_NUMBER = 'Settings:PhoneNumber';
    const VERSION      = 'version';

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
        return $this->_properties[$property];
    }

    /**
     * Setter
     */
    public function __set($property, $value)
    {
        $this->_properties[$property] = $value;
    }

    /**
     * Magic isset
     */
    public function __isset($property)
    {
        return !empty($this->_properties[$property]);
    }

    /**
     * Check if we should enforce provisioning on this device.
     *
     * @return @boolean
     */
    public function enforceProvisioning()
    {
        if (strpos($this->userAgent, 'Android') !== false) {
            if (preg_match('@EAS[/-]{0,1}([.0-9]{2,})@', $this->userAgent, $matches)) {
                return $matches[1] > 1.2;
            }
            return false;
        }

        // Outlook?
        if (strpos($this->userAgent, 'Microsoft.Outlook') !== false) {
            return false;
        }

        // Enforce provisioning if needed.
        return true;
    }

    /**
     * Set the device's DEVICEINFO data.
     *
     * @param array $data  The data array sent from the device.
     */
    public function setDeviceProperties(array $data)
    {
        if (empty($data['userAgent']) && !empty($this->_properties['userAgent'])) {
            $data['userAgent'] = $this->_properties['userAgent'];
        }
        $this->_state->setDeviceProperties($data, $this->id);
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

        if ($this->properties[self::MODEL]) {
            $data[_("Model")] = $this->properties[self::MODEL];
        }
        if ($this->properties[self::IMEI]) {
            $data[_("IMEI")] = $this->properties[self::IMEI];
        }
        if ($this->properties[self::NAME]) {
            $data[_("Common Name")] = $this->properties[self::NAME];
        }
        if ($this->properties[self::OS]) {
            $data[_("OS")] = $this->properties[self::OS];
        }
        if ($this->properties[self::OS_LANGUAGE]) {
            $data[_("OS Language")] = $this->properties[self::OS_LANGUAGE];
        }
        if ($this->properties[self::PHONE_NUMBER]) {
            $data[_("Phone Number")] = $this->properties[self::PHONE_NUMBER];
        }

        return $data;
    }

    public function getLastSyncTimestamp()
    {
        return $this->_state->getLastSyncTimestamp($this->id, $this->user);
    }

    public function save()
    {
        $this->_state->setDeviceInfo($this);
    }

}