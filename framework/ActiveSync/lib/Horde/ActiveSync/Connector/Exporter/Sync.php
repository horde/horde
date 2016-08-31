<?php
/**
 * Horde_ActiveSync_Connector_Exporter_Sync::
 *
 * @license   http://www.horde.org/licenses/gpl GPLv2
 *            NOTE: According to sec. 8 of the GENERAL PUBLIC LICENSE (GPL),
 *            Version 2, the distribution of the Horde_ActiveSync module in or
 *            to the United States of America is excluded from the scope of this
 *            license.
 * @copyright 2009-2016 Horde LLC (http://www.horde.org)
 * @author    Michael J Rubinsky <mrubinsk@horde.org>
 * @package   ActiveSync
 */
/**
 * Horde_ActiveSync_Connector_Exporter_Sync:: Responsible for outputing
 * blocks of WBXML responses in SYNC responses. E.g., sending all WBXML
 * necessary to transmit a new/changed message object to the client.
 *
 * @license   http://www.horde.org/licenses/gpl GPLv2
 *            NOTE: According to sec. 8 of the GENERAL PUBLIC LICENSE (GPL),
 *            Version 2, the distribution of the Horde_ActiveSync module in or
 *            to the United States of America is excluded from the scope of this
 *            license.
 * @copyright 2009-2016 Horde LLC (http://www.horde.org)
 * @author    Michael J Rubinsky <mrubinsk@horde.org>
 * @package   ActiveSync
 */
class Horde_ActiveSync_Connector_Exporter_Sync extends Horde_ActiveSync_Connector_Exporter_Base
{

    /**
     * Local cache of object ids we have already dealt with.
     *
     * @var array
     */
    protected $_seenObjects = array();

    /**
     * Currently syncing collection.
     *
     * @var array
     */
    protected $_currentCollection;


    /**
     * Set the changes to send to the client.
     *
     * @param array $changes  The changes array returned from the collection
     *                        handler.
     * @param array $collection  The collection we are currently syncing.
     */
    public function setChanges($changes, $collection = null)
    {
        parent::setChanges($changes, $collection);
        $this->_currentCollection = $collection;
    }

    /**
     * Sends the next change in the set to the client.
     *
     * @return boolean|Horde_Exception True if more changes can be sent false if
     *                                 all changes were sent, Horde_Exception if
     *                                 there was an error sending an item.
     */
    public function sendNextChange()
    {
        return $this->_sendNextChange();
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
            $this->_logger->notice(sprintf(
                '[%s] IGNORING message %s since it looks like it was already sent or does not belong to this collection. Class: %s, CurrentClass: %s',
                $this->_procid,
                $id,
                $message->getClass(),
                $this->_currentCollection['class']));
            return;
        }

        // Ignore any empty objects.
        if ($message->isEmpty()) {
            $this->_logger->notice(sprintf(
                '[%s] IGNORING message %s since it looks like it does not contain any data. Class: %s, CurrentClass: %s',
                $this->_procid,
                $id,
                $message->getClass(),
                $this->_currentCollection['class']));
            return;
        }

        // Remember this message
        $this->_seenObjects[] = $id;

        // Specify if this is an ADD or a MODIFY change?
        if ($message->flags === Horde_ActiveSync::FLAG_NEWMESSAGE) {
            $this->_encoder->startTag(Horde_ActiveSync::SYNC_ADD);
        } else {
            $this->_encoder->startTag(Horde_ActiveSync::SYNC_MODIFY);
        }

