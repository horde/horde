<?php
/**
 * Horde_ActiveSync_Request_Base::
 *
 * @license   http://www.horde.org/licenses/gpl GPLv2
 *            NOTE: According to sec. 8 of the GENERAL PUBLIC LICENSE (GPL),
 *            Version 2, the distribution of the Horde_ActiveSync module in or
 *            to the United States of America is excluded from the scope of this
 *            license.
 * @copyright 2010-2014 Horde LLC (http://www.horde.org)
 * @author    Michael J Rubinsky <mrubinsk@horde.org>
 * @package   ActiveSync
 */
/**
 * Base class for handlig ActiveSync requests.
 *
 * @license   http://www.horde.org/licenses/gpl GPLv2
 *            NOTE: According to sec. 8 of the GENERAL PUBLIC LICENSE (GPL),
 *            Version 2, the distribution of the Horde_ActiveSync module in or
 *            to the United States of America is excluded from the scope of this
 *            license.
 * @copyright 2010-2014 Horde LLC (http://www.horde.org)
 * @author    Michael J Rubinsky <mrubinsk@horde.org>
 * @package   ActiveSync
 * @internal
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
     * State driver
     *
     * @var Horde_ActiveSync_State_Base
     */
    protected $_state;

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
     * Valid values are:
     *  - Horde_ActiveSync::PROVISIONING_FORCE: Accept ONLY provisioned devices
     *  - Horde_ActiveSync::PROVISIONING_LOOSE: Force provisioning if device
     *        supports provisioning, allow non-provisionable devices as well.
     *  - Horde_ActiveSync::PROVISIONING_NONE:  Allow any device.
     *
     * @var integer
     */
    protected $_provisioning;

    /**
     * Used to track what error code to send back to PIM on failure
     *
     * @var integer
     */
    protected $_statusCode = 0;

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
     * @var Horde_ActiveSync_Device
     */
    protected $_device;

    /**
     * The procid
     *
     * @var integer
     */
    protected $_procid;

    /**
     * Const'r
     *
     * @param Horde_ActiveSync $as             The ActiveSync server.
     * @param Horde_ActiveSync_Device $device  The device descriptor.
     *
     * @return Horde_ActiveSync_Request_Base
     */
    public function __construct(Horde_ActiveSync $as)
    {
        // Server
        $this->_activeSync = $as;

        // Backend driver
        $this->_driver = $as->driver;

        // Wbxml handlers
        $this->_encoder = $as->encoder;
        $this->_decoder = $as->decoder;

        // The http request
        $this->_request = $as->request;

        // Provisioning support
        $this->_provisioning = $as->provisioning;

        // Get the state object
        $this->_state = $as->state;

        // Device info
        $this->_device = $as->device;

        // Procid
        $this->_procid = getmypid();
    }

    /**
     * Ensure the client's policy key is current.
     *
     * @param string $sentKey      The policykey sent to us by the client
     * @param string $requestType  The type of request we are handling. A
     *                             Horde_ActiveSync constant.
     *
     * @return boolean
     */
    public function checkPolicyKey($sentKey, $requestType = null)
    {
        $this->_logger->info(sprintf(
            '[%s] Checking policykey for device: %s user: %s',
            $this->_procid,
            $this->_device->id,
            $this->_driver->getUser()));

        // Use looseprovisioning?
        if (empty($sentKey) && !$this->_device->enforceProvisioning() &&
            $this->_provisioning === Horde_ActiveSync::PROVISIONING_LOOSE) {
            $sentKey = null;
            $this->_logger->info(sprintf(
                '[%s] Allowing %s to connect since PROVISIONING_LOOSE is true and is either non-provisionable or has broken provisioning.',
                $this->_procid,
                $this->_device->id));
        } elseif (empty($sentKey) && $this->_device->isNonProvisionable()) {
            // Check for non-provisionable, but allowable, devices.
            $this->_logger->info(sprintf(
                '[%s] Allowing %s to connect since it is non-provisionable.',
                $this->_procid,
                $this->_device->id));
            $sentKey = null;
        }

        // Don't attempt if we don't care
        if ($this->_provisioning !== Horde_ActiveSync::PROVISIONING_NONE) {
            // Get the stored key
            $storedKey = $this->_state->getPolicyKey($this->_device->id);
            $this->_logger->info(sprintf(
                '[%s] Stored key: %s',
                $this->_procid, $storedKey));

            // Did we request a remote wipe?
            if ($this->_state->getDeviceRWStatus($this->_device->id) == Horde_ActiveSync::RWSTATUS_PENDING) {
                $this->_requireProvisionWbxml($requestType, Horde_ActiveSync_Status::REMOTEWIPE_REQUESTED);
                return false;
            }

            // Validate the stored key against the device key, honoring
            // the value of _provisioning.
            if ((empty($storedKey) || $storedKey != $sentKey) &&
               ($this->_provisioning != Horde_ActiveSync::PROVISIONING_LOOSE ||
               ($this->_provisioning == Horde_ActiveSync::PROVISIONING_LOOSE && !is_null($sentKey)))) {

                // We send the headers AND the WBXML if EAS 12.1+ since some
                // devices report EAS 14.1 but don't accept the WBXML.
                $this->_activeSync->provisioningRequired();
                if ($this->_device->version > Horde_ActiveSync::VERSION_TWELVEONE) {
                    if (empty($sentKey)) {
                        $status = Horde_ActiveSync_Status::DEVICE_NOT_PROVISIONED;
                    } else {
                        $status = Horde_ActiveSync_Status::INVALID_POLICY_KEY;
                    }
                    $this->_requireProvisionWbxml($requestType, Horde_ActiveSync_Status::DEVICE_NOT_PROVISIONED);
                }

                return false;
            }
        }

        // Either successfully validated, or we didn't care enough to check.
        $this->_logger->info(sprintf(
            '[%s] Policykey: %s verified.',
            $this->_procid, $sentKey));
        return true;
    }

    /**
     * Set the logger.
     *
     * @var Horde_Log_Logger
     */
    public function setLogger(Horde_Log_Logger $logger)
    {
        $this->_logger = $logger;
    }

    /**
     * Handle the request.
     *
     * @return boolean
     */
    public function handle()
    {
        $this->_logger->info(sprintf(
            '[%s] Request being handled for device: %s, Supporting protocol version: %s, Using Horde_ActiveSync v%s',
            $this->_procid,
            $this->_device->id,
            $this->_device->version,
            Horde_ActiveSync::LIBRARY_VERSION)
        );
        $this->_logger->info(sprintf(
            '[%s] GET VARIABLES: %s',
            $this->_procid,
            print_r($this->_activeSync->getGetVars(), true)));
        try {
            return $this->_handle();
        } catch (Exception $e) {
            $this->_logger->err($e->getMessage());
            throw $e;
        }
    }

    /**
     * Clean up after initial pairing. Initial pairing can happen either as a
     * result of either a FOLDERSYNC or PROVISION command, depending on the
     * device capabilities.
     */
    protected function _cleanUpAfterPairing()
    {
        // Android sends a bogus device id of 'validate' during initial
        // handshake. This data is never used again, and the resulting
        // FOLDERSYNC response is ignored by the client. Remove the entry,
        // to avoid having 2 device entries for every android client.
        if ($this->_device->id == 'validate') {
            $this->_logger->info(sprintf(
                '[%s] Removing state for bogus VALIDATE device.',
                $this->_procid));
            $this->_state->removeState(array('devId' => 'validate'));
        }
    }

    /**
     * Send WBXML to indicate provisioning is required.
     *
     * @param string $requestType  The type of request we are handling.
     * @param integer $status      The reason we need to provision.
     */
    protected function _requireProvisionWbxml($requestType, $status)
    {
        $this->_encoder->startWBXML();
        $this->_encoder->startTag($requestType);
        $this->_encoder->startTag(Horde_ActiveSync::SYNC_STATUS);
        $this->_encoder->content($status);
        $this->_encoder->endTag();
        $this->_encoder->endTag();
    }

    /**
     * Implementation method for handling request.
     *
     * @return string|boolean  Content-Type of results if not wbxml, or boolean.
     */
    abstract protected function _handle();

}
