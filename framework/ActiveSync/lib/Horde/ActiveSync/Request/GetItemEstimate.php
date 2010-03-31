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

    /**
     * Handle the request
     *
     * @return boolean
     * @throws Horde_ActiveSync_Exception
     */
    public function handle(Horde_ActiveSync $activeSync, $devId)
    {
        parent::handle($activeSync, $devId);
        $this->_logger->info('[Horde_ActiveSync::handleFolderSync] Beginning GETITEMESTIMATE');

        /* Check policy */
        if (!$this->checkPolicyKey($activeSync->getPolicyKey())) {
            return false;
        }

        $status = array();
        $collections = array();

        if (!$this->_decoder->getElementStartTag(SYNC_GETITEMESTIMATE_GETITEMESTIMATE) ||
            !$this->_decoder->getElementStartTag(SYNC_GETITEMESTIMATE_FOLDERS)) {

            // Not sure why the protocol doesn't have a status for this...
            return false;
        }

        /* Obtain all the collections we are getting estimates for */
        while ($this->_decoder->getElementStartTag(SYNC_GETITEMESTIMATE_FOLDER)) {

            /* Status - Assume success */
            $cStatus = self::STATUS_SUCCESS;

            /* Collection Class */
            if (!$this->_decoder->getElementStartTag(SYNC_GETITEMESTIMATE_FOLDERTYPE)) {
                return false;
            }
            $class = $this->_decoder->getElementContent();
            if (!$this->_decoder->getElementEndTag()) {
                return false;
            }

            /* Collection Id */
            if ($this->_decoder->getElementStartTag(SYNC_GETITEMESTIMATE_FOLDERID)) {
                $collectionid = $this->_decoder->getElementContent();
                if (!$this->_decoder->getElementEndTag()) {
                    return false;
                }
            }

            /* Filter Type */
            if (!$this->_decoder->getElementStartTag(SYNC_FILTERTYPE)) {
                return false;
            }
            $filtertype = $this->_decoder->getElementContent();
            if (!$this->_decoder->getElementEndTag()) {
                return false;
            }

            /* Sync Key */
            if (!$this->_decoder->getElementStartTag(SYNC_SYNCKEY)) {
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
                $state = &$this->_driver->getStateObject();
                $collectionid = $state>getFolderData($this->_devid, $collection['class']);
            }
            $collection['id'] = $collectionid;
            $status[$collection['id']] = $cStatus;

            array_push($collections, $collection);
        }

        $this->_encoder->startWBXML();

        /* Start getting the actual esitmates and outputting the results */
        $this->_encoder->startTag(SYNC_GETITEMESTIMATE_GETITEMESTIMATE);
        foreach ($collections as $collection) {
            $this->_encoder->startTag(SYNC_GETITEMESTIMATE_RESPONSE);
            $this->_encoder->startTag(SYNC_GETITEMESTIMATE_STATUS);
            $this->_encoder->content($status[$collection['id']]);
            $this->_encoder->endTag();
            $this->_encoder->startTag(SYNC_GETITEMESTIMATE_FOLDER);
            $this->_encoder->startTag(SYNC_GETITEMESTIMATE_FOLDERTYPE);
            $this->_encoder->content($collection['class']);
            $this->_encoder->endTag();
            $this->_encoder->startTag(SYNC_GETITEMESTIMATE_FOLDERID);
            $this->_encoder->content($collection['id']);
            $this->_encoder->endTag();
            $this->_encoder->startTag(SYNC_GETITEMESTIMATE_ESTIMATE);

            $importer = new Horde_ActiveSync_Connector_NullImporter();
            $state = $this->_driver->getStateObject($collection);
            $state->loadState($collection['synckey']);
            $exporter = $this->_driver->getSyncObject();
            $exporter->init($state, $importer, $collection);

            $this->_encoder->content($exporter->GetChangeCount());
            $this->_encoder->endTag();
            $this->_encoder->endTag();
            $this->_encoder->endTag();
        }
        $this->_encoder->endTag();

        return true;
    }

}
