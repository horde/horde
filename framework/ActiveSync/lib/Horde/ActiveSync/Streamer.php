<?php
/**
 * File      :   streamimporter.php
 * Project   :   Z-Push
 * Descr     :   Stream import classes
 *
 * Created   :   01.10.2007
 *
 * � Zarafa Deutschland GmbH, www.zarafaserver.de
 * This file is distributed under GPL v2.
 * Consult LICENSE file for details
 */

/**
 *
 *
 */
class Horde_ActiveSync_Streamer
{
    protected $_encoder;
    protected $_type;
    protected $_seenObjects;

    /**
     * Const'r
     *
     * @param Horde_ActiveSync_Wbxml_Encoder $encoder
     * @param string $class  The collection class
     *
     * @return Horde_ActiveSync_Streamer
     */
    public function __construct(&$encoder, $class)
    {
        $this->_encoder = &$encoder;
        $this->_type = $class;
        $this->_seenObjects = array();
    }

    /**
     *
     * @param $id
     * @param $message
     * @return unknown_type
     */
    public function messageChange($id, $message)
    {
        if ($message->getClass() != $this->_type) {
            return true; // ignore other types
        }

        // prevent sending the same object twice in one request
        if (in_array($id, $this->_seenObjects)) {
        	return true;
        }

        $this->_seenObjects[] = $id;
        if ($message->flags === false || $message->flags === SYNC_NEWMESSAGE) {
            $this->_encoder->startTag(SYNC_ADD);
        } else {
            $this->_encoder->startTag(SYNC_MODIFY);
        }

        $this->_encoder->startTag(SYNC_SERVERENTRYID);
        $this->_encoder->content($id);
        $this->_encoder->endTag();
        $this->_encoder->startTag(SYNC_DATA);
        $message->encodeStream($this->_encoder);
        $this->_encoder->endTag();
        $this->_encoder->endTag();

        return true;
    }

    /**
     *
     * @param $id
     * @return unknown_type
     */
    public function messageDeletion($id)
    {
        $this->_encoder->startTag(SYNC_REMOVE);
        $this->_encoder->startTag(SYNC_SERVERENTRYID);
        $this->_encoder->content($id);
        $this->_encoder->endTag();
        $this->_encoder->endTag();

        return true;
    }

    /**
     *
     * @param $id
     * @param $flags
     * @return unknown_type
     */
    public function messageReadFlag($id, $flags)
    {
        if ($this->_type != "syncmail") {
            return true;
        }
        $this->_encoder->startTag(SYNC_MODIFY);
        $this->_encoder->startTag(SYNC_SERVERENTRYID);
        $this->_encoder->content($id);
        $this->_encoder->endTag();
        $this->_encoder->startTag(SYNC_DATA);
        $this->_encoder->startTag(SYNC_POOMMAIL_READ);
        $this->_encoder->content($flags);
        $this->_encoder->endTag();
        $this->_encoder->endTag();
        $this->_encoder->endTag();

        return true;
    }

    /**
     *
     * @param $message
     * @return unknown_type
     */
    function messageMove($message)
    {
        return true;
    }
}