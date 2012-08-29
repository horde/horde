<?php
/**
 * Horde_ActiveSync_Request_Base::
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
 * Base class for handlig ActiveSync requests.
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
    protected $_stateDriver;

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
     * The ActiveSync Version
     *
     * @var float
     */
    protected $_version;

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
     * @var stdClass
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
     * @param Horde_ActiveSync $as                       The ActiveSync server.
     * @param stdClass $device                           The device descriptor.
     *
     * @return Horde_ActiveSync_Request_Base
     */
    public function __construct(Horde_ActiveSync $as, $device)
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
        $this->_stateDriver = &$as->state;

        // Device info
        $this->_device = $device;

        // Procid
        $this->_procid = getmypid();
    }

    /**
     * Ensure the PIM's policy key is current.
     *
     * @param string $sentKey  The policykey sent to us by the PIM
     *
     * @return boolean
     */
    public function checkPolicyKey($sentKey)
    {
        $this->_logger->debug(sprintf(
            "[%s] Checking policykey for device: %s user: %s",
            $this->_device->id,
            $sentKey,
            $this->_driver->getUser()));

        // Use looseprovisioning?
        if (empty($sentKey) && $this->_hasBrokenProvisioning() &&
            $this->_provisioning == Horde_ActiveSync::PROVISIONING_LOOSE) {
            $sentKey = null;
        }

        // Don't attempt if we don't care
        if ($this->_provisioning !== Horde_ActiveSync::PROVISIONING_NONE) {
            $storedKey = $this->_stateDriver->getPolicyKey($this->_device->id);
            $this->_logger->debug('[' . $this->_device->id . '] Stored key: ' . $storedKey);

            // Loose provsioning should allow a blank key
            if ((empty($storedKey) || $storedKey != $sentKey) &&
               ($this->_provisioning !== Horde_ActiveSync::PROVISIONING_LOOSE ||
               ($this->_provisioning === Horde_ActiveSync::PROVISIONING_LOOSE && !is_null($sentKey)))) {

                $this->_activeSync->provisioningRequired();
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
     * Handle the request.
     *
     * @return boolean
     */
    public function handle()
    {
        $this->_version = $this->_activeSync->getProtocolVersion();
        $this->_logger->info(sprintf(
            "Request being handled for device: %s Supporting protocol version: %s",
            $this->_device->id,
            $this->_version)
        );

        try {
            return $this->_handle();
        } catch (Exception $e) {
            $this->_logger->err($e->getMessage());
            throw $e;
        }
    }

    abstract protected function _handle();

    /**
     * Simple factory for the Sync object.
     *
     * @return Horde_ActiveSync_Sync
     */
    protected function _getSyncObject()
    {
        $sync = new Horde_ActiveSync_Sync($this->_driver);
        $sync->setLogger($this->_logger);

        return $sync;
    }

    /**
     * Simple factory method for the importer.
     *
     * @return Horde_ActiveSync_Connector_Importer
     */
    protected function _getImporter()
    {
        $importer = new Horde_ActiveSync_Connector_Importer($this->_driver);
        return $importer;
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
            $this->_logger->debug('[' . $this->_device->id . '] Removing state for bogus VALIDATE device.');
            $this->_stateDriver->removeState(array('devId' => 'validate'));
        }
    }

}
