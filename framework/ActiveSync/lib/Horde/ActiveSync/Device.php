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
 */
class Horde_ActiveSync_Device
{
    /**
     * Device properties.
     *
     * @var array
     */
    protected $_properties = array();

    public function __construct($data = array())
    {
        $this->_properties = $data;
    }

    public function __get($property)
    {
        return $this->_properties[$property];
    }

    public function __set($property, $value)
    {
        $this->_properties[$property] = $value;
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
                return !($matches[1] < 1.2);
            }
            return false;
        }

        // WP7 not only doesn't support all EAS 2.5 security poliices, it flat
        // out refuses to notify the server of a partial acceptance and just
        // completely fails.
        if (strpos($this->_device->userAgent, 'MSFT-WP/7') !== false) {
            return false;
        }

        // Outlook?
        if (strpos($this->_device->userAgent, 'Microsoft.Outlook') !== false) {
            return false;
        }

        // Enforce provisioning if needed.
        return true;
    }

}