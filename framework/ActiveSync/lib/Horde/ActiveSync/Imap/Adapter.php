<?php
/**
 * Horde_ActiveSync_Imap_Adapter
 *
 * PHP Version 5
 *
 * @license   http://www.horde.org/licenses/gpl GPLv2
 * @copyright 2010-2012 Horde LLC (http://www.horde.org/)
 * @author    Michael J Rubinsky <mrubinsk@horde.org>
 * @link      http://pear.horde.org/index.php?package=ActiveSync
 * @package   ActiveSync
 */
/**
 * Horde_ActiveSync_Imap_Adapter:: Contains methods for communicating with
 * Horde's Horde_Imap_Client library.
 *
 * @license   http://www.horde.org/licenses/gpl GPLv2
 * @copyright 2010-2012 Horde LLC (http://www.horde.org/)
 * @author    Michael J Rubinsky <mrubinsk@horde.org>
 * @link      http://pear.horde.org/index.php?package=ActiveSync
 * @package   ActiveSync
 */
class Horde_ActiveSync_Imap_Adapter
{
    /**
     * @var Horde_ActiveSync_Interface_ImapFactory
     */
    protected $_imap;

    /**
     * @var Horde_Log_Logger
     */
    protected $_logger;

    /**
     * Cont'r
     *
     * @param array $params  Parameters:
     *   - factory:  (Horde_ActiveSync_Interface_ImapFactory) Factory object
     *               REQUIRED
     */
    public function __construct(array $params = array())
    {
        $this->_imap = $params['factory'];
    }

    /**
     * Set this instance's logger.
     *
     * @param Horde_Log_Logger $logger  The logger.
     */
    public function setLogger(Horde_Log_Logger $logger)
    {
        $this->_logger = $logger;
    }

    /**
     * Return an array of available mailboxes. Uses's the mail/mailboxList API
     * method for obtaining the list.
     *
     * @return array
     */
    public function getMailboxes()
    {
        return $this->_imap->getMailboxes();
    }

    /**
     * Return the list of special mailboxes.
     *
     * @return array
     */
    public function getSpecialMailboxes()
    {
        return $this->_imap->getSpecialMailboxes();
    }

    /**
     * Ping a mailbox. This detects only if any new messages have arrived in
     * the specified mailbox. Flag changes are not detected for performance
     * reasons. This allows quick change detection, as well as a great
     * reduction in PING/SYNC/PING cycles while reading mail on the device.
     *
     * @param Horde_ActiveSync_Folder_Imap $folder  The folder object.
     *
     * @return boolean  True if changes were detected, otherwise false.
     * @throws Horde_ActiveSync_Exception
     */
    public function ping(
        Horde_ActiveSync_Folder_Imap $folder)
    {
        $imap = $this->_getImapOb();
        $mbox = new Horde_Imap_Client_Mailbox($folder->serverid());
        try {
            $status = $imap->status($mbox, Horde_Imap_Client::STATUS_UIDNEXT);
        } catch (Horde_Imap_Client_Exception $e) {
            throw new Horde_ActiveSync_Exception($e);
        }

        if ($folder->uidnext() < $status['uidnext']) {
            return true;
        }

        return false;
    }

