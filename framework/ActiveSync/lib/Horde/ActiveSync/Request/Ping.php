<?php
/**
 * Horde_ActiveSync_Request_Ping::
 *
 * Portions of this class were ported from the Z-Push project:
 *   File      :   wbxml.php
 *   Project   :   Z-Push
 *   Descr     :   WBXML mapping file
 *
 *   Created   :   01.10.2007
 *
 *   � Zarafa Deutschland GmbH, www.zarafaserver.de
 *   This file is distributed under GPL-2.0.
 *   Consult COPYING file for details
 *
 * @license   http://www.horde.org/licenses/gpl GPLv2
 *            NOTE: According to sec. 8 of the GENERAL PUBLIC LICENSE (GPL),
 *            Version 2, the distribution of the Horde_ActiveSync module in or
 *            to the United States of America is excluded from the scope of this
 *            license.
 * @copyright 2009-2012 Horde LLC (http://www.horde.org)
 * @author    Michael J Rubinsky <mrubinsk@horde.org>
 * @package   ActiveSync
 */
/**
 * Handle PING requests.
 *
 * @license   http://www.horde.org/licenses/gpl GPLv2
 *            NOTE: According to sec. 8 of the GENERAL PUBLIC LICENSE (GPL),
 *            Version 2, the distribution of the Horde_ActiveSync module in or
 *            to the United States of America is excluded from the scope of this
 *            license.
 * @copyright 2009-2012 Horde LLC (http://www.horde.org)
 * @author    Michael J Rubinsky <mrubinsk@horde.org>
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
            $this->_procid,
            $now));

        // Get the settings for the server and load the syncCache
        $this->_pingSettings = $this->_driver->getHeartbeatConfig();
        $timeout = $this->_pingSettings['waitinterval'];
        $this->_statusCode = self::STATUS_NOCHANGES;
        $syncCache = new Horde_ActiveSync_SyncCache(
            $this->_stateDriver,
            $this->_device->id,
            $this->_device->user);

        // Build the collection array from anything we have in the cache.
        $collections = array();
        $cache_collections = $syncCache->getCollections(false);
        $lifetime = $this->_checkHeartbeat(empty($syncCache->pingheartbeat)
            ? 300
            : $syncCache->pingheartbeat);

        // Build the $collections array if we receive request from PIM
        if ($this->_decoder->getElementStartTag(self::PING)) {
            if ($this->_decoder->getElementStartTag(self::HEARTBEATINTERVAL)) {
                $lifetime = $this->_checkHeartbeat($this->_decoder->getElementContent());
                $this->_decoder->getElementEndTag();
            }
            if ($lifetime == 0) {
                $lifetime = $this->_pingSettings['heartbeatdefault'];
            }

            // Save the hbinterval to the syncCache.
            $syncCache->pingheartbeat = $lifetime;

            if ($this->_decoder->getElementStartTag(self::FOLDERS)) {
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

                    // Ensure we have a synckey, or force a resync.
                    $collection['synckey'] = !empty($cache_collections[$collection['id']]['lastsynckey'])
                        ? $cache_collections[$collection['id']]['lastsynckey']
                        : 0;

                    $collections[$collection['id']] = $collection;
                }

                // Set the collections as PINGable.
                foreach ($cache_collections as $value) {
                    if (!empty($collections[$value['id']])) {
                        $syncCache->setPingableCollection($value['id']);
                    } else {
                        $syncCache->removePingableCollection($value['id']);
                    }
                }

                if (!$this->_decoder->getElementEndTag()) {
                    throw new Horde_ActiveSync_Exception('Protocol Error');
                }
            }
            if (!$this->_decoder->getElementEndTag()) {
                throw new Horde_ActiveSync_Exception('Protocol Error');
            }
        } elseif (empty($cache_collections)) {
                // If empty here, we have an empty PING request, but have no
                // cached sync collections.
                $this->_statusCode = self::STATUS_MISSING;
        } else {
            // Build the list of PINGable collections from the cache.
            foreach ($cache_collections as $key => $collection) {
                if ($syncCache->collectionIsPingable($key)) {
                    $collections[$key] = $collection;
                    $collections[$key]['synckey'] = !empty($collection['lastsynckey'])
                        ? $collection['lastsynckey']
                        : 0;
                }
            }
            $this->_logger->debug(sprintf('Reusing PING state: %s', print_r($collections, true)));
        }

        // Remove any collections that have not yet been synced.
        foreach ($collections as $id => $collection) {
            if (!isset($collection['synckey'])) {
                unset($collections[$id]);
            }
        }

        // If empty here, we have collections requested to be PINGed but have
        // not sync'd any yet.
        if (empty($collections)) {
            $this->_logger->err('0 collections');
            $this->_statusCode = self::STATUS_MISSING;
        }

        // Start waiting for changes, but only if we don't have any errors
        $changes = array();
        $dataavailable = false;
        if ($this->_statusCode == self::STATUS_NOCHANGES) {
            $this->_logger->info(sprintf(
                '[%s] Waiting for changes (heartbeat interval: %d)',
                $this->_procid,
                $lifetime)
            );

            // Save the timestamps
            $syncCache->lastuntil = $now + $lifetime;
            $syncCache->lasthbsyncstarted = time();

            while (time() < $syncCache->lastuntil) {
                // Check the remote wipe status
                if ($this->_provisioning === true) {
                    $rwstatus = $this->_stateDriver->getDeviceRWStatus($this->_device->id);
                    if ($rwstatus == Horde_ActiveSync::RWSTATUS_PENDING ||
                        $rwstatus == Horde_ActiveSync::RWSTATUS_WIPED) {

                        $this->_statusCode = self::STATUS_FOLDERSYNCREQD;
                        $syncCache->lastuntil = time();
                        break;
                    }
                }

                foreach ($collections as $collection) {
                    $sync = $this->_getSyncObject();
                    try {
                        $this->_initState($collection);
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
                            $this->_procid,
                            $e->getMessage()));
                        $syncCache->lastuntil = time();
                        $this->_statusCode = self::STATUS_NEEDSYNC;
                        $dataavailable = true;
                        $changes[$collection['id']] = 1;
                        break;
                    } catch (Horde_ActiveSync_Exception_StateGone $e) {
                        $this->_logger->err(sprintf(
                            "[%s] State gone, PING terminating and forcing a SYNC: %s",
                            $this->_procid,
                            $e->getMessage()));
                        $this->_statusCode = self::STATUS_NEEDSYNC;
                        $dataavailable = true;
                        $changes[$collection['id']] = 1;
                        $syncCache->lastuntil = time();
                        $syncCache->removeCollection($collection['id']);
                        break;
                    } catch (Horde_ActiveSync_Exception $e) {
                        $this->_logger->err(sprintf(
                            "[%s] PING terminating unknown error: %s",
                            $this->_procid,
                            $e->getMessage()));
                        $this->_statusCode = self::STATUS_SERVERERROR;
                        $syncCache->lastuntil = time();
                        $syncCache->removeCollection($collection['id']);
                        break;
                    }
                    try {
                        $sync->init($this->_stateDriver, null, $collection, true);
                    } catch (Horde_ActiveSync_Exception_StaleState $e) {
                        $this->_logger->err(sprintf(
                            "[%s] PING terminating and force-clearing device state: %s",
                            $this->_procid,
                            $e->getMessage()));
                        $this->_stateDriver->loadState(array(), null, Horde_ActiveSync::REQUEST_TYPE_SYNC, $collection['id']);
                        $changes[$collection['id']] = 1;
                        $this->_statusCode = self::STATUS_NEEDSYNC;
                        $syncCache->lastuntil = time();
                        break;
                    } catch (Horde_ActiveSync_Exception_FolderGone $e) {
                        $this->_logger->err(sprintf(
                            "[%s] PING terminating and forcing a FOLDERSYNC",
                            $this->_procid));
                        $this->_statusCode = self::STATUS_FOLDERSYNCREQD;
                        $syncCache->lastuntil = time();
                        break;
                    } catch (Horde_ActiveSync_Exception $e) {
                        // Stop ping if exporter cannot be configured
                        $this->_logger->err(sprintf(
                            "[%s] PING error: Exporter can not be configured: %s Waiting 30 seconds before PING is retried.",
                            $this->_procid,
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
                        $this->_procid));
                    break;
                }
                sleep($timeout);
                // Need to refresh collection data in case a SYNC was performed
                // while the PING was still alive. Note that just killing the
                // PING if a SYNC is detected will cause the device to stop
                // pushing.
                $syncCache->refreshCollections();
            }
        }

        $syncCache->lastsyncendnormal = time();
        $syncCache->save();

        // Prepare for response
        $this->_logger->info(sprintf(
            "[%s] Sending response for PING.",
            $this->_procid));

        $this->_encoder->StartWBXML();
        $this->_encoder->startTag(self::PING);
        $this->_encoder->startTag(self::STATUS);
        $this->_encoder->content($this->_statusCode);
        $this->_encoder->endTag();
        if ($this->_statusCode == self::STATUS_HBOUTOFBOUNDS) {
            $this->_encoder->startTag(self::HEARTBEATINTERVAL);
            $this->_encoder->content($lifetime);
            $this->_encoder->endTag();
        } elseif (!empty($collections) && $this->_statusCode != self::STATUS_NOCHANGES) {
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

        return true;
    }

    /**
     * Attempt to initialize the sync state.
     *
     * @param array $collection  The collection array
     */
    protected function _initState($collection)
    {
        if (empty($collection['synckey'])) {
            throw new Horde_ActiveSync_Exception_InvalidRequest('Empty synckey for ' . $collection['id']);
        }

        // Initialize the state
        $this->_logger->debug(sprintf(
            "[%s] Initializing state for collection: %s, synckey: %s",
            getmypid(),
            $collection['id'],
            $collection['synckey'])
        );

        $this->_stateDriver->loadState(
            $collection,
            $collection['synckey'],
            Horde_ActiveSync::REQUEST_TYPE_SYNC,
            $collection['id']);
    }

}
