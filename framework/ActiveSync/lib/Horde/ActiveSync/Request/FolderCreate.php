<?php
/**
 * Handle FolderCreate requests.
 * 
 * Logic adapted from Z-Push, original copyright notices below.
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
class Horde_ActiveSync_Request_FolderCreate extends Horde_ActiveSync_Request_Base
{
    /**
     * Handle request
     *
     * @return boolean
     */
    public function handle()
    {
        $el = $this->_decoder->getElement();
        if ($el[Horde_ActiveSync_Wbxml::EN_TYPE] != Horde_ActiveSync_Wbxml::EN_TYPE_STARTTAG) {
            return false;
        }

        $create = $update = $delete = false;

        if ($el[Horde_ActiveSync_Wbxml::EN_TAG] == self::FOLDERHIERARCHY_FOLDERCREATE) {
            $create = true;
        } elseif ($el[Horde_ActiveSync_Wbxml::EN_TAG] == self::FOLDERHIERARCHY_FOLDERUPDATE) {
            $update = true;
        } elseif ($el[Horde_ActiveSync_Wbxml::EN_TAG] == self::FOLDERHIERARCHY_FOLDERDELETE) {
            $delete = true;
        }

        if (!$create && !$update && !$delete) {
            return false;
        }

        // SyncKey
        if (!$this->_decoder->getElementStartTag(self::FOLDERHIERARCHY_SYNCKEY)) {
            return false;
        }
        $synckey = $this->_decoder->getElementContent();
        if (!$this->_decoder->getElementEndTag()) {
            return false;
        }

        // ServerID
        $serverid = false;
        if ($this->_decoder->getElementStartTag(self::FOLDERHIERARCHY_SERVERENTRYID)) {
            $serverid = $this->_decoder->getElementContent();
            if (!$this->_decoder->getElementEndTag()) {
                return false;
            }
        }

        // when creating or updating more information is necessary
        if (!$delete) {
            // Parent
            $parentid = false;
            if ($this->_decoder->getElementStartTag(self::FOLDERHIERARCHY_PARENTID)) {
                $parentid = $this->_decoder->getElementContent();
                if (!$this->_decoder->getElementEndTag()) {
                    return false;
                }
            }

            // Displayname
            if (!$this->_decoder->getElementStartTag(self::FOLDERHIERARCHY_DISPLAYNAME)) {
                return false;
            }
            $displayname = $this->_decoder->getElementContent();
            if (!$this->_decoder->getElementEndTag()) {
                return false;
            }

            // Type
            $type = false;
            if ($this->_decoder->getElementStartTag(self::FOLDERHIERARCHY_TYPE)) {
                $type = $this->_decoder->getElementContent();
                if (!$this->_decoder->getElementEndTag()) {
                    return false;
                }
            }
        }

        if (!$this->_decoder->getElementEndTag()) {
            return false;
        }

        // Get state of hierarchy
        try {
            $syncstate = $this->_stateMachine->loadState($synckey);
            $newsynckey = $this->_stateMachine->getNewSyncKey($synckey);
        } catch (Horde_ActiveSync_Exception $e) {
            // @TODO - send error status keymism when refactored.
        }

        // additional information about already seen folders
        $seenfolders = unserialize($this->_stateMachine->loadState('s' . $synckey));
        if (!$seenfolders) {
            $seenfolders = array();
        }
        // Configure importer with last state
        $importer = $this->_driver->getHierarchyImporter();
        $importer->Config($syncstate);

        if (!$delete) {
            // Send change
            $serverid = $importer->importFolderChange($serverid, $parentid, $displayname, $type);
        } else {
            // delete folder
            $deletedstat = $importer->importFolderDeletion($serverid, 0);
        }

        $this->_encoder->startWBXML();
        if ($create) {
            // add folder id to the seen folders
            $seenfolders[] = $serverid;

            $this->_encoder->startTag(self::FOLDERHIERARCHY_FOLDERCREATE);


            $this->_encoder->startTag(self::FOLDERHIERARCHY_STATUS);
            $this->_encoder->content(1);
            $this->_encoder->endTag();

            $this->_encoder->startTag(self::FOLDERHIERARCHY_SYNCKEY);
            $this->_encoder->content($newsynckey);
            $this->_encoder->endTag();

            $this->_encoder->startTag(self::FOLDERHIERARCHY_SERVERENTRYID);
            $this->_encoder->content($serverid);
            $this->_encoder->endTag();

            $this->_encoder->endTag();

            $this->_encoder->endTag();
        } elseif ($update) {

            $this->_encoder->startTag(self::FOLDERHIERARCHY_FOLDERUPDATE);

            $this->_encoder->startTag(self::FOLDERHIERARCHY_STATUS);
            $this->_encoder->content(1);
            $this->_encoder->endTag();

            $this->_encoder->startTag(self::FOLDERHIERARCHY_SYNCKEY);
            $this->_encoder->content($newsynckey);
            $this->_encoder->endTag();

            $this->_encoder->endTag();
        } elseif ($delete) {
            $this->_encoder->startTag(self::FOLDERHIERARCHY_FOLDERDELETE);

            $this->_encoder->startTag(self::FOLDERHIERARCHY_STATUS);
            $this->_encoder->content($deletedstat);
            $this->_encoder->endTag();

            $this->_encoder->startTag(self::FOLDERHIERARCHY_SYNCKEY);
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
        $this->_stateMachine->setState($newsynckey, $importer->GetState());
        $this->_stateMachine->setState('s' . $newsynckey, serialize($seenfolders));
        $this->_stateMachine->save();

        return true;
    }
}