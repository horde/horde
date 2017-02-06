<?php
/**
 * @license   http://www.horde.org/licenses/gpl GPLv2
 *            NOTE: According to sec. 8 of the GENERAL PUBLIC LICENSE (GPL),
 *            Version 2, the distribution of the Horde_ActiveSync module in or
 *            to the United States of America is excluded from the scope of this
 *            license.
 * @copyright 2012-2017 Horde LLC (http://www.horde.org)
 * @author    Michael J Rubinsky <mrubinsk@horde.org>
 * @package   ActiveSync
 */

/**
 * Horde_ActiveSync_Imap_Adapter contains methods for communicating with
 * Horde's Horde_Imap_Client library.
 *
 * @license   http://www.horde.org/licenses/gpl GPLv2
 *            NOTE: According to sec. 8 of the GENERAL PUBLIC LICENSE (GPL),
 *            Version 2, the distribution of the Horde_ActiveSync module in or
 *            to the United States of America is excluded from the scope of this
 *            license.
 * @copyright 2012-2017 Horde LLC (http://www.horde.org)
 * @author    Michael J Rubinsky <mrubinsk@horde.org>
 * @package   ActiveSync
 */
class Horde_ActiveSync_Imap_Adapter
{
    /**
     * Maximum number of of messages to fetch from the IMAP server in one go.
     */
    const MAX_FETCH = 2000;

    /**
     * @var Horde_ActiveSync_Interface_ImapFactory
     */
    protected $_imap;

    /**
     * @var Horde_Log_Logger
     */
    protected $_logger;

    /**
     * @var string
     */
    protected $_defaultNamespace;

