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
     *   - factory: (Horde_ActiveSync_Interface_ImapFactory) Factory object
     *              DEFAULT: none - REQUIRED
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
     * Create a new mailbox on the server, and subscribe to it.
     *
     * @param string $name  The new mailbox name.
     *
     * @throws Horde_ActiveSync_Exception
     */
    public function createMailbox($name)
    {
        $mbox = new Horde_Imap_Client_Mailbox($name);
        $imap = $this->_getImapOb();
        try {
            $imap->createMailbox($mbox);
            $imap->subscribeMailbox($mbox, true);
        } catch (Horde_Imap_Client_Exception $e) {
            if ($e->getCode() == Horde_Imap_Client_Exception::ALREADYEXISTS) {
                throw new Horde_ActiveSync_Exception_FolderExists();
            }
            throw new Horde_ActiveSync_Exception($e);
        }
    }

    /**
     * Delete a mailbox
     *
     * @param string $name  The mailbox name to delete.
     *
     * @throws Horde_ActiveSync_Exception
     */
    public function deleteMailbox($name)
    {
        $mbox = new Horde_Imap_Client_Mailbox($name);
        $imap = $this->_getImapOb();
        try {
            $imap->deleteMailbox($mbox);
        } catch (Horde_Imap_Client_Exception $e) {
            if ($e->getCode() == Horde_Imap_Client_Exception::NONEXISTENT) {
                throw new Horde_ActiveSync_Exception_FolderGone();
            }
            throw new Horde_ActiveSync_Exception($e);
        }
    }

    public function renameMailbox($old, $new)
    {
        $imap = $this->_getImapOb();
        try {
            $imap->renameMailbox(
                new Horde_Imap_Client_Mailbox($old),
                new Horde_Imap_Client_Mailbox($new)
            );
        } catch (Horde_Imap_Client_Exception $e) {
            throw new Horde_ActiveSync_Exception($e);
        }
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
            // See if the folder disappeared.
            if (!$this->_mailboxExists($mbox->utf8)) {
                throw new Horde_ActiveSync_Exception_FolderGone();
            }
            throw new Horde_ActiveSync_Exception($e);
        }

        if ($folder->uidnext() < $status['uidnext']) {
            return true;
        }

        return false;
    }

    protected function _mailboxExists($mbox)
    {
        $mailboxes = $this->_imap->getMailboxes(true);
        foreach ($mailboxes as $mailbox) {
            if ($mailbox['ob']->utf8 == $mbox) {
                return true;
            }
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
     *  - protocolversion: (float)  EAS protocol version to support.
     *                     DEFAULT: none REQUIRED
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
            // If we can't status the mailbox, assume it's gone.
            throw new Horde_ActiveSync_Exception_FolderGone($e);
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
                throw new Horde_ActiveSync_Exception($e);
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
                        'read' => (array_search(Horde_Imap_Client::FLAG_SEEN, $data->getFlags()) !== false) ? 1 : 0
                    );
                    if (($options['protocolversion']) > Horde_ActiveSync::VERSION_TWOFIVE) {
                        $flags[$uid]['followup'] = (array_search(Horde_Imap_Client::FLAG_FLAGGED, $data->getFlags()) !== false ? 1 : 0;
                    }
                }
            }
            $folder->setChanges($changes, $flags);
            try {
                $deleted = $imap->vanished($mbox, $folder->modseq());
            } catch (Horde_Imap_Client_Excetion $e) {
                $this->_logger->err($e->getMessage());
                throw new Horde_ActiveSync_Exception($e);
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
                // All changes in AS are a change in flags. Get the flags only
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
                        'read' => (array_search(Horde_Imap_Client::FLAG_SEEN, $data->getFlags()) !== false) ? 1 : 0
                    );
                    if (($options['protocolversion']) > Horde_ActiveSync::VERSION_TWOFIVE) {
                        $flags[$uid]['followup'] = (array_search(Horde_Imap_Client::FLAG_FLAGGED, $data->getFlags()) !== false ? 1 : 0;
                    }
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
     * @param array $ids           The message UIDs of the messages to move.
     * @param string $newfolderid  The folder id to move $id to.
     *
     * @return array  An hash of oldUID => newUID. If the server does not
     *                support UIDPLUS, then this is a best guess and might fail
     *                on busy folders.
     *
     * @throws Horde_ActiveSync_Exception, Horde_ActiveSync_Exception_FolderGone
     */
    public function moveMessage($folderid, array $ids, $newfolderid)
    {
        $imap = $this->_getImapOb();
        $from = new Horde_Imap_Client_Mailbox($folderid);
        $to = new Horde_Imap_Client_Mailbox($newfolderid);
        if (!$imap->queryCapability('UIDPLUS')) {
            $status = $imap->status($to, Horde_Imap_Client::STATUS_UIDNEXT);
            $uidnext = $status[Horde_Imap_Client::STATUS_UIDNEXT];
        }
        $ids = new Horde_Imap_Client_Ids($ids);
        try {
            $copy_res = $imap->copy($from, $to, array('ids' => $ids, 'move' => true));
        } catch (Horde_Imap_Client_Exception $e) {
            throw new Horde_ActiveSync_Exception($e);
        }
        if (is_array($copy_res)) {
            return $copy_res;
        }
        $ret = array();
        foreach ($ids as $id) {
            $ret[$id] = $uidnext++;
        }

        return $ret;
    }

    /**
     * Append a message to the specified mailbox. Used for saving sent email.
     *
     * @param string $folderid     The mailbox
     * @param string|stream $msg   The message
     * @param array $flags         Any flags to set on the newly appended message.
     *
     * @throws new Horde_ActiveSync_Exception, Horde_ActiveSync_Exception_FolderGone
     */
    public function appendMessage($folderid, $msg, array $flags = array())
    {
        $imap = $this->_getImapOb();
        $message = array(array('data' => $msg, 'flags' => $flags));
        $mbox = new Horde_Imap_Client_Mailbox($folderid);
        try {
            $imap->append($mbox, $message);
        } catch (Horde_Imap_Client_Exception $e) {
            if (!$this->_mailboxExists($folderid)) {
                throw new Horde_ActiveSync_Exception_FolderGone();
            }
            throw new Horde_ActiveSync_Exception($e);
        }
    }

    /**
     * Permanently delete a mail message.
     *
     * @param array $uids       The message UIDs
     * @param string $folderid  The folder id.
     *
     * @throws Horde_ActiveSync_Exception, Horde_ActiveSync_Exception_FolderGone
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
            throw new Horde_ActiveSync_Exception($e);
        }
    }

    /**
     * Return AS mail messages, from the given IMAP UIDs.
     *
     * @param string $folderid  The mailbox folder.
     * @param array $messages   List of IMAP message UIDs
     * @param array $options    Additional Options:
     *   - truncation: (integer) The truncation constant, if sent from device.
     *                 DEFAULT: false (No truncation).
     *   - bodyprefs:  (array)  The bodypref settings, if sent from device.
     *                 DEFAULT: none (No body prefs sent, or enforced).
     *   - protocolversion: (float)  The EAS protocol version to support.
     *                      DEFAULT: 2.5
     *
     * @return array  An array of Horde_ActiveSync_Message_Mail objects.
     */
    public function getMessages($folderid, array $messages, array $options = array())
    {
        $mbox = new Horde_Imap_Client_Mailbox($folderid);
        $results = $this->_getMailMessages($mbox, $messages);
        $ret = array();
        if (!empty($options['truncation'])) {
            $options['truncation'] = Horde_ActiveSync::getTruncSize($options['truncation']);
        }
        foreach ($results as $data) {
            $ret[] = $this->_buildMailMessage($mbox, $data, $options);
        }

        return $ret;
    }

    /**
     * Set a POOMMAIL_FLAG on a mail message. This method differs from
     * setReadFlag() in that it is passed a Flag object, which contains
     * other data beside the seen status. Used for setting flagged for followup
     * and potentially creating tasks based on the email.
     *
     * @param string $mailbox                      The mailbox name.
     * @param string $uid                          The message uid.
     * @param Horde_ActiveSync_Message_Flag $flag  The flag
     */
    public function setMessageFlag($mailbox, $uid, $flag)
    {
        // There is no standard in EAS for the name of flags, so it is impossible
        // to map flagtype to an actual message flag. Until a better solution
        // is thought of, just always use \flagged. There is also no meaning
        // of a "completed" flag/task in IMAP email, so if it's not active,
        // clear the flag.
        $mbox = new Horde_Imap_Client_Mailbox($mailbox);
        $options = array(
            'ids' => new Horde_Imap_Client_Ids(array($uid)),
        );
        switch ($flag->flagstatus) {
        case Horde_ActiveSync_Message_Flag::FLAG_STATUS_ACTIVE:
            $options['add'] = array(Horde_Imap_Client::FLAG_FLAGGED);
            break;
        default:
            $options['remove'] = array(Horde_Imap_Client::FLAG_FLAGGED);
        }
        $imap = $this->_getImapOb();
        try {
            $imap->store($mbox, $options);
        } catch (Horde_Imap_Client_Exception $e) {
            throw new Horde_ActiveSync_Exception($e);
        }
    }

    /**
     * Set the message's read status.
     *
     * @param string $mailbox  The mailbox name.
     * @param string $uid      The message uid.
     * @param integer $flag  Horde_ActiveSync_Message_Mail::FLAG_* constant
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

    /**
     * Return a Horde_ActiveSync_Imap_Message object for the requested uid.
     *
     * @param string $mailbox     The mailbox name.
     * @param array|integer $uid  The message uid.
     * @param array $options      Additional options:
     *     - headers: (boolean) Also fetch the message headers if this is true.
     *                DEFAULT: false (Do not fetch headers).
     *
     * @return array  An array of Horde_ActiveSync_Imap_Message objects.
     */
    public function getImapMessage($mailbox, $uid, array $options = array())
    {
        if (!is_array($uid)) {
            $uid = array($uid);
        }
        $mbox = new Horde_Imap_Client_Mailbox($mailbox);
        $messages = $this->_getMailMessages($mbox, $uid, $options);
        foreach ($messages as $id => $message) {
            $res[$id] = new Horde_ActiveSync_Imap_Message($this->_getImapOb(), $mbox, $message);
        }

        return $res;
    }

    /**
     *
     * @param Horde_Imap_Client_Mailbox $mbox   The mailbox
     * @param array $uids                       An array of message uids
     * @param array $options                    An options array
     *   - headers: (boolean)  Fetch header text if true.
     *              DEFAULT: false (Do not fetch header text).
     *   - structure: (boolean) Fetch message structure.
     *            DEFAULT: true (Fetch message structure).
     *   - flags: (boolean) Fetch messagge flags.
     *            DEFAULT: true (Fetch message flags).
     *
     * @return array An array of Horde_Imap_Client_Data_Fetch objects.
     */
    protected function _getMailMessages(
        Horde_Imap_Client_Mailbox $mbox, array $uids, array $options = array())
    {
        $options = array_merge(
            array(
                'headers' => false,
                'structure' => true,
                'flags' => true),
            $options
        );

        $imap = $this->_getImapOb();
        $query = new Horde_Imap_Client_Fetch_Query();
        if ($options['structure']) {
            $query->structure();
        }
        if ($options['flags']) {
            $query->flags();
        }
        if (!empty($options['headers'])) {
            $query->headerText();
        }
        $ids = new Horde_Imap_Client_Ids($uids);
        try {
            return $imap->fetch($mbox, $query, array('ids' => $ids));
        } catch (Horde_Imap_Client_Exception $e) {
            throw new Horde_ActiveSync_Exception($e);
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
     *   - bodyprefs: (array)  Bodyprefs, if sent from device.
     *                DEFAULT: none (No body prefs sent or enforced).
     *   - mimesupport: (integer)  Indicates if MIME is supported (1) or not (0)
     *                  DEFAULT: 0 (No MIME support)
     *   - protocolversion: (float)  The EAS protocol version to support.
     *                      DEFAULT: 2.5
     *
     * @return Horde_ActiveSync_Message_Mail  The message object suitable for
     *                                        streaming to the device.
     * @throws Horde_Exception
     */
    protected function _buildMailMessage(
        Horde_Imap_Client_Mailbox $mbox,
        Horde_Imap_Client_Data_Fetch $data,
        $options = array())
    {
        $imap = $this->_getImapOb();

        $version = empty($options['protocolversion']) ?
            Horde_ActiveSync::VERSION_TWOFIVE :
            $options['protocolversion'];

        $imap_message = new Horde_ActiveSync_Imap_Message($imap, $mbox, $data);
        $eas_message = new Horde_ActiveSync_Message_Mail(array('protocolversion' => $version));
        if ($version == Horde_ActiveSync::VERSION_TWOFIVE || empty($options['bodyprefs'])) {
            // EAS 2.5 behavior or no bodyprefs sent
            $message_data = $imap_message->getMessageBodyData($options);
            if ($message_data['plain']['charset'] != 'UTF-8') {
                $eas_message->body = Horde_String::convertCharset(
                    $message_data['plain']['body'],
                    $message_data['plain']['charset'],
                    'UTF-8');
            } else {
                $eas_message->body = $message_data['plain']['body'];
            }
            $eas_message->bodysize = Horde_String::length($eas_message->body); // @TODO: Should this be full or sent?
            $eas_message->bodytruncated = $message_data['plain']['truncated'];
            $eas_message->attachments = $imap_message->getAttachments();
        } else {
            // Body pref EAS >= 12.0
            $message_data = $imap_message->getMessageBodyData($options);
            if (!empty($message_data['html'])) {
                $eas_message->airsyncbasenativebodytype = Horde_ActiveSync::BODYPREF_TYPE_HTML;
            } else {
                $eas_message->airsyncbasenativebodytype = Horde_ActiveSync::BODYPREF_TYPE_PLAIN;
            }

            // TODO s/MIME

            if (isset($options['bodyprefs'][Horde_ActiveSync::BODYPREF_TYPE_HTML])) {
                // HTML
                $airsync_body = new Horde_ActiveSync_Message_AirSyncBaseBody();
                if (empty($message_data['html'])) {
                    $airsync_body->type = Horde_ActiveSync::BODYPREF_TYPE_PLAIN;
                    $message_data['html'] = array(
                        'body' => $message_data['plain']['body'],
                        'estimated_size' => $message_data['plain']['size'],
                        'truncated' => $message_data['plain']['truncated'],
                        'body' => $message_data['plain']['body'],
                        'charset' => $message_data['plain']['charset']
                    );
                } else {
                    $airsync_body->type = Horde_ActiveSync::BODYPREF_TYPE_HTML;
                }
                if ($message_data['html']['charset'] != 'UTF-8') {
                    $message_data['html']['body'] = Horde_String::convertCharset(
                        $message_data['html']['body'],
                        $message_data['html']['charset']
                    );
                }
                $airsync_body->estimateddatasize = $message_data['html']['estimated_size'];
                $airsync_body->truncated = $message_data['html']['truncated'];
                $airsync_body->data = $message_data['html']['body'];
                $eas_message->airsyncbasebody = $airsync_body;
            }
        }

        if ($version >= Horde_ActiveSync::VERSION_TWELVE) {
            $eas_message->contentclass = 'urn:content-classes:message';
            $poommail_flag = new Horde_ActiveSync_Message_Flag();
            $poommail_flag->flagstatus = 0; // @TODO
            $eas_message->flag = $poommail_flag;
            $eas_message->airsyncbaseattachments = $imap_message->getAttachments(array('protocolversion' => $version));
        }
        $to = $imap_message->getToAddresses();
        $eas_message->to = implode(',', $to['to']);
        $eas_message->displayto = implode(',', $to['displayto']);
        $eas_message->from = $imap_message->getFromAddress();
        $eas_message->subject = $imap_message->getSubject();
        $eas_message->datereceived = $imap_message->getDate();
        $eas_message->read = $imap_message->getFlag(Horde_Imap_Client::FLAG_SEEN);

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