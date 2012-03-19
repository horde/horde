<?php
/**
 * Handler for MoveItems requests.
 *
 * Some code adapted from the Z-Push project. Original file header below.
 * File      :   diffbackend.php
 * Project   :   Z-Push
 * Descr     :   We do a standard differential
 *               change detection by sorting both
 *               lists of items by their unique id,
 *               and then traversing both arrays
 *               of items at once. Changes can be
 *               detected by comparing items at
 *               the same position in both arrays.
 *
 *  Created   :   01.10.2007
 *
 * © Zarafa Deutschland GmbH, www.zarafaserver.de
 * This file is distributed under GPL-2.0.
 * Consult COPYING file for details
 *
 * @copyright 2009-2012 Horde LLC (http://www.horde.org/)
 * @author Michael J Rubinsky <mrubinsk@horde.org>
 * @package ActiveSync
 */
/**
 * ActiveSync Handler for MoveItems requests.
 *
 * @copyright 2009-2012 Horde LLC (http://www.horde.org/)
 * @author Michael J Rubinsky <mrubinsk@horde.org>
 * @package ActiveSync
 */
class Horde_ActiveSync_Request_MoveItems extends Horde_ActiveSync_Request_Base
{

    /* Wbxml constants */
    const MOVES    = 'Move:Moves';
    const MOVE     = 'Move:Move';
    const SRCMSGID = 'Move:SrcMsgId';
    const SRCFLDID = 'Move:SrcFldId';
    const DSTFLDID = 'Move:DstFldId';
    const RESPONSE = 'Move:Response';
    const STATUS   = 'Move:Status';
    const DSTMSGID = 'Move:DstMsgId';

    /* keys */
    const SRCMSGKEY = 'srcmsgid';
    const SRCFLDKEY = 'srcfldid';
    const DSTFLDKEY = 'dstfldid';

    /* Status */
    const STATUS_INVALID_SRC = 1;
    const STATUS_INVALID_DST = 2;
    const STATUS_SUCCESS     = 3;

    /**
     * Handle request
     *
     * @return boolean
     */
    protected function _handle()
    {
        $this->_logger->info(sprintf(
            "[%s] Handling MoveItems command.",
            $this->_device_id)
        );

        if (!$this->_decoder->getElementStartTag(self::MOVES)) {
            throw new Horde_ActiveSync_Exception('Protocol Error');
        }

        $moves = array();
        while ($this->_decoder->getElementStartTag(self::MOVE)) {
            $move = array();
            if ($this->_decoder->getElementStartTag(self::SRCMSGID)) {
                $move[self::SRCMSGKEY] = $this->_decoder->getElementContent();
                if(!$this->_decoder->getElementEndTag())
                    break;
            }
            if ($this->_decoder->getElementStartTag(self::SRCFLDID)) {
                $move[self::SRCFLDKEY] = $this->_decoder->getElementContent();
                if (!$this->_decoder->getElementEndTag()) {
                    break;
                }
            }
            if ($this->_decoder->getElementStartTag(self::DSTFLDID)) {
                $move[self::DSTFLDKEY] = $this->_decoder->getElementContent();
                if (!$this->_decoder->getElementEndTag()) {
                    break;
                }
            }
            array_push($moves, $move);
            if (!$this->_decoder->getElementEndTag()) {
                throw new Horde_ActiveSync_Exception('Protocol Error');
            }
        }
        if (!$this->_decoder->getElementEndTag()) {
            throw new Horde_ActiveSync_Exception('Protocol Error');
        }

        // Start response
        $this->_encoder->StartWBXML();
        $this->_encoder->startTag(self::MOVES);

        foreach ($moves as $move) {
            $status = self::STATUS_SUCCESS;
            $this->_encoder->startTag(self::RESPONSE);
            $this->_encoder->startTag(self::SRCMSGID);
            $this->_encoder->content($move[self::SRCMSGKEY]);
            $this->_encoder->endTag();

            $importer = $this->_getImporter();
            $importer->init($this->_stateDriver, $move[self::SRCFLDKEY]);
            try {
                $new_msgid = $importer->importMessageMove($move[self::SRCMSGKEY], $move[self::DSTFLDKEY]);
            } catch (Horde_ActiveSYnc_Exception $e) {
                $this->_logger->err($e->getMessage());
                // Right now, we don't know the reason, just use 1.
                $status = self::STATUS_INVALID_DST;
            }
            if (!$new_msgid) {
                $status = self::STATUS_INVALID_DST;
            }

            // We discard the importer state for now.
            $this->_encoder->startTag(self::STATUS);
            $this->_encoder->content($status);
            $this->_encoder->endTag();

            $this->_encoder->startTag(self::DSTMSGID);
            $this->_encoder->content($new_msgid);
            $this->_encoder->endTag();
            $this->_encoder->endTag();
        }
        $this->_encoder->endTag();

        return true;
    }

}