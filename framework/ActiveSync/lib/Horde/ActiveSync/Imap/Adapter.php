<?php
/**
 * Horde_ActiveSync_Imap_Adapter::
 *
 * @license   http://www.horde.org/licenses/gpl GPLv2
 *            NOTE: According to sec. 8 of the GENERAL PUBLIC LICENSE (GPL),
 *            Version 2, the distribution of the Horde_ActiveSync module in or
 *            to the United States of America is excluded from the scope of this
 *            license.
 * @copyright 2012-2015 Horde LLC (http://www.horde.org)
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
 * @copyright 2012-2015 Horde LLC (http://www.horde.org)
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
        $this->_logger = new Horde_Support_Stub();
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
        $message = array(array('data' => $msg, 'flags' => $flags));
        $mbox = new Horde_Imap_Client_Mailbox($folderid);
        try {
            $this->_getImapOb()->append($mbox, $message);
        } catch (Horde_Imap_Client_Exception $e) {
            if (!$this->_mailboxExists($folderid)) {
                throw new Horde_ActiveSync_Exception_FolderGone();
            }
            throw new Horde_ActiveSync_Exception($e);
        }
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
                $this->_logger(sprintf(
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
        $fetch_res = $imap->search($mbox, $search_q);
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
     *  - sincedate: (integer)  Timestamp of earliest message to retrieve.
     *               DEFAULT: 0 (Don't filter).
     *  - protocolversion: (float)  EAS protocol version to support.
     *                     DEFAULT: none REQUIRED
     *  - softdelete: (boolean)  If true, calculate SOFTDELETE data.
     *                           @since 2.8.0 @todo Rename this to something
     *                           more appropriate now that it's not just for
     *                           triggering SOFTDELETE.
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

        $modseq = $status[Horde_ActiveSync_Folder_Imap::HIGHESTMODSEQ];
        if ($modseq && $folder->modseq() > 0 && ($folder->modseq() < $modseq || !empty($options['softdelete']))) {
            $this->_logger->info(sprintf(
                '[%s] CONDSTORE and CHANGES', $this->_procid));
            $folder->checkValidity($status);
            $query = new Horde_Imap_Client_Fetch_Query();
            $query->modseq();
            $query->flags();
            $query->headerText(array('peek' => true));
            try {
                $fetch_ret = $imap->fetch($mbox, $query, array(
                    'changedsince' => $folder->modseq()
                ));
            } catch (Horde_Imap_Client_Exception $e) {
                $this->_logger->err($e->getMessage());
                throw new Horde_ActiveSync_Exception($e);
            }

            $changes = array();
            $flags = array();
            $categories = array();

            // "Normal" changes.
            $this->_buildModSeqChanges($changes, $flags, $categories, $fetch_ret, $options, $modseq);

            // Check for mail outside of the time restriction if the filtertype
            // changed.
            if ($options['softdelete']) {
                $this->_logger->info(sprintf(
                    '[%s] Checking for additional messages within the new FilterType parameters.',
                    $this->_procid));
                if ($fetch_ret = $this->_refreshFilterQuery($folder, $options, $mbox, false)) {
                    $this->_buildModSeqChanges($changes, $flags, $categories, $fetch_ret, $options, $modseq);
                } else {
                    $this->_logger->info(sprintf(
                        '[%s] Found NO additional messages.',
                        $this->_procid));
                }
            }

            // Set the changes in the folder object.
            $folder->setChanges($changes, $flags, $categories, !empty($options['softdelete']));

            // Check for deleted.
            try {
                $deleted = $imap->vanished(
                    $mbox,
                    $folder->modseq(),
                    array('ids' => new Horde_Imap_Client_Ids($folder->messages())));
            } catch (Horde_Imap_Client_Excetion $e) {
                $this->_logger->err($e->getMessage());
                throw new Horde_ActiveSync_Exception($e);
            }
            $folder->setRemoved($deleted->ids);

            // Check for SOFTDELETE messages.
            if (!empty($options['softdelete']) && !empty($options['sincedate'])) {
                $this->_logger->info(sprintf(
                    '[%s] Polling for SOFTDELETE in %s before %d',
                    $this->_procid, $folder->serverid(), $options['sincedate']));
                $search_ret = $this->_buildSearchQuery($folder, $options, $mbox, true);
                if ($search_ret['count']) {
                    $this->_logger->info(sprintf(
                        '[%s] Found %d messages to SOFTDELETE.',
                        $this->_procid, count($search_ret['match']->ids)));
                    $folder->setSoftDeleted($search_ret['match']->ids);
                } else {
                     $this->_logger->info(sprintf(
                        '[%s] Found NO messages to SOFTDELETE.',
                        $this->_procid));
                }
                $folder->setSoftDeleteTimes($options['sincedate'], time());
            }

        } elseif ($folder->uidnext() == 0) {
            $this->_logger->info(sprintf(
                '[%s] INITIAL SYNC', $this->_procid));
            $query = new Horde_Imap_Client_Search_Query();
            if (!empty($options['sincedate'])) {
                $query->dateSearch(
                    new Horde_Date($options['sincedate']),
                    Horde_Imap_Client_Search_Query::DATE_SINCE);
            }
            $search_ret = $imap->search(
                $mbox,
                $query,
                array('results' => array(Horde_Imap_Client::SEARCH_RESULTS_MATCH)));
            if ($modseq && $folder->modseq() > 0 && $search_ret['count']) {
                $folder->setChanges($search_ret['match']->ids, array());
            } elseif (count($search_ret['match']->ids)) {
                $query = new Horde_Imap_Client_Fetch_Query();
                $query->flags();
                $fetch_ret = $imap->fetch($mbox, $query, array('ids' => $search_ret['match']));
                foreach ($fetch_ret as $uid => $data) {
                    $flags[$uid] = array(
                        'read' => (array_search(Horde_Imap_Client::FLAG_SEEN, $data->getFlags()) !== false) ? 1 : 0
                    );
                    if (($options['protocolversion']) > Horde_ActiveSync::VERSION_TWOFIVE) {
                        $flags[$uid]['flagged'] = (array_search(Horde_Imap_Client::FLAG_FLAGGED, $data->getFlags()) !== false) ? 1 : 0;
                    }
                }
                $folder->setChanges($search_ret['match']->ids, $flags);
            }
        } elseif ($modseq == 0) {
            $this->_logger->info(sprintf(
                '[%s] NO CONDSTORE or per mailbox MODSEQ. minuid: %s, total_messages: %s',
                $this->_procid, $folder->minuid(), $status['messages']));
            $folder->checkValidity($status);
            $query = new Horde_Imap_Client_Search_Query();
            if (!empty($options['sincedate'])) {
                $query->dateSearch(
                    new Horde_Date($options['sincedate']),
                    Horde_Imap_Client_Search_Query::DATE_SINCE);
            }
            try {
                $search_ret = $imap->search(
                    $mbox,
                    $query,
                    array('results' => array(Horde_Imap_Client::SEARCH_RESULTS_MATCH)));
            } catch (Horde_Imap_Client_Exception $e) {
                $this->_logger->err($e->getMessage());
                throw new Horde_ActiveSync_Exception($e);
            }

            if (count($search_ret['match']->ids)) {
                // Update flags.
                $query = new Horde_Imap_Client_Fetch_Query();
                $query->flags();
                try {
                    $fetch_ret = $imap->fetch($mbox, $query, array('ids' => $search_ret['match']));
                } catch (Horde_Imap_Client_Exception $e) {
                    $this->_logger->err($e->getMessage());
                    throw new Horde_ActiveSync_Exception($e);
                }
                foreach ($fetch_ret as $uid => $data) {
                    $flags[$uid] = array(
                        'read' => (array_search(Horde_Imap_Client::FLAG_SEEN, $data->getFlags()) !== false) ? 1 : 0
                    );
                    if (($options['protocolversion']) > Horde_ActiveSync::VERSION_TWOFIVE) {
                        $flags[$uid]['flagged'] = (array_search(Horde_Imap_Client::FLAG_FLAGGED, $data->getFlags()) !== false) ? 1 : 0;
                    }
                }
                $folder->setChanges($search_ret['match']->ids, $flags);
            }
            $folder->setRemoved($imap->vanished($mbox, null, array('ids' => new Horde_Imap_Client_Ids($folder->messages())))->ids);
        } elseif ($modseq > 0 && $folder->modseq() == 0) {
                throw new Horde_ActiveSync_Exception_StaleState('Transition to MODSEQ enabled server');
        }
        $folder->setStatus($status);

        return $folder;
    }

    /**
     * Return messages that are now either within or outside of the current
     * FILTERTYPE value.
     *
     * @param  Horde_ActiveSync_Folder_Imap $folder    The IMAP folder object.
     * @param  array                        $options   Options array.
     * @param  Horde_Imap_Client_Mailbox    $mbox      The current mailbox.
     * @param  boolean                      $is_delete If true, return messages
     *                                                 to SOFTDELETE.
     *
     * @return mixed Horde_Imap_Client_Fetch_Results | false if no message found.
     */
    protected function _refreshFilterQuery(
        Horde_ActiveSync_Folder_Imap $folder, array $options, Horde_Imap_Client_Mailbox $mbox, $is_delete)
    {
        $search_ret = $this->_buildSearchQuery($folder, $options, $mbox, $is_delete);
        if ($search_ret['count']) {
            $this->_logger->info(sprintf(
                '[%s] Found %d messages that are now outside FilterType.',
                $this->_procid, count($search_ret['match']->ids)));
            $query = new Horde_Imap_Client_Fetch_Query();
            $query->modseq();
            $query->flags();
            $query->headerText(array('peek' => true));
            try {
                $fetch_ret = $this->_getImapOb()->fetch($mbox, $query, array(
                    'ids' => $search_ret['match']
                ));
            } catch (Horde_Imap_Client_Exception $e) {
                $this->_logger->err($e->getMessage());
                throw new Horde_ActiveSync_Exception($e);
            }

            return $fetch_ret;
        }

        return false;
    }

    /**
     * Return message UIDs that are now within the cureent FILTERTYPE value.
     *
     * @param  Horde_ActiveSync_Folder_Imap $folder    The IMAP folder object.
     * @param  array                        $options   Options array.
     * @param  Horde_Imap_Client_Mailbox    $mbox      The current mailbox.
     * @param  boolean                      $is_delete If true, return messages
     *                                                 to SOFTDELETE.
     *
     * @return Horde_Imap_Client_Search_Results
     */
    protected function _buildSearchQuery($folder, $options, $mbox, $is_delete)
    {
        $query = new Horde_Imap_Client_Search_Query();
        $query->dateSearch(
            new Horde_Date($options['sincedate']),
            $is_delete
                ? Horde_Imap_Client_Search_Query::DATE_BEFORE
                : Horde_Imap_Client_Search_Query::DATE_SINCE
        );
        $query->ids(new Horde_Imap_Client_Ids($folder->messages()), !$is_delete);
        try {
            return $this->_getImapOb()->search(
                $mbox,
                $query,
                array('results' => array(Horde_Imap_Client::SEARCH_RESULTS_MATCH)));
        } catch (Horde_Imap_Client_Exception $e) {
            $this->_logger->err($e->getMessage());
            throw new Horde_ActiveSync_Exception($e);
        }
    }

    /**
     * Populates the changes, flags, and categories arrays with data from
     * any messages added/changed on the IMAP server since the last poll.
     *
     * @param array &$changes                             Changes array.
     * @param array &$flags                               Flags array.
     * @param array &$categories                          Categories array.
     * @param Horde_Imap_Client_Fetch_Results $fetch_ret  Fetch results.
     * @param array $options                              Options array.
     * @param integer $modseq                             Current MODSEQ.
     */
    protected function _buildModSeqChanges(
        &$changes, &$flags, &$categories, $fetch_ret, $options, $modseq)
    {
        // Get custom flags to use as categories.
        $msgFlags = $this->_getMsgFlags();

        foreach ($fetch_ret as $uid => $data) {
            if ($options['sincedate']) {
                $since = new Horde_Date($options['sincedate']);
                $headers = Horde_Mime_Headers::parseHeaders($data->getHeaderText());
                try {
                    $date = new Horde_Date($headers->getValue('Date'));
                    if ($date->compareDate($since) <= -1) {
                        // Ignore, it's out of the FILTERTYPE range.
                        $this->_logger->info(sprintf(
                            '[%s] Ignoring UID %s since it is outside of the FILTERTYPE (%s)',
                            $this->_procid,
                            $uid,
                            $headers->getValue('Date')));
                        continue;
                    }
                } catch (Horde_Date_Exception $e) {}
            }

            // Ensure no changes after $modseq sneak in
            // (they will be caught on the next PING or SYNC).
            if ($data->getModSeq() <= $modseq) {
                $changes[] = $uid;
                $flags[$uid] = array(
                    'read' => (array_search(Horde_Imap_Client::FLAG_SEEN, $data->getFlags()) !== false) ? 1 : 0
                );
                if (($options['protocolversion']) > Horde_ActiveSync::VERSION_TWOFIVE) {
                    $flags[$uid]['flagged'] = (array_search(Horde_Imap_Client::FLAG_FLAGGED, $data->getFlags()) !== false) ? 1 : 0;
                }
                if ($options['protocolversion'] > Horde_ActiveSync::VERSION_TWELVEONE) {
                    $categories[$uid] = array();
                    foreach ($data->getFlags() as $flag) {
                        if (!empty($msgFlags[strtolower($flag)])) {
                            $categories[$uid][] = $msgFlags[strtolower($flag)];
                        }
                    }
                }
            }
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
     * Return an array of message UIDs from a list of Message-IDs.
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
            $imapflag = strtolower($atom->stripNonAtomCharacters());
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
        $version = empty($options['protocolversion']) ?
            Horde_ActiveSync::VERSION_TWOFIVE :
            $options['protocolversion'];

        $imap_message = new Horde_ActiveSync_Imap_Message($this->_getImapOb(), $mbox, $data);
        $eas_message = Horde_ActiveSync::messageFactory('Mail');

        // Build To: data (POOMMAIL_TO has a max length of 32768).
        $to = $imap_message->getToAddresses();
        $eas_message->to = array_pop($to['to']);
        foreach ($to['to'] as $to_atom) {
            if (strlen($eas_message->to) + strlen($to_atom) > 32768) {
                break;
            }
            $eas_message->to .= ',' . $to_atom;
        }
        $eas_message->displayto = implode(';', $to['displayto']);
        if (empty($eas_message->displayto)) {
            $eas_message->displayto = $eas_message->to;
        }

        // Ensure we don't send broken UTF8 data to the client. It makes clients
        // angry. And we don't like angry clients.
        $hdr_charset = $imap_message->getStructure()->getHeaderCharset();

        // Fill in other header data
        $eas_message->from = $imap_message->getFromAddress();
        $eas_message->subject = Horde_ActiveSync_Utils::ensureUtf8($imap_message->getSubject(), $hdr_charset);
        $eas_message->threadtopic = $eas_message->subject;
        $eas_message->datereceived = $imap_message->getDate();
        $eas_message->read = $imap_message->getFlag(Horde_Imap_Client::FLAG_SEEN);
        $eas_message->cc = $imap_message->getCc();
        $eas_message->reply_to = $imap_message->getReplyTo();

        // Default to IPM.Note - may change below depending on message content.
        $eas_message->messageclass = 'IPM.Note';

        // Codepage id. MS recommends to always set to UTF-8 when possible.
        // See http://msdn.microsoft.com/en-us/library/windows/desktop/dd317756%28v=vs.85%29.aspx
        $eas_message->cpid = Horde_ActiveSync_Message_Mail::INTERNET_CPID_UTF8;

        // Message importance. First try X-Priority, then Importance since
        // Outlook sends the later.
        if ($priority = $imap_message->getHeaders()->getValue('X-priority')) {
            $priority = preg_replace('/\D+/', '', $priority);
        } else {
            $priority = $imap_message->getHeaders()->getValue('Importance');
        }
        $eas_message->importance = $this->_getEASImportance($priority);

        // Get the body data.
        $mbd = $imap_message->getMessageBodyDataObject($options);

        if ($version == Horde_ActiveSync::VERSION_TWOFIVE) {
            $eas_message->body = $mbd->plain['body']->stream;
            $eas_message->bodysize = $mbd->plain['body']->length(true);
            $eas_message->bodytruncated = $mbd->plain['truncated'];
            $eas_message->attachments = $imap_message->getAttachments($version);
        } else {
            // Get the message body and determine original type.
            if ($mbd->html) {
                $eas_message->airsyncbasenativebodytype = Horde_ActiveSync::BODYPREF_TYPE_HTML;
            } else {
                $eas_message->airsyncbasenativebodytype = Horde_ActiveSync::BODYPREF_TYPE_PLAIN;
            }
            $airsync_body = Horde_ActiveSync::messageFactory('AirSyncBaseBody');
            $body_type_pref = $mbd->getBodyTypePreference();

            if ($body_type_pref == Horde_ActiveSync::BODYPREF_TYPE_MIME) {
                $this->_logger->info(sprintf(
                    '[%s] Sending MIME Message.', $this->_procid));
                // ActiveSync *REQUIRES* all data sent to be in UTF-8, so we
                // must convert the body parts to UTF-8. Unfortunately if the
                // email is signed (or encrypted for that matter) we can't
                // alter the data in anyway or the signature will not be
                // verified, so we fetch the entire message and hope for the best.
                if (!$imap_message->isSigned() && !$imap_message->isEncrypted()) {
                    $mime = new Horde_Mime_Part();

                    if ($mbd->plain) {
                        $plain_mime = new Horde_Mime_Part();
                        $plain_mime->setType('text/plain');
                        $plain_mime->setContents($mbd->plain['body']->stream, array('usestream' => true));
                        $plain_mime->setCharset('UTF-8');
                    }

                    if ($mbd->html) {
                        $html_mime = new Horde_Mime_Part();
                        $html_mime->setType('text/html');
                        $html_mime->setContents($mbd->html['body']->stream, array('usestream' => true));
                        $html_mime->setCharset('UTF-8');
                    }

                    // Sanity check the mime type
                    if (!$mbd->html && !empty($plain_mime)) {
                        $mime = $plain_mime;
                    } elseif (!$mbd->plain && !empty($html_mime)) {
                        $mime = $html_mime;
                    } elseif (!empty($plain_mime) && !empty($html_mime)) {
                        $mime->setType('multipart/alternative');
                        $mime->addPart($plain_mime);
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
                        $eas_message->airsyncbaseattachments = $imap_message->getAttachments($version);
                    } else {
                        $base = $mime;
                    }

                    // Populate the EAS body structure with the MIME data, but
                    // remove the Content-Type and Content-Transfer-Encoding
                    // headers since we are building this ourselves.
                    $headers = $imap_message->getHeaders();
                    $headers->removeHeader('Content-Type');
                    $headers->removeHeader('Content-Transfer-Encoding');
                    $airsync_body->data = $base->toString(array(
                        'headers' => $headers,
                        'stream' => true)
                    );
                    $airsync_body->estimateddatasize = $base->getBytes();
                } else {
                    // Signed/Encrypted message - can't mess with it at all.
                    $raw = new Horde_ActiveSync_Rfc822($imap_message->getFullMsg(true), false);
                    $airsync_body->estimateddatasize = $raw->getBytes();
                    $airsync_body->data = $raw->getString();
                    $eas_message->airsyncbaseattachments = $imap_message->getAttachments($version);
                }

                $airsync_body->type = Horde_ActiveSync::BODYPREF_TYPE_MIME;

                // MIME Truncation
                // @todo Remove this sanity-check hack in 3.0. This is needed
                // since truncationsize incorrectly defaulted to a
                // MIME_TRUNCATION constant and could be cached in the sync-cache.
                $ts = !empty($options['bodyprefs'][Horde_ActiveSync::BODYPREF_TYPE_MIME]['truncationsize'])
                    ? $options['bodyprefs'][Horde_ActiveSync::BODYPREF_TYPE_MIME]['truncationsize']
                    : false;
                $mime_truncation = (!empty($ts) && $ts > 9)
                    ? $ts
                    : (!empty($options['truncation']) && $options['truncation'] > 9
                        ? $options['truncation']
                        : false);

                $this->_logger->info(sprintf(
                    '[%s] Checking MIMETRUNCATION: %s, ServerData: %s',
                    $this->_procid,
                    $mime_truncation,
                    $airsync_body->estimateddatasize));

                if (!empty($mime_truncation) &&
                    $airsync_body->estimateddatasize > $mime_truncation) {
                    ftruncate($airsync_body->data, $mime_truncation);
                    $airsync_body->truncated = '1';
                } else {
                    $airsync_body->truncated = '0';
                }
                $eas_message->airsyncbasebody = $airsync_body;
            } elseif ($body_type_pref == Horde_ActiveSync::BODYPREF_TYPE_HTML) {
                // Sending non MIME encoded HTML message text.
                $eas_message->airsyncbasebody = $this->_buildHtmlPart($mbd, $airsync_body);
                $eas_message->airsyncbaseattachments = $imap_message->getAttachments($version);
            } elseif ($body_type_pref == Horde_ActiveSync::BODYPREF_TYPE_PLAIN) {
                // Non MIME encoded plaintext
                $this->_logger->info(sprintf(
                    '[%s] Sending PLAINTEXT Message.', $this->_procid));
                if (!empty($mbd->plain['size'])) {
                    $airsync_body->estimateddatasize = $mbd->plain['size'];
                    $airsync_body->truncated = $mbd->plain['truncated'];
                    $airsync_body->data = $mbd->plain['body']->stream;
                    $airsync_body->type = Horde_ActiveSync::BODYPREF_TYPE_PLAIN;
                    $eas_message->airsyncbasebody = $airsync_body;
                }
                $eas_message->airsyncbaseattachments = $imap_message->getAttachments($version);
            }

            // It's legal to have both a BODY and a BODYPART, so we must also
            // check for that.
            if ($version > Horde_ActiveSync::VERSION_FOURTEEN && !empty($options['bodypartprefs'])) {
                $body_part = Horde_ActiveSync::messageFactory('AirSyncBaseBodypart');
                $eas_message->airsyncbasebodypart = $this->_buildBodyPart($mbd, $options, $body_part);
            }

            if ($version > Horde_ActiveSync::VERSION_TWELVEONE) {
                $flags = array();
                $msgFlags = $this->_getMsgFlags();
                foreach ($imap_message->getFlags() as $flag) {
                    if (!empty($msgFlags[strtolower($flag)])) {
                        $flags[] = $msgFlags[strtolower($flag)];
                    }
                }
                $eas_message->categories = $flags;
            }
        }

        // Body Preview? Note that this is different from BodyPart's preview
        if ($version >= Horde_ActiveSync::VERSION_FOURTEEN && !empty($options['bodyprefs']['preview'])) {
            $mbd->plain['body']->rewind();
            $eas_message->airsyncbasebody->preview =
                $mbd->plain['body']->substring(0, $options['bodyprefs']['preview']);
        }

        // Check for special message types.
        if ($imap_message->isEncrypted()) {
            $eas_message->messageclass = 'IPM.Note.SMIME';
        } elseif ($imap_message->isSigned()) {
            $eas_message->messageclass = 'IPM.Note.SMIME.MultipartSigned';
        }
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
        if ($version >= Horde_ActiveSync::VERSION_TWELVE) {
            $eas_message->contentclass = 'urn:content-classes:message';
            if ($mime_part = $imap_message->hasiCalendar()) {
                $data = Horde_ActiveSync_Utils::ensureUtf8($mime_part->getContents(), $mime_part->getCharset());
                $vCal = new Horde_Icalendar();
                if ($vCal->parsevCalendar($data, 'VCALENDAR', $mime_part->getCharset())) {
                    $classes = $vCal->getComponentClasses();
                } else {
                    $classes = array();
                }
                if (!empty($classes['horde_icalendar_vevent'])) {
                    try {
                        $method = $vCal->getAttribute('METHOD');
                        $eas_message->contentclass = 'urn:content-classes:calendarmessage';
                    } catch (Horde_Icalendar_Exception $e) {
                    }
                    switch ($method) {
                    case 'REQUEST':
                    case 'PUBLISH':
                        $eas_message->messageclass = 'IPM.Schedule.Meeting.Request';
                        $mtg = Horde_ActiveSync::messageFactory('MeetingRequest');
                        $mtg->fromvEvent($vCal);
                        $eas_message->meetingrequest = $mtg;
                        break;
                    case 'REPLY':
                        try {
                            $reply_status = $this->_getiTipStatus($vCal);
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
                            $mtg = Horde_ActiveSync::messageFactory('MeetingRequest');
                            $mtg->fromvEvent($vCal);
                            $eas_message->meetingrequest = $mtg;
                        } catch (Horde_ActiveSync_Exception $e) {
                            $this->_logger->err($e->getMessage());
                        }
                    }
                }
            }

            if ($imap_message->getFlag(Horde_Imap_Client::FLAG_FLAGGED)) {
                $poommail_flag = Horde_ActiveSync::messageFactory('Flag');
                $poommail_flag->subject = $imap_message->getSubject();
                $poommail_flag->flagstatus = Horde_ActiveSync_Message_Flag::FLAG_STATUS_ACTIVE;
                $poommail_flag->flagtype = Horde_Imap_Client::FLAG_FLAGGED;
                $eas_message->flag = $poommail_flag;
            }
        }

        if ($version >= Horde_ActiveSync::VERSION_FOURTEEN) {
            $eas_message->messageid = $imap_message->getHeaders()->getValue('Message-ID');
            $eas_message->forwarded = $imap_message->getFlag(Horde_Imap_Client::FLAG_FORWARDED);
            $eas_message->answered  = $imap_message->getFlag(Horde_Imap_Client::FLAG_ANSWERED);
        }

        return $eas_message;
    }

    /**
     * Build the HTML body and populate the appropriate message object.
     *
     * @param Horde_ActiveSync_Imap_MessageBodyData $mbd  The body data array.
     * @param array $options    The options array.
     * @param Horde_ActiveSync_Message_AirSyncBaseBody $message
     *            The body or bodypart object.
     */
    protected function _buildHtmlPart(
        Horde_ActiveSync_Imap_MessageBodyData $mbd,
        Horde_ActiveSync_Message_AirSyncBaseBody $message)
    {
        // Sending non MIME encoded HTML message text.
        $this->_logger->info(sprintf(
            '[%s] Sending HTML Message.',
            $this->_procid));

        if (!$mbd->html) {
            $message->type = Horde_ActiveSync::BODYPREF_TYPE_PLAIN;
            $mbd->html = array(
                'body' => $mbd->plain['body'],
                'estimated_size' => $mbd->plain['size'],
                'truncated' => $mbd->plain['truncated']
            );
        } else {
            $message->type = Horde_ActiveSync::BODYPREF_TYPE_HTML;
        }

        if (!empty($mbd->html['estimated_size'])) {
            $message->estimateddatasize = $mbd->html['estimated_size'];
            $message->truncated = $mbd->html['truncated'];
            $message->data = $mbd->html['body']->stream;
        }

        return $message;
    }

    protected function _buildBodyPart(
        Horde_ActiveSync_Imap_MessageBodyData $mbd,
        array $options,
        Horde_ActiveSync_Message_AirSyncBaseBodypart $message)
    {
        $this->_logger->info(sprintf(
            '[%s] Preparing BODYPART data.',
            $this->_procid)
        );

        $message->status = Horde_ActiveSync_Message_AirSyncBaseBodypart::STATUS_SUCCESS;
        if (!empty($options['bodypartprefs']['preview']) && $mbd->plain) {
            $mbd->plain['body']->rewind();
            $message->preview = $mbd->plain['body']->substring(0, $options['bodypartprefs']['preview']);
        }
        $message->data = $mbd->bodyPart['body']->stream;
        $message->truncated = $mbd->bodyPart['truncated'];

        return $message;
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
     * Map Importance header values to EAS importance values.
     *
     * @param string $importance  The importance [high|normal|low].
     *
     * @return integer  The EAS importance value [0|1|2].
     */
    protected function _getEASImportance($importance)
    {
        switch (strtolower($importance)) {
        case '1':
        case 'high':
            return 2;
        case '5':
        case 'low':
            return 0;
        case 'normal':
        case '3':
        default:
            return 1;
        }
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
     * Return the attendee participation status.
     *
     * @param Horde_Icalendar $vCal  The vCalendar component.
     *
     * @param Horde_Icalendar
     * @throws Horde_ActiveSync_Exception
     */
    protected function _getiTipStatus($vCal)
    {
        foreach ($vCal->getComponents() as $component) {
            switch ($component->getType()) {
            case 'vEvent':
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
        $ids = new Horde_Imap_Client_Ids($uids);
        try {
            return $this->_getImapOb()->fetch($mbox, $query, array('ids' => $ids, 'exists' => true));
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
