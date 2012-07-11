<?php
/**
 * Horde_ActiveSync_Request_Sync::
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
 * Handle Sync requests
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
class Horde_ActiveSync_Request_Sync extends Horde_ActiveSync_Request_Base
{
    /* Status */
    const STATUS_SUCCESS                = 1;
    const STATUS_VERSIONMISM            = 2;
    const STATUS_KEYMISM                = 3;
    const STATUS_PROTERROR              = 4;
    const STATUS_SERVERERROR            = 5;

    // 12.1
    const STATUS_FOLDERSYNC_REQUIRED    = 12;
    const STATUS_REQUEST_INCOMPLETE     = 13;
    const STATUS_INVALID_WAIT_HEARTBEAT = 14;

    /* Maximum window size (12.1 only) */
    const MAX_WINDOW_SIZE    = 512;

    /* Maximum HEARTBEAT value (seconds) (12.1 only) */
    const MAX_HEARTBEAT      = 3540;

    /**
     * Collection of all collection arrays for the current SYNC request.
     *
     * @var array
     */
    protected $_collections = array();

    /**
     * The syncCache
     *
     * @var Horde_ActiveSync_SyncCache
     */
    protected $_syncCache ;

    /**
     * Handle the sync request
     *
     * @return boolean
     * @throws Horde_ActiveSync_Exception
     */
    protected function _handle()
    {
        $this->_logger->info(sprintf(
            "[%s] Handling SYNC command.",
            $this->_device->id)
        );

        // Check policy
        if (!$this->checkPolicyKey($this->_activeSync->getPolicyKey())) {
            return true;
        }

        // Defaults
        $this->_statusCode = self::STATUS_SUCCESS;
        $partial = false;

        try {
            $this->_syncCache = new Horde_ActiveSync_SyncCache(
                $this->_stateDriver,
                $this->_device->id,
                $this->_device->user);
        } catch (Horde_ActiveSync_Exception $e) {
            $this->_statusCode = self::STATUS_SERVERERROR;
            $this->_handleGlobalSynError();
            return true;
        }

        // Start decoding request
        if (!$this->_decoder->getElementStartTag(Horde_ActiveSync::SYNC_SYNCHRONIZE)) {
            if ($this->_version == Horde_ActiveSync::VERSION_TWELVEONE) {
                if ($this->_syncCache->countCollections() == 0) {
                    $this->_logger->err(
                        'Empty SYNC request but no SyncCache or SyncCache with no collections.');
                    $this->_statusCode = self::STATUS_REQUEST_INCOMPLETE;
                    $this->_handleGlobalSyncError();
                    return true;
                } else {
                    if (count($this->_syncCache->confirmed_synckeys) > 0) {
                        $this->_logger->err(
                            'Unconfirmed synckeys, but handling a short request. Request full SYNC.');
                        $this->_statusCode = self::STATUS_REQUEST_INCOMPLETE;
                        $this->_handleGlobalSyncError();
                        return true;
                    }
                    $shortsyncreq = true;
                    $this->_syncCache->save();
                    $this->_logger->debug('Empty Sync request taking info from SyncCache.');
                    $this->_collections = $this->_syncCache->getCollections();
                }
            } else {
                $this->_statusCode = self::STATUS_REQUEST_INCOMPLETE;
                $this->_handleGlobalSyncError();
                $this->_logger->err('Empty Sync request and protocolversion < 12.1');
                return true;
            }
        } else {
            // Non-empty SYNC request. Either < 12.1 or a full 12.1 reqeust.
            if ($this->_version == Horde_ActiveSync::VERSION_TWELVEONE) {
                $this->_syncCache->wait = false;
                $this->_syncCache->hbinterval = false;
            }

            while (($sync_tag = ($this->_decoder->getElementStartTag(Horde_ActiveSync::SYNC_WINDOWSIZE) ? Horde_ActiveSync::SYNC_WINDOWSIZE :
                   ($this->_decoder->getElementStartTag(Horde_ActiveSync::SYNC_FOLDERS) ? Horde_ActiveSync::SYNC_FOLDERS :
                   ($this->_decoder->getElementStartTag(Horde_ActiveSync::SYNC_PARTIAL) ? Horde_ActiveSync::SYNC_PARTIAL :
                   ($this->_decoder->getElementStartTag(Horde_ActiveSync::SYNC_WAIT) ? Horde_ActiveSync::SYNC_WAIT :
                   ($this->_decoder->getElementStartTag(Horde_ActiveSync::SYNC_HEARTBEATINTERVAL) ? Horde_ActiveSync::SYNC_HEARTBEATINTERVAL :
                   -1)))))) != -1 ) {

                switch($sync_tag) {
                case Horde_ActiveSync::SYNC_HEARTBEATINTERVAL:
                    if ($this->_syncCache->hbinterval = $this->_decoder->getElementContent()) {
                        $this->_decoder->getElementEndTag();
                    }
                    $this->_logger->debug(sprintf(
                        "[%s] HeartbeatInterval %s Seconds.",
                        $this->_device->id,
                        $this->_syncCache->hbinterval)
                    );
                    if ($this->_syncCache->hbinterval > (self::MAX_HEARTBEAT)) {
                        $this->_logger->err(sprintf(
                            "[%s] HeartbeatInterval outside of allowed range.",
                            $this->_device->id)
                        );
                        $this->_statusCode = self::STATUS_INVALID_WAIT_HEARTBEATINTERVAL;
                        $this->_handleGlobalSyncError(self::MAX_HEARTBEAT);
                        return true;
                    }
                    break;
                case Horde_ActiveSync::SYNC_WAIT:
                    if ($this->_syncCache->wait = $this->_decoder->getElementContent()) {
                        $this->_decoder->getElementEndTag();
                    }
                    $this->_logger->debug(sprintf(
                        "[%s] Wait %s Minutes.",
                        $this->_device->id,
                        $this->_syncCache->wait)
                    );
                    if ($this->_syncCache->wait > (self::MAX_HEARTBEAT / 60)) {
                        $this->_logger->err(sprintf(
                            "[%s] Wait value outside of allowed range.",
                            $this->_device->id)
                        );
                        $this->_statusCode = self::STATUS_INVALID_WAIT_HEARTBEATINTERVAL;
                        $this->_handleGlobalSyncError(self::MAX_HEARBEAT / 60);
                        return true;
                    }
                    break;
                case Horde_ActiveSync::SYNC_PARTIAL:
                    if ($this->_decoder->getElementContent(Horde_ActiveSync::SYNC_PARTIAL)) {
                        $this->_decoder->getElementEndTag();
                    }
                    $partial = true;
                    break;
                case Horde_ActiveSync::SYNC_WINDOWSIZE:
                    $default_maxitems = $this->_decoder->getElementContent();
                    $this->_logger->debug(sprintf(
                        "[%s] Global WINDOWSIZE set to %s",
                        $this->_device->id,
                        $default_maxitems));
                    if (!$this->_decoder->getElementEndTag()) {
                        $this->_logger->err('PROTOCOL ERROR');
                        return false;
                    }
                    break;
                case Horde_ActiveSync::SYNC_FOLDERS:
                    $this->_parseSyncFolders();
                }
            }

            if (!$this->_haveSyncableCollections()) {
                $this->_statusCode = self::STATUS_REQUEST_INCOMPLETE;
                $this->_handleGlobalSyncError();
                return true;
            }

            // Ensure the FILTERTYPE hasn't changed. If so, we need to invalidate
            // the client's synckey to force a sync reset. This is the only
            // reliable way of fetching an older set of data from the backend.
            foreach ($this->_collections as $collection) {
                $cc = $this->_syncCache->getCollections();
                if (!empty($cc[$collection['id']]['filtertype']) &&
                    !empty($collection['filtertype']) &&
                    $cc[$collection['id']]['filtertype'] != $collection['filtertype']) {
                    $this->_syncCache->removeCollection($collection['id']);
                    $this->_syncCache->save();
                    $this->_logger->debug('Invalidating SYNCKEY - found updated filtertype');
                    $this->_statusCode = self::STATUS_KEYMISM;
                    $this->_handleError($collection);
                    return true;
                }
            }

            // Fill in missing values from the cache.
            if ($this->_version == Horde_ActiveSync::VERSION_TWELVEONE) {
                // Give up in case we don't have a synched hierarchy synckey
                if (!isset($this->_syncCache->hierarchy)) {
                    $this->_logger->debug('No HIERARCHY SYNCKEY in sync_cache, invalidating.');
                    $this->_statusCode = self::STATUS_FOLDERSYNC_REQUIRED;
                    $this->_handleGlobalSyncError();
                    return true;
                }

                // Sanity check. These are not allowed in the same request.
                if ($this->_syncCache->hbinterval !== false && $this->_syncCache->wait !== false) {
                    $this->_logger->err('Received both HBINTERVAL and WAIT interval in same request. VIOLATION.');
                    $this->_statusCode = self::STATUS_PROTERROR;
                    $this->_handleGlobalSyncError();
                    return true;
                }

                // Fill in missing information from cache.
                $this->_syncCache->validateCollectionsFromCache($this->_collections);
            }

            // Handle PARTIALSYNC requests
            if ($partial === true) {
                $this->_logger->debug('PARTIAL SYNC');
                $foundsynckey = false;
                $tempSyncCache = clone $this->_syncCache;
                $unchanged_count = 0;
                $synckey_count = 0;
                $confirmed_synckey_count = 0;
                foreach ($this->_collections as $key => $value) {
                    $v1 = $this->_collections[$key];
                    unset($v1['id'], $v1['clientids'], $v1['fetchids'],
                          $v1['getchanges'], $v1['changeids']);
                    $c = $tempSyncCache->getCollections();
                    $v2 = $c[$value['id']];
                    ksort($v1);
                    if (isset($v1['bodyprefs'])) {
                        ksort($v1['bodyprefs']);
                        foreach (array_keys($v1['bodyprefs']) as $k) {
                            ksort($v1['bodyprefs'][$k]);
                        }
                    }
                    ksort($v2);
                    if (isset($v2['bodyprefs'])) {
                        ksort($v2['bodyprefs']);
                        foreach (array_keys($v2['bodyprefs']) as $k) {
                            ksort($v2['bodyprefs'][$k]);
                        }
                    }
                    if (md5(serialize($v1)) == md5(serialize($v2))) {
                        $unchanged_count++;
                    }
                    // Unset in tempSyncCache, since we have it from device.
                    // Afterwards, anything left in tempSyncCache needs to be
                    // added to _collections.
                    $tempSyncCache->removeCollection($value['id']);

                    // Remove keys from confirmed synckeys array and count them
                    if (isset($value['synckey'])) {
                        $foundsynckey = true;
                        if (isset($this->_syncCache->confirmed_synckeys[$value['synckey']])) {
                            $this->_logger->debug(sprintf(
                                'Removed %s from confirmed_synckeys',
                                $value['synckey'])
                            );
                            $this->_syncCache->removeConfirmedKey($value['synckey']);
                            $confirmed_synckey_count++;
                        }
                        $synckey_count++;
                    }
                }
                unset($v1);
                unset($v2);

                if (!$this->_syncCache->validateTimestamps()) {
                    $this->_logger->debug('Request full sync, timestamp validation failed.');
                    $this->_statusCode = self::STATUS_REQUEST_INCOMPLETE;
                    $this->_handleGlobalSyncError();
                    return true;
                }

                // If there are no changes within partial sync, send status 13
                // since sending partial elements without any changes is suspect
                if ($synckey_count > 0 &&
                    $confirmed_synckey_count == 0 &&
                    $unchanged_count == $synckey_count &&
                    time() <= $this->_syncCache->lastuntil &&
                    ($this->_syncCache->wait == false &&
                     $this->_syncCache->hbinterval == false)) {

                    $this->_logger->debug('Partial Request with completely unchanged collections. Request a full SYNC');
                    $this->_statusCode = self::STATUS_REQUEST_INCOMPLETE;
                    $this->_handleGlobalSyncError();
                    return true;
                }

                // Update _collections with all data that was not sent, but we
                // have a synckey for in the sync_cache.
                foreach ($tempSyncCache->getCollections() as $value) {
                    if (isset($default_maxitems)) {
                        $value['windowsize'] = $default_maxitems;
                    }
                    $this->_logger->debug(sprintf(
                        'Using SyncCache State for %s',
                        $value['id']
                    ));
                    array_push($this->_collections, $value);
                }
                unset($tempSyncCache);
            } else {
                // We received a full sync so don't look for missing collections
                // since device only knows the synckeys that it is sending now.
                $this->_syncCache->confirmed_synckeys = array();
                $this->_syncCache->lastuntil = time();
                $this->_syncCache->clearCollectionKeys();
            }

            // Update the sync_cache
            foreach ($this->_collections as $value) {
                $this->_syncCache->updateCollection($value);
            }

            // End SYNC tag.
            if (!$this->_decoder->getElementEndTag()) {
                $this->_statusCode = self::STATUS_PROTERROR;
                $this->_handleGlobalSyncError();
                $this->_logger->err('PROTOCOL ERROR: Missing closing SYNC tag');
                return false;
            }

            // In case some synckeys didn't get confirmed by device we issue a full sync
            if (count($this->_syncCache->confirmed_synckeys) > 0) {
                $this->_logger->debug(sprintf(
                    'Confirmed Synckeys contains %s',
                    print_r($this->_syncCache->confirmed_synckeys, true))
                );
                $this->_logger->err('Some synckeys were not confirmed. Requesting full SYNC');
                $this->_syncCache->confirmed_synckeys = array();
                $this->_syncCache->save();
                $this->_statusCode = self::STATUS_REQUEST_INCOMPLETE;
                $this->_handleGlobalSyncError();
                return true;
            } else {
                $this->_logger->debug('All synckeys confirmed. Continuing with SYNC');
                $this->_syncCache->save();
            }
        } // End of non-empty SYNC request.

        // If this is 12.1, see if we want a looping SYNC.
        if ($this->_version == Horde_ActiveSync::VERSION_TWELVEONE &&
            $this->_statusCode == self::STATUS_SUCCESS &&
            !$this->_dataimported &&
            ($this->_syncCache->wait !== false ||
             $this->_syncCache->hbinterval !== false ||
             $shortsyncreq === true)) {

            // Use the same settings as PING for things like sleep() timeout etc...
            $pingSettings = $this->_driver->getHeartbeatConfig();
            $dataavailable = false;
            $timeout = $pingSettings['waitinterval'];

            if ($this->_syncCache->wait !== false) {
                $until = time() + ($this->_syncCache->wait * 60);
            } elseif ($this->_syncCache->hbinterval !== false) {
                $until = time() + $this->_syncCache->hbinterval;
            } else {
                $until = time() + empty($pingSettings['heartbeatdefault']) ? 10 : $pingSettings['hearbeatdefault'];
            }
            $this->_logger->debug(sprintf(
                'Waiting for changes for %s seconds',
                $until - time())
            );
            $this->_syncCache->lastuntil = $until;
            $this->_syncCache->lasthbsyncstarted = time();
            $this->_syncCache->save();

            // Start the looping SYNC
            $hbrunavrgduration = 0;
            $hbrunmaxduration = 0;
            while ((time() + $hbrunavrgduration) < ($until - $hbrunmaxduration)) {
                $hbrunstarttime = microtime(true);

                // See if another process has altered the sync_cache.
                if (!$this->_syncCache->validateCache()) {
                    $this->_logger->err('Changes in cache determined during looping SYNC exiting here.');
                    return true;
                }

                // Check for WIPE request. If so, force a foldersync so it is performed.
                if ($this->_provisioning === true) {
                    $rwstatus = $this->_stateDriver->getDeviceRWStatus($this->_device->id);
                    if ($rwstatus == Horde_ActiveSync::RWSTATUS_PENDING || $rwstatus == Horde_ActiveSync::RWSTATUS_WIPED) {
                        $this->_statusCode = self::STATUS_FOLDERSYNC_REQUIRED;
                        $this->_handleGlobalSyncError();
                        return true;
                    }
                }

                // Check each collection we are interested in.
                for ($i = 0; $i < count($this->_collections); $i++) {
                    $collection = $this->_collections[$i];
                    try {
                        $this->_initState($collection);
                    } catch (Horde_ActiveSync_Exception_StateGone $e) {
                        $this->_logger->err(sprintf(
                            '[%s] State not found for %s, continuing',
                            $this->_device->id,
                            $collection['id'])
                        );
                        $dataavailable = true;
                        $collections[$i]['getchanges'] = true;
                        continue;
                    } catch (Horde_ActiveSync_Exception $e) {
                        $this->_statusCode = self::STATUS_SERVERERROR;
                        $this->_handleGlobalSyncError();
                        return true;
                    }
                    $sync = $this->_getSyncObject();
                    try {
                        $sync->init($this->_stateDriver, null, array(), true);
                    } catch (Horde_ActiveSync_Expcetion_StaleState $e) {
                        $this->_logger->err(sprintf(
                            '[%s] SYNC terminating and force-clearing device state: %s',
                            $this->_device->id,
                            $e->getMessage())
                        );
                        $this->_stateDriver->loadState(
                            array(),
                            null,
                            Horde_ActiveSync::REQUEST_TYPE_SYNC,
                            $collection['id']);
                        $changecount = 1;
                    } catch (Horde_ActiveSync_Exception_FolderGone $e) {
                        $this->_logger->err(sprintf(
                            '[%s] SYNC terminating: %s',
                            $this->_device->id,
                            $e->getMessage())
                        );
                        $this->_statusCode = self::STATUS_FOLDERSYNC_REQUIRED;
                        $this->_handleGlobalSyncError();
                        return true;
                    } catch (Horde_ActiveSync_Exception $e) {
                        $this->_logger->err(sprintf(
                            '[%s] Sync object cannot be configured, throttling: %s',
                            $this->_device->id,
                            $e->getMessage())
                        );
                        sleep(30);
                        continue;
                    }
                    $changecount = $sync->getChangeCount();
                    if (($changecount > 0)) {
                        $dataavailable = true;
                        $collections[$i]['getchanges'] = true;
                    }
                }

                if ($dataavailable) {
                    $this->_logger->debug(sprintf(
                        '[%s] Found changes!',
                        $this->_device->id)
                    );
                    break;
                }

                sleep ($timeout);
                $hbrunthisduration = (microtime(true) - $hbrunstarttime);
                if ($hbrunavrgduration > 0) {
                    $hbrunavrgduration = ($hbrunavrgduration + $hbrunthisduration) / 2;
                } else {
                    $hbrunavrgduration = $hbrunthisduration;
                }
                if ($hbrunthisduration > $hbrunmaxduration) {
                    $hbrunmaxduration = $hbrunthisduration;
                }
            }
            $this->_logger->debug(sprintf(
                'Max Heartbeat run duration is %s',
                $hbrunmaxduration)
            );
            $this->_logger->debug(sprintf(
                'Average Heartbeat run duration is %s',
                $hbrunavrgduration)
            );

            // Check that no other Sync process already started
            // If so, we exit here and let the other process do the export.
            if (!$this->_syncCache->validateCache()) {
                $this->_logger->debug('Changes in cache determined during Sync Wait/Heartbeat, exiting here.');
                return true;
            }

            $this->_logger->debug(sprintf(
                '[%s] 12.1 SYNC loop complete: DataAvailable: %s, DataImported: %s',
                $dataavailable,
                $dataimported)
            );
        }

        // See if we can do an empty response
        if ($this->_version == Horde_ActiveSync::VERSION_TWELVEONE &&
            $this->_statusCode == SYNC_STATUS_SUCCESS &&
            $dataavailable == false &&
            $dataimported == false &&
            ($this->_syncCache->wait !== false ||
             $this->_syncCache->hbinterval !== false)) {

            $this->_logger->debug('Sending an empty SYNC response.');
            $this->_syncCache->lastsyncendnormal = time();
            $this->_syncCache->save();
            return true;
        }

        // Start output to PIM
        $this->_encoder->startWBXML();
        $this->_encoder->startTag(Horde_ActiveSync::SYNC_SYNCHRONIZE);
        $this->_encoder->startTag(Horde_ActiveSync::SYNC_FOLDERS);
        foreach ($this->_collections as $collection) {
            $statusCode = self::STATUS_SUCCESS;
            $changecount = 0;
            if ((isset($collection['getchanges']) && $collection['getchanges'] == true) ||
                (!isset($collection['getchanges']) && $collection['synckey'] != 0)) {
                try {
                    $this->_initState($collection);
                } catch (Horde_ActiveSync_Exception_StateGone $e) {
                    $this->_logger->err(sprintf(
                        '[%s] SYNC terminating, state not found',
                        $this->_device->id)
                    );
                    $statusCode = self::STATUS_KEYMISM;
                } catch (Horde_ActiveSync_Exception $e) {
                    $this->_logger->err('UNKNOWN ERROR');
                    return false;
                }

                if ($statusCode == self::STATUS_SUCCESS) {
                    $exporter = new Horde_ActiveSync_Connector_Exporter($this->_encoder, $collection['class']);
                    $sync = $this->_getSyncObject();
                    try {
                        $sync->init($this->_stateDriver, $exporter, $collection);
                    } catch (Horde_ActiveSync_Exception_StaleState $e) {
                        $this->_logger->err(sprintf(
                            "[%s] Force restting of state for %s. Invalid state encountered.",
                            $this->_device->id,
                            $collection['id']));
                        $this->_stateDriver->loadState(
                            array(),
                            null,
                            Horde_ActiveSync::REQUEST_TYPE_SYNC,
                            $collection['id']);
                        $statusCode = self::STATUS_KEYMISM;
                    } catch (Horde_ActiveSync_Exception_FolderGone $e) {
                        $this->_logger->err(sprintf(
                            "[%s] FOLDERSYNC required, collection gone.",
                            $this->_device->id));
                        $statusCode = self::STATUS_FOLDERSYNC_REQUIRED;
                    }
                    $changecount = $sync->getChangeCount();
                }
            }

            // Get new synckey if needed
            if ($statusCode == self::STATUS_SUCCESS &&
                (isset($collection['importedchanges']) ||
                $changecount > 0 ||
                $collection['synckey'] == '0' ||
                !empty($collection['fetchids']))) {
                try {
                    $collection['newsynckey'] = $this->_stateDriver->getNewSyncKey($collection['synckey']);
                    $this->_logger->debug(sprintf(
                        "Old SYNCKEY: %s, New SYNCKEY: %s",
                        $collection['synckey'],
                        $collection['newsynckey'])
                    );
                } catch (Horde_ActiveSync_Exception $e) {
                    $statusCode = self::STATUS_KEYMISM;
                }
            }

            $this->_encoder->startTag(Horde_ActiveSync::SYNC_FOLDER);

            // Not sent in > 12.0
            if ($this->_version <= Horde_ActiveSync::VERSION_TWELVE) {
                $this->_encoder->startTag(Horde_ActiveSync::SYNC_FOLDERTYPE);
                $this->_encoder->content($collection['class']);
                $this->_encoder->endTag();
            }

            $this->_encoder->startTag(Horde_ActiveSync::SYNC_SYNCKEY);
            if (isset($collection['newsynckey'])) {
                $this->_encoder->content($collection['newsynckey']);
            } else {
                $this->_encoder->content($collection['synckey']);
            }
            $this->_encoder->endTag();

            $this->_encoder->startTag(Horde_ActiveSync::SYNC_FOLDERID);
            $this->_encoder->content($collection['id']);
            $this->_encoder->endTag();

            $this->_encoder->startTag(Horde_ActiveSync::SYNC_STATUS);
            $this->_encoder->content($statusCode);
            $this->_encoder->endTag();

            // Check the mimesupport because we need it for advanced emails
            if (!isset($collection['mimesupport'])) {
                $collection['mimesupport'] = 0;
            }

            if ($statusCode == self::STATUS_SUCCESS) {
                // Output server IDs for new items we received and added from PIM
                if (isset($collection['clientids']) || count($collection['fetchids']) > 0) {
                    $this->_encoder->startTag(Horde_ActiveSync::SYNC_REPLIES);
                    foreach ($collection['clientids'] as $clientid => $serverid) {
                        $this->_encoder->startTag(Horde_ActiveSync::SYNC_ADD);
                        $this->_encoder->startTag(Horde_ActiveSync::SYNC_CLIENTENTRYID);
                        $this->_encoder->content($clientid);
                        $this->_encoder->endTag();
                        $this->_encoder->startTag(Horde_ActiveSync::SYNC_SERVERENTRYID);
                        $this->_encoder->content($serverid);
                        $this->_encoder->endTag();
                        $this->_encoder->startTag(Horde_ActiveSync::SYNC_STATUS);
                        $this->_encoder->content(1);
                        $this->_encoder->endTag();
                        $this->_encoder->endTag();
                    }

                    // Output any FETCH requests
                    foreach ($collection['fetchids'] as $id) {
                        $data = $this->_driver->fetch($collection['id'], $id, $collection);
                        if ($data !== false) {
                            $this->_encoder->startTag(Horde_ActiveSync::SYNC_FETCH);
                            $this->_encoder->startTag(Horde_ActiveSync::SYNC_SERVERENTRYID);
                            $this->_encoder->content($id);
                            $this->_encoder->endTag();
                            $this->_encoder->startTag(Horde_ActiveSync::SYNC_STATUS);
                            $this->_encoder->content(1);
                            $this->_encoder->endTag();
                            $this->_encoder->startTag(Horde_ActiveSync::SYNC_DATA);
                            $data->encodeStream($this->_encoder);
                            $this->_encoder->endTag();
                            $this->_encoder->endTag();
                        } else {
                            $this->_logger->err(sprintf(
                                "[%s] Unable to fetch %s",
                                $this->_device->id,
                                $id)
                            );
                        }
                    }
                    $this->_encoder->endTag();
                }

                // Send server changes to PIM
                if ((isset($collection['getchanges']) && $collection['getchanges'] == true) ||
                    !isset($collection['getchanges']) && !empty($collection['synckey'])) {
                    if (!empty($collection['windowsize']) && $changecount > $collection['windowsize']) {
                        $this->_encoder->startTag(Horde_ActiveSync::SYNC_MOREAVAILABLE, false, true);
                    }
                    $this->_encoder->startTag(Horde_ActiveSync::SYNC_COMMANDS);
                    $n = 0;
                    while (1) {
                        $progress = $sync->syncronize();
                        if (!is_array($progress)) {
                            break;
                        }
                        $n++;
                        if (!empty($collection['windowsize']) &&
                            $n >= $collection['windowsize']) {

                            $this->_logger->info(sprintf(
                                "[%s] Exported maxItems of messages - more available.",
                                $this->_device->id)
                            );
                            break;
                        }
                    }
                    $this->_encoder->endTag();
                }

                // Save the sync state for the next time
                if (isset($collection['newsynckey'])) {
                    if (!empty($sync) || !empty($importer) || !empty($exporter) || $collection['synckey'] == 0)  {
                        $this->_stateDriver->setNewSyncKey($collection['newsynckey']);
                        $this->_stateDriver->save();
                    } else {
                        $this->_logger->err(sprintf(
                            "[%s] Error saving %s - no state information available.",
                            $this->_device->id,
                            $collection['newsynckey'])
                        );
                    }

                    // Do we need to add the new synckey to the syncCache?
                    if (trim($collection['newsynckey']) != trim($collection['synckey'])) {
                        $this->_syncCache->addConfirmedKey($collection['newsynckey']);
                    }
                    $this->_syncCache->updateCollection(
                        $collection, array('newsynckey' => true, 'unsetChanges' => true)
                    );
                }
            }
            $this->_encoder->endTag();
        }

        $this->_encoder->endTag();
        $this->_encoder->endTag();

        if ($this->_version == Horde_ActiveSync::VERSION_TWELVEONE) {
            if (!$this->_syncCache->validateCache()) {
                $this->_logger->err('Changes detected in sync_cache during wait interval, exiting without updating cache.');
                return true;
            } else {
                $this->_syncCache->lastsyncendnormal = time();
                $this->_syncCache->save();
            }
        } else {
            $this->_syncCache->save();
        }

        return true;
    }

    /**
     * Helper method for parsing incoming SYNC_FOLDERS nodes.
     *
     */
    protected function _parseSyncFolders()
    {
        while ($this->_decoder->getElementStartTag(Horde_ActiveSync::SYNC_FOLDER)) {
            // Defaults
            $collection = array();
            $collection['truncation'] = Horde_ActiveSync::TRUNCATION_ALL;
            $collection['clientids'] = array();
            $collection['fetchids'] = array();
            $collection['windowsize'] = 100;
            $collection['conflict'] = Horde_ActiveSync::CONFLICT_OVERWRITE_PIM;

            while (($folder_tag = ($this->_decoder->getElementStartTag(Horde_ActiveSync::SYNC_FOLDERTYPE) ? Horde_ActiveSync::SYNC_FOLDERTYPE :
                   ($this->_decoder->getElementStartTag(Horde_ActiveSync::SYNC_SYNCKEY) ? Horde_ActiveSync::SYNC_SYNCKEY :
                   ($this->_decoder->getElementStartTag(Horde_ActiveSync::SYNC_FOLDERID) ? Horde_ActiveSync::SYNC_FOLDERID :
                   ($this->_decoder->getElementStartTag(Horde_ActiveSync::SYNC_WINDOWSIZE) ? Horde_ActiveSync::SYNC_WINDOWSIZE :
                   ($this->_decoder->getElementStartTag(Horde_ActiveSync::SYNC_SUPPORTED) ? Horde_ActiveSync::SYNC_SUPPORTED :
                   ($this->_decoder->getElementStartTag(Horde_ActiveSync::SYNC_DELETESASMOVES) ? Horde_ActiveSync::SYNC_DELETESASMOVES :
                   ($this->_decoder->getElementStartTag(Horde_ActiveSync::SYNC_GETCHANGES) ? Horde_ActiveSync::SYNC_GETCHANGES :
                   ($this->_decoder->getElementStartTag(Horde_ActiveSync::SYNC_OPTIONS) ? Horde_ActiveSync::SYNC_OPTIONS :
                   ($this->_decoder->getElementStartTag(Horde_ActiveSync::SYNC_COMMANDS) ? Horde_ActiveSync::SYNC_COMMANDS :
                   -1)))))))))) != -1) {

                switch ($folder_tag) {
                case Horde_ActiveSync::SYNC_FOLDERTYPE:
                    // Evidently not always sent in 12.1 requests??
                    $collection['class'] = $this->_decoder->getElementContent();
                    $this->_logger->info(sprintf(
                        "[%s] Syncing folder class: %s",
                        $this->_device->id,
                        $collection['class'])
                    );
                    if (!$this->_decoder->getElementEndTag()) {
                        throw new Horde_ActiveSync_Exception('Protocol error');
                    }
                    break;

                case Horde_ActiveSync::SYNC_SYNCKEY:
                    $collection['synckey'] = $this->_decoder->getElementContent();
                    if (!$this->_decoder->getElementEndTag()) {
                        throw new Horde_ActiveSync_Exception('Protocol error');
                    }
                    break;

                case Horde_ActiveSync::SYNC_FOLDERID:
                    $collection['id'] = $this->_decoder->getElementContent();
                    $this->_logger->info(sprintf(
                        "[%s] Folder server id: %s",
                        $this->_device->id,
                        $collection['id'])
                    );
                    if (!$this->_decoder->getElementEndTag()) {
                        throw new Horde_ActiveSync_Exception('Protocol error');
                    }
                    break;

                case Horde_ActiveSync::SYNC_WINDOWSIZE:
                    $collection['windowsize'] = $this->_decoder->getElementContent();
                    if (!$this->_decoder->getElementEndTag()) {
                        $this->_statusCode = self::STATUS_PROTERROR;
                        $this->_handleError($collection);
                        exit;
                    }
                    if ($collection['windowsize'] < 1 || $collection['windowsize'] > self::MAX_WINDOW_SIZE) {
                        $this->_logger->err('[' . $this->_device->id . '] Bad windowsize sent, defaulting to 100');
                        $collection['windowsize'] = 100;
                    }
                    break;

                case Horde_ActiveSync::SYNC_SUPPORTED:
                    // Only allowed on initial sync request
                    if ($collection['synckey'] != 0) {
                        $this->_statusCode = self::STATUS_PROTERROR;
                        $this->_handleError($collection);
                        exit;
                    }
                    while (1) {
                        $el = $this->_decoder->getElement();
                        if ($el[Horde_ActiveSync_Wbxml::EN_TYPE] == Horde_ActiveSync_Wbxml::EN_TYPE_ENDTAG) {
                            break;
                        }
                        $collection['supported'][] = $el[2];
                    }
                    if (!empty($collection['supported'])) {
                        // Initial sync and we have SUPPORTED data - save it
                        if (empty($this->_device->supported)) {
                            $this->_device->supported = array();
                        }
                        $this->_device->supported[$collection['class']] = $collection['supported'];
                        $this->_stateDriver->setDeviceInfo($this->_device);
                    }
                    break;

                case Horde_ActiveSync::SYNC_DELETESASMOVES:
                    if ($collection['deletesasmoves'] = $this->_decoder->getElementContent() &&
                        !$this->_decoder->getElementEndTag()) {
                        throw new Horde_ActiveSync_Exception('Protocol Error');
                    } else {
                        $collection['deletesasmoves'] = true;
                    }
                    break;

                case Horde_ActiveSync::SYNC_GETCHANGES:
                    if (($collection['getchanges'] = $this->_decoder->getElementContent() !== false)) {
                        if (!$this->_decoder->getElementEndTag()) {
                            throw new Horde_ActiveSync_Exception('Protocol Error');
                        }
                    } else {
                        $collection['getchanges'] = true;
                    }
                    break;

                case Horde_ActiveSync::SYNC_OPTIONS:
                    $this->_parseSyncOptions($collection);
                    break;

                case Horde_ActiveSync::SYNC_COMMANDS:
                    $this->_initState($collection);
                    if (!$this->_parseSyncCommands($collection)) {
                        return true;
                    }
                }
            }

            if (!$this->_decoder->getElementEndTag()) {
                $this->_statusCode = self::STATUS_PROTERROR;
                $this->_handleError($collection);
                exit;
            }
            array_push($this->_collections, $collection);

            if ($collection['importedchanges']) {
                $this->_importedChanges = true;
            }
            if ($collection['fetchids']) {
                $this->_fetchids = true;
            }
            if (!$this->_syncCache->collectionExists($collection['id'])) {
                $this->_logger->debug('Creating new sync_cache entry for: ' . $collection['id']);
                $this->_syncCache->addCollection($collection);
            } elseif (isset($collection['windowsize'])) {
                $this->_syncCache->updateWindowSize($collection['id'], $collection['windowsize']);
            }
        }

        if (!$this->_decoder->getElementEndTag()) {
            $this->_logger->err('Parsing Error');
            return false;
        }
    }

    /**
     * Handle incoming SYNC nodes
     *
     * @param array $collection  The current collection array.
     */
    protected function _parseSyncCommands(&$collection)
    {
        // Some broken clients send SYNC_COMMANDS with a synckey of 0.
        // This is a violation of the spec, and could lead to all kinds
        // of data integrity issues.
        if (empty($collection['synckey'])) {
            $this->_logger->err(sprintf(
                "[%s] Attempting a SYNC_COMMANDS, but device failed to send synckey.",
                $this->_device->id));
            $this->_statusCode = self::STATUS_PROTERROR;
            $this->_handleGlobalSyncError();
            return false;
        }

        // Sanity checking, synccahe etc..
        if ($this->_version == Horde_ActiveSync::VERSION_TWELVEONE &&
            !isset($collection['class']) &&
            isset($collection['id'])) {
            if (isset($this->_syncCache->folders[$collection['id']]['class'])) {
                $collection['class'] = $this->_syncCache->folders[$collection['id']]['class'];
                $this->_logger->debug(sprintf(
                    'Obtaining folder %s class from sync_cache: %s',
                    $collection['id'],
                    $collection['class']));
            } else {
                $this->_statusCode = self::STATUS_FOLDERSYNC_REQUIRED;
                $this->_handleGlobalSyncError();
                $this->_logger->debug(sprintf(
                    'No collection class found for %s sending STATUS_FOLDERSYNC_REQUIRED',
                    $collection['id']));
                return false;
            }
        }

        // Configure importer with last state
        $importer = $this->_getImporter();
        $importer->init(
            $this->_stateDriver, $collection['id'], $collection['conflict']);
        $nchanges = 0;
        while (1) {
            // MODIFY or REMOVE or ADD or FETCH
            $element = $this->_decoder->getElement();
            if ($element[Horde_ActiveSync_Wbxml::EN_TYPE] != Horde_ActiveSync_Wbxml::EN_TYPE_STARTTAG) {
                $this->_decoder->_ungetElement($element);
                break;
            }
            $nchanges++;

            if ($this->_decoder->getElementStartTag(Horde_ActiveSync::SYNC_SERVERENTRYID)) {
                $serverid = $this->_decoder->getElementContent();
                if (!$this->_decoder->getElementEndTag()) { // end serverid
                    $this->_statusCode = self::STATUS_PROTERROR;
                    $this->_handleGlobalSyncError();
                    $this->_logger->err('Parsing Error');
                    return false;
                }
            } else {
                $serverid = false;
            }

            if ($this->_decoder->getElementStartTag(Horde_ActiveSync::SYNC_CLIENTENTRYID)) {
                $clientid = $this->_decoder->getElementContent();
                if (!$this->_decoder->getElementEndTag()) { // end clientid
                    $this->_statusCode = self::STATUS_PROTERROR;
                    $this->_handleGlobalSyncError();
                    $this->_logger->err('PARSING ERROR');
                    return false;
                }
            } else {
                $clientid = false;
            }

            // Create Message object from messages passed from PIM
            if ($this->_decoder->getElementStartTag(Horde_ActiveSync::SYNC_DATA)) {
                switch ($collection['class']) {
                case Horde_ActiveSync::CLASS_EMAIL:
                    $appdata = new Horde_ActiveSync_Message_Mail(
                        array('logger' => $this->_logger,
                              'protocolversion' => $this->_version)
                    );
                    $appdata->decodeStream($this->_decoder);
                    break;
                case Horde_ActiveSync::CLASS_CONTACTS:
                    $appdata = new Horde_ActiveSync_Message_Contact(
                        array('logger' => $this->_logger,
                              'protocolversion' => $this->_version));
                    $appdata->decodeStream($this->_decoder);
                    break;
                case Horde_ActiveSync::CLASS_CALENDAR:
                    $appdata = new Horde_ActiveSync_Message_Appointment(
                        array('logger' => $this->_logger,
                              'protocolversion' => $this->_version));
                    $appdata->decodeStream($this->_decoder);
                    break;
                case Horde_ActiveSync::CLASS_TASKS:
                    $appdata = new Horde_ActiveSync_Message_Task(
                        array('logger' => $this->_logger,
                              'protocolversion' => $this->_version));
                    $appdata->decodeStream($this->_decoder);
                    break;
                }
                if (!$this->_decoder->getElementEndTag()) {
                    // End application data
                    $this->_statusCode = self::STATUS_PROTERROR;
                    $this->_handleGlobalSyncError();
                    return false;
                }
            }
            switch ($element[Horde_ActiveSync_Wbxml::EN_TAG]) {
            case Horde_ActiveSync::SYNC_MODIFY:
                if (isset($appdata)) {
                    // Currently, 'read' is only sent by the PDA when it
                    // is ONLY setting the read flag.
                    if ($appdata->propertyExists('read') && $appdata->read !== false) {
                        $importer->importMessageReadFlag(
                            $serverid, $appdata->read, $this->_device->id);
                    } else {
                        $importer->importMessageChange(
                            $serverid, $appdata, $this->_device, false);
                    }
                    $collection['importedchanges'] = true;
                }
                break;

            case Horde_ActiveSync::SYNC_ADD:
                if (isset($appdata)) {
                    $id = $importer->importMessageChange(
                        false, $appdata, $this->_device, $clientid);
                    if ($clientid && $id) {
                        $collection['clientids'][$clientid] = $id;
                        $collection['importedchanges'] = true;
                    }
                }
                break;

            case Horde_ActiveSync::SYNC_REMOVE:
                $collection['removes'][] = $serverid;
                break;

            case Horde_ActiveSync::SYNC_FETCH:
                array_push($collection['fetchids'], $serverid);
                break;
            }

            if (!$this->_decoder->getElementEndTag()) {
                // end change/delete/move
                $this->_statusCode = self::STATUS_PROTERROR;
                $this->_handleGlobalSyncError();
                $this->_logger->err('PARSING ERROR');
                return false;
            }
        }

        // Do all the SYNC_REMOVE requests at once
        if (!empty($collection['removes'])) {
            if (isset($collection['deletesasmoves']) && $folderid = $this->_driver->getWasteBasket($collection['class'])) {
                $importer->importMessageMove($collection['removes'], $folderid);
            } else {
                $importer->importMessageDeletion($collection['removes'], $collection['class']);
            }
            unset($collection['removes']);
            $collection['importedchanges'] = true;
        }

        $this->_logger->debug(sprintf(
            '[%s] Processed %d incoming changes',
            $this->_device->id,
            $nchanges));

        if (!$this->_decoder->getElementEndTag()) {
            // end commands
            $this->_statusCode = self::STATUS_PROTERROR;
            $this->_handleGlobalSyncError();
            $this->_logger->err('PARSING ERROR');
            return false;
        }

        return true;
    }

    /**
     * Helper method to handle incoming OPTIONS nodes.
     *
     * @param array $collection  The current collection array.
     */
    public function _parseSyncOptions(&$collection)
    {
        while(1) {
            if ($this->_decoder->getElementStartTag(Horde_ActiveSync::SYNC_FILTERTYPE)) {
                $collection['filtertype'] = $this->_decoder->getElementContent();
                if (!$this->_decoder->getElementEndTag()) {
                    $this->_statusCode = self::STATUS_PROTERROR;
                    $this->_handleError($collection);
                    exit;
                }
            }
            if ($this->_decoder->getElementStartTag(Horde_ActiveSync::SYNC_TRUNCATION)) {
                $collection['truncation'] = $this->_decoder->getElementContent();
                if (!$this->_decoder->getElementEndTag()) {
                    $this->_statusCode = self::STATUS_PROTERROR;
                    $this->_handleError($collection);
                    exit;
                }
            }
            if ($this->_decoder->getElementStartTag(Horde_ActiveSync::SYNC_RTFTRUNCATION)) {
                $collection['rtftruncation'] = $this->_decoder->getElementContent();
                if (!$this->_decoder->getElementEndTag()) {
                    $this->_statusCode = self::STATUS_PROTERROR;
                    $this->_handleError($collection);
                    exit;
                }
            }

            if ($this->_decoder->getElementStartTag(Horde_ActiveSync::SYNC_MIMESUPPORT)) {
                $collection['mimesupport'] = $this->_decoder->getElementContent();
                if (!$this->_decoder->getElementEndTag()) {
                    $this->_statusCode = self::STATUS_PROTERROR;
                    $this->_handleError($collection);
                    exit;
                }
            }

            if ($this->_decoder->getElementStartTag(Horde_ActiveSync::SYNC_MIMETRUNCATION)) {
                $collection['mimetruncation'] = $this->_decoder->getElementContent();
                if (!$this->_decoder->getElementEndTag()) {
                    $this->_statusCode = self::STATUS_PROTERROR;
                    $this->_handleError($collection);
                    exit;
                }
            }

            if ($this->_decoder->getElementStartTag(Horde_ActiveSync::SYNC_CONFLICT)) {
                $collection['conflict'] = $this->_decoder->getElementContent();
                if (!$this->_decoder->getElementEndTag()) {
                    $this->_statusCode = self::STATUS_PROTERROR;
                    $this->_handleError;
                    exit;
                }
            }

            if ($this->_decoder->getElementStartTag(Horde_ActiveSync::AIRSYNCBASE_BODYPREFERENCE)) {
                $body_pref = array();
                while (1) {
                    if ($this->_decoder->getElementStartTag(Horde_ActiveSync::AIRSYNCBASE_TYPE)) {
                        $body_pref['type'] = $this->_decoder->getElementContent();
                        if (!$this->_decoder->getElementEndTag()) {
                            $this->_statusCode = self::STATUS_PROTERROR;
                            $this->_handleError($collection);
                            exit;
                        }
                    }

                    if ($this->_decoder->getElementStartTag(Horde_ActiveSync::AIRSYNCBASE_TRUNCATIONSIZE)) {
                        $body_pref['truncationsize'] = $this->_decoder->getElementContent();
                        if (!$this->_decoder->getElementEndTag()) {
                            $this->_statusCode = self::STATUS_PROTERROR;
                            $this->_handleError($collection);
                            exit;
                        }
                    }

                    if ($this->_decoder->getElementStartTag(Horde_ActiveSync::AIRSYNCBASE_ALLORNONE)) {
                        $body_pref['allornone'] = $this->_decoder->getElementContent();
                        if (!$this->_decoder->getElementEndTag()) {
                            $this->_statusCode = self::STATUS_PROTERROR;
                            $this->_handleError($collection);
                            exit;
                        }
                    }

                    $e = $this->_decoder->peek();
                    if ($e[Horde_ActiveSync_Wbxml::EN_TYPE] == Horde_ActiveSync_Wbxml::EN_TYPE_ENDTAG) {
                        $this->_decoder->getElementEndTag();
                        if (isset($body_pref['type']) && !isset($collection['bodyprefs']['wanted'])) {
                            $collection['bodyprefs']['wanted'] = $body_pref['type'];
                        }
                        $collection['bodyprefs'][$body_pref['type']] = $body_pref;
                        break;
                    }
                }
            }

            $e = $this->_decoder->peek();
            if ($e[Horde_ActiveSync_Wbxml::EN_TYPE] == Horde_ActiveSync_Wbxml::EN_TYPE_ENDTAG) {
                $this->_decoder->getElementEndTag();
                break;
            }
        }
    }

    /**
     * Attempt to initialize the sync state.
     *
     * @param array $collection  The collection array
     */
    protected function _initState($collection)
    {
        // Initialize the state
        $this->_logger->debug(sprintf(
            "[%s] Initializing state for collection: %s, synckey: %s",
            getmypid(),
            $collection['id'],
            $collection['synckey']));
        $this->_stateDriver->loadState(
            $collection,
            $collection['synckey'],
            Horde_ActiveSync::REQUEST_TYPE_SYNC,
            $collection['id']);
    }

    protected function _handleGlobalSyncError($limit = false)
    {
        $this->_encoder->StartWBXML();
        $this->_encoder->startTag(Horde_ActiveSync::SYNC_SYNCHRONIZE);
        $this->_encoder->startTag(Horde_ActiveSync::SYNC_STATUS);
        $this->_encoder->content($this->_statusCode);
        $this->_encoder->endTag();
        if ($limit !== false) {
            $this->_encoder->startTag(Horde_ActiveSync::SYNC_LIMIT);
            $this->_encoder->content($limit);
            $this->_encoder->endTag();
        }
        $this->_encoder->endTag();
    }

    /**
     * Helper for handling sync errors
     *
     * @param <type> $collection
     */
    protected function _handleError($collection)
    {
        $this->_encoder->startWBXML();
        $this->_encoder->startTag(Horde_ActiveSync::SYNC_SYNCHRONIZE);

        $this->_encoder->startTag(Horde_ActiveSync::SYNC_FOLDERS);

        // Get new synckey if needed
        if ($this->_statusCode == self::STATUS_KEYMISM ||
            isset($collection['importedchanges']) ||
            isset($collection['getchanges']) ||
            $collection['synckey'] == '0') {

            $collection['newsynckey'] = Horde_ActiveSync_State_Base::getNewSyncKey(($this->_statusCode == self::STATUS_KEYMISM) ? 0 : $collection['synckey']);
            if ($collection['synckey'] != 0) {
                $this->_stateDriver->removeState(array('synckey' => $collection['synckey']));
            }
        }

        $this->_encoder->startTag(Horde_ActiveSync::SYNC_FOLDER);

        $this->_encoder->startTag(Horde_ActiveSync::SYNC_FOLDERTYPE);
        $this->_encoder->content($collection['class']);
        $this->_encoder->endTag();

        $this->_encoder->startTag(Horde_ActiveSync::SYNC_SYNCKEY);
        if (isset($collection['newsynckey'])) {
            $this->_encoder->content($collection['newsynckey']);
        } else {
            $this->_encoder->content($collection['synckey']);
        }
        $this->_encoder->endTag();

        $this->_encoder->startTag(Horde_ActiveSync::SYNC_FOLDERID);
        $this->_encoder->content($collection['id']);
        $this->_encoder->endTag();

        $this->_encoder->startTag(Horde_ActiveSync::SYNC_STATUS);
        $this->_encoder->content($this->_statusCode);
        $this->_encoder->endTag();

        $this->_encoder->endTag(); // Horde_ActiveSync::SYNC_FOLDER
        $this->_encoder->endTag();
        $this->_encoder->endTag();
    }

    /**
     * Check if we have at least one syncable collection for a hanging SYNC.
     *
     * @return boolean
     */
    protected function _haveSyncableCollections()
    {
        // Ensure we have syncable collections, using the cache if needed.
        if ($this->_version == Horde_ActiveSync::VERSION_TWELVEONE &&
            empty($this->_collections)) {
            $this->_logger->debug('No collections - looking in sync_cache.');
            $found = false;
            foreach ($this->_syncCache->getCollections() as $value) {
                if (isset($value['synckey'])) {
                    $this->_logger->debug('Found a syncable collection: ' . $value['type'] . ' : ' . $value['synckey']);
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $this->_syncCache->lastuntil = time();
                $this->_syncCache->save();
            }
            return $found;
        } elseif (empty($this->_collections)) {
            return false;
        }
        $this->_logger->debug('Have syncable collections');

        return true;
    }

}
