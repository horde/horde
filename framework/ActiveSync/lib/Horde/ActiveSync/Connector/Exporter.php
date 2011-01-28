<?php
/**
 * Connector class for exporting ActiveSync messages to the wbxml output stream.
 * Contains code written by the Z-Push project. Original file header preserved
 * below.
 *
 * @copyright 2010-2011 The Horde Project (http://www.horde.org)
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Horde_ActiveSync
 */
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
class Horde_ActiveSync_Connector_Exporter
{
    /**
     * The wbxml encoder
     *
     * @var Horde_ActiveSync_Wbxml_Encoder
     */
    protected $_encoder;

    /**
     * The collection class for what we are exporting
     *
     * @var string
     */
    protected $_class;

    /**
     * Local cache of object ids we have already dealt with.
     *
     * @var array
     */
    protected $_seenObjects = array();

    /**
     * Array of object ids that have changed.
     * Used when exporting folder structure changes since they are not streamed
     * from this object.
     *
     * @var array
     */
    public $changed = array();

    /**
     * Array of folder ids that have been deleted on the server.
     *
     * @var array
     */
    public $deleted = array();

    /**
     * Tracks the total number of folder changes
     *
     * @var integer
     */
    public $count = 0;

    /**
     * Const'r
     *
     * @param Horde_ActiveSync_Wbxml_Encoder $encoder The encoder
     * @param string $class                           The collection class
     *
     * @return Horde_ActiveSync_Connector_Exporter
     */
    public function __construct($encoder = null, $class = null)
    {
        $this->_encoder = $encoder;
        $this->_class = $class;
    }

    /**
     * Send a message change over the wbxml stream
     *
     * @param string $id                              Thenuid of the message
     * @param Horde_ActiveSync_Message_Base $message  The message object
     *
     * @return boolean
     */
    public function messageChange($id, $message)
    {
        /* Just ignore any messages that are not from this collection */
        if ($message->getClass() != $this->_class) {
            return true;
        }

        /* Prevent sending the same object twice in one request */
        if (in_array($id, $this->_seenObjects)) {
        	return true;
        }

        /* Remember this message */
        $this->_seenObjects[] = $id;

        /* Specify if this is an ADD or a MODIFY change? */
        if ($message->flags === false || $message->flags === Horde_ActiveSync::FLAG_NEWMESSAGE) {
            $this->_encoder->startTag(Horde_ActiveSync::SYNC_ADD);
        } else {
            $this->_encoder->startTag(Horde_ActiveSync::SYNC_MODIFY);
        }

        /* Send the message */
        $this->_encoder->startTag(Horde_ActiveSync::SYNC_SERVERENTRYID);
        $this->_encoder->content($id);
        $this->_encoder->endTag();
        $this->_encoder->startTag(Horde_ActiveSync::SYNC_DATA);
        $message->encodeStream($this->_encoder);
        $this->_encoder->endTag();
        $this->_encoder->endTag();

        return true;
    }

    /**
     * Stream a message deletion to the PIM
     *
     * @param string $id  The uid of the message we are deleting.
     *
     * @return boolean
     */
    public function messageDeletion($id)
    {
        $this->_encoder->startTag(Horde_ActiveSync::SYNC_REMOVE);
        $this->_encoder->startTag(Horde_ActiveSync::SYNC_SERVERENTRYID);
        $this->_encoder->content($id);
        $this->_encoder->endTag();
        $this->_encoder->endTag();

        return true;
    }

    /**
     * Change a message's READ flag.
     *
     * @param string $id      The uid
     * @param integer $flags  The flag
     *
     * @return boolean
     */
    public function messageReadFlag($id, $flags)
    {
        /* This only applies to mail folders */
        if ($this->_class != "syncmail") {
            return true;
        }

        /* Encode and stream */
        $this->_encoder->startTag(Horde_ActiveSync::SYNC_MODIFY);
        $this->_encoder->startTag(Horde_ActiveSync::SYNC_SERVERENTRYID);
        $this->_encoder->content($id);
        $this->_encoder->endTag();
        $this->_encoder->startTag(Horde_ActiveSync::SYNC_DATA);
        $this->_encoder->startTag(SYNC_POOMMAIL_READ);
        $this->_encoder->content($flags);
        $this->_encoder->endTag();
        $this->_encoder->endTag();
        $this->_encoder->endTag();

        return true;
    }

    /**
     * Move a message to a different folder.
     * @TODO
     * @param Horde_ActiveSync_Message_Base $message  The message
     *
     * @return boolean
     */
    function messageMove($message)
    {
        return true;
    }

    /**
     * Add a folder change to the cache. (used during FolderSync Requests).
     *
     * @param Horde_ActiveSync_Message_Folder $folder
     *
     * @return boolean
     */
    public function folderChange($folder)
    {
        array_push($this->changed, $folder);
        $this->count++;

        return true;
    }

    /**
     * Add a folder deletion to the cache (used during FolderSync Requests).
     *
     * @param string $id  The folder id
     *
     * @return boolean
     */
    public function folderDeletion($id)
    {
        array_push($this->deleted, $id);
        $this->count++;

        return true;
    }
}