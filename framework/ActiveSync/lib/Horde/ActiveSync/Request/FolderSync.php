<?php
/**
 * ActiveSync Handler for FOLDERSYNC requests
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
class Horde_ActiveSync_Request_FolderSync extends Horde_ActiveSync_Request_Base
{
    /* SYNC Status response codes */
    const STATUS_SUCCESS = 1;
    const STATUS_SERVERERROR = 6;  // Should probably return to synckey 0?
    const STATUS_TIMEOUT = 8;
    const STATUS_KEYMISM = 9;
    const STATUS_PROTOERR = 10;

    public function handle(Horde_ActiveSync $activeSync)
    {
        /* Be optimistic */
        $this->_statusCode = self::STATUS_SUCCESS;
        $this->_logger->info('[Horde_ActiveSync::handleFolderSync] Beginning FOLDERSYNC');

        /* Check policy */
        if (!$this->checkPolicyKey($activeSync->getPolicyKey())) {
            return false;
        }

        /* Maps serverid -> clientid for items that are received from the PIM */
        $map = array();

        /* Start parsing input */
        if (!$this->_decoder->getElementStartTag(SYNC_FOLDERHIERARCHY_FOLDERSYNC)) {
            $this->_logger->err('[Horde_ActiveSync::handleFolderSync] No input to parse');
            $this->_statusCode = self::STATUS_PROTOERR;
            $this->_handleError();
            exit;
        }

        /* Get the current synckey from PIM */
        if (!$this->_decoder->getElementStartTag(SYNC_FOLDERHIERARCHY_SYNCKEY)) {
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

        /* Initialize state engine */
        $state = &$this->_driver->getStateObject(array('synckey' => $synckey));
        try {
            /* Get folders that we know about already */
            $state->loadState($synckey);
        } catch (Horde_ActiveSync_Exception $e) {
            $this->_statusCode = self::STATUS_KEYMISM;
            $this->_handleError();
            exit;
        }
        $seenfolders = $state->getKnownFolders();

        /* Get new synckey to send back */
        $newsynckey = $state->getNewSyncKey($synckey);
        $this->_logger->debug('[Horde_ActiveSync::handleFolderSync] newSyncKey: ' . $newsynckey);

        /* Deal with folder hierarchy changes */
        if ($this->_decoder->getElementStartTag(SYNC_FOLDERHIERARCHY_CHANGES)) {
            // Ignore <Count> if present
            if ($this->_decoder->getElementStartTag(SYNC_FOLDERHIERARCHY_COUNT)) {
                $this->_decoder->getElementContent();
                if (!$this->_decoder->getElementEndTag()) {
                    $this->_statusCode = self::STATUS_PROTOERR;
                    $this->_handleError();
                    exit;
                }
            }

            /* Process the incoming changes to folders */
            $element = $this->_decoder->getElement();
            if ($element[Horde_ActiveSync_Wbxml::EN_TYPE] != Horde_ActiveSync_Wbxml::EN_TYPE_STARTTAG) {
                $this->_statusCode = self::STATUS_PROTOERR;
                $this->_handleError();
                exit;
            }

            /* Configure importer with last state */
            $importer = $this->_driver->getImporter();
            $importer->init($state, false, $synckey);

            while (1) {
                $folder = new Horde_ActiveSync_Message_Folder(array('logger' => $this->_logger));
                if (!$folder->decodeStream($this->_decoder)) {
                    break;
                }

                switch ($element[Horde_ActiveSync_Wbxml::EN_TAG]) {
                case SYNC_ADD:
                case SYNC_MODIFY:
                    $serverid = $importer->ImportFolderChange($folder);
                    break;
                case SYNC_REMOVE:
                    $serverid = $importer->ImportFolderDeletion($folder);
                    break;
                }

                /* Update the map */
                if ($serverid) {
                    // FIXME: Yet Another property used, but never defined
                    $map[$serverid] = $folder->clientid;
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

        /* Start sending server -> PIM changes */
        $this->_logger->debug('[Horde_ActiveSync::handleFolderSync] Preparing to send changes to PIM');

        // The $importer caches all imports in-memory, so we can send a change
        // count before sending the actual data. As the amount of data done in
        // this operation is rather low, this is not memory problem. Note that
        // this is not done when sync'ing messages - we let the exporter write
        // directly to WBXML.
        // TODO: Combine all these import caches into a single Class
        $importer = new Horde_ActiveSync_HierarchyCache();
        $exporter = $this->_driver->GetExporter();
        $exporter->init($state, $importer, array('synckey' => $synckey));

        /* Perform the actual sync operation */
        while(is_array($exporter->syncronize()));

        // Output our WBXML reply now
        $this->_encoder->StartWBXML();

        $this->_encoder->startTag(SYNC_FOLDERHIERARCHY_FOLDERSYNC);

        $this->_encoder->startTag(SYNC_FOLDERHIERARCHY_STATUS);
        $this->_encoder->content($this->_statusCode);
        $this->_encoder->endTag();

        $this->_encoder->startTag(SYNC_FOLDERHIERARCHY_SYNCKEY);
        $this->_encoder->content($newsynckey);
        $this->_encoder->endTag();

        $this->_encoder->startTag(SYNC_FOLDERHIERARCHY_CHANGES);

        $this->_encoder->startTag(SYNC_FOLDERHIERARCHY_COUNT);
        $this->_encoder->content($importer->count);
        $this->_encoder->endTag();

        if (count($importer->changed) > 0) {
            foreach ($importer->changed as $folder) {
                if (isset($folder->serverid) && in_array($folder->serverid, $seenfolders)) {
                    $this->_encoder->startTag(SYNC_FOLDERHIERARCHY_UPDATE);
                } else {
                    $this->_encoder->startTag(SYNC_FOLDERHIERARCHY_ADD);
                }
                $folder->encodeStream($this->_encoder);
                $this->_encoder->endTag();
            }
        }

        if (count($importer->deleted) > 0) {
            foreach ($importer->deleted as $folder) {
                $this->_encoder->startTag(SYNC_FOLDERHIERARCHY_REMOVE);
                $this->_encoder->startTag(SYNC_FOLDERHIERARCHY_SERVERENTRYID);
                $this->_encoder->content($folder);
                $this->_encoder->endTag();
                $this->_encoder->endTag();
            }
        }

        $this->_encoder->endTag();
        $this->_encoder->endTag();

        /* Save the state as well as the known folder cache */
        $state->setNewSyncKey($newsynckey);
        $state->save();

        return true;
    }

    /**
     * Helper function for sending error responses
     *
     */
    private function _handleError()
    {
        $this->_encoder->StartWBXML();
        $this->_encoder->startTag(SYNC_FOLDERHIERARCHY_FOLDERSYNC);
        $this->_encoder->startTag(SYNC_FOLDERHIERARCHY_STATUS);
        $this->_encoder->content($this->_statusCode);
        $this->_encoder->endTag();
        $this->_encoder->endTag();
    }

}