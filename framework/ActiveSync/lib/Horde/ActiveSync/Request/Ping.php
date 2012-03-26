<?php
/**
 * Horde_ActiveSync_Request_Ping
 *
 * PHP Version 5
 *
 * Contains portions of code from ZPush
 * Zarafa Deutschland GmbH, www.zarafaserver.de
 *
 * @license   http://www.horde.org/licenses/gpl GPLv2
 * @copyright 2009-2012 Horde LLC (http://www.horde.org/)
 * @author    Michael J Rubinsky <mrubinsk@horde.org>
 * @link      http://pear.horde.org/index.php?package=ActiveSync
 * @package   ActiveSync
 */
/**
 * ActiveSync Handler for PING requests
 *
 * @license   http://www.horde.org/licenses/gpl GPLv2
 * @copyright 2009-2012 Horde LLC (http://www.horde.org/)
 * @author    Michael J Rubinsky <mrubinsk@horde.org>
 * @link      http://pear.horde.org/index.php?package=ActiveSync
 * @package   ActiveSync
 */

class Horde_ActiveSync_Request_Ping extends Horde_ActiveSync_Request_Base
{
    /* Status Constants */
    const STATUS_NOCHANGES      = 1;
    const STATUS_NEEDSYNC       = 2;
    const STATUS_MISSING        = 3;
    const STATUS_PROTERROR      = 4;
    const STATUS_HBOUTOFBOUNDS  = 5;
    const STATUS_MAXFOLDERS     = 6;
    const STATUS_FOLDERSYNCREQD = 7;
    const STATUS_SERVERERROR    = 8;

    /* PING Wbxml entities */
    const PING              = 'Ping:Ping';
    const STATUS            = 'Ping:Status';
    const HEARTBEATINTERVAL = 'Ping:HeartbeatInterval';
    const FOLDERS           = 'Ping:Folders';
    const FOLDER            = 'Ping:Folder';
    const SERVERENTRYID     = 'Ping:ServerEntryId';
    const FOLDERTYPE        = 'Ping:FolderType';

    /**
     * The device's PING configuration (obtained from state)
     *
     * @var array
     */
    protected $_pingSettings;

    /**
     * Validate the configured/requested heartbeat
     * Will set self::_statusCode appropriately in case of an invalid interval.
     *
     * @param integer $lifetime  The heartbeat to verify
     *
     * @return integer  The valid heartbeat value to use.
     */
    protected function _checkHeartbeat($lifetime)
    {
        if (!empty($this->_pingSettings['forcedheartbeat'])) {
            return $this->_pingSettings['forcedheartbeat'];
        }
        if ($lifetime !== 0 && $lifetime < $this->_pingSettings['heartbeatmin']) {
            $this->_statusCode = self::STATUS_HBOUTOFBOUNDS;
            $lifetime = $this->_pingSettings['heartbeatmin'];
            $this->_stateDriver->setHeartbeatInterval($lifetime);
        } elseif ($lifetime > $this->_pingSettings['heartbeatmax']) {
            $this->_statusCode = self::STATUS_HBOUTOFBOUNDS;
            $lifetime = $this->_pingSettings['heartbeatmax'];
            $this->_stateDriver->setHeartbeatInterval($lifetime);
        }

        return $lifetime;
    }

