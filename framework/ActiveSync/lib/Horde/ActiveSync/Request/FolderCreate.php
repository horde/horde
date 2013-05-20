<?php
/**
 * Horde_ActiveSync_Request_FolderCreate::
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
 *            Version 2, the distribution   of the Horde_ActiveSync module in or
 *            to the United States of America is excluded from the scope of this
 *            license.
 * @copyright 2009-2013 Horde LLC (http://www.horde.org)
 * @author    Michael J Rubinsky <mrubinsk@horde.org>
 * @package   ActiveSync
 */
/**
 * Handle FolderCreate requests.
 *
 * @license   http://www.horde.org/licenses/gpl GPLv2
 *            NOTE: According to sec. 8 of the GENERAL PUBLIC LICENSE (GPL),
 *            Version 2, the distribution of the Horde_ActiveSync module in or
 *            to the United States of America is excluded from the scope of this
 *            license.
 * @copyright 2009-2013 Horde LLC (http://www.horde.org)
 * @author    Michael J Rubinsky <mrubinsk@horde.org>
 * @package   ActiveSync
 */
class Horde_ActiveSync_Request_FolderCreate extends Horde_ActiveSync_Request_Base
{
    const FOLDERCREATE  = 'FolderHierarchy:FolderCreate';
    const FOLDERDELETE  = 'FolderHierarchy:FolderDelete';
    const FOLDERUPDATE  = 'FolderHierarchy:FolderUpdate';

    const STATUS_SUCCESS = 1;
    const STATUS_ERROR   = 6;
    const STATUS_KEYMISM = 9;
    /**
     * Handle request
     *
     * @return boolean
     */
    protected function _handle()
    {
        $status = self::STATUS_SUCCESS;
        $create = $update = $delete = false;
        $this->_logger->info(sprintf(
            '[%s] Handling FOLDER[CREATE|DELETE|CHANGE] command.',
            $this->_device->id)
        );

        $el = $this->_decoder->getElement();
        if ($el[Horde_ActiveSync_Wbxml::EN_TYPE] != Horde_ActiveSync_Wbxml::EN_TYPE_STARTTAG) {
            throw new Horde_ActiveSync_Exception('Protocol Error');
        }
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

        if (!$delete) {
            $parentid = false;
            if ($this->_decoder->getElementStartTag(Horde_ActiveSync::FOLDERHIERARCHY_PARENTID)) {
                $parentid = $this->_decoder->getElementContent();
                if (!$this->_decoder->getElementEndTag()) {
                    throw new Horde_ActiveSync_Exception('Protocol Error');
                }
            }
            if (!$this->_decoder->getElementStartTag(Horde_ActiveSync::FOLDERHIERARCHY_DISPLAYNAME)) {
                throw new Horde_ActiveSync_Exception('Protocol Error');
            }
            $displayname = $this->_decoder->getElementContent();
            if (!$this->_decoder->getElementEndTag()) {
                throw new Horde_ActiveSync_Exception('Protocol Error');
            }
            $type = false;
            if ($this->_decoder->getElementStartTag(Horde_ActiveSync::FOLDERHIERARCHY_TYPE)) {
                $type = $this->_decoder->getElementContent();
                if (!$this->_decoder->getElementEndTag()) {
                    throw new Horde_ActiveSync_Exception('Protocol Error');
                }
            }
        }

        if (!$this->_decoder->getElementEndTag()) {
            throw new Horde_ActiveSync_Exception('Protocol Error');
        }

        try {
            $this->_state->loadState(
                array(), $synckey, Horde_ActiveSync::REQUEST_TYPE_FOLDERSYNC);
            $newsynckey = $this->_state->getNewSyncKey($synckey);
        } catch (Horde_ActiveSync_Exception $e) {
            $status = self::STATUS_KEYMISM;
        }

        if ($status == self::STATUS_SUCCESS) {
            $seenfolders = $this->_state->getKnownFolders();
            if (!$seenfolders) {
                $seenfolders = array();
            }
            $this->_logger->info(sprintf(
                '[%s] KNOWNFOLDERS: %s',
                $this->_device->device_id,
                print_r($seenfolders, true))
            );

            // Configure importer with last state
            $importer = $this->_activeSync->getImporter();
            $importer->init($this->_state);
            if (!$delete) {
                if (!$serverid = $importer->importFolderChange($serverid, $displayname, $parentid)) {
                    $status = self::STATUS_ERROR;
                }
            } else {
               try {
                   $importer->importFolderDeletion($serverid);
               } catch (Horde_ActiveSync_Exception $e) {
                    $status = self::STATUS_ERROR;
                }
            }
        }

        $this->_encoder->startWBXML();
        if ($create) {
            $seenfolders[] = $serverid;
            $this->_encoder->startTag(self::FOLDERCREATE);

            $this->_encoder->startTag(Horde_ActiveSync::FOLDERHIERARCHY_STATUS);
            $this->_encoder->content($status);
            $this->_encoder->endTag();

            $this->_encoder->startTag(Horde_ActiveSync::FOLDERHIERARCHY_SYNCKEY);
            $this->_encoder->content($newsynckey);
            $this->_encoder->endTag();

            $this->_encoder->startTag(Horde_ActiveSync::FOLDERHIERARCHY_SERVERENTRYID);
            $this->_encoder->content($serverid);
            $this->_encoder->endTag();

            $this->_encoder->endTag();
        } elseif ($update) {
            $this->_encoder->startTag(self::FOLDERUPDATE);

            $this->_encoder->startTag(Horde_ActiveSync::FOLDERHIERARCHY_STATUS);
            $this->_encoder->content($status);
            $this->_encoder->endTag();

            $this->_encoder->startTag(Horde_ActiveSync::FOLDERHIERARCHY_SYNCKEY);
            $this->_encoder->content($newsynckey);
            $this->_encoder->endTag();

            $this->_encoder->endTag();
        } elseif ($delete) {
            $this->_encoder->startTag(self::FOLDERDELETE);

            $this->_encoder->startTag(Horde_ActiveSync::FOLDERHIERARCHY_STATUS);
            $this->_encoder->content($status);
            $this->_encoder->endTag();

            $this->_encoder->startTag(Horde_ActiveSync::FOLDERHIERARCHY_SYNCKEY);
            $this->_encoder->content($newsynckey);
            $this->_encoder->endTag();

            $this->_encoder->endTag();
        }

        $this->_encoder->endTag();
        $this->_state->setNewSyncKey($newsynckey);
        $this->_state->save();

        return true;
    }
}