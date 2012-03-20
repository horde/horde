<?php
/**
 * ActiveSync Handler for FOLDERSYNC requests
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
class Horde_ActiveSync_Request_FolderSync extends Horde_ActiveSync_Request_Base
{

    const ADD     = 'FolderHierarchy:Add';
    const REMOVE  = 'FolderHierarchy:Remove';
    const UPDATE  = 'FolderHierarchy:Update';

    /* SYNC Status response codes */
    const STATUS_SUCCESS     = 1;
    const STATUS_SERVERERROR = 6;
    const STATUS_TIMEOUT     = 8;
    const STATUS_KEYMISM     = 9;
    const STATUS_PROTOERR    = 10;

    /**
     * Handle the request.
     *
     * @return boolean
     */
    protected function _handle()
    {
        // Be optimistic
        $this->_statusCode = self::STATUS_SUCCESS;
        $this->_logger->info('[Horde_ActiveSync::handleFolderSync] Beginning FOLDERSYNC');

        // Check policy
        if (!$this->checkPolicyKey($this->_activeSync->getPolicyKey())) {
            return false;
        }

        // Start parsing input
        if (!$this->_decoder->getElementStartTag(Horde_ActiveSync::FOLDERHIERARCHY_FOLDERSYNC)) {
            $this->_logger->err('[Horde_ActiveSync::handleFolderSync] No input to parse');
            $this->_statusCode = self::STATUS_PROTOERR;
            $this->_handleError();
            exit;
        }

        // Get the current synckey from PIM
        if (!$this->_decoder->getElementStartTag(Horde_ActiveSync::FOLDERHIERARCHY_SYNCKEY)) {
            $this->_logger->err('[Horde_ActiveSync::handleFolderSync] No input to parse');
            $this->_statusCode = self::STATUS_PROTOERR;
            $this->_handleError();
            exit;
        }
        $synckey = $this->_decoder->getElementContent();
        if (!$this->_decoder->getElementEndTag()) {
            $this->_logger->err('[Horde_ActiveSync::handleFolderSync] No input to parse');
            $this->_statusCode = self::STATUS_PROTOERR;
            $this->_handleError();
            exit;
        }
        $this->_logger->debug('[Horde_ActiveSync::handleFolderSync] syncKey: ' . $synckey);

        try {
            $this->_stateDriver->loadState($synckey, Horde_ActiveSync::REQUEST_TYPE_FOLDERSYNC);
        } catch (Horde_ActiveSync_Exception $e) {
            $this->_statusCode = self::STATUS_KEYMISM;
            $this->_handleError();
            exit;
        }

        // Track if we have changes or not
        $changes = false;

        // Deal with folder hierarchy changes
        if ($this->_decoder->getElementStartTag(Horde_ActiveSync::FOLDERHIERARCHY_CHANGES)) {
            // Ignore <Count> if present
            if ($this->_decoder->getElementStartTag(Horde_ActiveSync::FOLDERHIERARCHY_COUNT)) {
                $this->_decoder->getElementContent();
                if (!$this->_decoder->getElementEndTag()) {
                    $this->_statusCode = self::STATUS_PROTOERR;
                    $this->_handleError();
                    exit;
                }
            }

            // Process the incoming changes to folders
            $element = $this->_decoder->getElement();
            if ($element[Horde_ActiveSync_Wbxml::EN_TYPE] != Horde_ActiveSync_Wbxml::EN_TYPE_STARTTAG) {
                $this->_statusCode = self::STATUS_PROTOERR;
                $this->_handleError();
                exit;
            }

            // Configure importer with last state
            $importer = $this->_getImporter();
            $importer->init($this->_stateDriver, false);

            while (1) {
                $folder = new Horde_ActiveSync_Message_Folder(array('logger' => $this->_logger));
                if (!$folder->decodeStream($this->_decoder)) {
                    break;
                }

                switch ($element[Horde_ActiveSync_Wbxml::EN_TAG]) {
                case SYNC_ADD:
                case SYNC_MODIFY:
                    $serverid = $importer->importFolderChange($folder);
                    $changes = true;
                    break;
                case SYNC_REMOVE:
                    $serverid = $importer->importFolderDeletion($folder);
                    $changes = true;
                    break;
                }
            }

            if (!$this->_decoder->getElementEndTag()) {
                $this->_statusCode = self::STATUS_PROTOERR;
                $this->_handleError();
                exit;
            }
        }

        if (!$this->_decoder->getElementEndTag()) {
            $this->_statusCode = self::STATUS_PROTOERR;
            $this->_handleError();
            exit;
        }

        // Start sending server -> PIM changes
        $newsynckey = $this->_stateDriver->getNewSyncKey($synckey);
        $seenfolders = $this->_stateDriver->getKnownFolders();
        $this->_logger->debug('[Horde_ActiveSync::handleFolderSync] newSyncKey: ' . $newsynckey);

        // The $exporter just caches all folder changes in-memory, so we can
        // count before sending the actual data.
        $exporter = new Horde_ActiveSync_Connector_Exporter();
        $sync = $this->_getSyncObject();
        $sync->init($this->_stateDriver, $exporter, array('synckey' => $synckey));

        // Perform the actual sync operation
        while(is_array($sync->syncronize()));

        // Output our WBXML reply now
        $this->_encoder->StartWBXML();

        $this->_encoder->startTag(Horde_ActiveSync::FOLDERHIERARCHY_FOLDERSYNC);

        $this->_encoder->startTag(Horde_ActiveSync::FOLDERHIERARCHY_STATUS);
        $this->_encoder->content($this->_statusCode);
        $this->_encoder->endTag();

        $this->_encoder->startTag(Horde_ActiveSync::FOLDERHIERARCHY_SYNCKEY);
        $this->_encoder->content((($changes || $exporter->count > 0) ? $newsynckey : $synckey));
        $this->_encoder->endTag();

        $this->_encoder->startTag(Horde_ActiveSync::FOLDERHIERARCHY_CHANGES);

        $this->_encoder->startTag(Horde_ActiveSync::FOLDERHIERARCHY_COUNT);
        $this->_encoder->content($exporter->count);
        $this->_encoder->endTag();

        if (count($exporter->changed) > 0) {
            foreach ($exporter->changed as $folder) {
                if (isset($folder->serverid) && in_array($folder->serverid, $seenfolders)) {
                    $this->_encoder->startTag(self::UPDATE);
                } else {
                    $this->_encoder->startTag(self::ADD);
                }
                $folder->encodeStream($this->_encoder);
                $this->_encoder->endTag();
            }
        }

        if (count($exporter->deleted) > 0) {
            foreach ($exporter->deleted as $folder) {
                $this->_encoder->startTag(self::REMOVE);
                $this->_encoder->startTag(Horde_ActiveSync::FOLDERHIERARCHY_SERVERENTRYID);
                $this->_encoder->content($folder);
                $this->_encoder->endTag();
                $this->_encoder->endTag();
            }
        }

        $this->_encoder->endTag();
        $this->_encoder->endTag();

        // Save the state as well as the known folder cache if we had any
        // changes.
        if ($exporter->count || $changed) {
            $this->_stateDriver->setNewSyncKey($newsynckey);
            $this->_stateDriver->save();
        }
        $this->_cleanUpAfterPairing();

        return true;
    }

    /**
     * Helper function for sending error responses
     *
     */
    private function _handleError()
    {
        $this->_encoder->startWBXML();
        $this->_encoder->startTag(Horde_ActiveSync::FOLDERHIERARCHY_FOLDERSYNC);
        $this->_encoder->startTag(Horde_ActiveSync::FOLDERHIERARCHY_STATUS);
        $this->_encoder->content($this->_statusCode);
        $this->_encoder->endTag();
        $this->_encoder->endTag();
    }

}