    /**
     * Handle a PING command from the PIM. PING is sent periodically by the PIM
     * to tell the server what folders we are interested in monitoring for
     * changes. If no changes are detected by the server during the 'heartbeat'
     * interval, the server sends back a status of self::STATUS_NOCHANGES to
     * indicate heartbeat expired and the client should re-issue the PING
     * command. If a change has been found, the client is sent a
     * self::STATUS_NEEDSYNC and should issue a SYNC command.
     *
     * @return boolean
     */
    protected function _handle()
    {
        $now = time();
        $this->_logger->info(sprintf(
            "[%s] PING received at timestamp: %s.",
            $this->_device->id,
            $now));

        // Get the settings for the server
        $this->_pingSettings = $this->_driver->getHeartbeatConfig();
        $timeout = $this->_pingSettings['waitinterval'];
        $this->_statusCode = self::STATUS_NOCHANGES;

        // Initialize the state machine
        $this->_stateDriver->loadDeviceInfo(
            $this->_device->id, $this->_driver->getUser());

        // See if we have an existing PING state. Need to do this here, before
        // we read in the PING request since the PING request is allowed to omit
        // sections if they have been sent previously
        $collections = array_values($this->_stateDriver->initPingState($this->_device));
        $lifetime = $this->_checkHeartbeat($this->_stateDriver->getHeartbeatInterval());

        // Build the $collections array if we receive request from PIM
        if ($this->_decoder->getElementStartTag(self::PING)) {
            if ($this->_decoder->getElementStartTag(self::HEARTBEATINTERVAL)) {
                $lifetime = $this->_checkHeartbeat($this->_decoder->getElementContent());
                $this->_decoder->getElementEndTag();
            }
            if ($lifetime == 0) {
                $lifetime = $this->_pingSettings['heartbeatdefault'];
            }
            $this->_stateDriver->setHeartbeatInterval($lifetime);

            if ($this->_decoder->getElementStartTag(self::FOLDERS)) {
                $collections = array();
                while ($this->_decoder->getElementStartTag(self::FOLDER)) {
                    $collection = array();
                    if ($this->_decoder->getElementStartTag(self::SERVERENTRYID)) {
                        $collection['id'] = $this->_decoder->getElementContent();
                        $this->_decoder->getElementEndTag();
                    }
                    if ($this->_decoder->getElementStartTag(self::FOLDERTYPE)) {
                        $collection['class'] = $this->_decoder->getElementContent();
                        $this->_decoder->getElementEndTag();
                    }
                    $this->_decoder->getElementEndTag();
                    // Ensure we only PING each collection once
                    $collections = array_merge(
                        $collections,
                        array($collection['id'] => $collection));
                }
                $collections = array_values($collections);

                if (!$this->_decoder->getElementEndTag()) {
                    throw new Horde_ActiveSync_Exception('Protocol Error');
                }
            }
            if (!$this->_decoder->getElementEndTag()) {
                throw new Horde_ActiveSync_Exception('Protocol Error');
            }
            $this->_stateDriver->addPingCollections($collections);
        } else {
            $this->_logger->debug(sprintf('Reusing PING state: %s', print_r($collections, true)));
        }

        $changes = array();
        $dataavailable = false;

        // Start waiting for changes, but only if we don't have any errors
        if ($this->_statusCode == self::STATUS_NOCHANGES) {
            $this->_logger->info(sprintf(
                '[%s] Waiting for changes (heartbeat interval: %d)',
                $this->_device->id,
                $lifetime)
            );
            $expire = $now + $lifetime;
            while (time() <= $expire) {
                // Check the remote wipe status and request a foldersync if
                // we want the device wiped.
                if ($this->_provisioning === true) {
                    $rwstatus = $this->_stateDriver->getDeviceRWStatus($this->_device->id);
                    if ($rwstatus == Horde_ActiveSync::RWSTATUS_PENDING || $rwstatus == Horde_ActiveSync::RWSTATUS_WIPED) {
                        $this->_statusCode = self::STATUS_FOLDERSYNCREQD;
                        break;
                    }
                }

                if (count($collections) == 0) {
                    $this->_logger->err('0 collections');
                    $this->_statusCode = self::STATUS_MISSING;
                    break;
                }

                for ($i = 0; $i < count($collections); $i++) {
                    $collection = $collections[$i];
                    $collection['synckey'] = $this->_device->id;
                    $sync = $this->_getSyncObject();
                    try {
                        $this->_stateDriver->loadPingCollectionState($collection);
                    } catch (Horde_ActiveSync_Exception_InvalidRequest $e) {
                        // I *love* standards that nobody follows. This
                        // really should throw an exception and return a HTTP 400
                        // response since this is explicitly forbidden by the
                        // specification. Some clients, e.g., TouchDown, send
                        // a PING in place of the initial SYNC. But sending the
                        // 400 causes TD to disable push entirely. Instead,
                        // cause the PING to terminate early and hope we have
                        // a SYNC next time it's pinged.
                        $this->_logger->err(sprintf(
                            "[%s] PING terminating: %s",
                            $this->_device->id,
                            $e->getMessage()));
                        $expire = time();
                        break;
                    } catch (Horde_ActiveSync_Exception_StateGone $e) {
                        $this->_logger->err(sprintf(
                            "[%s] PING terminating, forcing a SYNC: %s",
                            $this->_device->id,
                            $e->getMessage()));
                        $this->_statusCode = self::STATUS_NEEDSYNC;
                        $dataavailable = true;
                        $changes[$collection['id']] = 1;
                        $expire = time();
                        break;
                    } catch (Horde_ActiveSync_Exception $e) {
                        $this->_logger->err(sprintf(
                            "[%s] PING terminating: %s",
                            $this->_device->id,
                            $e->getMessage()));
                        $this->_statusCode = self::STATUS_SERVERERROR;
                        $expire = time();
                        break;
                    }
                    try {
                        $sync->init($this->_stateDriver, null, $collection, true);
                    } catch (Horde_ActiveSync_Exception_StaleState $e) {
                        $this->_logger->err(sprintf(
                            "[%s] PING terminating and force-clearing device state: %s",
                            $this->_device->id,
                            $e->getMessage()));
                        $this->_stateDriver->loadState(null, $collection['id']);
                        $changes[$collection['id']] = 1;
                        $this->_statusCode = self::STATUS_NEEDSYNC;
                        $expire = time();
                        break;
                    } catch (Horde_ActiveSync_Exception_FolderGone $e) {
                        $this->_logger->err(sprintf(
                            "[%s] PING terminating and forcing a FOLDERSYNC",
                            $this->_device->id));
                        $this->_statusCode = self::STATUS_FOLDERSYNCREQD;
                        $expire = time();
                        break;
                    } catch (Horde_ActiveSync_Exception $e) {
                        // Stop ping if exporter cannot be configured
                        $this->_logger->err(sprintf(
                            "[%s] PING error: Exporter can not be configured: %s Waiting 30 seconds before PING is retried.",
                            $e->getMessage()));
                        sleep(30);
                        break;
                    }

                    $changecount = $sync->getChangeCount();
                    if ($changecount > 0) {
                        $dataavailable = true;
                        $changes[$collection['id']] = $changecount;
                        $this->_statusCode = self::STATUS_NEEDSYNC;
                    }
                }

                if ($dataavailable) {
                    $this->_logger->info(sprintf(
                        "[%s] Changes available!",
                        $this->_device->id));
                    break;
                }

                sleep($timeout);
            }
        }

        // Prepare for response
        $this->_logger->info(sprintf(
            "[%s] Sending response for PING.",
            $this->_device->id));

        $this->_encoder->StartWBXML();
        $this->_encoder->startTag(self::PING);
        $this->_encoder->startTag(self::STATUS);
        $this->_encoder->content($this->_statusCode);
        $this->_encoder->endTag();
        if ($this->_statusCode == self::STATUS_HBOUTOFBOUNDS) {
            $this->_encoder->startTag(self::HEARTBEATINTERVAL);
            $this->_encoder->content($lifetime);
            $this->_encoder->endTag();
        } else {
            $this->_encoder->startTag(self::FOLDERS);
            foreach ($collections as $collection) {
                if (isset($changes[$collection['id']])) {
                    $this->_encoder->startTag(self::FOLDER);
                    $this->_encoder->content($collection['id']);
                    $this->_encoder->endTag();
                }
            }
            $this->_encoder->endTag();
        }
        $this->_encoder->endTag();
        $this->_stateDriver->savePingState();

        return true;
    }

}
