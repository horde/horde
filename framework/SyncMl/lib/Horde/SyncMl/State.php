<?php
/**
 * The Horde_SyncMl_State class provides a SyncML state object.
 *
 * Copyright 2003-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Anthony Mills <amills@pyramid6.com>
 * @author  Jan Schneider <jan@horde.org>
 * @package SyncMl
 */
class Horde_SyncMl_State
{
    /**
     * Id of this SyncML session.
     *
     * This is not to confuse with the PHP session id, though it is part of
     * the generated PHP session id.
     *
     * @var string
     */
    public $sessionID;

    /**
     * Id of the current message.
     *
     * @var integer
     */
    public $messageID;

    /**
     * The target URI as sent by the client.
     *
     * This is normally the URL of the RPC server. However the client is
     * free to send anything.
     *
     * @var string
     */
    public $targetURI;

    /**
     * The source URI as sent by the client.
     *
     * Can be used to identify the client and is part of the PHP session id.
     *
     * @var string
     */
    public $sourceURI;

    /**
     * SyncML protocol version.
     *
     * 0 for SyncML 1.0, 1 for SyncML 1.1, etc.
     *
     * @var integer
     */
    public $version;

    /**
     * Username used to authenticate with the backend.
     *
     * @var string
     */
    public $user;

    /**
     * Whether this session has authenticated successfully.
     *
     * @var boolean
     */
    public $authenticated = false;

    /**
     * <SyncML> namespace uri.
     *
     * @var string
     */
    protected $_uri;

    /**
     * <Meta> namespace uri.
     *
     * @var string
     */
    public $uriMeta;

    /**
     * <DevInf> namespace uri.
     *
     * @var string
     */
    public $uriDevInf;

    /**
     * Whether WBXML encoding is used.
     *
     * @var boolean
     */
    public $wbxml = false;

    /**
     * The maximum allowed message size in bytes.
     *
     * @todo Change to PHP_INT_MAX.
     *
     * @var integer
     */
    public $maxMsgSize = 1000000000;

    /**
     * Array of Horde_SyncMl_Sync objects.
     *
     * @var array
     */
    protected $_syncs = array();

    /**
     * The list of all server changes being sent to the client as a reference
     * for Status responses from the client.
     *
     * @var array
     */
    public $serverChanges = array();

    /**
     * Name of the appropriate device driver.
     *
     * @var string
     */
    protected $_deviceDriver;

    /**
     * Device info provided by the SyncML DevInf data.
     *
     * @var Horde_SyncMl_DeviceInfo
     */
    public $deviceInfo;

    /**
     * Current sync element sent from client.
     *
     * Stored in state if one element is split into multiple message packets.
     *
     * @var Horde_SyncMl_SyncElement
     */
    public $curSyncItem;

    /**
     * Flag that is set if the client sends a Final but we are not finished
     * with the current package and thus can't final this package yet.
     *
     * @var boolean
     */
    public $delayedFinal = false;

    /**
     * Constructor.
     */
    public function __construct($sourceURI, $user, $sessionID)
    {
        $this->sourceURI = $sourceURI;
        $this->user = $user;
        $this->sessionID = $sessionID;

        /* Create empty dummy device info. Will be replaced with real DevInf
         * information if provided by the client. */
        $this->deviceInfo = new Horde_SyncMl_DeviceInfo();
    }

    /**
     * Returns the <DevInf><VerDTD> content based on the protocol version.
     */
    public function getVerDTD()
    {
        switch ($this->version) {
            case 1:
                return '1.1';
            case 2:
                return '1.2';
            default:
                return '1.0';
        }
    }

    /**
     * Returns the DevInf URI based on the protocol version.
     */
    public function getDevInfURI()
    {
        switch ($this->version) {
            case 1:
                return './devinf11';
            case 2:
                return './devinf12';
            default:
                return './devinf10';
        }
    }

    /**
     * Returns the protocol name based on the protocol version.
     */
    public function getProtocolName()
    {
        switch ($this->version) {
            case 1:
                return 'SyncML/1.1';
            case 2:
                return 'SyncML/1.2';
            default:
                return 'SyncML/1.0';
        }
    }

