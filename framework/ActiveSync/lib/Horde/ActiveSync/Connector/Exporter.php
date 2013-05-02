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
 *   © Zarafa Deutschland GmbH, www.zarafaserver.de
 *   This file is distributed under GPL-2.0.
 *   Consult COPYING file for details
 *
 * @license   http://www.horde.org/licenses/gpl GPLv2
 *            NOTE: According to sec. 8 of the GENERAL PUBLIC LICENSE (GPL),
 *            Version 2, the distribution of the Horde_ActiveSync module in or
 *            to the United States of America is excluded from the scope of this
 *            license.
 * @copyright 2009-2013 Horde LLC (http://www.horde.org)
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
 * @copyright 2009-2013 Horde LLC (http://www.horde.org)
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
     * Local cache of changes to send.
     *
     * @var array
     */
    protected $_changes = array();

    /**
     * Counter of changes sent.
     *
     * @var integer
     */
    protected $_step = 0;

    /**
     * Currently syncing collection.
     *
     * @var array
     */
    protected $_currentCollection;


    /**
     * Const'r
     *
     * @param Horde_ActiveSync $as                    The ActiveSync server.
     * @param Horde_ActiveSync_Wbxml_Encoder $encoder The encoder
     *
     * @return Horde_ActiveSync_Connector_Exporter
     */
    public function __construct(
        Horde_ActiveSync $as,
        Horde_ActiveSync_Wbxml_Encoder $encoder = null)
    {
        $this->_as = $as;
        $this->_encoder = $encoder;
        $this->_logger = $as->logger;
    }

    /**
     * Set the changes to send to the client.
     *
     * @param array $changes  The changes array returned from the collection
     *                        handler.
     * @param array $collection  The collection we are currently syncing.
     */
    public function setChanges($changes, $collection)
    {
        $this->_changes = $changes;
        $this->_step = 0;
        $this->_currentCollection = $collection;
    }

    /**
     * Sends the next change in the set to the client.
     *
     * @return boolean  True if more changes can be sent, otherwise false.
     */
    public function sendNextChange()
    {
        if ($this->_step < count($this->_changes)) {
            $change = $this->_changes[$this->_step];
            while (empty($change['id']) && $this->_step < count($this->_changes) - 1) {
                $this->_logger->err('Missing UID value for an entry in: ' . $this->_currentCollection['id']);
                $this->_step++;
                $change = $this->_changes[$this->_step];
            }

            if (empty($change['ignore'])) {
                switch($change['type']) {
                case Horde_ActiveSync::CHANGE_TYPE_CHANGE:
                    try {
                        $message = $this->_as->driver->getMessage(
                            $this->_currentCollection['id'],
                            $change['id'],
                            $this->_currentCollection);
                        $message->flags = (isset($change['flags'])) ? $change['flags'] : 0;
                        $this->messageChange($change['id'], $message);
                    } catch (Horde_Exception_NotFound $e) {
                        $this->_logger->err('Message gone or error reading message from server: ' . $e->getMessage());
                    } catch (Horde_ActiveSync_Exception $e) {
                        $this->_logger->err('Unknown backend error skipping message: ' . $e->getMessage());
                    }
                    break;

                case Horde_ActiveSync::CHANGE_TYPE_DELETE:
                    $this->messageDeletion($change['id']);
                    break;

                case Horde_ActiveSync::CHANGE_TYPE_FLAGS:
                    // Read flag.
                    $message = Horde_ActiveSync::messageFactory('Mail');
                    $message->flags = Horde_ActiveSync::CHANGE_TYPE_CHANGE;
                    $message->read = isset($change['flags']['read']) ? $change['flags']['read'] : false;

                    // "Flagged" flag.
                    if (isset($change['flags']['flagged']) && $this->_as->device->version >= Horde_ActiveSync::VERSION_TWELVE) {
                        $flag = Horde_ActiveSync::messageFactory('Flag');
                        $flag->flagstatus = $change['flags']['flagged'] == 1
                            ? Horde_ActiveSync_Message_Flag::FLAG_STATUS_ACTIVE
                            : Horde_ActiveSync_Message_Flag::FLAG_STATUS_CLEAR;
                        $message->flag = $flag;
                    }

                    // Verbs
                    if ($this->_as->device->version >= Horde_ActiveSync::VERSION_FOURTEEN) {
                        if (isset($change['flags'][Horde_ActiveSync::CHANGE_REPLY_STATE])) {
                            $message->lastverbexecuted = Horde_ActiveSync_Message_Mail::VERB_REPLY_SENDER;
                            $message->lastverbexecutiontime = new Horde_Date($change['flags'][Horde_ActiveSync::CHANGE_REPLY_STATE]);
                        } elseif (isset($change['flags'][Horde_ActiveSync::CHANGE_REPLYALL_STATE])) {
                            $message->lastverbexecuted = Horde_ActiveSync_Message_Mail::VERB_REPLY_ALL;
                            $message->lastverbexecutiontime = new Horde_Date($change['flags'][Horde_ActiveSync::CHANGE_REPLYALL_STATE]);
                        } elseif (isset($change['flags'][Horde_ActiveSync::CHANGE_FORWARD_STATE])) {
                            $message->lastverbexecuted = Horde_ActiveSync_Message_Mail::VERB_FORWARD;
                            $message->lastverbexecutiontime = new Horde_Date($change['flags'][Horde_ActiveSync::CHANGE_FORWARD_STATE]);
                        }
                    }

                    // Export it.
                    $this->messageChange($change['id'], $message);
                    break;

                case Horde_ActiveSync::CHANGE_TYPE_MOVE:
                    $this->messageMove($change['id'], $change['parent']);
                    break;
                }
            }

            // Update the state.
            $this->_as->state->updateState($change['type'], $change);

            // Prepare the progress struct and return.
            $this->_step++;

            // Check windowsize
            if (!empty($this->_currentCollection['windowsize']) && $this->_step >= $this->_currentCollection['windowsize']) {
                $this->_logger->info(sprintf(
                    '[%s] Exported maxItems of messages (%s) - more available.',
                    $this->_procid,
                    $collection['windowsize'])
                );
                return false;
            }
            return true;
        } else {
            return false;
        }
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
        // prevent sending the same object twice in one request.
        if ($message->getClass() != $this->_currentCollection['class'] ||
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
        $this->changed[] = $folder;
        $this->count++;
    }

    /**
     * Add a folder deletion to the cache (used during FolderSync Requests).
     *
     * @param string $id  The folder id
     */
    public function folderDeletion($id)
    {
        $this->deleted[] = $id;
        $this->count++;
    }

}