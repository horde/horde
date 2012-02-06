<?php
/**
 * Base class for handling ActiveSync requests
 *
 * Copyright 2009-2012 Horde LLC (http://www.horde.org/)
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @package ActiveSync
 */
/**
 * Zarafa Deutschland GmbH, www.zarafaserver.de
 * This file is distributed under GPL-2.0.
 * Consult COPYING file for details
 */
abstract class Horde_ActiveSync_Request_Base
{
    /**
     * Driver for communicating with the backend datastore.
     *
     * @var Horde_ActiveSync_Driver_Base
     */
    protected $_driver;

    /**
     * Encoder
     *
     * @var Horde_ActiveSync_Wbxml_Encoder
     */
    protected $_encoder;

    /**
     * Decoder
     *
     * @var Horde_ActiveSync_Wbxml_Decoder
     */
    protected $_decoder;

    /**
     * Request object
     *
     * @var Horde_Controller_Request_Http
     */
    protected $_request;

    /**
     * Whether we require provisioned devices.
     * Valid values are true, false, or Horde_ActiveSync::PROVISIONING_LOOSE.
     * Loose allows devices that don't know about provisioning to continue to
     * function, but requires devices that are capable to be provisioned.
     *
     * @var mixed
     */
    protected $_provisioning;

    /**
     * The ActiveSync Version
     *
     * @var string
     */
    protected $_version;

    /**
     * Used to track what error code to send back to PIM on failure
     *
     * @var integer
     */
    protected $_statusCode = 0;

    /**
     * State object
     *
     * @var Horde_ActiveSync_State_Base
     */
    protected $_state;

    /**
     * ActiveSync server
     *
     * @var Horde_ActiveSync
     */
    protected $_activeSync;

    /**
     * Logger
     *
     * @var Horde_Log_Logger
     */
    protected $_logger;

    /**
     * The device info
     *
     * @var stdClass
     */
    protected $_device;

    /**
     * Const'r
     *
     * @param Horde_ActiveSync_Driver $driver            The backend driver
     * @param Horde_ActiveSync_Wbxml_Decoder $decoder    The Wbxml decoder
     * @param Horde_ActiveSync_Wbxml_Endcodder $encdoer  The Wbxml encoder
     * @param Horde_Controller_Request_Http $request     The request object
     * @param string $provisioning                       Is provisioning required?
     *
     * @return Horde_ActiveSync
     */
    public function __construct(Horde_ActiveSync_Driver_Base $driver,
                                Horde_ActiveSync_Wbxml_Decoder $decoder,
                                Horde_ActiveSync_Wbxml_Encoder $encoder,
                                Horde_Controller_Request_Http $request,
                                Horde_ActiveSync $as,
                                $device,
                                $provisioning)
    {
        /* Backend driver */
        $this->_driver = $driver;

        /* server */
        $this->_activeSync = $as;

        /* Wbxml handlers */
        $this->_encoder = $encoder;
        $this->_decoder = $decoder;

        /* The http request */
        $this->_request = $request;

        /* Provisioning support */
        $this->_provisioning = $provisioning;

        /* Get the state object */
        $this->_state = &$driver->getStateObject();

        /* Device info */
        $this->_device = $device;
    }

    /**
     * Ensure the PIM's policy key is current.
     *
     * @param integer $sentKey  The policykey sent to us by the PIM
     *
     * @return boolean
     */
    public function checkPolicyKey($sentKey)
    {
         $this->_logger->debug('[' . $this->_device->id . '] Checking policykey for device '
            . ' Key: ' . $sentKey
            . ' User: ' . $this->_driver->getUser());

         $this->_device = $this->_state->loadDeviceInfo($this->_device->id, $this->_driver->getUser());

         // Use looseprovisioning?
         if (empty($sentKey) && $this->_hasBrokenProvisioning() &&
             $this->_provisioning == Horde_ActiveSync::PROVISIONING_LOOSE) {
            $sentKey = null;
         }

        // Don't attempt if we don't care
        if ($this->_provisioning !== false) {
            $state = $this->_driver->getStateObject();
            $storedKey = $state->getPolicyKey($this->_device->id);
            $this->_logger->debug('[' . $this->_device->id . '] Stored key: ' . $storedKey);

            /* Loose provsioning should allow a blank key */
            if ((empty($storedKey) || $storedKey != $sentKey) &&
               ($this->_provisioning !== Horde_ActiveSync::PROVISIONING_LOOSE ||
               ($this->_provisioning === Horde_ActiveSync::PROVISIONING_LOOSE && !is_null($sentKey)))) {

                    Horde_ActiveSync::provisioningRequired();
                    return false;
            }
        }

        $this->_logger->debug('Policykey: ' . $sentKey . ' verified.');

        return true;
    }

    public function setLogger(Horde_Log_Logger $logger)
    {
        $this->_logger = $logger;
    }

    /**
     *
     * @param string $version
     * @param string $devId
     */
    public function handle()
    {
        $this->_version = $this->_activeSync->getProtocolVersion();
        $this->_logger->info('Request being handled for device: ' . $this->_device->id . ' Supporting protocol version: ' . $this->_version);
    }

    /**
     * Utility function to help determine if a device has broken provisioning.
     * This is impossible to get 100% right since versions of Android that
     * are broken and versions that are not both use the same User-Agent string
     * (Android/0.3 for both 2.1, 2.2 and even 2.3). We err on the side
     * of device compatibility at the expense of not being able to provision
     * some non-broken android devices when provisioning is set to
     * Horde_ActiveSync::PROVISIONING_LOOSE.
     *
     * @TODO This should be added to a device object, once we implement
     * Horde_ActiveSync_Device API.
     *
     * @return boolean
     */
    protected function _hasBrokenProvisioning()
    {
        if (strpos($this->_device->userAgent, 'Android') !== false) {
            if (preg_match('@EAS[/-]{0,1}([.0-9]{2,})@', $this->_device->userAgent, $matches)) {
                return ($matches[1] < 1.2);
            }
            return true;
        }

        // WP7 not only doesn't support all EAS 2.5 security poliices, it flat
        // out refuses to notify the server of a partial acceptance and just
        // completely fails.
        if (strpos($this->_device->userAgent, 'MSFT-WP/7') !== false) {
            return true;
        }

        // Not an android device - enforce provisioning if needed.
        return false;
    }

    /**
     * Clean up after initial pairing. Initial pairing can happen either as a
     * result of either a FOLDERSYNC or PROVISION command, depending on the
     * device capabilities.
     *
     * @TODO Move this to a device object??
     */
    protected function _cleanUpAfterPairing()
    {
        // Android sends a bogus device id of 'validate' during initial
        // handshake. This data is never used again, and the resulting
        // FOLDERSYNC response is ignored by the client. Remove the entry,
        // to avoid having 2 device entries for every android client.
        if ($this->_device->id == 'validate') {
            $this->_state->removeState(null, 'validate');
        }
    }

}
