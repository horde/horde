<?php
/**
 * ActiveSync Handler for GetItemEstimate requests
 *
 * Copyright 2009-2012 Horde LLC (http://www.horde.org/)
 *
 * @author Michael J Rubinsky <mrubinsk@horde.org>
 * @package ActiveSync
 */
/**
 * Zarafa Deutschland GmbH, www.zarafaserver.de
 * This file is distributed under GPL-2.0.
 * Consult COPYING file for details
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
            $conversationmode = false;
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
                    break;
                case Horde_ActiveSync::SYNC_CONVERSATIONMODE:
                    // 12.1/not supported anyway.
                    // if (($conversationmode = $this->_decoder->getElementContent()) !== false) {
                    //     if (!$this->_decoder->getElementEndTag()) {
                    //         return false;
                    //     }
                    // } else {
                    //     $conversationmode = true;
                    // }
                    // break;
                    return false;
                case Horde_ActiveSync::SYNC_OPTIONS:
                    // 12.1
                    // unset($options_tmp);
                    // while (($typeoptions =  ($this->_decoder->getElementStartTag(Horde_ActiveSync::SYNC_FOLDERTYPE) ? Horde_ActiveSync::SYNC_FOLDERTYPE :
                    //                         ($this->_decoder->getElementStartTag(Horde_ActiveSync::SYNC_MAXITEMS) ? Horde_ActiveSync::SYNC_MAXITEMS :
                    //                         ($this->_decoder->getElementStartTag(Horde_ActiveSync::SYNC_FILTERTYPE) ? Horde_ActiveSync::SYNC_FILTERTYPE :
                    //                         -1)))) != -1) {
                    //     switch ($typeoptions) {
                    //     case Horde_ActiveSync::SYNC_FOLDERTYPE:
                    //         $options_tmp['foldertype'] = $this->_decoder->getElementContent();
                    //         if (strtolower($options_tmp['foldertype']) == strtolower($SyncCache['folders'][$collectionid]['class'])) {
                    //             unset($options_tmp['foldertype']);
                    //         }
                    //         if (!$this->_decoder->getElementEndTag()) {
                    //             return false;
                    //         }
                    //         break;
                    //     case Horde_ActiveSync::SYNC_MAXITEMS:
                    //         $options_tmp['maxitems'] = $this->_decoder->getElementContent();
                    //         if (!$this->_decoder->getElementEndTag()) {
                    //             return false;
                    //         }
                    //         break;
                    //     case Horde_ActiveSync::SYNC_FILTERTYPE:
                    //         $options_tmp['filtertype'] = $this->_decoder->getElementContent();
                    //         if (!$this->_decoder->getElementEndTag()) {
                    //             return false;
                    //         }
                    //         break;
                    //     }
                    // }
                    // if (isset($options_tmp['foldertype'])) {
                    //     $options['foldertype'] = $options_tmp['foldertype'];
                    //     $options[$options_tmp['foldertype']] = $options_tmp;
                    // } else {
                    //     $options = array_merge($options,$options_tmp);
                    // }
                    // if (!$this->_decoder->getElementEndTag()) {// END Options
                    //     return false;
                    // }
                    return false;
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

            // compatibility mode - get id from state
            if (!isset($collectionid)) {
                $collectionid = $this->_stateDriver>getFolderData($this->_device->id, $collection['class']);
            }
            $collection['id'] = $collectionid;
            $status[$collection['id']] = $cStatus;

            array_push($collections, $collection);
        }

        $this->_encoder->startWBXML();
        $this->_encoder->startTag(self::GETITEMESTIMATE);
        foreach ($collections as $collection) {
            $this->_stateDriver->init($collection);
            try {
                $this->_stateDriver->loadState($collection['synckey'], Horde_ActiveSync::REQUEST_TYPE_SYNC, $collection['id']);
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
            $this->_encoder->startTag(self::ESTIMATE);
            $sync = $this->_getSyncObject();
            $sync->init($this->_stateDriver, null, $collection);
            $this->_encoder->content($sync->GetChangeCount());
            $this->_encoder->endTag();
            $this->_encoder->endTag();
            $this->_encoder->endTag();
        }
        $this->_encoder->endTag();

        return true;
    }

}
