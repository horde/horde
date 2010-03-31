<?php
/**
 * ActiveSync Handler for PING requests
 *
 * Copyright 2009 - 2010 The Horde Project (http://www.horde.org)
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Horde_ActiveSync
 */
/**
 * Zarafa Deutschland GmbH, www.zarafaserver.de
 * This file is distributed under GPL v2.
 * Consult LICENSE file for details
 */
class Horde_ActiveSync_Request_Ping extends Horde_ActiveSync_Request_Base
{
    const STATUS_NOCHANGES = 1;
    const STATUS_NEEDSYNC = 2;
    const STATUS_MISSING = 3;
    const STATUS_PROTERROR = 4;
    // Hearbeat out of bounds (TODO)
    const STATUS_HBOUTOFBOUNDS = 5;

    // Requested more then the max folders (TODO)
    const STATUS_MAXFOLDERS = 6;

    // Folder sync is required, hierarchy out of date.
    const STATUS_FOLDERSYNCREQD = 7;
    const STATUS_SERVERERROR = 8;

    /**
     * Handle a PING command from the PIM. Ping is sent periodically by the PIM
     * to tell the server what folders we are interested in monitoring for
     * changes. If no changes are detected by the server during the 'heartbeat'
     * interval, the server sends back a status of 1 to indicate heartbeat
     * expired and the client should re-issue the PING command. If a change
     * has been found, the client is sent a 2 status and should then issue a
     * SYNC command.
     *
     * @return boolean
     */
    public function handle(Horde_ActiveSync $activeSync, $devId)
    {
        parent::handle($activeSync, $devId);

        // FIXME
        $timeout = 3;
        $this->_logger->info('Ping received for: ' . $this->_devId);

        /* Glass half full kinda guy... */
        $this->_statusCode = self::STATUS_NOCHANGES;

        /* Initialize the state machine */
        $state = &$this->_driver->getStateObject();

        /* See if we have an existing PING state. Need to do this here, before
         * we read in the PING request since the PING request is allowed to omit
         * sections if they have been sent previously */
        $collections = array_values($state->initPingState($this->_devId));
        $lifetime = $state->getPingLifetime();

        /* Build the $collections array if we receive request from PIM */
        if ($this->_decoder->getElementStartTag(SYNC_PING_PING)) {
            $this->_logger->debug('Ping init');
            if ($this->_decoder->getElementStartTag(SYNC_PING_LIFETIME)) {
                $lifetime = $this->_decoder->getElementContent();
                $state->setPingLifetime($lifetime);
                $this->_decoder->getElementEndTag();
            }

            if ($this->_decoder->getElementStartTag(SYNC_PING_FOLDERS)) {
                $collections = array();
                while ($this->_decoder->getElementStartTag(SYNC_PING_FOLDER)) {
                    $collection = array();
                    if ($this->_decoder->getElementStartTag(SYNC_PING_SERVERENTRYID)) {
                        $collection['id'] = $this->_decoder->getElementContent();
                        $this->_decoder->getElementEndTag();
                    }
                    if ($this->_decoder->getElementStartTag(SYNC_PING_FOLDERTYPE)) {
                        $collection['class'] = $this->_decoder->getElementContent();
                        $this->_decoder->getElementEndTag();
                    }

                    $this->_decoder->getElementEndTag();
                    array_push($collections, $collection);
                }

                if (!$this->_decoder->getElementEndTag()) {
                    $this->_statusCode = self::STATUS_PROTERROR;
                    return false;
                }
            }

            if (!$this->_decoder->getElementEndTag()) {
                $this->_statusCode = self::STATUS_PROTERROR;
                return false;
            }
        }

        $changes = array();
        $dataavailable = false;

        /* Start waiting for changes, but only if we don't have any errors */
        if ($this->_statusCode == self::STATUS_NOCHANGES) {
            $this->_logger->info(sprintf('Waiting for changes... (lifetime %d)', $lifetime));
            // FIXME
            //for ($n = 0; $n < $lifetime / $timeout; $n++) {
            for ($n = 0; $n < 10; $n++) {
                //check the remote wipe status
                if ($this->_provisioning === true) {
                    $rwstatus = $this->_driver->getDeviceRWStatus($this->_devId);
                    if ($rwstatus == SYNC_PROVISION_RWSTATUS_PENDING || $rwstatus == SYNC_PROVISION_RWSTATUS_WIPED) {
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
                    // Make sure we have the synckey (which is the devid for
                    // PING requests.
                    $collection['synckey'] = $this->_devId;
                    $exporter = $this->_driver->getSyncObject();
                    $state->loadPingCollectionState($collection);
                    try {
                        $exporter->init($state, false, $collection);
                    } catch (Horde_ActiveSync_Exception $e) {
                        /* Stop ping if exporter cannot be configured */
                        $this->_logger->err('Ping error: Exporter can not be configured. Waiting 30 seconds before ping is retried.');
                        $n = $lifetime/ $timeout;
                        sleep(30);
                        break;
                    }

                    $changecount = $exporter->GetChangeCount();
                    if ($changecount > 0) {
                        $dataavailable = true;
                        $changes[$collection['id']] = $changecount;
                        $this->_statusCode = self::STATUS_NEEDSYNC;
                    }

                    // Update the state, but don't bother with the backend since we
                    // are not updating any data.
                    while (is_array($exporter->syncronize(BACKEND_DISCARD_DATA)));
                }

                if ($dataavailable) {
                    $this->_logger->info('Found changes');
                    break;
                }
                sleep($timeout);
            }
        }
        $this->_logger->debug('Starting StartWBXML output');
        $this->_encoder->StartWBXML();

        $this->_encoder->startTag(SYNC_PING_PING);

        $this->_encoder->startTag(SYNC_PING_STATUS);
        $this->_encoder->content($this->_statusCode);
        $this->_encoder->endTag();

        $this->_encoder->startTag(SYNC_PING_FOLDERS);
        foreach ($collections as $collection) {
            if (isset($changes[$collection['id']])) {
                $this->_encoder->startTag(SYNC_PING_FOLDER);
                $this->_encoder->content($collection['id']);
                $this->_encoder->endTag();
            }
        }
        $this->_encoder->endTag();

        $this->_encoder->endTag();

        $state->savePingState();

        return true;
    }
}