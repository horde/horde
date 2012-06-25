<?php
/**
 * Horde_ActiveSync_Request_GetItemEstimate::
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
 * Handle GetItemEstimate requests.
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
class Horde_ActiveSync_Request_GetItemEstimate extends Horde_ActiveSync_Request_Base
{
    /** Status Codes **/
    const STATUS_SUCCESS    = 1;
    const STATUS_INVALIDCOL = 2;
    const STATUS_NOTPRIMED  = 3;
    const STATUS_KEYMISM    = 4;

    /* Request tag constants */
    const GETITEMESTIMATE = 'GetItemEstimate:GetItemEstimate';
    const VERSION         = 'GetItemEstimate:Version';
    const FOLDERS         = 'GetItemEstimate:Folders';
    const FOLDER          = 'GetItemEstimate:Folder';
    const FOLDERTYPE      = 'GetItemEstimate:FolderType';
    const FOLDERID        = 'GetItemEstimate:FolderId';
    const DATETIME        = 'GetItemEstimate:DateTime';
    const ESTIMATE        = 'GetItemEstimate:Estimate';
    const RESPONSE        = 'GetItemEstimate:Response';
    const STATUS          = 'GetItemEstimate:Status';

    /**
     * Handle the request
     *
     * @return boolean
     * @throws Horde_ActiveSync_Exception
     */
    protected function _handle()
    {
        $this->_logger->info(sprintf(
            "[%s] Beginning GETITEMESTIMATE",
            $this->_device->id)
        );
        if (!$this->checkPolicyKey($this->_activeSync->getPolicyKey())) {
            return true;
        }

        $status = array();
        $collections = array();
        if (!$this->_decoder->getElementStartTag(self::GETITEMESTIMATE) ||
            !$this->_decoder->getElementStartTag(self::FOLDERS)) {
            return false;
        }

        while ($this->_decoder->getElementStartTag(self::FOLDER)) {
            $options = array();
            $cStatus = self::STATUS_SUCCESS;
            while (($type = ($this->_decoder->getElementStartTag(self::FOLDERTYPE) ? self::FOLDERTYPE :
                            ($this->_decoder->getElementStartTag(self::FOLDERID) ? self::FOLDERID :
                            ($this->_decoder->getElementStartTag(Horde_ActiveSync::SYNC_FILTERTYPE) ? Horde_ActiveSync::SYNC_FILTERTYPE :
                            ($this->_decoder->getElementStartTag(Horde_ActiveSync::SYNC_SYNCKEY) ? Horde_ActiveSync::SYNC_SYNCKEY :
                            ($this->_decoder->getElementStartTag(Horde_ActiveSync::SYNC_CONVERSATIONMODE) ? Horde_ActiveSync::SYNC_CONVERSATIONMODE :
                            ($this->_decoder->getElementStartTag(Horde_ActiveSync::SYNC_OPTIONS) ? Horde_ActiveSync::SYNC_OPTIONS :
                            -1))))))) != -1) {
                switch ($type) {
                case self::FOLDERTYPE:
                    $class = $this->_decoder->getElementContent();
                    if (!$this->_decoder->getElementEndTag()) {
                        return false;
                    }
                    break;
                case self::FOLDERID:
                    $collectionid = $this->_decoder->getElementContent();
                    if (!$this->_decoder->getElementEndTag()) {
                        return false;
                    }
                    break;
                case Horde_ActiveSync::SYNC_FILTERTYPE:
                    $filtertype = $this->_decoder->getElementContent();
                    if (!$this->_decoder->getElementEndTag()) {
                        return false;
                    }
                    break;
                case Horde_ActiveSync::SYNC_SYNCKEY:
                    $synckey = $this->_decoder->getElementContent();
                    if (empty($synckey)) {
                        $cStatus = self::STATUS_NOTPRIMED;
                    }
                    if (!$this->_decoder->getElementEndTag()) {
                        return false;
                    }
                }
            }
            // End the FOLDER element
            if (!$this->_decoder->getElementEndTag()) {
                return false;
            }

            // Build the collection array
            $collection = array();
            $collection['synckey'] = $synckey;
            $collection['class'] = $class;
            $collection['filtertype'] = $filtertype;
            $collection['id'] = $collectionid;
            $status[$collection['id']] = $cStatus;
            array_push($collections, $collection);
        }

        // End Folders
        $this->_decoder->getElementEndTag();

        // End GETITEMESTIMATE
        $this->_decoder->getElementEndTag();

        $this->_encoder->startWBXML();
        $this->_encoder->startTag(self::GETITEMESTIMATE);
        foreach ($collections as $collection) {
            try {
                $this->_stateDriver->loadState(
                    $collection,
                    $collection['synckey'],
                    Horde_ActiveSync::REQUEST_TYPE_SYNC,
                    $collection['id']);
            } catch (Horde_ActiveSync_Exception $e) {
                $status[$collection['id']] = self::STATUS_KEYMISM;
            }
            $this->_encoder->startTag(self::RESPONSE);
            $this->_encoder->startTag(self::STATUS);
            $this->_encoder->content($status[$collection['id']]);
            $this->_encoder->endTag();
            $this->_encoder->startTag(self::FOLDER);
            $this->_encoder->startTag(self::FOLDERTYPE);
            $this->_encoder->content($collection['class']);
            $this->_encoder->endTag();
            $this->_encoder->startTag(self::FOLDERID);
            $this->_encoder->content($collection['id']);
            $this->_encoder->endTag();
            if ($status[$collection['id']] == self::STATUS_SUCCESS) {
                $this->_encoder->startTag(self::ESTIMATE);
                $sync = $this->_getSyncObject();
                $sync->init($this->_stateDriver, null, $collection);
                $this->_encoder->content($sync->GetChangeCount());
                $this->_encoder->endTag();
            }
            $this->_encoder->endTag();
            $this->_encoder->endTag();
        }
        $this->_encoder->endTag();

        return true;
    }

}