    /**
     * Return a list of messages from the specified mailbox. If QRESYNC is NOT
     * available, or if QRESYNC IS available, but this is the first request
     * then the entire message list is returned. Otherwise, only changes since
     * the last MODSEQ value are taken into consideration.
     *
     * @param Horde_ActiveSync_Folder_Imap $folder  The folder object.
     * @param array $options                        Additional options:
     *  - sincedate: (integer)  Timestamp of earliest message to retrieve.
     *               DEFAULT: 0 (Don't filter).
     *
     * @return Horde_ActiveSync_Folder_Imap  The folder object, containing any
     *                                       change instructions for the device.
     *
     * @throws Horde_Exception
     */
    public function getMessageChanges(
        Horde_ActiveSync_Folder_Imap $folder, array $options = array())
    {
        $imap = $this->_getImapOb();
        $mbox = new Horde_Imap_Client_Mailbox($folder->serverid());
        if ($qresync = $imap->queryCapability('QRESYNC')) {
            $status_flags = Horde_Imap_Client::STATUS_HIGHESTMODSEQ |
                Horde_Imap_Client::STATUS_UIDVALIDITY |
                Horde_Imap_Client::STATUS_UIDNEXT;
        } else {
            $status_flags = Horde_Imap_Client::STATUS_MESSAGES |
                Horde_Imap_Client::STATUS_UIDVALIDITY |
                Horde_Imap_Client::STATUS_UIDNEXT;
        }

        try {
            $status = $imap->status($mbox, $status_flags);
        } catch (Horde_Imap_Client_Exception $e) {
            throw new Horde_ActiveSync_Exception($e);
        }
        if ($qresync) {
            $modseq = $status['highestmodseq'];
        } else {
            $modseq = $status[Horde_ActiveSync_Folder_Imap::HIGHESTMODSEQ] = 0;
        }
        $this->_logger->debug('IMAP status: ' . print_r($status, true));
        if ($qresync && $folder->modseq() > 0 && $folder->modseq() < $modseq) {
            // QRESYNC and have known changes
            $folder->checkValidity($status);
            $query = new Horde_Imap_Client_Fetch_Query();
            $query->modseq();
            $query->flags();
            try {
                $fetch_ret = $imap->fetch($mbox, $query, array(
                    'changedsince' => $folder->modseq()
                ));
            } catch (Horde_Imap_Client_Exception $e) {
                $this->_logger->err($e->getMessage());
                throw new Horde_Exception($e);
            }

            // Prepare the changes and flags array, ensuring no changes after
            // $modseq sneak in yet (they will be caught on the next PING or
            // SYNC) and no \deleted messages are included since EAS doesn't
            // support that flag.
            $changes = array();
            foreach ($fetch_ret as $uid => $data) {
                if ($data->getModSeq() <= $modseq &&
                    array_search('\deleted', $data->getFlags()) === false) {
                    $changes[] = $uid;
                    $flags[$uid] = array(
                        'read' => (array_search('\seen', $data->getFlags()) !== false) ? 1 : 0
                    );
                }
            }
            $folder->setChanges($changes, $flags);
            try {
                $deleted = $imap->vanished($mbox, $folder->modseq());
            } catch (Horde_Imap_Client_Excetion $e) {
                $this->_logger->err($e->getMessage());
                throw new Horde_Exception($e);
            }
            $folder->setRemoved($deleted->ids);
        } elseif ($folder->modseq() == 0) {
            // Initial priming or we don't support QRESYNC.
            // Either way, we need the full message uid list.
            $query = new Horde_Imap_Client_Search_Query();
            if (!empty($options['sincedate'])) {
                $query->dateSearch(
                    new Horde_Date($options['sincedate']),
                    Horde_Imap_Client_Search_Query::DATE_SINCE);
            }
            $query->flag(Horde_Imap_Client::FLAG_DELETED, false);
            $search_ret = $imap->search(
                $mbox,
                $query,
                array('results' => array(Horde_Imap_Client::SEARCH_RESULTS_MATCH, Horde_Imap_Client::SEARCH_RESULTS_MIN)));

            if ($qresync && $modseq > 0) {
                // Support QRESYNC, but this is initial priming - set the results.
                $folder->setChanges($search_ret['match']->ids);
                $status[Horde_ActiveSync_Folder_Imap::MINUID] = $search_ret['min'];
            } else {
                // No QRESYNC, perform some magic.
                $uids = $folder->messages();
                $deleted = array_diff($uids, $search_ret['match']->ids);
                $changed = $search_ret['match']->ids;
                // All changes in AS are a change in /seen. Get the flags only
                // for the messages we think have been changed and set them in
                // the folder. We don't care about what state the flag is in
                // on the device currently. We will assume that all flag changes
                // from the server are authoritative. There is no other sane way
                // to do this without killing the server.
                $query = new Horde_Imap_Client_Fetch_Query();
                $query->flags();
                $fetch_ret = $imap->fetch($mbox, $query, array('uids' => $search_ret['match']));
                $flags = array();
                foreach ($fetch_ret as $uid => $data) {
                    $flags[$uid] = array(
                        'read' => (array_search('\seen', $data->getFlags()) !== false) ? 1 : 0
                    );
                }
                $folder->setChanges($changed, $flags);
                $folder->setRemoved($deleted);
            }
        }
        $folder->setStatus($status);

        return $folder;
    }

