<?php
/**
 * ActiveSync Handler for GetItemEstimate requests
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
class Horde_ActiveSync_Request_GetItemEstimate extends Horde_ActiveSync_Request_Base
{
    /** Status Codes **/
    const STATUS_SUCCESS = 1;
    const STATUS_INVALIDCOL = 2;
    const STATUS_NOTPRIMED = 3;
    const STATUS_KEYMISM = 4;

    /* Request tag constants */
    const GETITEMESTIMATE = 'GetItemEstimate:GetItemEstimate';
    const VERSION = 'GetItemEstimate:Version';
    const FOLDERS = 'GetItemEstimate:Folders';
    const FOLDER = 'GetItemEstimate:Folder';
    const FOLDERTYPE = 'GetItemEstimate:FolderType';
    const FOLDERID = 'GetItemEstimate:FolderId';
    const DATETIME = 'GetItemEstimate:DateTime';
    const ESTIMATE = 'GetItemEstimate:Estimate';
    const RESPONSE = 'GetItemEstimate:Response';
    const STATUS = 'GetItemEstimate:Status';

    /**
     * Handle the request
     *
     * @return boolean
     * @throws Horde_ActiveSync_Exception
     */
    public function handle()
    {
        parent::handle();
        $this->_logger->info('[' . $this->_device->id . '] Beginning GETITEMESTIMATE');

        /* Check policy */
        if (!$this->checkPolicyKey($this->_activeSync->getPolicyKey())) {
            return false;
        }

        $status = array();
        $collections = array();
        if (!$this->_decoder->getElementStartTag(self::GETITEMESTIMATE) ||
            !$this->_decoder->getElementStartTag(self::FOLDERS)) {

            // Not sure why the protocol doesn't have a status for this...
            return false;
        }

        /* Obtain all the collections we are getting estimates for */
        while ($this->_decoder->getElementStartTag(self::FOLDER)) {

            /* Status - Assume success */
            $cStatus = self::STATUS_SUCCESS;

            /* Collection Class */
            if (!$this->_decoder->getElementStartTag(self::FOLDERTYPE)) {
                return false;
            }
            $class = $this->_decoder->getElementContent();
            if (!$this->_decoder->getElementEndTag()) {
                return false;
            }

            /* Collection Id */
            if ($this->_decoder->getElementStartTag(self::FOLDERID)) {
                $collectionid = $this->_decoder->getElementContent();
                if (!$this->_decoder->getElementEndTag()) {
                    return false;
                }
            }

            /* Filter Type */
            if (!$this->_decoder->getElementStartTag(Horde_ActiveSync::SYNC_FILTERTYPE)) {
                return false;
            }
            $filtertype = $this->_decoder->getElementContent();
            if (!$this->_decoder->getElementEndTag()) {
                return false;
            }

            /* Sync Key */
            if (!$this->_decoder->getElementStartTag(Horde_ActiveSync::SYNC_SYNCKEY)) {
                return false;
            }
            $synckey = $this->_decoder->getElementContent();
            if (empty($synckey)) {
                $cStatus = self::STATUS_NOTPRIMED;
            }
            if (!$this->_decoder->getElementEndTag()) {
                return false;
            }

            /* End the FOLDER element */
            if (!$this->_decoder->getElementEndTag()) {
                return false;
            }

            /* Build the collection array */
            $collection = array();
            $collection['synckey'] = $synckey;
            $collection['class'] = $class;
            $collection['filtertype'] = $filtertype;

            /* compatibility mode - get id from state */
            if (!isset($collectionid)) {
                $collectionid = $this->_state>getFolderData($this->_device->id, $collection['class']);
            }
            $collection['id'] = $collectionid;
            $status[$collection['id']] = $cStatus;

            array_push($collections, $collection);
        }

        $this->_encoder->startWBXML();

        /* Start getting the actual esitmates and outputting the results */
        $this->_encoder->startTag(self::GETITEMESTIMATE);
        foreach ($collections as $collection) {
            $this->_state->init($collection);
            try {
                $this->_state->loadState($collection['synckey']);
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
            $sync = $this->_driver->getSyncObject();
            $sync->init($this->_state, null, $collection);
            $this->_encoder->content($sync->GetChangeCount());
            $this->_encoder->endTag();
            $this->_encoder->endTag();
            $this->_encoder->endTag();
        }
        $this->_encoder->endTag();

        return true;
    }

}
