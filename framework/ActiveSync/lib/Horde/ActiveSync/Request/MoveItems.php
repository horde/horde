<?php
/**
 * Handle MoveItems requests.
 * 
 * Logic adapted from Z-Push, original copyright notices below.
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
// Move
define("SYNC_MOVE_MOVES","Move:Moves");
define("SYNC_MOVE_MOVE","Move:Move");
define("SYNC_MOVE_SRCMSGID","Move:SrcMsgId");
define("SYNC_MOVE_SRCFLDID","Move:SrcFldId");
define("SYNC_MOVE_DSTFLDID","Move:DstFldId");
define("SYNC_MOVE_RESPONSE","Move:Response");
define("SYNC_MOVE_STATUS","Move:Status");
define("SYNC_MOVE_DSTMSGID","Move:DstMsgId");
class Horde_ActiveSync_Request_MoveItems extends Horde_ActiveSync_Request_Base
{
    /**
     * Handle request
     *
     * @return boolean
     */
    public function handle()
    {
       if (!$this->_decoder->getElementStartTag(SYNC_MOVE_MOVES)) {
            return false;
        }

        $moves = array();
        while ($this->_decoder->getElementStartTag(SYNC_MOVE_MOVE)) {
            $move = array();
            if ($this->_decoder->getElementStartTag(SYNC_MOVE_SRCMSGID)) {
                $move['srcmsgid'] = $this->_decoder->getElementContent();
                if(!$this->_decoder->getElementEndTag())
                    break;
            }
            if ($this->_decoder->getElementStartTag(SYNC_MOVE_SRCFLDID)) {
                $move['srcfldid'] = $this->_decoder->getElementContent();
                if (!$this->_decoder->getElementEndTag()) {
                    break;
                }
            }
            if ($this->_decoder->getElementStartTag(SYNC_MOVE_DSTFLDID)) {
                $move['dstfldid'] = $this->_decoder->getElementContent();
                if (!$this->_decoder->getElementEndTag()) {
                    break;
                }
            }
            array_push($moves, $move);

            if (!$this->_decoder->getElementEndTag()) {
                return false;
            }
        }

        if (!$this->_decoder->getElementEndTag())
            return false;

        $this->_encoder->StartWBXML();

        $this->_encoder->startTag(SYNC_MOVE_MOVES);

        foreach ($moves as $move) {
            $this->_encoder->startTag(SYNC_MOVE_RESPONSE);
            $this->_encoder->startTag(SYNC_MOVE_SRCMSGID);
            $this->_encoder->content($move['srcmsgid']);
            $this->_encoder->endTag();

            $importer = $this->_driver->getContentsImporter($move['srcfldid']);
            $result = $importer->importMessageMove($move['srcmsgid'], $move['dstfldid']);

            // We discard the importer state for now.
            $this->_encoder->startTag(SYNC_MOVE_STATUS);
            $this->_encoder->content($result ? 3 : 1);
            $this->_encoder->endTag();

            $this->_encoder->startTag(SYNC_MOVE_DSTMSGID);
            $this->_encoder->content(is_string($result) ? $result : $move['srcmsgid']);
            $this->_encoder->endTag();
            $this->_encoder->endTzg();
        }
        $this->_encoder->endTag();

        return true;
    }
}