    /**
     * Move a mail message
     *
     * @param string $folderid     The existing folderid.
     * @param string $id           The message UID of the message to move.
     * @param string $newfolderid  The folder id to move $id to.
     *
     * @return array  An hash of oldUID => newUIDs. If the server does not
     *                support UIDPLUS, then this is a best guess and might fail
     *                on busy folders.
     */
    public function moveMessage($folderid, $id, $newfolderid)
    {
        $imap = $this->_getImapOb();
        $from = new Horde_Imap_Client_Mailbox($folderid);
        $to = new Horde_Imap_Client_Mailbox($newfolderid);
        if (!$imap->queryCapability('UIDPLUS')) {
            $status = $imap->status($to, Horde_Imap_Client::STATUS_UIDNEXT);
            $uidnext = $status[Horde_Imap_Client::STATUS_UIDNEXT];
        }
        $ids = new Horde_Imap_Client_Ids(array($id));
        try {
            $copy_res = $imap->copy($from, $to, array('ids' => $ids, 'move' => true));
        } catch (Horde_Imap_Client_Exception $e) {
            throw new Horde_Exception($e);
        }
        if (is_array($copy_res)) {
            return $copy_res;
        }

        return array($id => $uidnext);
    }

    /**
     * Append a message to the specified mailbox. Used for saving sent email.
     *
     * @param string $folderid     The mailbox
     * @param string|stream $msg   The message
     * @param array $flags         Any flags to set on the newly appended message.
     *
     * @throws new Horde_Exception
     */
    public function appendMessage($folderid, $msg, array $flags = array())
    {
        $imap = $this->_getImapOb();
        $message = array(array('data' => $msg, 'flags' => $flags));
        $mbox = new Horde_Imap_Client_Mailbox($folderid);
        try {
            $imap->append($mbox, $message);
        } catch (Horde_Imap_Client_Exception $e) {
            throw new Horde_Exception($e);
        }
    }

    /**
     * Permanently delete a mail message.
     *
     * @param array $uids       The message UIDs
     * @param string $folderid  The folder id.
     */
    public function deleteMessages(array $uids, $folderid)
    {
        $imap = $this->_getImapOb();
        $mbox = new Horde_Imap_Client_Mailbox($folderid);
        $ids = new Horde_Imap_Client_Ids($uids);
        try {
            $imap->store($mbox, array(
                'ids' => $ids,
                'add' => array('\deleted'))
            );
            $imap->expunge($mbox, array('ids' => $ids));
        } catch (Horde_Imap_Client_Exception $e) {
            throw new Horde_Exception($e);
        }
    }

    /**
     * Return AS mail messages, from the given IMAP UIDs.
     *
     * @param string $folderid  The mailbox folder.
     * @param array $messages   List of IMAP message UIDs
     * @param array $options    Additional Options:
     *   -truncation:  (integer)  Truncate body of email to this length.
     *                            DEFAULT: false (No truncation).
     *
     * @return array  An array of Horde_ActiveSync_Message_Mail objects.
     */
    public function getMessages($folderid, array $messages, array $options = array())
    {
        $mbox = new Horde_Imap_Client_Mailbox($folderid);
        $results = $this->_getMailMessages($mbox, $messages);
        $ret = array();
        foreach ($results as $data) {
            $ret[] = $this->_buildMailMessage($mbox, $data, $options);
        }

        return $ret;
    }

    /**
     * Set the message's read status.
     *
     * @param string $mailbox  The mailbox name.
     * @param string $uid
     * @param integer $flag  Horde_ActiveSYnc_Message_Mail::FLAG_* constant
     */
    public function setReadFlag($mailbox, $uid, $flag)
    {
        $mbox = new Horde_Imap_Client_Mailbox($mailbox);
        $options = array(
            'ids' => new Horde_Imap_Client_Ids(array($uid)),
        );
        if ($flag == Horde_ActiveSync_Message_Mail::FLAG_READ_SEEN) {
            $options['add'] = array(Horde_Imap_Client::FLAG_SEEN);
        } else if ($flag == Horde_ActiveSync_Message_Mail::FLAG_READ_UNSEEN) {
            $options['remove'] = array(Horde_Imap_Client::FLAG_SEEN);
        }
        $imap = $this->_getImapOb();
        try {
            $imap->store($mbox, $options);
        } catch (Horde_Imap_Client_Exception $e) {
            throw new Horde_ActiveSync_Exception($e);
        }
    }

