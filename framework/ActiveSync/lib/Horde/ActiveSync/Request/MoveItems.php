<?php
/**
 * Horde_ActiveSync_Request_MoveItems::
 *
 * Portions of this class were ported from the Z-Push project:
 *   File      :   wbxml.php
 *   Project   :   Z-Push
 *   Descr     :   WBXML mapping file
 *
 *   Created   :   01.10.2007
 *
 *   © Zarafa Deutschland GmbH, www.zarafaserver.de
 *   This file is distributed under GPL-2.0.
 *   Consult COPYING file for details
 *
 * @license   http://www.horde.org/licenses/gpl GPLv2
 *            NOTE: According to sec. 8 of the GENERAL PUBLIC LICENSE (GPL),
 *            Version 2, the distribution of the Horde_ActiveSync module in or
 *            to the United States of America is excluded from the scope of this
 *            license.
 * @copyright 2012-2014 Horde LLC (http://www.horde.org)
 * @author    Michael J Rubinsky <mrubinsk@horde.org>
 * @package   ActiveSync
 */
/**
 * Handle MoveItems requests.
 *
 * @license   http://www.horde.org/licenses/gpl GPLv2
 *            NOTE: According to sec. 8 of the GENERAL PUBLIC LICENSE (GPL),
 *            Version 2, the distribution of the Horde_ActiveSync module in or
 *            to the United States of America is excluded from the scope of this
 *            license.
 * @copyright 2012-2014 Horde LLC (http://www.horde.org)
 * @author    Michael J Rubinsky <mrubinsk@horde.org>
 * @package   ActiveSync
 * @internal
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
    const STATUS_INVALID_SRC  = 1;
    const STATUS_INVALID_DST  = 2;
    const STATUS_SUCCESS      = 3;
    const STATUS_SAME_FOLDERS = 4;
    const STATUS_SERVER_ERR   = 5;

    /**
     * Handle request
     *
     * @return boolean
     */
    protected function _handle()
    {
        $this->_logger->info(sprintf(
            '[%s] Handling MoveItems command.',
            $this->_procid)
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
            $moves[] = $move;
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

        // Can't do these all at once since the device may send any combination
        // of src and dest mailboxes in the same request, though oddly enough
        // the server response only needs to include the message uids, not
        // the mailbox identifier.
        foreach ($moves as $move) {
            $status = self::STATUS_SUCCESS;
            $this->_encoder->startTag(self::RESPONSE);
            $this->_encoder->startTag(self::SRCMSGID);
            $this->_encoder->content($move[self::SRCMSGKEY]);
            $this->_encoder->endTag();

            if ($move[self::SRCFLDKEY] == $move[self::DSTFLDKEY]) {
                $status = self::STATUS_SAME_FOLDERS;
            } else {
                $importer = $this->_activeSync->getImporter();
                $importer->init($this->_state, $move[self::SRCFLDKEY]);
                try {
                    $move_res = $importer->importMessageMove(
                        array($move[self::SRCMSGKEY]),
                        $move[self::DSTFLDKEY]);
                    if (empty($move_res['results'][$move[self::SRCMSGKEY]])) {
                        // Hm. Specs say to send INVALID_SRC if the msg is
                        // already moved, or no longer present, but that seems
                        // to lead to the client keeping the item and continuously
                        // retrying. We can't fake the move since we need a
                        // new uid.....
                        $status = in_array($move[self::SRCMSGKEY], $move_res['missing'])
                            ? self::STATUS_INVALID_SRC
                            : self::STATUS_SERVER_ERR;
                    } else {
                        $new_msgid = $move_res['results'][$move[self::SRCMSGKEY]];
                    }
                } catch (Horde_ActiveSync_Exception $e) {
                    $this->_logger->err($e->getMessage());
                    $status = self::STATUS_INVALID_SRC;
                }
            }

            $this->_encoder->startTag(self::STATUS);
            $this->_encoder->content($status);
            $this->_encoder->endTag();

            if ($status == self::STATUS_SUCCESS) {
                $this->_encoder->startTag(self::DSTMSGID);
                $this->_encoder->content($new_msgid);
                $this->_encoder->endTag();
            }
            $this->_encoder->endTag();
        }
        $this->_encoder->endTag();

        return true;
    }

}