    /**
     * Sets the protocol version
     *
     * @param integer $version  The protocol version: 0 for SyncML 1.0, 1 for
     *                          SyncML 1.1 etc.
     */
    public function setVersion($version)
    {
        switch ($version) {
        case 1:
            $this->_uri = Horde_SycnMl::NAME_SPACE_URI_SYNCML_1_1;
            $this->uriMeta = Horde_SycnMl::NAME_SPACE_URI_METINF_1_1;
            $this->uriDevInf = Horde_SycnMl::NAME_SPACE_URI_DEVINF_1_1;
            break;
        case 2:
            $this->_uri = Horde_SycnMl::NAME_SPACE_URI_SYNCML_1_2;
            $this->uriMeta = Horde_SycnMl::NAME_SPACE_URI_METINF_1_2;
            $this->uriDevInf = Horde_SycnMl::NAME_SPACE_URI_DEVINF_1_2;
            break;
        default:
            $this->_uri = Horde_SycnMl::NAME_SPACE_URI_SYNCML;
            $this->uriMeta = Horde_SycnMl::NAME_SPACE_URI_METINF;
            $this->uriDevInf = Horde_SycnMl::NAME_SPACE_URI_DEVINF;
            break;
        }

        $this->version = $version;
    }

    /**
     * Returns the namespace URI for the <SyncML> element.
     *
     * @return string  The namespace URI to use, if any.
     */
    public function getURI()
    {
        /* The non WBXML devices (notably SonyEricsson and Funambol) seem to
         * get confused by a <SyncML xmlns="syncml:SYNCML1.1"> element. They
         * require just <SyncML>. So don't use a namespace for non-wbxml
         * devices. */
        if ($this->wbxml || $this->version > 0) {
            return $this->_uri;
        } else {
            return '';
        }
    }

    /**
     * Returns a Horde_SyncMl_Device instance for the device used in this session.
     *
     * @return Horde_SyncMl_Device  A Horde_SyncMl_Device instance.
     */
    public function getDevice()
    {
        if (empty($this->_deviceDriver)) {
            $si = $this->sourceURI;
            $di = $this->deviceInfo;

            if (stristr($si, 'sync4j') !== false ||
                stristr($si, 'sc-pim') !== false ||
                stristr($si, 'fol-') !== false ||
                stristr($si, 'fwm-') !== false ||
                stristr($si, 'fbb-') !== false) {
                $this->_deviceDriver = 'Sync4j';
            } elseif (!empty($di->Man) &&
                      (stristr($di->Man, 'Sony Ericsson') !== false ||
                       stristr($di->Mod, 'A1000') !== false)) {
                /* The Morola A1000 has a similar (UIQ) firmware as the
                 * P800: */
                $this->_deviceDriver = 'P800';
            } elseif (!empty($di->Man) &&
                      stristr($di->Man, 'synthesis') !== false) {
                $this->_deviceDriver = 'Synthesis';
            } elseif (!empty($di->Man) &&
                      stristr($di->Man, 'nokia') !== false) {
                $this->_deviceDriver = 'Nokia';
            } elseif (stristr($si, 'fmz-') !== false) {
                $this->_deviceDriver = 'Sync4JMozilla';
            } else {
                $this->_deviceDriver = 'default';
            }
        }

        return Horde_SyncMl_Device::factory($this->_deviceDriver);
    }

    /**
     * @param string $target
     * @param Horde_SyncMl_Sync $sync
     */
    public function setSync($target, $sync)
    {
        $this->_syncs[$target] = $sync;
    }

    /**
     * @param string $target
     * @return Horde_SyncMl_Sync
     */
    public function getSync($target)
    {
        if (isset($this->_syncs[$target])) {
            return $this->_syncs[$target];
        } else {
            return false;
        }
    }

    /**
     * @return array
     */
    public function getSyncs()
    {
        return $this->_syncs;
    }

    /**
     * Returns whether there are any pending elements that have not been sent
     * to due to message size restrictions. These will be sent int the next
     * message.
     *
     * @return boolean  True if there are pending elements that have yet to be
     *                  sent.
     */
    public function hasPendingSyncs()
    {
        if (is_array($this->_syncs)) {
            foreach ($this->_syncs as $sync) {
                if ($sync->hasPendingElements()) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Returns all syncs which have pending elements left.
     *
     * @return array  Array of TargetLocURIs which can be used as a key in
     *                getSync() calls.
     */
    public function getPendingSyncs()
    {
        $pending = array();
        if (is_array($this->_syncs)) {
            foreach ($this->_syncs as $target => $sync) {
                if ($sync->hasPendingElements()) {
                    $pending[] = $target;
                }
            }
        }
        return $pending;
    }

    /**
     * Returns whether all syncs are in completed state or no syncs are
     * present.
     *
     * @return boolean  True if all syncs are in completed state.
     */
    public function isAllSyncsComplete()
    {
        if (is_array($this->_syncs)) {
            foreach ($this->_syncs as $target => $sync) {
                if (!$sync->isComplete()) {
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * Propagates final tags here and then further to every sync.
     *
     * This allows the sync objects to determine if they are complete.
     */
    public function handleFinal(&$output, $debug = false)
    {
        if (is_array($this->_syncs)) {
            foreach (array_keys($this->_syncs) as $t) {
                $this->_syncs[$t]->handleFinal($output, $debug);
            }
        }
    }
}