        // Send the message
        $this->_encoder->startTag(Horde_ActiveSync::SYNC_SERVERENTRYID);
        $this->_encoder->content($id);
        $this->_encoder->endTag();
        $this->_encoder->startTag(Horde_ActiveSync::SYNC_DATA);
        try {
            $message->encodeStream($this->_encoder);
        } catch (Horde_ActiveSync_Exception $e) {
            $this->_logger->err($e);
        }
        $this->_encoder->endTag();
        $this->_encoder->endTag();
    }

    /**
     * Stream a message deletion to the client.
     *
     * @param string $id  The uid of the message we are deleting.
     * @param boolean $soft  If true, send a SOFTDELETE, otherwise a REMOVE.
     */
    public function messageDeletion($id, $soft = false)
    {
        if ($soft) {
            $this->_encoder->startTag(Horde_ActiveSync::SYNC_SOFTDELETE);
        } else {
            $this->_encoder->startTag(Horde_ActiveSync::SYNC_REMOVE);
        }
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
    public function messageMove($message)
    {
    }

    public function syncAddResponse($collection)
    {
        foreach ($collection['clientids'] as $clientid => $serverid) {
            if ($serverid) {
                $status = Horde_ActiveSync_Request_Sync::STATUS_SUCCESS;
            } else {
                $status = Horde_ActiveSync_Request_Sync::STATUS_INVALID;
            }
            // Start SYNC_ADD
            $this->_encoder->startTag(Horde_ActiveSync::SYNC_ADD);

            // If we have clientids and a CLASS_EMAIL, this is
            // a SMS response.
            // @TODO: have collection classes be able to
            // generate their own responses??
            if ($collection['class'] == Horde_ActiveSync::CLASS_EMAIL) {
                $this->_encoder->startTag(Horde_ActiveSync::SYNC_FOLDERTYPE);
                $this->_encoder->content(Horde_ActiveSync::CLASS_SMS);
                $this->_encoder->endTag();
            }

            // CLIENTENTRYID
            $this->_encoder->startTag(Horde_ActiveSync::SYNC_CLIENTENTRYID);
            $this->_encoder->content($clientid);
            $this->_encoder->endTag();

            // SERVERENTRYID
            if ($status == Horde_ActiveSync_Request_Sync::STATUS_SUCCESS) {
                $this->_encoder->startTag(Horde_ActiveSync::SYNC_SERVERENTRYID);
                $this->_encoder->content($serverid);
                $this->_encoder->endTag();
            }

            // EAS 16?
            $this->_sendEas16MessageResponse($serverid, $collection);

            // STATUS
            $this->_encoder->startTag(Horde_ActiveSync::SYNC_STATUS);
            $this->_encoder->content($status);
            $this->_encoder->endTag();

            // END SYNC_ADD
            $this->_encoder->endTag();
        }
    }

    protected function _sendEas16MessageResponse($serverid, $collection)
    {
        if ($this->_as->device->version >= Horde_ActiveSync::VERSION_SIXTEEN &&
            $collection['class'] == Horde_ActiveSync::CLASS_CALENDAR &&
            !empty($collection['atchash'][$serverid])) {

            $this->_encoder->startTag(Horde_ActiveSync::SYNC_SERVERENTRYID);
            $this->_encoder->content($serverid);
            $this->_encoder->endTag();

            $msg = $this->_as->messageFactory('Appointment');
            $msg->uid = $serverid;
            $msg->airsyncbaseattachments = $this->_as->messageFactory('AirSyncBaseAttachments');
            $msg->airsyncbaseattachments->attachment = array();
            foreach ($collection['atchash'][$serverid]['add'] as $clientid => $filereference) {
                $atc = $this->_as->messageFactory('AirSyncBaseAttachment');
                $atc->clientid = $clientid;
                $atc->attname = $filereference;
                $msg->airsyncbaseattachments->attachment[] = $atc;
            }
            $this->_encoder->startTag(Horde_ActiveSync::SYNC_DATA);
            $msg->encodeStream($this->_encoder);
            $this->_encoder->endTag();
        }
    }

    public function syncModifiedResponse($collection)
    {
        foreach ($collection['modifiedids'] as $serverid) {
            // Start SYNC_MODIFY
            $this->_encoder->startTag(Horde_ActiveSync::SYNC_MODIFY);

            // EAS 16?
            $this->_sendEas16MessageResponse($serverid, $collection);

            // SYNC_STATUS
            $this->_encoder->startTag(Horde_ActiveSync::SYNC_STATUS);
            $this->_encoder->content(Horde_ActiveSync_Request_Sync::STATUS_SUCCESS);
            $this->_encoder->endTag();

            // End SYNC_MODIFY
            $this->_encoder->endTag();
        }
    }

    protected function _getNextChange()
    {
        $change = $this->_changes[$this->_step];
        if (!is_array($change)) {
            // This is an initial sync, so we know it's a CHANGE_TYPE_CHANGE
            // and a new message with no flag changes etc...
            $change = array(
                'id' => $change,
                'type' => Horde_ActiveSync::CHANGE_TYPE_CHANGE,
                'flags' => Horde_ActiveSync::FLAG_NEWMESSAGE
            );
        }

        return $change;
    }

    /**
     * Sends the next message change to the client.
     *
     * @return @see self::sendNextChange()
     */
    protected function _sendNextChange()
    {
        if ($this->_step >= count($this->_changes)) {
            return false;
        }

        $change = $this->_getNextChange();
        // Ignore this change, no UID value, keep trying until we get a
        // good entry or we run out of entries.
        while (empty($change['id']) && $this->_step < count($this->_changes) - 1) {
            $this->_logger->notice(sprintf(
                'Missing UID value for an entry in: %s. Details: %s.',
                $this->_currentCollection['id'],
                print_r($change, true)
            ));
            $this->_step++;
            $change = $this->_getNextChange();
        }

        // Actually export the change by calling the appropriate
        // method to output the correct wbxml for this change.
        if (empty($change['ignore'])) {
            switch($change['type']) {
            case Horde_ActiveSync::CHANGE_TYPE_CHANGE:
                try {
                    $message = $this->_as->driver->getMessage(
                        $this->_currentCollection['serverid'],
                        $change['id'],
                        $this->_currentCollection);
                    $message->flags = (isset($change['flags'])) ? $change['flags'] : false;
                    $this->messageChange($change['id'], $message);
                } catch (Horde_Exception_NotFound $e) {
                    $this->_logger->notice(sprintf(
                        '[%s] Message gone or error reading message from server: %s',
                        $this->_procid, $e->getMessage()));
                    $this->_as->state->updateState($change['type'], $change);
                    $this->_step++;
                    return $e;
                } catch (Horde_ActiveSync_Exception $e) {
                    $this->_logger->err(sprintf(
                        '[%s] Unknown backend error skipping message: %s',
                        $this->_procid,
                        $e->getMessage()));
                    $this->_as->state->updateState($change['type'], $change);
                    $this->_step++;
                    return $e;
                }
                break;

            case Horde_ActiveSync::CHANGE_TYPE_DELETE:
                $this->messageDeletion($change['id']);
                break;

            case Horde_ActiveSync::CHANGE_TYPE_SOFTDELETE:
                $this->messageDeletion($change['id'], true);
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

                // Categories
                if (!empty($change['categories']) && $this->_as->device->version > Horde_ActiveSync::VERSION_TWELVEONE) {
                    $message->categories = $change['categories'];
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
        $this->_step++;
        return true;
    }

}
