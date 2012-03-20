<?php
/**
 * Handle FolderCreate requests.
 *
 * Logic adapted from Z-Push, original copyright notices below.
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
class Horde_ActiveSync_Request_FolderCreate extends Horde_ActiveSync_Request_Base
{
    const FOLDERCREATE  = 'FolderHierarchy:FolderCreate';
    const FOLDERDELETE  = 'FolderHierarchy:FolderDelete';
    const FOLDERUPDATE  = 'FolderHierarchy:FolderUpdate';

    /**
     * Handle request
     *
     * @return boolean
     */
    protected function _handle()
    {
        $this->_logger->info(sprintf(
            "[%s] Handling FOLDERCREATE command.",
            $this->_device->id)
        );

        $el = $this->_decoder->getElement();
        if ($el[Horde_ActiveSync_Wbxml::EN_TYPE] != Horde_ActiveSync_Wbxml::EN_TYPE_STARTTAG) {
            throw new Horde_ActiveSync_Exception('Protocol Error');
        }

        $create = $update = $delete = false;
        if ($el[Horde_ActiveSync_Wbxml::EN_TAG] == self::FOLDERCREATE) {
            $create = true;
        } elseif ($el[Horde_ActiveSync_Wbxml::EN_TAG] == self::FOLDERUPDATE) {
            $update = true;
        } elseif ($el[Horde_ActiveSync_Wbxml::EN_TAG] == self::FOLDERDELETE) {
            $delete = true;
        }

        if (!$create && !$update && !$delete) {
            $this->_logger->err('No CREATE/UPDATE/DELETE specified');
            throw new Horde_ActiveSync_Exception('Protocol Error');
        }

        // SyncKey
        if (!$this->_decoder->getElementStartTag(Horde_ActiveSync::FOLDERHIERARCHY_SYNCKEY)) {
            throw new Horde_ActiveSync_Exception('Protocol Error');
        }
        $synckey = $this->_decoder->getElementContent();
        if (!$this->_decoder->getElementEndTag()) {
            $this->_logger->err('No FOLDERSYNCKEY');
            throw new Horde_ActiveSync_Exception('Protocol Error');
        }

        // ServerID
        $serverid = false;
        if ($this->_decoder->getElementStartTag(Horde_ActiveSync::FOLDERHIERARCHY_SERVERENTRYID)) {
            $serverid = $this->_decoder->getElementContent();
            if (!$this->_decoder->getElementEndTag()) {
                throw new Horde_ActiveSync_Exception('Protocol Error');
            }
        }

        // when creating or updating more information is necessary
        if (!$delete) {
            // Parent
            $parentid = false;
            if ($this->_decoder->getElementStartTag(Horde_ActiveSync::FOLDERHIERARCHY_PARENTID)) {
                $this->_logger->debug(sprintf(
                    "[%s] PARENTID: %s",
                    $this->_device->id,
                    $parentid)
                );
                $parentid = $this->_decoder->getElementContent();
                if (!$this->_decoder->getElementEndTag()) {
                    throw new Horde_ActiveSync_Exception('Protocol Error');
                }
            }

            // Displayname
            if (!$this->_decoder->getElementStartTag(Horde_ActiveSync::FOLDERHIERARCHY_DISPLAYNAME)) {
                throw new Horde_ActiveSync_Exception('Protocol Error');
            }
            $displayname = $this->_decoder->getElementContent();
            if (!$this->_decoder->getElementEndTag()) {
                throw new Horde_ActiveSync_Exception('Protocol Error');
            }
            $this->_logger->debug(sprintf(
                "[%s] DISPLAYNAME: %s",
                $this->_device->id,
                $displayname)
            );

            // Type
            $type = false;
            if ($this->_decoder->getElementStartTag(Horde_ActiveSync::FOLDERHIERARCHY_TYPE)) {
                $type = $this->_decoder->getElementContent();
                if (!$this->_decoder->getElementEndTag()) {
                    throw new Horde_ActiveSync_Exception('Protocol Error');
                }
                $this->_logger->debug(sprintf(
                    "[%s] TYPE: %s",
                    $this->_device->id,
                    $type)
                );
            }
        }

        if (!$this->_decoder->getElementEndTag()) {
            throw new Horde_ActiveSync_Exception('Protocol Error');
        }

        // Get state of hierarchy
        try {
            $syncstate = $this->_stateDriver->loadState($synckey, Horde_ActiveSync::REQUEST_TYPE_FOLDERSYNC);
            $newsynckey = $this->_stateDriver->getNewSyncKey($synckey);
        } catch (Horde_ActiveSync_Exception $e) {
            // @TODO - send error status keymism when refactored.
        }

        // additional information about already seen folders
        $seenfolders = $this->_stateDriver->getKnownFolders();
        if (!$seenfolders) {
            $seenfolders = array();
        }
        $this->_logger->debug(sprintf(
            "[%s] KNOWNFOLDERS: %s",
            $this->_device->device_id,
            print_r($seenfolders, true))
        );

        // Configure importer with last state
        $importer = $this->_getImporter();
        $importer->init($this->_stateDriver);

        if (!$delete) {
            $serverid = $importer->importFolderChange($serverid, $parentid, $displayname, $type);
        } else {
            // delete folder
            $deletedstat = $importer->importFolderDeletion($serverid);
        }

        $this->_encoder->startWBXML();
        if ($create) {
            $seenfolders[] = $serverid;

            $this->_encoder->startTag(self::FOLDERCREATE);


            $this->_encoder->startTag(Horde_ActiveSync::FOLDERHIERARCHY_STATUS);
            $this->_encoder->content(1);
            $this->_encoder->endTag();

            $this->_encoder->startTag(Horde_ActiveSync::FOLDERHIERARCHY_SYNCKEY);
            $this->_encoder->content($newsynckey);
            $this->_encoder->endTag();

            $this->_encoder->startTag(Horde_ActiveSync::FOLDERHIERARCHY_SERVERENTRYID);
            $this->_encoder->content($serverid);
            $this->_encoder->endTag();

            $this->_encoder->endTag();

            $this->_encoder->endTag();
        } elseif ($update) {

            $this->_encoder->startTag(self::FOLDERUPDATE);

            $this->_encoder->startTag(Horde_ActiveSync::FOLDERHIERARCHY_STATUS);
            $this->_encoder->content(1);
            $this->_encoder->endTag();

            $this->_encoder->startTag(Horde_ActiveSync::FOLDERHIERARCHY_SYNCKEY);
            $this->_encoder->content($newsynckey);
            $this->_encoder->endTag();

            $this->_encoder->endTag();
        } elseif ($delete) {
            $this->_encoder->startTag(self::FOLDERDELETE);

            $this->_encoder->startTag(Horde_ActiveSync::FOLDERHIERARCHY_STATUS);
            $this->_encoder->content($deletedstat);
            $this->_encoder->endTag();

            $this->_encoder->startTag(Horde_ActiveSync::FOLDERHIERARCHY_SYNCKEY);
            $this->_encoder->content($newsynckey);
            $this->_encoder->endTag();

            $this->_encoder->endTag();

            // remove folder from the folderflags array
            if (($sid = array_search($serverid, $seenfolders)) !== false) {
                unset($seenfolders[$sid]);
                $seenfolders = array_values($seenfolders);
                $this->_logger->debug('Deleted from seenfolders: ' . $serverid);
            }
        }
        $this->_encoder->endTag();
        // Save the sync state for the next time
        $this->_stateDriver->setNewSyncKey($newsynckey);
        $this->_stateDriver->save();

        return true;
    }
}