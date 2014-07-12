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
 * @copyright 2009-2014 Horde LLC (http://www.horde.org)
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
 * @copyright 2009-2014 Horde LLC (http://www.horde.org)
 * @author    Michael J Rubinsky <mrubinsk@horde.org>
 * @package   ActiveSync
 * @internal
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
     * Handle request.
     *
     * Notes: For FOLDERCREATE requests or non-email collections, the parent
     * seems to be set to the root of that collection type and the EAS type is
     * included. For new root email folders, the parent is set to ROOT.
     * For FOLDERCHANGE requests, the type is NOT included so the backend must
     * be able to determine the folder type given the folder's id only. Also,
     * the parent element does not seem to be transmitted by the clients I
     * have tested, so even though it is included when creating the new
     * folder, it is NOT included when edting that same folder. *sigh*
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

        // Server_uid (the EAS uid for this collection).
        // Another place we have to work around broken Blackberry clients that
        // send an empty SERVERENTRYID tag (Bug #13351, #12370).
        $server_uid = false;
        if ($this->_decoder->getElementStartTag(Horde_ActiveSync::FOLDERHIERARCHY_SERVERENTRYID)) {
            $server_uid = $this->_decoder->getElementContent();
            if ($server_uid !== false && !$this->_decoder->getElementEndTag()) {
                throw new Horde_ActiveSync_Exception('Protocol Error - expecting </SERVERENTRYID>');
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

        $collections = $this->_activeSync->getCollectionsObject();
        try {
            $collections->initHierarchySync($synckey);
            $newsynckey = $this->_state->getNewSyncKey($synckey);
        } catch (Horde_ActiveSync_Exception $e) {
            $status = self::STATUS_KEYMISM;
        }

        if ($status == self::STATUS_SUCCESS) {
            // Configure importer with last state
            $importer = $this->_activeSync->getImporter();
            $importer->init($this->_state);
            if (!$delete) {
                try {
                    $folder = $importer->importFolderChange($server_uid, $displayname, $parentid, $type);
                } catch (Horde_ActiveSync_Exception $e) {
                    $this->_logger->err($e->getMessage());
                    if ($e->getCode() == Horde_ActiveSync_Exception::UNSUPPORTED) {
                        $status = Horde_ActiveSync_Status::UNEXPECTED_ITEM_CLASS;
                    } else {
                        $status = self::STATUS_ERROR;
                    }
                }
            } else {
                try {
                   $importer->importFolderDeletion($server_uid);
                } catch (Horde_ActiveSync_Exception $e) {
                    $status = self::STATUS_ERROR;
                }
            }
        }

        $this->_encoder->startWBXML();
        if ($create) {
            if ($status == self::STATUS_SUCCESS) {
                $collections->updateFolderInHierarchy($folder, true);
                $collections->save();
            }

            $this->_encoder->startTag(self::FOLDERCREATE);

            $this->_encoder->startTag(Horde_ActiveSync::FOLDERHIERARCHY_STATUS);
            $this->_encoder->content($status);
            $this->_encoder->endTag();

            if ($status == self::STATUS_SUCCESS) {
                $this->_encoder->startTag(Horde_ActiveSync::FOLDERHIERARCHY_SYNCKEY);
                $this->_encoder->content($newsynckey);
                $this->_encoder->endTag();

                $this->_encoder->startTag(Horde_ActiveSync::FOLDERHIERARCHY_SERVERENTRYID);
                $this->_encoder->content($folder->serverid);
                $this->_encoder->endTag();
            }

            $this->_encoder->endTag();
        } elseif ($update) {
            if ($status == self::STATUS_SUCCESS) {
                $collections->updateFolderInHierarchy($folder, true);
                $collections->save();
            }

            $this->_encoder->startTag(self::FOLDERUPDATE);

            $this->_encoder->startTag(Horde_ActiveSync::FOLDERHIERARCHY_STATUS);
            $this->_encoder->content($status);
            $this->_encoder->endTag();

            $this->_encoder->startTag(Horde_ActiveSync::FOLDERHIERARCHY_SYNCKEY);
            $this->_encoder->content($newsynckey);
            $this->_encoder->endTag();

            $this->_encoder->endTag();
        } elseif ($delete) {
            if ($status == self::STATUS_SUCCESS) {
                $collections->deleteFolderFromHierarchy($server_uid);
            }
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

        if ($status == self::STATUS_SUCCESS) {
            $this->_state->setNewSyncKey($newsynckey);
            $this->_state->save();
        }

        return true;
    }
}