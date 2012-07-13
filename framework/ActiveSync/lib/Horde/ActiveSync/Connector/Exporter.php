<?php
/**
 * Horde_ActiveSync_Connector_Exporter::
 *
 * Portions of this class were ported from the Z-Push project:
 *   File      :   wbxml.php
 *   Project   :   Z-Push
 *   Descr     :   WBXML mapping file
 *
 *   Created   :   01.10.2007
 *
 *   � Zarafa Deutschland GmbH, www.zarafaserver.de
 *   This file is distributed under GPL-2.0.
 *   Consult COPYING file for details
 *
 * @license   http://www.horde.org/licenses/gpl GPLv2
 *            NOTE: According to sec. 8 of the GENERAL PUBLIC LICENSE (GPL),
 *            Version 2, the distribution of the Horde_ActiveSync module in or
 *            to the United States of America is excluded from the scope of this
 *            license.
 * @copyright 2009-2012 Horde LLC (http://www.horde.org)
 * @author    Michael J Rubinsky <mrubinsk@horde.org>
 * @package   ActiveSync
 */
/**
 * Horde_ActiveSync_Connector_Exporter:: Outputs necessary wbxml to device.
 *
 * @license   http://www.horde.org/licenses/gpl GPLv2
 *            NOTE: According to sec. 8 of the GENERAL PUBLIC LICENSE (GPL),
 *            Version 2, the distribution of the Horde_ActiveSync module in or
 *            to the United States of America is excluded from the scope of this
 *            license.
 * @copyright 2009-2012 Horde LLC (http://www.horde.org)
 * @author    Michael J Rubinsky <mrubinsk@horde.org>
 * @package   ActiveSync
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
    public function __construct(
        Horde_ActiveSync_Wbxml_Encoder $encoder = null,
        $class = null)
    {
        $this->_encoder = $encoder;
        $this->_class = $class;
    }

    /**
     * Send a message change over the wbxml stream
     *
     * @param string $id                              The uid of the message
     * @param Horde_ActiveSync_Message_Base $message  The message object
     */
    public function messageChange($id, Horde_ActiveSync_Message_Base $message)
    {
        // Just ignore any messages that are not from this collection and
        // Prevent sending the same object twice in one request
        if ($message->getClass() != $this->_class ||
            in_array($id, $this->_seenObjects)) {
            return;
        }

        // Remember this message
        $this->_seenObjects[] = $id;

        // Specify if this is an ADD or a MODIFY change?
        if ($message->flags === false || $message->flags === Horde_ActiveSync::FLAG_NEWMESSAGE) {
            $this->_encoder->startTag(Horde_ActiveSync::SYNC_ADD);
        } else {
            $this->_encoder->startTag(Horde_ActiveSync::SYNC_MODIFY);
        }

        // Send the message
        $this->_encoder->startTag(Horde_ActiveSync::SYNC_SERVERENTRYID);
        $this->_encoder->content($id);
        $this->_encoder->endTag();
        $this->_encoder->startTag(Horde_ActiveSync::SYNC_DATA);
        $message->encodeStream($this->_encoder);
        $this->_encoder->endTag();
        $this->_encoder->endTag();
    }

    /**
     * Stream a message deletion to the PIM
     *
     * @param string $id  The uid of the message we are deleting.
     */
    public function messageDeletion($id)
    {
        $this->_encoder->startTag(Horde_ActiveSync::SYNC_REMOVE);
        $this->_encoder->startTag(Horde_ActiveSync::SYNC_SERVERENTRYID);
        $this->_encoder->content($id);
        $this->_encoder->endTag();
        $this->_encoder->endTag();
    }

    /**
     * Change a message's READ flag.
     *
     * @param string $id      The uid
     * @param integer $flags  The flag
     */
    public function messageReadFlag($id, $flag)
    {
        // This only applies to mail folders
        if ($this->_class != Horde_ActiveSync::CLASS_EMAIL) {
            return;
        }

        /* Encode and stream */
        $this->_encoder->startTag(Horde_ActiveSync::SYNC_MODIFY);
        $this->_encoder->startTag(Horde_ActiveSync::SYNC_SERVERENTRYID);
        $this->_encoder->content($id);
        $this->_encoder->endTag();
        $this->_encoder->startTag(Horde_ActiveSync::SYNC_DATA);
        $this->_encoder->startTag(Horde_ActiveSync_Message_Mail::POOMMAIL_READ);
        $this->_encoder->content($flag);
        $this->_encoder->endTag();
        $this->_encoder->endTag();
        $this->_encoder->endTag();
    }

    public function messageFlag($id, $flag)
    {
        $this->_encoder->startTag(Horde_ActiveSync::SYNC_MODIFY);
        $this->_encoder->startTag(Horde_ActiveSync::SYNC_SERVERENTRYID);
        $this->_encoder->content($id);
        $this->_encoder->endTag();
        $this->_encoder->startTag(Horde_ActiveSync::SYNC_DATA);
        $pflag = new Horde_ActiveSync_Message_Flag();
        $pflag->flagstatus = $flag == 1 ? Horde_ActiveSync_Message_Flag::FLAG_STATUS_ACTIVE : Horde_ActiveSync_Message_Flag::FLAG_STATUS_CLEAR;
        $this->_encoder->startTag(Horde_ActiveSync_Message_Mail::POOMMAIL_FLAG);
        $pflag->encodeStream($this->_encoder);
        $this->_encoder->endTag();
        $this->_encoder->endTag();
        $this->_encoder->endTag();
    }

    /**
     * Move a message to a different folder.
     *
     * @param Horde_ActiveSync_Message_Base $message  The message
     */
    function messageMove($message)
    {
    }

    /**
     * Add a folder change to the cache (used during FolderSync requests).
     *
     * @param Horde_ActiveSync_Message_Folder $folder
     */
    public function folderChange(Horde_ActiveSync_Message_Folder $folder)
    {
        array_push($this->changed, $folder);
        $this->count++;
    }

    /**
     * Add a folder deletion to the cache (used during FolderSync Requests).
     *
     * @param string $id  The folder id
     */
    public function folderDeletion($id)
    {
        array_push($this->deleted, $id);
        $this->count++;
    }

}