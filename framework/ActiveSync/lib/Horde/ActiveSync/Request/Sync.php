<?php
/**
 * ActiveSync Handler for SYNC requests
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
class Horde_ActiveSync_Request_Sync extends Horde_ActiveSync_Request_Base
{    
    /* Status */
    const STATUS_SUCCESS = 1;
    const STATUS_VERSIONMISM = 2;
    const STATUS_KEYMISM = 3;
    const STATUS_PROTERROR = 4;
    const STATUS_SERVERERROR = 5;

    /**
     * Handle the sync request
     *
     * @return boolean
     * @throws Horde_ActiveSync_Exception
     */
    public function handle(Horde_ActiveSync $activeSync, $devId)
    {
        parent::handle($activeSync, $devId);
        $this->_logger->info('[' . $this->_devId . '] Handling SYNC command.');

        /* Check policy */
        if (!$this->checkPolicyKey($activeSync->getPolicyKey())) {
            return false;
        }

        /* Be optimistic */
        $this->_statusCode = self::STATUS_SUCCESS;

        /* Contains all containers requested */
        $collections = array();

        /* Start decoding request */
        // FIXME: Need to figure out the proper response structure for errors
        // that occur this early
        if (!$this->_decoder->getElementStartTag(Horde_ActiveSync::SYNC_SYNCHRONIZE)) {
            throw new Horde_ActiveSync_Exception('Protocol error');
        }
        if (!$this->_decoder->getElementStartTag(Horde_ActiveSync::SYNC_FOLDERS)) {
            throw new Horde_ActiveSync_Exception('Protocol error');
        }

        while ($this->_statusCode == self::STATUS_SUCCESS &&
               $this->_decoder->getElementStartTag(Horde_ActiveSync::SYNC_FOLDER)) {

            $collection = array();
            $collection['truncation'] = Horde_ActiveSync::TRUNCATION_ALL;
            $collection['clientids'] = array();
            $collection['fetchids'] = array();

            if (!$this->_decoder->getElementStartTag(Horde_ActiveSync::SYNC_FOLDERTYPE)) {
                throw new Horde_ActiveSync_Exception('Protocol error');
            }

            $collection['class'] = $this->_decoder->getElementContent();
            $this->_logger->info('[' . $this->_devId . '] Syncing folder class: ' . $collection['class']);
            if (!$this->_decoder->getElementEndTag()) {
                throw new Horde_ActiveSync_Exception('Protocol error');
            }

            if (!$this->_decoder->getElementStartTag(Horde_ActiveSync::SYNC_SYNCKEY)) {
                throw new Horde_ActiveSync_Exception('Protocol error');
            }
            $collection['synckey'] = $this->_decoder->getElementContent();
            if (!$this->_decoder->getElementEndTag()) {
                throw new Horde_ActiveSync_Exception('Protocol error');
            }

            if ($this->_decoder->getElementStartTag(Horde_ActiveSync::SYNC_FOLDERID)) {
                $collection['id'] = $this->_decoder->getElementContent();
                $this->_logger->info('[' . $this->_devId . '] Folder server id: ' . $collection['id']);
                if (!$this->_decoder->getElementEndTag()) {
                    throw new Horde_ActiveSync_Exception('Protocol error');
                }
            }

            /* Looks like we ignore the SYNC_SUPPORTED Tag? */
            // @TODO: This needs to be captured and stored in the state so we
            // can correctly support ghosted properties
            if ($this->_decoder->getElementStartTag(Horde_ActiveSync::SYNC_SUPPORTED)) {
                // SUPPORTED only allowed on initial sync request
                if ($collection['synckey'] != 0) {
                    $this->_statusCode = Horde_ActiveSync::SYNC_STATUS_PROTERROR;
                    $this->_handleError($collection);
                    exit;
                }
                while (1) {
                    $el = $this->_decoder->getElement();
                    if ($el[Horde_ActiveSync_Wbxml::EN_TYPE] == Horde_ActiveSync_Wbxml::EN_TYPE_ENDTAG) {
                        break;
                    }
                }
            }

            if ($this->_decoder->getElementStartTag(Horde_ActiveSync::SYNC_DELETESASMOVES)) {
                $collection['deletesasmoves'] = true;
            }

            if ($this->_decoder->getElementStartTag(Horde_ActiveSync::SYNC_GETCHANGES)) {
                $collection['getchanges'] = true;
            }

            if ($this->_decoder->getElementStartTag(Horde_ActiveSync::SYNC_WINDOWSIZE)) {
                $collection['windowsize'] = $this->_decoder->getElementContent();
                if (!$this->_decoder->getElementEndTag()) {
                    $this->_statusCode = self::STATUS_PROTERROR;
                    $this->_handleError($collection);
                    exit;
                }
            }

            if ($this->_decoder->getElementStartTag(Horde_ActiveSync::SYNC_OPTIONS)) {
                while(1) {
                    if ($this->_decoder->getElementStartTag(Horde_ActiveSync::SYNC_FILTERTYPE)) {
                        $collection['filtertype'] = $this->_decoder->getElementContent();
                        if (!$this->_decoder->getElementEndTag()) {
                            $this->_statusCode = self::STATUS_PROTERROR;
                            $this->_handleError($collection);
                            exit;
                        }
                    }
                    if ($this->_decoder->getElementStartTag(Horde_ActiveSync::SYNC_TRUNCATION)) {
                        $collection['truncation'] = $this->_decoder->getElementContent();
                        if (!$this->_decoder->getElementEndTag()) {
                            $this->_statusCode = self::STATUS_PROTERROR;
                            $this->_handleError($collection);
                            exit;
                        }
                    }
                    if ($this->_decoder->getElementStartTag(Horde_ActiveSync::SYNC_RTFTRUNCATION)) {
                        $collection['rtftruncation'] = $this->_decoder->getElementContent();
                        if (!$this->_decoder->getElementEndTag()) {
                            $this->_statusCode = self::STATUS_PROTERROR;
                            $this->_handleError($collection);
                            exit;
                        }
                    }

                    if ($this->_decoder->getElementStartTag(Horde_ActiveSync::SYNC_MIMESUPPORT)) {
                        $collection['mimesupport'] = $this->_decoder->getElementContent();
                        if (!$this->_decoder->getElementEndTag()) {
                            $this->_statusCode = self::STATUS_PROTERROR;
                            $this->_handleError($collection);
                            exit;
                        }
                    }

                    if ($this->_decoder->getElementStartTag(Horde_ActiveSync::SYNC_MIMETRUNCATION)) {
                        $collection['mimetruncation'] = $this->_decoder->getElementContent();
                        if (!$this->_decoder->getElementEndTag()) {
                            $this->_statusCode = self::STATUS_PROTERROR;
                            $this->_handleError($collection);
                            exit;
                        }
                    }

                    if ($this->_decoder->getElementStartTag(Horde_ActiveSync::SYNC_CONFLICT)) {
                        $collection['conflict'] = $this->_decoder->getElementContent();
                        if (!$this->_decoder->getElementEndTag()) {
                            $this->_statusCode = self::STATUS_PROTERROR;
                            $this->_handleError;
                            exit;
                        }
                    }
                    $e = $this->_decoder->peek();
                    if ($e[Horde_ActiveSync_Wbxml::EN_TYPE] == Horde_ActiveSync_Wbxml::EN_TYPE_ENDTAG) {
                        $this->_decoder->getElementEndTag();
                        break;
                    }
                }
            }

            if ($this->_statusCode == self::STATUS_SUCCESS) {
                /* Initialize the state */
                $state = &$this->_driver->getStateObject($collection);
                try {
                    $state->loadState($collection['synckey']);
                } catch (Horde_ActiveSync_Exception $e) {
                    $this->_statusCode = self::STATUS_KEYMISM;
                    $this->_handleError($collection);
                    exit;
                }

                /* compatibility mode - get folderid from the state directory */
                if (!isset($collection['id'])) {
                    $collection['id'] = $state->getFolderData($this->_devId, $collection['class']);
                }

                /* compatibility mode - set default conflict behavior if no
                 * conflict resolution algorithm is set */
                if (!isset($collection['conflict'])) {
                    $collection['conflict'] = Horde_ActiveSync::CONFLICT_OVERWRITE_PIM;
                }
            }

            if ($this->_decoder->getElementStartTag(Horde_ActiveSync::SYNC_COMMANDS)) {
                /* Configure importer with last state */
                $importer = $this->_driver->getImporter();
                $importer->init($state, $collection['id'], $collection['conflict']);
                $nchanges = 0;
                while (1) {
                    // MODIFY or REMOVE or ADD or FETCH
                    $element = $this->_decoder->getElement();
                    if ($element[Horde_ActiveSync_Wbxml::EN_TYPE] != Horde_ActiveSync_Wbxml::EN_TYPE_STARTTAG) {
                        $this->_decoder->_ungetElement($element);
                        break;
                    }

                    $nchanges++;

                    if ($this->_decoder->getElementStartTag(Horde_ActiveSync::SYNC_SERVERENTRYID)) {
                        $serverid = $this->_decoder->getElementContent();

                        if (!$this->_decoder->getElementEndTag()) {// end serverid
                            $this->_statusCode = self::STATUS_PROTERROR;
                            $this->_handleError($collection);
                            exit;
                        }
                    } else {
                        $serverid = false;
                    }

                    if ($this->_decoder->getElementStartTag(Horde_ActiveSync::SYNC_CLIENTENTRYID)) {
                        $clientid = $this->_decoder->getElementContent();

                        if (!$this->_decoder->getElementEndTag()) { // end clientid
                            $this->_statusCode = self::STATUS_PROTERROR;
                            $this->_handleError($collection);
                            exit;
                        }
                    } else {
                        $clientid = false;
                    }

                    /* Create Streamer object from messages passed from PIM */
                    if ($this->_decoder->getElementStartTag(Horde_ActiveSync::SYNC_DATA)) {
                        switch ($collection['class']) {
                        case 'Email':
                            //@TODO
                            //$appdata = new SyncMail();
                            //$appdata->decode($decoder);
                            // Remove error code when implemented.
                            $this->_statusCode = self::STATUS_SERVERERROR;
                            break;
                        case 'Contacts':
                            $appdata = new Horde_ActiveSync_Message_Contact(
                                array('logger' => $this->_logger,
                                      'protocolversion' => $this->_version));
                            $appdata->decodeStream($this->_decoder);
                            break;
                        case 'Calendar':
                            $appdata = new Horde_ActiveSync_Message_Appointment(array('logger' => $this->_logger));
                            $appdata->decodeStream($this->_decoder);
                            break;
                        case 'Tasks':
                            $appdata = new Horde_ActiveSync_Message_Task(array('logger' => $this->_logger));
                            $appdata->decodeStream($this->_decoder);
                            break;
                        }
                        if (!$this->_decoder->getElementEndTag()) {
                            // End application data
                            $this->_statusCode = self::STATUS_PROTERROR;
                            break;
                        }
                    }

                    switch ($element[Horde_ActiveSync_Wbxml::EN_TAG]) {
                    case Horde_ActiveSync::SYNC_MODIFY:
                        if (isset($appdata)) {
                            // Currently, 'read' is only sent by the PDA when it
                            // is ONLY setting the read flag.
                            if (isset($appdata->read)) {
                                $importer->ImportMessageReadFlag($serverid, $appdata->read);
                            } else {
                                $importer->ImportMessageChange($serverid, $appdata);
                            }
                            $collection['importedchanges'] = true;
                        }
                        break;
                    case Horde_ActiveSync::SYNC_ADD:
                        if (isset($appdata)) {
                            $id = $importer->ImportMessageChange(false, $appdata);
                            if ($clientid && $id) {
                                $collection['clientids'][$clientid] = $id;
                                $collection['importedchanges'] = true;
                            }
                        }
                        break;
                    case Horde_ActiveSync::SYNC_REMOVE:
                        if (isset($collection['deletesasmoves'])) {
                            $folderid = $this->_driver->GetWasteBasket();

                            if ($folderid) {
                                $importer->ImportMessageMove($serverid, $folderid);
                                $collection['importedchanges'] = true;
                                break;
                            }
                        }

                        $importer->ImportMessageDeletion($serverid);
                        $collection['importedchanges'] = true;
                        break;
                    case Horde_ActiveSync::SYNC_FETCH:
                        array_push($collection['fetchids'], $serverid);
                        break;
                    }

                    if (!$this->_decoder->getElementEndTag()) {
                        // end change/delete/move
                        $this->_statusCode = self::STATUS_PROTERROR;
                        $this->_handleSyncError($collection);
                        exit;
                    }
                }

                $this->_logger->debug(sprintf('[%s] Processed %d incoming changes', $this->_devId, $nchanges));

                if (!$this->_decoder->getElementEndTag()) {
                    // end commands
                    $this->_statusCode = self::STATUS_PROTERROR;
                    $this->_handleError($collection);
                    exit;
                }
            }

            if (!$this->_decoder->getElementEndTag()) {
                // end collection
                $this->_statusCode = self::STATUS_PROTERROR;
                $this->_handleError($collection);
                exit;
            }

            array_push($collections, $collection);
        }

        if (!$this->_decoder->getElementEndTag()) {
            // end collections
            return false;
        }

        if (!$this->_decoder->getElementEndTag()) {
            // end sync
            return false;
        }

        /* Start output to PIM */
        $this->_logger->info('[' . $this->_devId . '] Beginning SYNC Response.');
        $this->_encoder->startWBXML();
        $this->_encoder->startTag(Horde_ActiveSync::SYNC_SYNCHRONIZE);
        $this->_encoder->startTag(Horde_ActiveSync::SYNC_FOLDERS);
        foreach ($collections as $collection) {

            /* Get new synckey if needed */
            if (isset($collection['importedchanges']) ||
                isset($collection['getchanges']) ||
                $collection['synckey'] == '0') {

                $collection['newsynckey'] = $state->getNewSyncKey($collection['synckey']);
            }

            $this->_encoder->startTag(Horde_ActiveSync::SYNC_FOLDER);
            $this->_encoder->startTag(Horde_ActiveSync::SYNC_FOLDERTYPE);
            $this->_encoder->content($collection['class']);
            $this->_encoder->endTag();

            $this->_encoder->startTag(Horde_ActiveSync::SYNC_SYNCKEY);
            if (isset($collection['newsynckey'])) {
                $this->_encoder->content($collection['newsynckey']);
            } else {
                $this->_encoder->content($collection['synckey']);
            }
            $this->_encoder->endTag();

            $this->_encoder->startTag(Horde_ActiveSync::SYNC_FOLDERID);
            $this->_encoder->content($collection['id']);
            $this->_encoder->endTag();

            $this->_encoder->startTag(Horde_ActiveSync::SYNC_STATUS);
            $this->_encoder->content($this->_statusCode);
            $this->_encoder->endTag();

            /* Check the mimesupport because we need it for advanced emails */
            $mimesupport = isset($collection['mimesupport']) ? $collection['mimesupport'] : 0;

            /* Output server IDs for new items we received and added from PIM */
            if (isset($collection['clientids']) || count($collection['fetchids']) > 0) {
                $this->_encoder->startTag(Horde_ActiveSync::SYNC_REPLIES);
                foreach ($collection['clientids'] as $clientid => $serverid) {
                    $this->_encoder->startTag(Horde_ActiveSync::SYNC_ADD);
                    $this->_encoder->startTag(Horde_ActiveSync::SYNC_CLIENTENTRYID);
                    $this->_encoder->content($clientid);
                    $this->_encoder->endTag();
                    $this->_encoder->startTag(Horde_ActiveSync::SYNC_SERVERENTRYID);
                    $this->_encoder->content($serverid);
                    $this->_encoder->endTag();
                    $this->_encoder->startTag(Horde_ActiveSync::SYNC_STATUS);
                    $this->_encoder->content(1);
                    $this->_encoder->endTag();
                    $this->_encoder->endTag();
                }

                /* Output any FETCH requests */
                foreach ($collection['fetchids'] as $id) {
                    $data = $this->_driver->Fetch($collection['id'], $id, $mimesupport);
                    if ($data !== false) {
                        $this->_encoder->startTag(Horde_ActiveSync::SYNC_FETCH);
                        $this->_encoder->startTag(Horde_ActiveSync::SYNC_SERVERENTRYID);
                        $this->_encoder->content($id);
                        $this->_encoder->endTag();
                        $this->_encoder->startTag(Horde_ActiveSync::SYNC_STATUS);
                        $this->_encoder->content(1);
                        $this->_encoder->endTag();
                        $this->_encoder->startTag(Horde_ActiveSync::SYNC_DATA);
                        $data->encodeStream($this->_encoder);
                        $this->_encoder->endTag();
                        $this->_encoder->endTag();
                    } else {
                        $this->_logger->err(sprintf('[Horde_ActiveSync::handleSync] Unable to fetch %s', $id));
                    }
                }
                $this->_encoder->endTag();
            }

            /* Send server changes to PIM */
            if (isset($collection['getchanges'])) {
                $filtertype = isset($collection['filtertype']) ? $collection['filtertype'] : false;
                $exporter = new Horde_ActiveSync_Connector_Exporter($this->_encoder, $collection['class']);
                $sync = $this->_driver->getSyncObject();
                $sync->init($state, $exporter, $collection);
                $changecount = $sync->getChangeCount();
                if (!empty($collection['windowsize']) && $changecount > $collection['windowsize']) {
                    $this->_encoder->startTag(Horde_ActiveSync::SYNC_MOREAVAILABLE, false, true);
                }

                /* Output message changes per folder */
                $this->_encoder->startTag(Horde_ActiveSync::SYNC_COMMANDS);

                // Stream the changes to the PDA
                $n = 0;
                while (1) {
                    $progress = $sync->syncronize();
                    if (!is_array($progress)) {
                        break;
                    }
                    $n++;

                    if (!empty($collection['windowsize']) && $n >= $collection['windowsize']) {
                        $this->_logger->info(sprintf('[%s] Exported maxItems of messages: %d - more available.', $this->_devId, $collection['windowsize']));
                        break;
                    }
                }
                $this->_encoder->endTag();
            }

            $this->_encoder->endTag();

            /* Save the sync state for the next time */
            if (isset($collection['newsynckey'])) {
                if (!empty($sync) || !empty($importer) || !empty($exporter) || $collection['synckey'] == 0)  {
                    $state->setNewSyncKey($collection['newsynckey']);
                    $state->save();
                } else {
                    $this->_logger->err(sprintf('[%s] Error saving %s - no state information available.', $this->_devId, $collection['newsynckey']));
                }
            }
        }

        $this->_encoder->endTag();

        $this->_encoder->endTag();

        return true;
    }

    /**
     * Helper for handling sync errors
     *
     * @param <type> $collection
     */
    private function _handleError($collection)
    {
        $this->_encoder->startWBXML();
        $this->_encoder->startTag(Horde_ActiveSync::SYNC_SYNCHRONIZE);

        $this->_encoder->startTag(Horde_ActiveSync::SYNC_FOLDERS);

        /* Get new synckey if needed */
        if ($this->_statusCode == self::STATUS_KEYMISM ||
            isset($collection['importedchanges']) ||
            isset($collection['getchanges']) ||
            $collection['synckey'] == '0') {

            $collection['newsynckey'] = Horde_ActiveSync_State_Base::getNewSyncKey(($this->_statusCode == self::STATUS_KEYMISM) ? 0 : $collection['synckey']);
            // @TODO: Need to reset the state??
        }

        $this->_encoder->startTag(Horde_ActiveSync::SYNC_FOLDER);

        $this->_encoder->startTag(Horde_ActiveSync::SYNC_FOLDERTYPE);
        $this->_encoder->content($collection['class']);
        $this->_encoder->endTag();

        $this->_encoder->startTag(Horde_ActiveSync::SYNC_SYNCKEY);
        if (isset($collection['newsynckey'])) {
            $this->_encoder->content($collection['newsynckey']);
        } else {
            $this->_encoder->content($collection['synckey']);
        }
        $this->_encoder->endTag();

        $this->_encoder->startTag(Horde_ActiveSync::SYNC_FOLDERID);
        $this->_encoder->content($collection['id']);
        $this->_encoder->endTag();

        $this->_encoder->startTag(Horde_ActiveSync::SYNC_STATUS);
        $this->_encoder->content($this->_statusCode);
        $this->_encoder->endTag();

        $this->_encoder->endTag(); // Horde_ActiveSync::SYNC_FOLDER
        $this->_encoder->endTag();
        $this->_encoder->endTag();
    }

}