    /**
     * Process id used for logging.
     *
     * @var integer
     */
    protected $_procid;

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
        Horde_Mime_Part::$defaultCharset = 'UTF-8';
        Horde_Mime_Headers::$defaultCharset = 'UTF-8';
        $this->_procid = getmypid();
        $this->_logger = new Horde_Log_Logger(new Horde_Log_Handler_Null());
    }

    /**
     * Append a message to the specified mailbox. Used for saving sent email.
     *
     * @param string $folderid     The mailbox
     * @param string|stream $msg   The message
     * @param array $flags         Any flags to set on the newly appended message.
     *
     * @return integer|boolean     The imap uid of the appended message or false
     *                             on failure. @since 2.37.0
     * @throws new Horde_ActiveSync_Exception, Horde_ActiveSync_Exception_FolderGone
     */
    public function appendMessage($folderid, $msg, array $flags = array())
    {
        $message = array(array('data' => $msg, 'flags' => $flags));
        $mbox = new Horde_Imap_Client_Mailbox($folderid);
        try {
            $ids = $this->_getImapOb()->append($mbox, $message);
        } catch (Horde_Imap_Client_Exception $e) {
            if (!$this->_mailboxExists($folderid)) {
                throw new Horde_ActiveSync_Exception_FolderGone();
            }
            throw new Horde_ActiveSync_Exception($e);
        }
        if (!count($ids->ids)) {
            return false;
        }

        return array_pop($ids->ids);
    }

    /**
     * Create a new mailbox on the server, and subscribe to it.
     *
     * @param string $name    The new mailbox name.
     * @param string $parent  The parent mailbox, if any.
     *
     *  @return string  The new serverid for the mailbox. This is the UTF-8 name
     *                  of the mailbox. @since 2.9.0
     *  @throws Horde_ActiveSync_Exception, Horde_ActiveSync_Exception_FolderExists
     */
    public function createMailbox($name, $parent = null)
    {
        if (!empty($parent)) {
            $ns = $this->_defaultNamespace();
            $name = $parent . $ns['delimiter'] . $name;
        }
        $mbox = new Horde_Imap_Client_Mailbox($this->_prependNamespace($name));
        $imap = $this->_getImapOb();
        try {
            $imap->createMailbox($mbox);
            $imap->subscribeMailbox($mbox, true);
        } catch (Horde_Imap_Client_Exception $e) {
            if ($e->getCode() == Horde_Imap_Client_Exception::ALREADYEXISTS) {
                $this->_logger->warn(sprintf(
                    '[%s] Mailbox %s already exists, subscribing to it.',
                    $this->_procid,
                    $name
                ));
                try {
                    $imap->subscribeMailbox($mbox, true);
                } catch (Horde_Imap_Client_Exception $e) {
                    // Exists, but could not subscribe to it, something is
                    // *really* wrong.
                    throw new Horde_ActiveSync_Exception_FolderExists('Folder Exists!');
                }
            }
            throw new Horde_ActiveSync_Exception($e);
        }

        return $mbox->utf8;
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
        try {
            $this->_getImapOb()->deleteMailbox($mbox);
        } catch (Horde_Imap_Client_Exception $e) {
            if ($e->getCode() == Horde_Imap_Client_Exception::NONEXISTENT) {
                throw new Horde_ActiveSync_Exception_FolderGone();
            }
            throw new Horde_ActiveSync_Exception($e);
        }
    }

    /**
     * Completely empty specified mailbox.
     *
     * @param string $mbox  The mailbox to empty.
     *
     * @throws Horde_ActiveSync_Exception
     * @since 2.18.0
     */
    public function emptyMailbox($mbox)
    {
        $mbox = new Horde_Imap_Client_Mailbox($mbox);
        try {
            $this->_getImapOb()->expunge($mbox, array('delete' => true));
        } catch (Horde_Imap_Client_Exception $e) {
            throw new Horde_ActiveSync_Exception($e);
        }
    }

    /**
     * Permanently delete a mail message.
     *
     * @param array $uids       The message UIDs
     * @param string $folderid  The folder id.
     *
     * @return array  An array of uids that were successfully deleted.
     * @throws Horde_ActiveSync_Exception
     */
    public function deleteMessages(array $uids, $folderid)
    {
        $imap = $this->_getImapOb();
        $mbox = new Horde_Imap_Client_Mailbox($folderid);
        $ids_obj = new Horde_Imap_Client_Ids($uids);

        // Need to ensure the source message exists so we may properly notify
        // the client of the error.
        $search_q = new Horde_Imap_Client_Search_Query();
        $search_q->ids($ids_obj);
        try {
            $fetch_res = $imap->search($mbox, $search_q);
        } catch (Horde_Imap_Client_Exception $e) {
            throw new Horde_ActiveSync_Exception($e);
        }
        if ($fetch_res['count'] != count($uids)) {
            $ids_obj = $fetch_res['match'];
        }

        try {
            $imap->store($mbox, array(
                'ids' => $ids_obj,
                'add' => array('\deleted'))
            );
            $imap->expunge($mbox, array('ids' => $ids_obj));
        } catch (Horde_Imap_Client_Exception $e) {
            throw new Horde_ActiveSync_Exception($e);
        }

        return $ids_obj->ids;
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
        if (empty($messages[$uid]) || !$messages[$uid]->exists(Horde_Imap_Client::FETCH_STRUCTURE)) {
            throw new Horde_ActiveSync_Exception('Message Gone');
        }
        $msg = new Horde_ActiveSync_Imap_Message($imap, $mbox, $messages[$uid]);
        $part = $msg->getMimePart($part);

        return $part;
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
     * Return a complete Horde_ActiveSync_Imap_Message object for the requested
     * uid.
     *
     * @param string $mailbox     The mailbox name.
     * @param array|integer $uid  The message uid.
     * @param array $options      Additional options:
     *     - headers: (boolean) Also fetch the message headers if this is true.
     *                DEFAULT: false (Do not fetch headers).
     *
     * @return array  An array of Horde_ActiveSync_Imap_Message objects.
     * @todo This should be renamed to getImapMessages since we can now accept
     *       an array of $uids.
     */
    public function getImapMessage($mailbox, $uid, array $options = array())
    {
        if (!is_array($uid)) {
            $uid = array($uid);
        }
        $mbox = new Horde_Imap_Client_Mailbox($mailbox);
        // @todo H6 - expand the $options array the same as _getMailMessages()
        // for now, always retrieve the envelope data as well.
        $options['envelope'] = true;
        $messages = $this->_getMailMessages($mbox, $uid, $options);
        $res = array();
        foreach ($messages as $id => $message) {
            if ($message->exists(Horde_Imap_Client::FETCH_STRUCTURE)) {
                $res[$id] = new Horde_ActiveSync_Imap_Message($this->_getImapOb(), $mbox, $message);
            }
        }

        return $res;
    }

    /**
     * Return message changes from the specified mailbox.
     *
     * @param Horde_ActiveSync_Folder_Imap $folder  The folder object.
     * @param array $options                        Additional options:
     *  - sincedate: (integer)       Timestamp of earliest message to retrieve.
     *                               DEFAULT: 0 (Don't filter).
     *  - protocolversion: (float)   EAS protocol version to support.
     *                               DEFAULT: none REQUIRED
     *  - softdelete: (boolean)      If true, calculate SOFTDELETE data.
     *                               @since 2.8.0
     *  - refreshfilter: (boolean)   If true, force refresh the query to reflect
     *                               changes in FILTERTYPE (using the sincedate)
     *                               @since 2.28.0
     *
     * @return Horde_ActiveSync_Folder_Imap  The folder object, containing any
     *                                       change instructions for the device.
     *
     * @throws Horde_ActiveSync_Exception_FolderGone,
     *         Horde_ActiveSync_Exception, Horde_ActiveSync_Exception_StaleState
     */
    public function getMessageChanges(
        Horde_ActiveSync_Folder_Imap $folder, array $options = array())
    {
        $imap = $this->_getImapOb();
        $mbox = new Horde_Imap_Client_Mailbox($folder->serverid());
        $flags = array();
        $search_uids = array();

        // Note: non-CONDSTORE servers will return a highestmodseq of 0
        $status_flags = Horde_Imap_Client::STATUS_HIGHESTMODSEQ |
            Horde_Imap_Client::STATUS_UIDVALIDITY |
            Horde_Imap_Client::STATUS_UIDNEXT_FORCE |
            Horde_Imap_Client::STATUS_MESSAGES |
            Horde_Imap_Client::STATUS_FORCE_REFRESH;

        try {
            $status = $imap->status($mbox, $status_flags);
        } catch (Horde_Imap_Client_Exception $e) {
            // If we can't status the mailbox, assume it's gone.
            throw new Horde_ActiveSync_Exception_FolderGone($e);
        }

        $this->_logger->info(sprintf(
            '[%s] IMAP status: %s',
            $this->_procid,
            serialize($status))
        );

        // UIDVALIDITY
        if ($folder->uidnext() != 0) {
            $folder->checkValidity($status);
        }

        $current_modseq = $status[Horde_ActiveSync_Folder_Imap::HIGHESTMODSEQ];
        $modseq_corrupted = $folder->modseq() > $current_modseq;
        if ($modseq_corrupted || (($current_modseq && $folder->modseq() > 0) &&
            (($folder->modseq() < $current_modseq) ||
             !empty($options['softdelete']) || !empty($options['refreshfilter'])))) {
            $strategy = new Horde_ActiveSync_Imap_Strategy_Modseq(
                $this->_imap, $status, $folder, $this->_logger
            );
            $folder = $strategy->getChanges($options);
        } elseif ($folder->uidnext() == 0) {
            $strategy = new Horde_ActiveSync_Imap_Strategy_Initial(
                $this->_imap, $status, $folder, $this->_logger
            );
            $folder = $strategy->getChanges($options);
        } elseif ($current_modseq == 0) {
            $strategy = new Horde_ActiveSync_Imap_Strategy_Plain(
                $this->_imap, $status, $folder, $this->_logger
            );
            $folder = $strategy->getChanges($options);
        } elseif ($current_modseq > 0 && $folder->modseq() == 0) {
            throw new Horde_ActiveSync_Exception_StaleState('Transition to MODSEQ enabled server');
        }
        $folder->setStatus($status);

        return $folder;
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
     *   - bodypartprefs: (array) The bodypartprefs settings, if present.
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
        $results = $this->_getMailMessages($mbox, $messages, array('headers' => true, 'envelope' => true));
        $ret = array();
        foreach ($results as $data) {
            if ($data->exists(Horde_Imap_Client::FETCH_STRUCTURE)) {
                try {
                    $ret[] = $this->_buildMailMessage($mbox, $data, $options);
                } catch (Horde_Exception_NotFound $e) {
                }
            }
        }

        return $ret;
    }

    /**
     * Return a message UIDs from the given Message-ID.
     *
     * @param string $mid                           The Message-ID
     * @param Horde_ActiveSync_Folder_Imap $folder  The folder object to search.
     *
     * @return integer  The UID
     * @throws Horde_Exception_NotFound
     */
    public function getUidFromMid($mid, Horde_ActiveSync_Folder_Imap $folder)
    {
        $iids = new Horde_Imap_Client_Ids(array_diff($folder->messages(), $folder->removed()));
        $search_q = new Horde_Imap_Client_Search_Query();
        $search_q->ids($iids);
        $search_q->headerText('Message-ID', $mid);
        $mbox = new Horde_Imap_Client_Mailbox($folder->serverid());
        $results = $this->_getImapOb()->search($mbox, $search_q);
        $uid = $results['match']->ids;
        if (!empty($uid)) {
            return current($uid);
        }
        throw new Horde_Exception_NotFound('Message not found.');
    }

    /**
     * Attempt to find a Message-ID in a list of mail folders.
     *
     * @return array  An array with the 0 element being the mbox
     * @throws Horde_Exception_NotFound, Horde_ActiveSync_Exception
     *
     * @deprecated This is unused and should be removed.
     */
    public function getUidFromMidInFolders($id, array $folders)
    {
        $search_q = new Horde_Imap_Client_Search_Query();
        $search_q->headerText('Message-ID', $id);
        foreach ($folders as $folder) {
            $mbox = new Horde_Imap_Client_Mailbox($folder->_serverid);
            try {
                $results = $this->_getImapOb()->search($mbox, $search_q);
            } catch (Horde_Imap_Client_Exception $e) {
                throw new Horde_ActiveSync_Exception($e->getMessage());
            }
            $uid = $results['match']->ids;
            if (!empty($uid)) {
                return array($mbox, current($uid));
            }
        }

        throw new Horde_Exception_NotFound('Message not found.');
    }

    /**
     * Move a mail message
     *
     * @param string $folderid     The existing folderid.
     * @param array $ids           The message UIDs of the messages to move.
     * @param string $newfolderid  The folder id to move $id to.
     *
     * @return array  An hash of oldUID => newUID.
     *
     * @throws Horde_ActiveSync_Exception
     */
    public function moveMessage($folderid, array $ids, $newfolderid)
    {
        $imap = $this->_getImapOb();
        $from = new Horde_Imap_Client_Mailbox($folderid);
        $to = new Horde_Imap_Client_Mailbox($newfolderid);
        $ids_obj = new Horde_Imap_Client_Ids($ids);

        // Need to ensure the source message exists so we may properly notify
        // the client of the error.
        $search_q = new Horde_Imap_Client_Search_Query();
        $search_q->ids($ids_obj);
        $fetch_res = $imap->search($from, $search_q);
        if ($fetch_res['count'] != count($ids)) {
            $ids_obj = $fetch_res['match'];
        }

        try {
            return $imap->copy($from, $to, array('ids' => $ids_obj, 'move' => true, 'force_map' => true));
        } catch (Horde_Imap_Client_Exception $e) {
            // We already got rid of the missing ids, must be something else.
            $this->_logger->err($e->getMessage());
            throw new Horde_ActiveSync_Exception($e);
        }
    }

    /**
     * Ping a mailbox. This detects only if any new messages have arrived in
     * the specified mailbox.
     *
     * @param Horde_ActiveSync_Folder_Imap $folder  The folder object.
     *
     * @return boolean  True if changes were detected, otherwise false.
     * @throws Horde_ActiveSync_Exception, Horde_ActiveSync_Exception_FolderGone
     */
    public function ping(Horde_ActiveSync_Folder_Imap $folder)
    {
        $mbox = new Horde_Imap_Client_Mailbox($folder->serverid());
        // Note: non-CONDSTORE servers will return a highestmodseq of 0
        $status_flags = Horde_Imap_Client::STATUS_HIGHESTMODSEQ |
            Horde_Imap_Client::STATUS_UIDNEXT_FORCE |
            Horde_Imap_Client::STATUS_MESSAGES |
            Horde_Imap_Client::STATUS_FORCE_REFRESH;

        // Get IMAP status.
        try {
            $status = $this->_getImapOb()->status($mbox, $status_flags);
        } catch (Horde_Imap_Client_Exception $e) {
            // See if the folder disappeared.
            if (!$this->_mailboxExists($mbox->utf8)) {
                throw new Horde_ActiveSync_Exception_FolderGone();
            }
            throw new Horde_ActiveSync_Exception($e);
        }

        $this->_logger->info(sprintf(
            '[%s] IMAP status: %s',
            $this->_procid,
            serialize($status))
        );

        // If we have per mailbox MODSEQ then we can pick up flag changes too.
        $modseq = $status[Horde_ActiveSync_Folder_Imap::HIGHESTMODSEQ];
        if ($modseq && $folder->modseq() > 0 && $folder->modseq() < $modseq) {
            return true;
        }

        // Increase in UIDNEXT is always a positive PING.
        if ($folder->uidnext() < $status['uidnext']) {
            return true;
        }

        // If the message count changes, something certainly changed.
        if ($folder->total_messages() != $status['messages']) {
            return true;
        }

        // Otherwise, no PING detectable changes present.
        return false;
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
        return $this->_doQuery($query['query']);
    }

    /**
     * Rename a mailbox
     *
     * @param string $old     The old mailbox name.
     * @param string $new     The new mailbox name.
     * @param string $parent  The parent mailbox, if any.
     *
     * @return string  The new serverid for the mailbox.
     *                 @since 2.9.0
     * @throws Horde_ActiveSync_Exception
     */
    public function renameMailbox($old, $new, $parent = null)
    {
        if (!empty($parent)) {
            $ns = $this->_defaultNamespace();
            $new = $parent . $ns['delimiter'] . $new;
        }
        $new_mbox = new Horde_Imap_Client_Mailbox($new);
        try {
            $this->_getImapOb()->renameMailbox(
                new Horde_Imap_Client_Mailbox($old),
                $new_mbox
            );
        } catch (Horde_Imap_Client_Exception $e) {
            throw new Horde_ActiveSync_Exception($e);
        }

        return $new_mbox->utf8;
    }

    /**
     * Set a IMAP message flag.
     *
     * @param string $mailbox  The mailbox name.
     * @param integer $uid     The message UID.
     * @param string $flag     The flag to set. A Horde_ActiveSync:: constant.
     *
     * @throws Horde_ActiveSync_Exception
     */
    public function setImapFlag($mailbox, $uid, $flag)
    {
        $mbox = new Horde_Imap_Client_Mailbox($mailbox);
        $options = array(
            'ids' => new Horde_Imap_Client_Ids(array($uid))
        );
        switch ($flag) {
        case Horde_ActiveSync::IMAP_FLAG_REPLY:
            $options['add'] = array(Horde_Imap_Client::FLAG_ANSWERED);
            break;
        case Horde_ActiveSync::IMAP_FLAG_FORWARD:
            $options['add'] = array(Horde_Imap_Client::FLAG_FORWARDED);
        }
        try {
            $this->_getImapOb()->store($mbox, $options);
        } catch (Horde_Imap_Client_Exception $e) {
            throw new Horde_ActiveSync_Exception($e);
        }
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
     * Set a POOMMAIL_FLAG on a mail message. This method differs from
     * setReadFlag() in that it is passed a Flag object, which contains
     * other data beside the seen status. Used for setting flagged for followup
     * and potentially creating tasks based on the email.
     *
     * @param string  $mailbox                     The mailbox name.
     * @param integer $uid                         The message uid.
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
        try {
            $this->_getImapOb()->store($mbox, $options);
        } catch (Horde_Imap_Client_Exception $e) {
            throw new Horde_ActiveSync_Exception($e);
        }
    }

    public function categoriesToFlags($mailbox, $categories, $uid)
    {
        $msgFlags = $this->_getMsgFlags();
        $mbox = new Horde_Imap_Client_Mailbox($mailbox);
        $options = array(
            'ids' => new Horde_Imap_Client_Ids(array($uid)),
            'add' => array()
        );
        foreach ($categories as $category) {
            // Do our best to make sure the imap flag is a RFC 3501 compliant.
            $atom = new Horde_Imap_Client_Data_Format_Atom(strtr(Horde_String_Transliterate::toAscii($category), ' ', '_'));
            $imapflag = Horde_String::lower($atom->stripNonAtomCharacters());
            if (!empty($msgFlags[$imapflag])) {
                $options['add'][] = $imapflag;
                unset($msgFlags[$imapflag]);
            }
        }
        $options['remove'] = array_keys($msgFlags);
        try {
            $this->_getImapOb()->store($mbox, $options);
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
        try {
            $this->_getImapOb()->store($mbox, $options);
        } catch (Horde_Imap_Client_Exception $e) {
            throw new Horde_ActiveSync_Exception($e);
        }
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
     * Builds a proper AS mail message object.
     *
     * @param Horde_Imap_Client_Mailbox    $mbox  The IMAP mailbox.
     * @param Horde_Imap_Client_Data_Fetch $data  The fetch results.
     * @param array $options                      Additional Options:
     *   - truncation:  (integer) Truncate the message body to this length.
     *                  DEFAULT: No truncation.
     *   - bodyprefs: (array)  Bodyprefs, if sent from device.
     *                DEFAULT: none (No body prefs sent or enforced).
     *   - bodypartprefs: (array)  Bodypartprefs, if sent from device.
     *                DEFAULT: none (No body part prefs sent or enforced).
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
        $version = $options['protocolversion'] = empty($options['protocolversion'])
            ? Horde_ActiveSync::VERSION_TWOFIVE
            : $options['protocolversion'];

        $imap_message = new Horde_ActiveSync_Imap_Message($this->_getImapOb(), $mbox, $data);

        // Build the message body.
        $easBodyBuilder = Horde_ActiveSync_Imap_EasMessageBuilder::create(
            $imap_message,
            $options,
            $this->_logger
        );

        // Inject flags since this saves us from having to inject the imapOb
        // into the builder.
        $params = array();
        if ($version > Horde_ActiveSync::VERSION_TWELVEONE) {
            $params['flags'] = $this->_getMsgFlags();
        }

        return $easBodyBuilder->getMessageObject($params);
    }

    /**
     * Prefix the default namespace to mailbox name if needed.
     *
     * @param string $name  The mailbox name.
     *
     * @return string  The mailbox name with the default namespace added, if
     *                 needed.
     */
    protected function _prependNamespace($name)
    {
        $def_ns = $this->_defaultNamespace();
        if (!is_null($def_ns)) {
            $empty_ns = $this->_getNamespace('');
            if (is_null($empty_ns) || $def_ns['name'] != $empty_ns['name']) {
                $name = $def_ns['name'] . $name;
            }

        }

        return $name;
    }

    /**
     * Return the default namespace.
     *
     * @return array  The namespace data.
     */
    protected function _defaultNamespace()
    {
        if (is_null($this->_defaultNamespace)) {
            foreach ($this->_getNamespacelist() as $ns) {
                if ($ns['type'] == Horde_Imap_Client::NS_PERSONAL) {
                    $this->_defaultNamespace = $ns;
                    break;
                }
            }
        }

        return $this->_defaultNamespace;
    }

    /**
     * Return the list of configured namespaces on the IMAP server.
     *
     * @return array
     */
    protected function _getNamespacelist()
    {
        try {
            return $this->_getImapOb()->getNamespaces();
        } catch (Horde_Imap_Client_Exception $e) {
            return array();
        }
    }

    protected function _getNamespace($path)
    {
        $ns = $this->_getNamespacelist();
        foreach ($ns as $key => $val) {
            $mbox = $path . $val['delimiter'];
            if (strlen($key) && (strpos($mbox, $key) === 0)) {
                return $val;
            }
        }

        return (isset($ns['']) && ($val['type'] == Horde_Imap_Client::NS_PERSONAL))
            ? $ns['']
            : null;
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
    protected function _doQuery(array $query)
    {
        $imap_query = new Horde_Imap_Client_Search_Query();
        $imap_query->charset('UTF-8', false);
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
                    case 'serverid':
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
                $search_res = $this->_getImapOb()->search(
                    $mbox,
                    $imap_query,
                    array(
                        'results' => array(Horde_Imap_Client::SEARCH_RESULTS_MATCH, Horde_Imap_Client::SEARCH_RESULTS_SAVE, Horde_Imap_Client::SEARCH_RESULTS_COUNT),
                        'sort' => array(Horde_Imap_Client::SORT_REVERSE, Horde_Imap_Client::SORT_ARRIVAL))
                );
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
     * Helper to obtain a valid IMAP client. Can't inject it since the user
     * is not yet authenticated at the time of object creation.
     *
     * @return Horde_Imap_Client_Base
     * @throws Horde_ActiveSync_Exception
     */
    protected function _getImapOb()
    {
        try {
            return $this->_imap->getImapOb();
        } catch (Horde_ActiveSync_Exception $e) {
            throw new Horde_Exception_AuthenticationFailure('EMERGENCY - Unable to obtain the IMAP Client');
        }
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
     *   - envelope: (boolen) Fetch the envelope data.
     *               DEFAULT: false (Do not fetch envelope). @since 2.4.0
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
                'flags' => true,
                'envelope' => false),
            $options
        );

        $query = new Horde_Imap_Client_Fetch_Query();
        if ($options['structure']) {
            $query->structure();
        }
        if ($options['flags']) {
            $query->flags();
        }
        if ($options['envelope']) {
            $query->envelope();
        }
        if (!empty($options['headers'])) {
            $query->headerText(array('peek' => true));
        }
        try {
            return $this->_getImapOb()->fetch(
                $mbox,
                $query,
                array('ids' => new Horde_Imap_Client_Ids($uids), 'exists' => true)
            );
        } catch (Horde_Imap_Client_Exception $e) {
            $this->_logger->err(sprintf(
                '[%s] Unable to fetch message: %s',
                $this->_procid,
                $e->getMessage()));
            throw new Horde_ActiveSync_Exception($e);
        }
    }

    /**
     * Check existence of a mailbox.
     *
     * @param string $mbox  The mailbox name.
     *
     * @return boolean
     */
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

    protected function _getMsgFlags()
    {
        // @todo Horde_ActiveSync 3.0 remove method_exists check.
        if (method_exists($this->_imap, 'getMsgFlags')) {
            return $this->_imap->getMsgFlags();
        }

        return array();
    }
}
