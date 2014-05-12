<?php
/**
 * Horde_ActiveSync_Request_FolderSync::
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
 * @copyright 2009-2014 Horde LLC (http://www.horde.org)
 * @author    Michael J Rubinsky <mrubinsk@horde.org>
 * @package   ActiveSync
 */
/**
 * Handle FolderSync requests.
 *
 * @license   http://www.horde.org/licenses/gpl GPLv2
 *            NOTE: According to sec. 8 of the GENERAL PUBLIC LICENSE (GPL),
 *            Version 2, the distribution of the Horde_ActiveSync module in or
 *            to the United States of America is excluded from the scope of this
 *            license.
 * @copyright 2009-2014 Horde LLC (http://www.horde.org)
 * @author    Michael J Rubinsky <mrubinsk@horde.org>
 * @package   ActiveSync
 * @internal
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
        $this->_logger->info(sprintf(
            '[%s] Handling FOLDERSYNC command.',
            $this->_procid));

        // Check policy
        if (!$this->checkPolicyKey($this->_activeSync->getPolicyKey(), Horde_ActiveSync::FOLDERHIERARCHY_FOLDERSYNC)) {
            return true;
        }

        // Check global errors from pairing.
        if ($error = $this->_activeSync->checkGlobalError()) {
            $this->_statusCode = $error;
            $this->_handleError();
            return true;
        }

        // Start parsing input
        if (!$this->_decoder->getElementStartTag(Horde_ActiveSync::FOLDERHIERARCHY_FOLDERSYNC)) {
            $this->_logger->err('[Horde_ActiveSync::handleFolderSync] No input to parse');
            $this->_statusCode = self::STATUS_PROTOERR;
            $this->_handleError();
            return true;
        }

        // Get the current synckey from PIM
        if (!$this->_decoder->getElementStartTag(Horde_ActiveSync::FOLDERHIERARCHY_SYNCKEY)) {
            $this->_logger->err('[Horde_ActiveSync::handleFolderSync] No input to parse');
            $this->_statusCode = self::STATUS_PROTOERR;
            $this->_handleError();
            return true;
        }
        $synckey = $this->_decoder->getElementContent();
        if (!$this->_decoder->getElementEndTag()) {
            $this->_logger->err('[Horde_ActiveSync::handleFolderSync] No input to parse');
            $this->_statusCode = self::STATUS_PROTOERR;
            $this->_handleError();
            return true;
        }

        // Prepare the collections handler.
        $collections = $this->_activeSync->getCollectionsObject();
        try {
            $seenfolders = $collections->initHierarchySync($synckey);
        } catch (Horde_ActiveSync_Exception $e) {
            $this->_statusCode = self::STATUS_KEYMISM;
            $this->_handleError($e);
            return true;
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
                    return true;
                }
            }

            // Process the incoming changes to folders
            $element = $this->_decoder->getElement();
            if ($element[Horde_ActiveSync_Wbxml::EN_TYPE] != Horde_ActiveSync_Wbxml::EN_TYPE_STARTTAG) {
                $this->_statusCode = self::STATUS_PROTOERR;
                $this->_handleError();
                return true;
            }

            // Configure importer with last state
            // @todo - integrate this with the collection manager.
            $importer = $this->_activeSync->getImporter();
            $importer->init($this->_state, false);

            while (1) {
                $folder = Horde_ActiveSync::messageFactory('Folder');
                if (!$folder->decodeStream($this->_decoder)) {
                    break;
                }

                switch ($element[Horde_ActiveSync_Wbxml::EN_TAG]) {
                case SYNC_ADD:
                case SYNC_MODIFY:
                    $new_server = $importer->importFolderChange(
                        $folder->serverid, $folder->displayname);
                    $serverid = $new_server->serverid;
                    if (!in_array($serverid, $seenfolders)) {
                        $seenfolders[] = $serverid;
                        $collections->updateFolderInHierarchy($folder);
                    } else {
                        $collections->updateFolderInHierarchy($folder, true);
                    }
                    $changes = true;
                    break;
                case SYNC_REMOVE:
                    $importer->importFolderDeletion($folder->serverid);
                    if (($sid = array_search($folder->serverid, $seenfolders)) !== false) {
                        unset($seenfolders[$sid]);
                        $seenfolders = array_values($seenfolders);
                    }
                    $collections->deleteFolderFromHierarchy($folder->serverid);
                    $changes = true;
                    break;
                }
            }

            if (!$this->_decoder->getElementEndTag()) {
                $this->_statusCode = self::STATUS_PROTOERR;
                $this->_handleError();
                return true;
            }
        }

        if (!$this->_decoder->getElementEndTag()) {
            $this->_statusCode = self::STATUS_PROTOERR;
            $this->_handleError();
            return true;
        }

        // Start sending server -> PIM changes
        $newsynckey = $this->_state->getNewSyncKey($synckey);
        $exporter = new Horde_ActiveSync_Connector_Exporter($this->_activeSync);
        $exporter->setChanges($collections->getHierarchyChanges(), false);

        // Perform the actual sync operation
        while($exporter->sendNextChange());

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

        // Validate/clean up the hierarchy changes.
        $collections->validateHierarchyChanges($exporter, $seenfolders);
        $collections->updateHierarchyKey($changes || $exporter->count > 0 ? $newsynckey : $synckey);

        $this->_encoder->startTag(Horde_ActiveSync::FOLDERHIERARCHY_COUNT);
        $this->_encoder->content($exporter->count);
        $this->_encoder->endTag();

        if (count($exporter->changed) > 0) {
            foreach ($exporter->changed as $key => $folder) {
                if (isset($folder->serverid) && in_array($folder->serverid, $seenfolders)) {
                    $this->_encoder->startTag(self::UPDATE);
                } else {
                    $seenfolders[] = $folder->serverid;
                    $this->_encoder->startTag(self::ADD);
                }
                $folder->encodeStream($this->_encoder);
                $this->_encoder->endTag();
                $collections->updateFolderInHierarchy($folder);
            }
        }

        if (count($exporter->deleted) > 0) {
            foreach ($exporter->deleted as $folder_uid) {
                $this->_encoder->startTag(self::REMOVE);
                $this->_encoder->startTag(Horde_ActiveSync::FOLDERHIERARCHY_SERVERENTRYID);
                $this->_encoder->content($folder_uid);
                $this->_encoder->endTag();
                $this->_encoder->endTag();
                $collections->deleteFolderFromHierarchy($folder_uid);
            }
        }

        $this->_encoder->endTag();
        $this->_encoder->endTag();

        // Save state, clean
        if ($exporter->count) {
            $this->_state->setNewSyncKey($newsynckey);
            $this->_state->save();
        }
        $this->_cleanUpAfterPairing();

        $collections->save();

        return true;
    }

    /**
     * Helper function for sending error responses
     *
     * @param Exception $e  The exception.
     */
    private function _handleError($e = null)
    {
        if (!is_null($e)) {
            $this->_logger->err($e->getMessage());
        }

        $this->_encoder->startWBXML();
        $this->_encoder->startTag(Horde_ActiveSync::FOLDERHIERARCHY_FOLDERSYNC);
        $this->_encoder->startTag(Horde_ActiveSync::FOLDERHIERARCHY_STATUS);
        $this->_encoder->content($this->_statusCode);
        $this->_encoder->endTag();
        $this->_encoder->endTag();
    }

}