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
    /**
     * Handle the request
     *
     * @return boolean
     * @throws Horde_ActiveSync_Exception
     */
    public function handle(Horde_ActiveSync $activeSync)
    {
        $collections = array();

        if (!$this->_decoder->getElementStartTag(SYNC_GETITEMESTIMATE_GETITEMESTIMATE)) {
            return false;
        }

        if (!$this->_decoder->getElementStartTag(SYNC_GETITEMESTIMATE_FOLDERS)) {
            return false;
        }

        while ($this->_decoder->getElementStartTag(SYNC_GETITEMESTIMATE_FOLDER)) {

            if (!$this->_decoder->getElementStartTag(SYNC_GETITEMESTIMATE_FOLDERTYPE)) {
                return false;
            }

            $class = $this->_decoder->getElementContent();

            if (!$this->_decoder->getElementEndTag()) {
                return false;
            }

            if ($this->_decoder->getElementStartTag(SYNC_GETITEMESTIMATE_FOLDERID)) {
                $collectionid = $this->_decoder->getElementContent();

                if (!$this->_decoder->getElementEndTag()) {
                    return false;
                }
            }

            if (!$this->_decoder->getElementStartTag(SYNC_FILTERTYPE)) {
                return false;
            }
            $filtertype = $this->_decoder->getElementContent();

            if (!$this->_decoder->getElementEndTag()) {
                return false;
            }

            if (!$this->_decoder->getElementStartTag(SYNC_SYNCKEY)) {
                return false;
            }

            $synckey = $this->_decoder->getElementContent();

            if (!$this->_decoder->getElementEndTag()) {
                return false;
            }
            if (!$this->_decoder->getElementEndTag()) {
                return false;
            }

            $collection = array();
            $collection['synckey'] = $synckey;
            $collection['class'] = $class;
            $collection['filtertype'] = $filtertype;

            /* Initialize the state */
            $state = &$this->_driver->getStateObject($collection);
            $state->loadState($collection['synckey']);

            // compatibility mode - get folderid from the state directory
            if (!isset($collectionid)) {
                $collectionid = $state>getFolderData($this->_devid, $collection['class']);
            }
            $collection['id'] = $collectionid;

            array_push($collections, $collection);
        }

        $this->_encoder->startWBXML();

        $this->_encoder->startTag(SYNC_GETITEMESTIMATE_GETITEMESTIMATE);
        foreach ($collections as $collection) {
            $this->_encoder->startTag(SYNC_GETITEMESTIMATE_RESPONSE);

            $this->_encoder->startTag(SYNC_GETITEMESTIMATE_STATUS);
            $this->_encoder->content(1);
            $this->_encoder->endTag();

            $this->_encoder->startTag(SYNC_GETITEMESTIMATE_FOLDER);

            $this->_encoder->startTag(SYNC_GETITEMESTIMATE_FOLDERTYPE);
            $this->_encoder->content($collection['class']);
            $this->_encoder->endTag();

            $this->_encoder->startTag(SYNC_GETITEMESTIMATE_FOLDERID);
            $this->_encoder->content($collection['id']);
            $this->_encoder->endTag();

            $this->_encoder->startTag(SYNC_GETITEMESTIMATE_ESTIMATE);

            $importer = new Horde_ActiveSync_ContentsCache();

            $state->loadState($collection['synckey']);

            $exporter = $this->_driver->GetExporter();
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
