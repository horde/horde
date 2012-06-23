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
 * @copyright 2009-2012 Horde LLC (http://www.horde.org)
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
 * @copyright 2009-2012 Horde LLC (http://www.horde.org)
 * @author    Michael J Rubinsky <mrubinsk@horde.org>
 * @package   ActiveSync
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
        $this->_logger->debug('[Horde_ActiveSync::handleFolderSync] syncKey: ' . $synckey);

        // Load Folder Sync State
        try {
            $this->_stateDriver->loadState(
                array(), $synckey, Horde_ActiveSync::REQUEST_TYPE_FOLDERSYNC);
        } catch (Horde_ActiveSync_Exception $e) {
            $this->_statusCode = self::STATUS_KEYMISM;
            $this->_handleError();
            return true;
        }

        // Load and validate the Sync Cache if we are 12.1
        if ($this->_version == Horde_ActiveSync::VERSION_TWELVEONE) {
            $syncCache = new Horde_ActiveSync_SyncCache(
                $this->_stateDriver,
                $this->_device->id,
                $this->_device->user);
            if (count($syncCache->getFolders())) {
                if (empty($synckey)) {
                    $syncCache->clearFolders();
                } else {
                    // @TODO: Don't think we need this. I don't think the
                    // cache can be written without  the class value to begin with
                    foreach ($syncCache->getFolders() as $key => $value) {
                        if (empty($value['class'])) {
                            $syncCache->delete();
                            $this->_statusCode = self::STATUS_KEYMISM;
                            $this->_handleError();
                            return true;
                        }
                    }
                }
            }
            $this->_logger->debug(sprintf(
                '[%s] Using syncCache',
                $this->_device->id)
            );
        } else {
            $syncCache = false;
        }

        // Seen Folders
        try {
            $seenfolders = $this->_stateDriver->getKnownFolders();
        } catch (Horde_ActiveSync_Exception $e) {
            $this->_statusCode = self::STATUS_KEYMISM;
            $this->_handleError();
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
                    $serverid = $importer->importFolderChange(
                        $folder->serverid, $folder->displayname);
                    if (!in_array($serverid, $seenfolders)) {
                        $seenfolders[] = $serverid;
                    }
                    if ($syncCache !== false) {
                        $syncCache->updateFolder($folder);
                    }
                    $changes = true;
                    break;
                case SYNC_REMOVE:
                    $serverid = $importer->importFolderDeletion($folder->serverid);
                    if (($sid = array_search($serverid, $seenfolders)) !== false) {
                        unset($seenfolders[$sid]);
                        $seenfolders = array_values($seenfolders);
                    }
                    if ($syncCache !== false) {
                        $syncCache->deleteFolder($folderid);
                    }
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
        $newsynckey = $this->_stateDriver->getNewSyncKey($synckey);
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
        if ($syncCache !== false) {
            $syncCache->hierarchy = (($changes || $exporter->count > 0) ? $newsynckey : $synckey);
        }
        $this->_encoder->startTag(Horde_ActiveSync::FOLDERHIERARCHY_CHANGES);

        // Remove unnecessary updates.
        if ($syncCache !== false && count($exporter->changed) > 0) {
            foreach ($exporter->changed as $key => $folder) {
                if (isset($folder->serverid) &&
                    $syncFolder = $syncCache->getFolder($folder->serverid) &&
                    in_array($folder->serverid, $seenfolders) &&
                    $syncFolder['parentid'] == $folder->parentid &&
                    $syncFolder['displayname'] == $folder->displayname &&
                    $syncFolder['type'] == $folder->type) {

                    $this->_logger->debug(sprintf(
                        "[%s] Ignoring %s from changes because it contains no changes from device.",
                        $this->_device->id,
                        $folder->serverid)
                    );
                    unset($exporter->changed[$key]);
                    $exporter->count--;
                }
            }
        }

        // Remove unnecessary deletes.
        if ($syncCache !== false && count($exporter->deleted) > 0) {
            foreach ($exporter->deleted as $key => $folder) {
                if (($sid = array_search($folder, $seenfolders)) === false) {
                    $this->_logger->debug(sprintf(
                        "[%s] Ignoring %s from deleted list because the device does not know it",
                        $this->_device->id,
                        $folder)
                    );
                    unset($exporter->deleted[$key]);
                    $exporter->count--;
                }
            }
        }

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
                if ($syncCache !== false) {
                    $syncCache->updateFolder($folder);
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
                if ($syncCache !== false) {
                    $syncCache->deleteFolder($folder);
                }
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

        if ($syncCache !== false) {
            $syncCache->save();
        }

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