<?php
/**
 * ActiveSync Handler for FOLDERSYNC requests
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
class Horde_ActiveSync_Request_FolderSync extends Horde_ActiveSync_Request_Base
{
    /* SYNC Status response codes */
    const STATUS_SUCCESS = 1;
    const STATUS_SERVERERROR = 6;  // Should probably return to synckey 0?
    const STATUS_TIMEOUT = 8;
    const STATUS_KEYMISM = 9;
    const STATUS_PROTOERR = 10;

    /**
     * Handle the request.
     *
     * @return boolean
     */
    public function handle()
    {
        parent::handle();

        /* Be optimistic */
        $this->_statusCode = self::STATUS_SUCCESS;
        $this->_logger->info('[Horde_ActiveSync::handleFolderSync] Beginning FOLDERSYNC');

        /* Check policy */
        if (!$this->checkPolicyKey($this->_activeSync->getPolicyKey())) {
            return false;
        }

        /* Maps serverid -> clientid for items that are received from the PIM */
        $map = array();

        /* Start parsing input */
        if (!$this->_decoder->getElementStartTag(Horde_ActiveSync::FOLDERHIERARCHY_FOLDERSYNC)) {
            $this->_logger->err('[Horde_ActiveSync::handleFolderSync] No input to parse');
            $this->_statusCode = self::STATUS_PROTOERR;
            $this->_handleError();
            exit;
        }

        /* Get the current synckey from PIM */
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

        /* Initialize state engine */
        $this->_state->init(array('synckey' => $synckey));
        try {
            /* Get folders that we know about already */
            $this->_state->loadState($synckey, 'foldersync');

            /* Get new synckey to send back */
            $newsynckey = $this->_state->getNewSyncKey($synckey);
        } catch (Horde_ActiveSync_Exception $e) {
            $this->_statusCode = self::STATUS_KEYMISM;
            $this->_handleError();
            exit;
        }
        $seenfolders = $this->_state->getKnownFolders();
        $this->_logger->debug('[Horde_ActiveSync::handleFolderSync] newSyncKey: ' . $newsynckey);

        /* Track if we have changes or not */
        $changes = false;

        /* Deal with folder hierarchy changes */
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

            /* Process the incoming changes to folders */
            $element = $this->_decoder->getElement();
            if ($element[Horde_ActiveSync_Wbxml::EN_TYPE] != Horde_ActiveSync_Wbxml::EN_TYPE_STARTTAG) {
                $this->_statusCode = self::STATUS_PROTOERR;
                $this->_handleError();
                exit;
            }

            /* Configure importer with last state */
            $importer = $this->_driver->getImporter();
            $importer->init($this->_state, false);

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

        // The $exporter just caches all folder changes in-memory, so we can
        // count before sending the actual data.
        $exporter = new Horde_ActiveSync_Connector_Exporter();
        $sync = $this->_driver->GetSyncObject();
        $sync->init($this->_state, $exporter, array('synckey' => $synckey));

        /* Perform the actual sync operation */
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
                    $this->_encoder->startTag(Horde_ActiveSync::FOLDERHIERARCHY_UPDATE);
                } else {
                    $this->_encoder->startTag(Horde_ActiveSync::FOLDERHIERARCHY_ADD);
                }
                $folder->encodeStream($this->_encoder);
                $this->_encoder->endTag();
            }
        }

        if (count($exporter->deleted) > 0) {
            foreach ($exporter->deleted as $folder) {
                $this->_encoder->startTag(Horde_ActiveSync::FOLDERHIERARCHY_REMOVE);
                $this->_encoder->startTag(Horde_ActiveSync::FOLDERHIERARCHY_SERVERENTRYID);
                $this->_encoder->content($folder);
                $this->_encoder->endTag();
                $this->_encoder->endTag();
            }
        }

        $this->_encoder->endTag();
        $this->_encoder->endTag();

        /* Save the state as well as the known folder cache */
        $this->_state->setNewSyncKey($newsynckey);
        $this->_state->save();

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