    /**
     * Return the content of a specific MIME part of the specified message.
     *
     * @param string $mailbox  The mailbox name.
     * @param string $uid      The message UID.
     * @param string $part     The MIME part identifier.
     *
     * @return Horde_Mime_Part  The attachment data
     */
    public function getAttachment($mailbox, $uid, $part)
    {
        $imap = $this->_getImapOb();
        $mbox = new Horde_Imap_Client_Mailbox($mailbox);
        $messages = $this->_getMailMessages($mbox, array($uid));
        $msg = new Horde_ActiveSync_Imap_Message(
            $imap, $mbox, $messages[$uid]);
        $part = $msg->getMimePart($part);

        return $part;
    }

    public function getImapMessage($mailbox, $uid)
    {
        $mbox = new Horde_Imap_Client_Mailbox($mailbox);
        $messages = $this->_getMailMessages($mbox, array($uid));
        $message = array_pop($messages);
                Horde::debug($message);
        return new Horde_ActiveSync_Imap_Message($this->_getImapOb(), $mbox, $message);
    }

    /**
     *
     * @return array An array of Horde_Imap_Client_Data_Fetch objects.
     */
    protected function _getMailMessages($mbox, array $uids)
    {
        $imap = $this->_getImapOb();
        $query = new Horde_Imap_Client_Fetch_Query();
        $query->structure();
        $query->flags();
        $ids = new Horde_Imap_Client_Ids($uids);
        try {
            return $imap->fetch($mbox, $query, array('ids' => $ids));
        } catch (Horde_Imap_Client_Exception $e) {
            throw new Horde_Exception($e);
        }
    }

    /**
     * Builds a proper AS mail message object.
     *
     * @param Horde_Imap_Client_Mailbox    $mbox  The IMAP mailbox.
     * @param Horde_Imap_Client_Data_Fetch $data  The fetch results.
     * @param array $options                      Additional Options:
     *   - truncation:  (integer) Truncate the message body to this length.
     *                  DEFAULT: No truncation.
     *
     * @return Horde_ActiveSync_Mail_Message
     * @throws Horde_Exception
     */
    protected function _buildMailMessage(
        Horde_Imap_Client_Mailbox $mbox,
        Horde_Imap_Client_Data_Fetch $data,
        $options = array())
    {
        $imap = $this->_getImapOb();

        // Get a message object.
        $imap_message = new Horde_ActiveSync_Imap_Message(
            $imap, $mbox, $data);

        $message_data = $imap_message->getMessageBody($options);
        $eas_message = new Horde_ActiveSync_Message_Mail();
        if ($message_data['charset'] != 'UTF-8') {
            $eas_message->body = Horde_String::convertCharset(
                $message_data['text'],
                $message_data['charset'],
                'UTF-8');
        } else {
            $eas_message->body = $message_data['text'];
        }
        $eas_message->bodysize = Horde_String::length($eas_message->body);
        $eas_message->bodytruncated = isset($options['truncation']) ? 1 : 0;
        $to = $imap_message->getToAddresses();
        $eas_message->to = implode(',', $to['to']);
        $eas_message->displayto = implode(',', $to['displayto']);
        $eas_message->from = $imap_message->getFromAddress();
        $eas_message->subject = $imap_message->getSubject();
        $eas_message->datereceived = $imap_message->getDate();
        $eas_message->read = $imap_message->getFlag(Horde_Imap_Client::FLAG_SEEN);

        // Attachments
        $eas_message->attachments = $imap_message->getAttachments();

        // @TODO: Parse out/detect at least meeting requests and notifications.
        $eas_message->messageclass = 'IPM.Note';

        return $eas_message;
    }

    /**
     * Helper to obtain a valid IMAP client. Can't inject it since the user
     * is not yet authenticated at the time of object creation.
     *
     * @return Horde_Imap_Client_Base
     */
    protected function _getImapOb()
    {
        return $this->_imap->getImapOb();
    }

}