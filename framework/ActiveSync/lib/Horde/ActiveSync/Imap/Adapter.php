<?php
/**
 * Horde_ActiveSync_Imap_Adapter::
 *
 * @license   http://www.horde.org/licenses/gpl GPLv2
 *            NOTE: According to sec. 8 of the GENERAL PUBLIC LICENSE (GPL),
 *            Version 2, the distribution of the Horde_ActiveSync module in or
 *            to the United States of America is excluded from the scope of this
 *            license.
 * @copyright 2012 Horde LLC (http://www.horde.org)
 * @author    Michael J Rubinsky <mrubinsk@horde.org>
 * @package   ActiveSync
 */
/**
 * Horde_ActiveSync_Imap_Adapter:: Contains methods for communicating with
 * Horde's Horde_Imap_Client library.
 *
 * @license   http://www.horde.org/licenses/gpl GPLv2
 *            NOTE: According to sec. 8 of the GENERAL PUBLIC LICENSE (GPL),
 *            Version 2, the distribution of the Horde_ActiveSync module in or
 *            to the United States of America is excluded from the scope of this
 *            license.
 * @copyright 2012 Horde LLC (http://www.horde.org)
 * @author    Michael J Rubinsky <mrubinsk@horde.org>
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
     * @throws Horde_ActiveSync_Exception, Horde_ActiveSync_Exception_FolderExists
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
     * @throws Horde_ActiveSync_Exception, Horde_ActiveSync_Exception_FolderGone
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

    /**
     * Rename a mailbox
     *
     * @param string $old  The old mailbox name.
     * @param string $new  The new mailbox name.
     *
     * @throws Horde_ActiveSync_Exception
     */
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
     * @throws Horde_ActiveSync_Exception, Horde_ActiveSync_Exception_FolderGone
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
     * @throws Horde_ActiveSync_Exception_FolderGone, Horde_ActiveSync_Exception
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
            $modseq = $status[Horde_ActiveSync_Folder_Imap::MODSEQ];
        } else {
            $modseq = $status[Horde_ActiveSync_Folder_Imap::MODSEQ] = 0;
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
                        $flags[$uid]['flagged'] = (array_search(Horde_Imap_Client::FLAG_FLAGGED, $data->getFlags()) !== false) ? 1 : 0;
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
                        $flags[$uid]['flagged'] = (array_search(Horde_Imap_Client::FLAG_FLAGGED, $data->getFlags()) !== false) ? 1 : 0;
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
     * @throws Horde_ActiveSync_Exception
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
     * @throws Horde_ActiveSync_Exception
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
     *   - mimesupport: (integer)  Indicates if MIME is supported or not.
     *                  Possible values: 0 - Not supported 1 - Only S/MIME or
     *                  2 - All MIME.
     *                  DEFAULT: 0 (No MIME support)
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
     *
     * @throws Horde_ActiveSync_Exception
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
     *
     * @throws Horde_ActiveSync_Exception
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
     *
     * @throws Horde_ActiveSync_Exception
     */
    public function getAttachment($mailbox, $uid, $part)
    {
        $imap = $this->_getImapOb();
        $mbox = new Horde_Imap_Client_Mailbox($mailbox);
        $messages = $this->_getMailMessages($mbox, array($uid));
        if (empty($messages[$uid])) {
            throw new Horde_ActiveSync_Exception('Message Gone');
        }
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
        $res = array();
        foreach ($messages as $id => $message) {
            $res[$id] = new Horde_ActiveSync_Imap_Message($this->_getImapOb(), $mbox, $message);
        }

        return $res;
    }

    /**
     * Perform a search from a search mailbox request.
     *
     * @param array $query  The query array.
     *
     * @return array  An array of 'uniqueid', 'searchfolderid' hashes.
     */
    public function queryMailbox($query)
    {
        return $this->_doQuery($query['query'], $query['range']);
    }

    /**
     * Perform an IMAP search based on a SEARCH request.
     *
     * @param array $query  The search query.
     *
     * @return array  The results array containing an array of hashes:
     *   'uniqueid' => [The unique identifier of the result]
     *   'searchfolderid' => [The mailbox name that this result comes from]
     *
     * @throws Horde_ActiveSync_Exception
     */
    protected function _doQuery(array $query, $range = null)
    {
        $imap_query = new Horde_Imap_Client_Search_Query();
        $mboxes = array();
        $results = array();
        foreach ($query as $q) {
            switch ($q['op']) {
            case Horde_ActiveSync_Request_Search::SEARCH_AND:
                return $this->_doQuery(array($q['value']), $range);
            default:
                foreach ($q as $key => $value) {
                    switch ($key) {
                    case 'FolderType':
                        if ($value != Horde_ActiveSync::CLASS_EMAIL) {
                            throw new Horde_ActiveSync_Exception('Only Email folders are supported.');
                        }
                        break;
                    case 'FolderId':
                        $mboxes[] = new Horde_Imap_Client_Mailbox($value);
                        break;
                    case Horde_ActiveSync_Message_Mail::POOMMAIL_DATERECEIVED:
                        if ($q['op'] == Horde_ActiveSync_Request_Search::SEARCH_GREATERTHAN) {
                            $query_range = Horde_Imap_Client_Search_Query::DATE_SINCE;
                        } elseif ($q['op'] == Horde_ActiveSync_Request_Search::SEARCH_LESSTHAN) {
                            $query_range = Horde_Imap_Client_Search_Query::DATE_BEFORE;
                        } else {
                            $query_range = Horde_Imap_Client_Search_Query::DATE_ON;
                        }
                        $imap_query->dateSearch($value, $query_range);
                        break;
                    case Horde_ActiveSync_Request_Search::SEARCH_FREETEXT:
                        $imap_query->text($value, false);
                        break;
                    case 'subquery':
                        $imap_query->andSearch(array($this->_buildSubQuery($value)));
                    }
                }
            }
        }
        if (empty($mboxes)) {
            foreach ($this->getMailboxes() as $mailbox) {
                $mboxes[] = $mailbox['ob'];
            }
        }
        foreach ($mboxes as $mbox) {
            try {
                $search_res = $this->_getImapOb()->search($mbox, $imap_query);
            } catch (Horde_Imap_Client_Exception $e) {
                throw new Horde_ActiveSync_Exception($e);
            }
            if ($search_res['count'] == 0) {
                continue;
            }

            $ids = $search_res['match']->ids;
            foreach ($ids as $id) {
                $results[] = array('uniqueid' => $mbox->utf8 . ':' . $id, 'searchfolderid' => $mbox->utf8);
            }
            if (!empty($range)) {
                preg_match('/(.*)\-(.*)/', $range, $matches);
                $return_count = $matches[2] - $matches[1];
                $results = array_slice($results, $matches[1], $return_count + 1, true);
            }
        }

        return $results;
    }

    /**
     * Helper to build a subquery
     *
     * @param array $query  A subquery array.
     *
     * @return Horde_Imap_Client_Search_Query  The query object.
     */
    protected function _buildSubQuery(array $query)
    {
        $imap_query = new Horde_Imap_Client_Search_Query();
        foreach ($query as $q) {
            foreach ($q['value'] as $type => $value) {
                switch ($type) {
                case Horde_ActiveSync_Message_Mail::POOMMAIL_DATERECEIVED:
                    if ($q['op'] == Horde_ActiveSync_Request_Search::SEARCH_GREATERTHAN) {
                        $range = Horde_Imap_Client_Search_Query::DATE_SINCE;
                    } elseif ($q['op'] == Horde_ActiveSync_Request_Search::SEARCH_LESSTHAN) {
                        $range = Horde_Imap_Client_Search_Query::DATE_BEFORE;
                    } else {
                        $range = Horde_Imap_Client_Search_Query::DATE_ON;
                    }
                    $imap_query->dateSearch($value, $range);
                    break;
                case Horde_ActiveSync_Request_Search::SEARCH_FREETEXT:
                    $imap_query->text($value);
                    break;
                }
            }
        }

        return $imap_query;
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
     * @return Horde_Imap_Fetch_Results  The results.
     * @throws Horde_ActiveSync_Exception
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
     *   - mimesupport: (integer)  Indicates if MIME is supported or not.
     *                  Possible values: 0 - Not supported 1 - Only S/MIME or
     *                  2 - All MIME.
     *                  DEFAULT: 0 (No MIME support)
     *   - protocolversion: (float)  The EAS protocol version to support.
     *                      DEFAULT: 2.5
     *
     * @return Horde_ActiveSync_Message_Mail  The message object suitable for
     *                                        streaming to the device.
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

        // Build To: data
        $to = $imap_message->getToAddresses();
        $eas_message->to = implode(',', $to['to']);
        $eas_message->displayto = implode(',', $to['displayto']);
        if (empty($eas_message->displayto)) {
            $eas_message->displayto = $eas_message->to;
        }

        // Fill in other header data
        $eas_message->from = $imap_message->getFromAddress();
        $eas_message->subject = $imap_message->getSubject();
        $eas_message->datereceived = $imap_message->getDate();
        $eas_message->read = $imap_message->getFlag(Horde_Imap_Client::FLAG_SEEN);
        $eas_message->cc = $imap_message->getCc();
        $eas_message->reply_to = $imap_message->getReplyTo();

        // Default to IPM.Note - may change below depending on message content.
        $eas_message->messageclass = 'IPM.Note';

        if ($version == Horde_ActiveSync::VERSION_TWOFIVE) {
            // EAS 2.5 behavior or no bodyprefs sent
            $message_body_data = $imap_message->getMessageBodyData($options);
            if ($message_body_data['plain']['charset'] != 'UTF-8') {
                $eas_message->body = Horde_String::convertCharset(
                    $message_body_data['plain']['body'],
                    $message_body_data['plain']['charset'],
                    'UTF-8');
            } else {
                $eas_message->body = $message_body_data['plain']['body'];
            }
            $eas_message->bodysize = Horde_String::length($eas_message->body);
            $eas_message->bodytruncated = $message_body_data['plain']['truncated'];
            $eas_message->attachments = $imap_message->getAttachments($version);
        } else {
            // Determine the message's native type.
            $message_body_data = $imap_message->getMessageBodyData($options);
            if (!empty($message_body_data['html'])) {
                $eas_message->airsyncbasenativebodytype = Horde_ActiveSync::BODYPREF_TYPE_HTML;
            } else {
                $eas_message->airsyncbasenativebodytype = Horde_ActiveSync::BODYPREF_TYPE_PLAIN;
            }
            $haveData = false;
            $airsync_body = new Horde_ActiveSync_Message_AirSyncBaseBody();

            if (isset($options['bodyprefs'][Horde_ActiveSync::BODYPREF_TYPE_MIME]) &&
                ($options['mimesupport'] == Horde_ActiveSync::MIME_SUPPORT_ALL ||
                 ($options['mimesupport'] == Horde_ActiveSync::MIME_SUPPORT_SMIME &&
                  $imap_message->isSigned()))) {

                $this->_logger->debug('Sending MIME Message.');
                // ActiveSync *REQUIRES* all data sent to be in UTF-8, so we
                // must convert the body parts to UTF-8. Unfortunately if the
                // email is signed (or encrypted for that matter) we can't
                // alter the data in anyway or the signature will not be
                // verified, so we fetch the entire message and hope for the best.
                if (!$imap_message->isSigned()) {

                    // Sending a non-signed MIME message, start building the
                    // UTF-8 converted structure.
                    $mime = new Horde_Mime_Part();
                    $mime->setType('multipart/alternative');

                    // Populate the text/plain part if we have one.
                    if (!empty($message_body_data['plain'])) {
                        $plain_mime = new Horde_Mime_Part();
                        $plain_mime->setType('text/plain');
                        $message_body_data['plain']['body'] = Horde_String::convertCharset(
                            $message_body_data['plain']['body'],
                            $message_body_data['plain']['charset'],
                            'UTF-8'
                        );
                        $plain_mime->setContents($message_body_data['plain']['body']);
                        $plain_mime->setCharset('UTF-8');
                        $mime->addPart($plain_mime);
                    }

                    // Populate the text/html part if we have one.
                    if (!empty($message_body_data['html'])) {
                        $html_mime = new Horde_Mime_Part();
                        $html_mime->setType('text/html');
                        $html_mime->setContents($message_body_data['html']['body']);
                        $html_mime->setCharset($message_body_data['html']['charset']);
                        $mime->addPart($html_mime);
                    }

                    // If we have attachments, create a multipart/mixed wrapper.
                    if ($imap_message->hasAttachments()) {
                        $base = new Horde_Mime_Part();
                        $base->setType('multipart/mixed');
                        $base->addPart($mime);
                        $atc = $imap_message->getAttachmentsMimeParts();
                        foreach ($atc as $atc_part) {
                            $base->addPart($atc_part);
                        }
                    } else {
                        $base = $mime;
                    }

                    // Populate the EAS body structure with the MIME data.
                    $airsync_body->data = $base->toString(array(
                        'headers' => true,
                        'stream' => true)
                    );
                    $airsync_body->estimateddatasize = $base->getBytes();
                } else {
                    // Signed/Encrypted message - can't mess with it at all.
                    $raw = new Horde_ActiveSync_Rfc822($imap_message->getFullMsg(true));
                    $airsync_body->estimateddatasize = $raw->getBytes();
                    $airsync_body->data = $raw->getString();
                    $eas_message->messageclass = 'IPM.Note.SMIME.MultipartSigned';
                }
                $airsync_body->type = Horde_ActiveSync::BODYPREF_TYPE_MIME;

                // MIME Truncation
                if (!empty($options['bodyprefs'][Horde_ActiveSync::BODYPREF_TYPE_MIME]['truncationsize']) &&
                    $airsync_body->estimateddatasize > $options['bodyprefs'][Horde_ActiveSync::BODYPREF_TYPE_MIME]['truncationsize']) {
                    ftruncate($airsync_body->data, $options['bodyprefs'][Horde_ActiveSync::BODYPREF_TYPE_MIME]['truncationsize']);
                    $airsync_body->truncated = '1';
                } else {
                    $airsync_body->truncated = '0';
                }
                $eas_message->airsyncbasebody = $airsync_body;
                $haveData = true;
            } elseif (isset($options['bodyprefs'][Horde_ActiveSync::BODYPREF_TYPE_HTML]) ||
                      isset($options['bodyprefs'][Horde_ActiveSync::BODYPREF_TYPE_RTF])) {

                // Sending non MIME encoded HTML message text.
                $this->_logger->debug('Sending HTML Message.');
                $haveData = true;
                if (empty($message_body_data['html'])) {
                    $airsync_body->type = Horde_ActiveSync::BODYPREF_TYPE_PLAIN;
                    $message_body_data['html'] = array(
                        'body' => Horde_String::convertCharset($message_body_data['plain']['body'], $message_body_data['plain']['charset'], 'UTF-8'),
                        'estimated_size' => $message_body_data['plain']['size'],
                        'truncated' => $message_body_data['plain']['truncated'],
                        'body' => $message_body_data['plain']['body']
                    );
                } else {
                    $airsync_body->type = Horde_ActiveSync::BODYPREF_TYPE_HTML;
                    $message_body_data['html']['body'] = Horde_String::convertCharset(
                        $message_body_data['html']['body'],
                        $message_body_data['html']['charset'],
                        'UTF-8'
                    );
                }
                $airsync_body->estimateddatasize = $message_body_data['html']['estimated_size'];
                $airsync_body->truncated = $message_body_data['html']['truncated'];
                $airsync_body->data = $message_body_data['html']['body'];
                $eas_message->airsyncbasebody = $airsync_body;
                $eas_message->airsyncbaseattachments = $imap_message->getAttachments($version);
            } elseif (isset($options['bodyprefs'][Horde_ActiveSync::BODYPREF_TYPE_PLAIN]) || !$haveData) {

                // Non MIME encoded plaintext
                $this->_logger->debug('Sending PLAINTEXT Message.');

                $message_body_data['plain']['body'] = Horde_String::convertCharset(
                    $message_body_data['plain']['body'],
                    $message_body_data['plain']['charset'],
                    'UTF-8'
                );
                $airsync_body->estimateddatasize = $message_body_data['plain']['size'];
                $airsync_body->truncated = $message_body_data['plain']['truncated'];
                $airsync_body->data = $message_body_data['plain']['body'];
                $airsync_body->type = Horde_ActiveSync::BODYPREF_TYPE_PLAIN;
                $eas_message->airsyncbasebody = $airsync_body;
                $eas_message->airsyncbaseattachments = $imap_message->getAttachments($version);
            }
        }

        // Check for special message types.
        $part = $imap_message->getStructure();
        if ($part->getType() == 'multipart/report') {
            $ids = array_keys($imap_message->contentTypeMap());
            reset($ids);
            $part1_id = next($ids);
            $part2_id = Horde_Mime::mimeIdArithmetic($part1_id, 'next');
            $lines = explode(chr(13), $imap_message->getBodyPart($part2_id, array('decode' => true)));
            switch ($part->getContentTypeParameter('report-type')) {
            case 'delivery-status':
                foreach ($lines as $line) {
                    if (strpos(trim($line), 'Action:') === 0) {
                        switch (trim(substr(trim($line), 7))) {
                        case 'failed':
                            $eas_message->messageclass = 'REPORT.IPM.NOTE.NDR';
                            break 2;
                        case 'delayed':
                            $eas_message->messageclass = 'REPORT.IPM.NOTE.DELAYED';
                            break 2;
                        case 'delivered':
                            $eas_message->messageclass = 'REPORT.IPM.NOTE.DR';
                            break 2;
                        }
                    }
                }
                break;
            case 'disposition-notification':
                foreach ($lines as $line) {
                    if (strpos(trim($line), 'Disposition:') === 0) {
                        if (strpos($line, 'displayed') !== false) {
                            $eas_message->messageclass = 'REPORT.IPM.NOTE.IPNRN';
                        } elseif (strpos($line, 'deleted') !== false) {
                            $eas_message->messageclass = 'REPORT.IPM.NOTE.IPNNRN';
                        }
                        break;
                    }
                }
            }
        }

        // Check for meeting requests and POOMMAIL_FLAG data
        if ($this->version >= Horde_ActiveSync::VERSION_TWELVE) {
            $eas_message->contentclass = 'urn:content-classes:message';
            if ($mime_part = $imap_message->hasiCalendar()) {
                $data = $mime_part->getContents();
                $vCal = new Horde_Icalendar();
                if ($vCal->parsevCalendar($data, 'VCALENDAR', $mime_part->getCharset())) {
                    $eas_message->contentclass = 'urn:content-classes:calendarmessage';
                    switch ($vCal->getAttribute('METHOD')) {
                    case 'REQUEST':
                    case 'PUBLISH':
                        $eas_message->messageclass = 'IPM.Schedule.Meeting.Request';
                        $mtg = new Horde_ActiveSync_Message_MeetingRequest();
                        $mtg->fromvEvent($vCal);
                        $eas_message->meetingrequest = $mtg;
                        break;
                    case 'REPLY':
                        try {
                            $reply_status = $this->_getiTipStatus($vCal, $eas_message->from);
                            switch ($reply_status) {
                            case 'ACCEPTED':
                                $eas_message->messageclass = 'IPM.Schedule.Meeting.Resp.Pos';
                                break;
                            case 'DECLINED':
                                $eas_message->messageclass = 'IPM.Schedule.Meeting.Resp.Neg';
                                break;
                            case 'TENTATIVE':
                                $eas_message->messageclass = 'IPM.Schedule.Meeting.Resp.Tent';
                            }
                            $mtg = new Horde_ActiveSync_Message_MeetingRequest();
                            $mtg->fromvEvent($vCal);
                            $eas_message->meetingrequest = $mtg;
                        } catch (Horde_ActiveSync_Exception $e) {
                            $this->_logger->err($e->getMessage());
                        }
                    }
                }
            }

            $poommail_flag = new Horde_ActiveSync_Message_Flag();
            $poommail_flag->subject = $imap_message->getSubject();
            $poommail_flag->flagstatus = $imap_message->getFlag(Horde_Imap_Client::FLAG_FLAGGED)
                ? Horde_ActiveSync_Message_Flag::FLAG_STATUS_ACTIVE
                : Horde_ActiveSync_Message_Flag::FLAG_STATUS_CLEAR;
            $poommail_flag->flagtype = Horde_Imap_Client::FLAG_FLAGGED;
            $eas_message->flag = $poommail_flag;
        }

        return $eas_message;
    }

    /**
     * Return the attendee participation status.
     *
     * @param Horde_Icalendar
     * @throws Horde_ActiveSync_Exception
     */
    protected function _getiTipStatus($vCal, $from)
    {
        foreach ($vCal->getComponents() as $key => $component) {
            switch ($component->getType()) {
            case 'vEvent':
                try {
                    $atname = $component->getAttribute('ATTENDEE');
                } catch (Horde_Icalendar_Exception $e) {
                    throw new Horde_ActiveSync_Exception($e);
                }
                // EAS only allows a single name per response.
                if (is_array($atname)) {
                    $atname = current($atname);
                }

                try {
                    $version = $component->getAttribute('VERSION');
                } catch (Horde_Icalendar_Exception $e) {
                    throw new Horde_ActiveSync_Exception($e);
                }
                if ($version < 2) {
                    $addr = new Horde_Mail_Rfc822_Address($atname);
                    if (!$addr->isValid) {
                        throw new Horde_ActiveSync_Exception('Invalid Email Address');
                    }
                    $attendee = Horde_String::lower($addr->bare_address);
                } else {
                    $attendee = str_replace('mailto:', '', Horde_String::lower($atname));
                }

                // Require the sender to be the same as the attendee.
                $addr = new Horde_Mail_Rfc822_Address($from);
                $from = Horde_String::lower($addr->bare_address);
                if ($from != $attendee) {
                    throw new Horde_ActiveSync_Exception(sprintf(
                        'Unmatched sender and attendee: %s, %s',
                        $from,
                        $attendee));
                }
                try {
                    $atparams = $component->getAttribute('ATTENDEE', true);
                } catch (Horde_Icalendar_Exception $e) {
                    throw new Horde_ActiveSync_Exception($e);
                }

                if (!is_array($atparams)) {
                    throw new Horde_Icalendar_Exception('Unexpected value');
                }

                return $atparams[0]['PARTSTAT'];
            }
        }
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