<?php
/**
 * Base class for handling ActiveSync requests
 *
 * Copyright 2009 - 2010 The Horde Project (http://www.horde.org)
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @package ActiveSync
 */
/**
 * Zarafa Deutschland GmbH, www.zarafaserver.de
 * This file is distributed under GPL v2.
 * Consult LICENSE file for details
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
     * Valid values are true, false, or loose.
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
        /* Android devices don't support provisioning, but also send a policykey
         * header - which is against the specification. Check the user agent
         * for Android (maybe need version sniffing in the future) and set the
         * policykey to null for those devices. */
         $this->_device = $this->_state->loadDeviceInfo($this->_device->id, $this->_driver->getUser());
         if (strpos($this->_device->userAgent, 'Android') !== false) {
             $sentKey = null;
         }

        /* Don't attempt if we don't care */
        if ($this->_provisioning !== false) {
            $state = $this->_driver->getStateObject();
            $storedKey = $state->getPolicyKey($this->_device->id);
            /* Loose provsioning should allow a blank key */
            if ((empty($storedKey) || $storedKey != $sentKey) &&
               ($this->_provisioning !== 'loose' ||
               ($this->_provisioning === 'loose' && !is_null($sentKey)))) {

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
        $this->_logger->info('Request received from device: ' . $this->_device->id . ' Supporting protocol version: ' . $this->_version);
    }

}
