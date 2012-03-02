<?php
/**
 * Registry connector for Horde backend.
 *
 * @copyright 2010-2012 Horde LLC (http://www.horde.org/)
 * @license http://www.horde.org/licenses/lgpl21 LGPL
 * @author  Michael J Rubinsky <mrubinsk@horde.org>
 * @package Core
 */
/**
 * Registry connector for Horde backend. Provides the communication between
 * the Horde Registry on the local machine and the ActiveSync Horde driver.
 *
 * @copyright 2010-2012 Horde LLC (http://www.horde.org/)
 * @license http://www.horde.org/licenses/lgpl21 LGPL
 * @author  Michael J Rubinsky <mrubinsk@horde.org>
 * @package Core
 */
class Horde_Core_ActiveSync_Connector
{
    /**
     * Horde registry
     *
     * @var Horde_Registry
     */
    private $_registry;

    /**
     * The logger
     *
     * @var Horde_Log_Logger
     */
    private $_logger;

    /**
     * Local cache of imap object.
     *
     * @var Horde_Imap_Client_Base
     */
    private $_imap;

    /**
     * Local cache of folderlist
     *
     * @var array
     */
    private $_folderlist;

    /**
     * Local cache of special mailbox list
     *
     * @var array
     */
    private $_specialMailboxes;

    /**
     * Const'r
     *
     * @param array $params  Configuration parameters. Requires:
     *     - registry: An instance of Horde_Registry
     *
     * @return Horde_ActiveSync_Driver_Horde_Connector_Registry
     * @throws InvalidArgumentException
     */
    public function __construct($params = array())
    {
        if (empty($params['registry'])) {
            throw new InvalidArgumentException('Missing required Horde_Registry object.');
        }

        $this->_registry = $params['registry'];
    }

    /**
     * Set a logger for this object.
     *
     * @var Horde_Log_Logger $logger  The logger.
     */
    public function setLogger($logger)
    {
        $this->_logger = $logger;
    }

    /**
     * Get a list of events from horde's calendar api
     *
     * @param integer $startstamp    The start of time period.
     * @param integer $endstamp      The end of time period
     *
     * @return array
     */
    public function calendar_listUids($startstamp, $endstamp)
    {
        try {
            return $this->_registry->calendar->listUids(null, $startstamp, $endstamp);
        } catch (Exception $e) {
            return array();
        }
    }

    /**
     * Export the specified event as an ActiveSync message
     *
     * @param string $uid          The calendar id.
     *
     * @return Horde_ActiveSync_Message_Appointment  The requested event.
     */
    public function calendar_export($uid)
    {
        return $this->_registry->calendar->export($uid, 'activesync');
    }

    /**
     * Import an event into the user's default calendar.
     *
     * @param Horde_ActiveSync_Message_Appointment $content  The event content
     *
     * @return string  The event's UID.
     */
    public function calendar_import(Horde_ActiveSync_Message_Appointment $content)
    {
        return $this->_registry->calendar->import($content, 'activesync');
    }

    /**
     * Replace the event with new data
     *
     * @param string $uid                                    The UID of the
     *                                                       event to replace.
     * @param Horde_ActiveSync_Message_Appointment $content  The new event.
     */
    public function calendar_replace($uid, Horde_ActiveSync_Message_Appointment $content)
    {
        $this->_registry->calendar->replace($uid, $content, 'activesync');
    }

    /**
     * Delete an event from Horde's calendar storage
     *
     * @param string $uid  The UID of the event to delete
     */
    public function calendar_delete($uid)
    {
        $this->_registry->calendar->delete($uid);
    }

    /**
     * Return the timestamp for the last time $action was performed.
     *
     * @param string $uid     The UID of the event we are interested in.
     * @param string $action  The action we are interested in (add, modify...)
     *
     * @return integer
     */
    public function calendar_getActionTimestamp($uid, $action)
    {
        return $this->_registry->calendar->getActionTimestamp($uid, $action);
    }

    /**
     * Get a list of all contacts a user can see
     *
     * @return array An array of contact UIDs
     */
    public function contacts_listUids()
    {
        return $this->_registry->contacts->listUids();
    }

    /**
     * Export the specified contact from Horde's contacts storage
     *
     * @param string $uid          The contact's UID
     *
     * @return array The contact hash
     */
    public function contacts_export($uid)
    {
        return $this->_registry->contacts->export($uid, 'activesync');
    }

    /**
     * Import the provided contact data into Horde's contacts storage
     *
     * @param Horde_ActiveSync_Message_Contact $content      The contact data
     *
     * @return mixed  string|boolean  The new UID or false on failure.
     */
    public function contacts_import(Horde_ActiveSync_Message_Contact $content)
    {
        return $this->_registry->contacts->import($content, 'activesync');
    }

    /**
     * Replace the specified contact with the data provided.
     *
     * @param string $uid          The UID of the contact to replace
     * @param string $content      The contact data
     */
    public function contacts_replace($uid, $content)
    {
        $this->_registry->contacts->replace($uid, $content, 'activesync', $sources);
    }

    /**
     * Delete the specified contact
     *
     * @param string $uid  The UID of the contact to remove
     *
     * @return bolean
     */
    public function contacts_delete($uid)
    {
        return $this->_registry->contacts->delete($uid);
    }

    /**
     * Get the timestamp of the most recent occurance of $action for the
     * specifed contact
     *
     * @param string $uid     The UID of the contact to search
     * @param string $action  The action to lookup
     *
     * @return integer
     */
    public function contacts_getActionTimestamp($uid, $action)
    {
        return $this->_registry->contacts->getActionTimestamp($uid, $action);
    }

    /**
     * Get a list of contact uids that have had $action happen since $from_ts.
     *
     * @param string $action    The action to check for (add, modify, delete)
     * @param integer $from_ts  The timestamp to start checking from
     * @param integer $to_ts    The ending timestamp
     *
     * @return array  An array of event uids
     */
    public function contacts_listBy($action, $from_ts, $to_ts)
    {
        return $this->_registry->contacts->listBy($action, $from_ts, null, $to_ts);
    }

    public function contacts_search($query)
    {
        $gal = $this->contacts_getGal();
        $fields = array($gal => array('firstname', 'lastname', 'alias', 'name', 'email'));
        return $this->_registry->contacts->search(array($query), array($gal), $fields, true, true);
    }

    /**
     * Get the GAL source uid.
     *
     * @return string | boolean  The address book id of the GAL, or false if
     *                           not available.
     */
    public function contacts_getGal()
    {
        return $this->_registry->contacts->getGalUid();
    }

    /**
     * List all tasks in the user's default tasklist.
     *
     * @return array  An array of task uids.
     */
    public function tasks_listUids()
    {
        return $this->_registry->tasks->listUids();
    }

    public function tasks_listTaskLists()
    {
        return $this->_registry->tasks->listTaskLists();
    }

    /**
     * Export a single task from the backend.
     *
     * @param string $uid  The task uid
     *
     * @return Horde_ActiveSync_Message_Task  The task message object
     */
    public function tasks_export($uid)
    {
        return $this->_registry->tasks->export($uid, 'activesync');
    }

    /**
     * Importa a single task into the backend.
     *
     * @param Horde_ActiveSync_Message_Task $message  The task message object
     *
     * @return string  The newly added task's uid.
     */
    public function tasks_import(Horde_ActiveSync_Message_Task $message)
    {
        return $this->_registry->tasks->import($message, 'activesync');
    }

    /**
     * Replace an existing task with the provided task.
     *
     * @param string $uid  The existing tasks's uid
     * @param Horde_ActiveSync_Message_Task $message  The task object
     */
    public function tasks_replace($uid, Horde_ActiveSync_Message_Task $message)
    {
        $this->_registry->tasks->replace($uid, $message, 'activesync');
    }

    /**
     * Delete a task from the backend.
     *
     * @param string $id  The task's uid
     */
    public function tasks_delete($id)
    {
        $this->_registry->tasks->delete($id);
    }

    /**
     * Return the timestamp for the last time $action was performed.
     *
     * @param string $uid     The UID of the task we are interested in.
     * @param string $action  The action we are interested in (add, modify...)
     *
     * @return integer
     */
    public function tasks_getActionTimestamp($uid, $action)
    {
        return $this->_registry->tasks->getActionTimestamp($uid, $action);
    }

    /**
     * Get a list of task uids that have had $action happen since $from_ts.
     *
     * @param string $action    The action to check for (add, modify, delete)
     * @param integer $from_ts  The timestamp to start checking from
     * @param integer $to_ts    The ending timestamp
     *
     * @return array  An array of event uids
     */
    public function tasks_listBy($action, $from_ts, $to_ts)
    {
        return $this->_registry->tasks->listBy($action, $from_ts, null, $to_ts);
    }

    /**
     * Return all active api interfaces.
     *
     * @return array  An array of interface names.
     */
    public function horde_listApis()
    {
        return $this->_registry->horde->listAPIs();
    }

    /**
     * Obtain a user's preference setting.
     *
     * @param string $app  The Horde application providing the setting.
     * @param string $pref The name of the preference setting.
     *
     * @return mixed  The preference value
     */
    public function horde_getPref($app, $pref)
    {
        return $this->_registry->horde->getPreference($app, $pref);
    }

    /**
     * Obtain the name of the Horde application that provides the specified api
     * interface.
     *
     * @param string $api  The interface name
     *
     * @return string  The application name.
     */
    public function horde_hasInterface($api)
    {
        return $this->_registry->hasInterface($api);
    }

    /**
     * Get all server changes for the specified collection
     *
     * @param string $collection  The collection type (calendar, contacts, tasks)
     * @param integer $from_ts    Starting timestamp
     * @param integer $to_ts      Ending timestamp
     *
     * @return array  A hash of add, modify, and delete uids
     * @throws InvalidArgumentException
     */
    public function getChanges($collection, $from_ts, $to_ts)
    {
        if (!in_array($collection, array('calendar', 'contacts', 'tasks'))) {
            throw new InvalidArgumentException('collection must be one of calendar, contacts, or tasks');
        }
        try {
            return $this->_registry->{$collection}->getChanges($from_ts, $to_ts);
        } catch (Exception $e) {
            return array('add' => array(),
                         'modify' => array(),
                         'delete' => array());
        }
    }

    /**
     * Get the IMAP folder list.
     *
     * @return array  An array of folders.
     */
    public function mail_folderList()
    {
        return $this->_getFolderlist();
    }

    /**
     * Get the full list of messages in the requested folder, optionally
     * restricting to a SINCE date.
     *
     * @param Horde_ActiveSync_Folder_Imap $folder  The mailbox folder.
     * @param array $options                        Additional Options:
     *   - sincedate:  Timestamp of earliest message to retrieve.
     *                 DEFAULT: 0 (Don't filter)
     *
     * @return Horde_ActiveSync_Folder_Imap  The folder object representing this
     *                                       IMAP folder.
     */
    public function mail_getMessageList(
        Horde_ActiveSync_Folder_Imap $folder,
        $options = array())
    {
        $imap = $this->_getImapOb();
        $mbox = new Horde_Imap_Client_Mailbox($folder->serverid());

        $status = $imap->status($mbox,
            Horde_Imap_Client::STATUS_HIGHESTMODSEQ |
            Horde_Imap_Client::STATUS_UIDVALIDITY |
            Horde_Imap_Client::STATUS_UIDNEXT
        );

        // If we don't support QRESYNC, don't bother with modseq.
        if ($qresync = $imap->queryCapability('QRESYNC')) {
            $modseq = $status['highestmodseq'];
        } else {
            $modseq = $status[Horde_Imap_Client::STATUS_HIGHESTMODSEQ] = 0;
        }

        $this->_logger->debug('IMAP status: ' . print_r($status, true));
        if ($qresync && $folder->modseq() > 0 && $folder->modseq() < $modseq) {
            // If we are here, we support QRESYNC and have known changes
            $query = new Horde_Imap_Client_Fetch_Query();
            $query->modseq();
            $query->flags();
            $messages = $imap->fetch($mbox, $query, array(
                'changedsince' => $folder->modseq()
            ));

            // Need to make sure there were no further changes after the
            // modseq reported above. This would happen if a change occurs after
            // the $imap->status() call. This would lead to duplicate fetches
            // on the next sync, since we have the older modseq value.
            // We also currently ignore messages with /deleted set since EAS
            // 2.5 doesn't support showing deleted messages.
            $changes = array();
            foreach ($messages as $uid => $message) {
                if ($message->getModSeq() <= $modseq &&
                    array_search('\deleted', $message->getFlags()) === false) {
                    $changes[] = $uid;
                    $flags[$uid] = array(
                        'read' => (array_search('\seen', $message->getFlags()) !== false) ? 1 : 0
                    );
                }
            }
            $folder->setChanges($changes, $flags);
            $query = new Horde_Imap_Client_Fetch_Query();

            // Get deleted.
            $deleted = $imap->vanished($mbox, $folder->modseq());
            $folder->setRemoved($deleted->ids);

        } elseif ($folder->modseq() == 0) {
            // This is either an initial priming or we don't support QRESYNC
            // Either way, we need the full message uid list.
            $query = new Horde_Imap_Client_Search_Query();
            if ($options['sincedate']) {
                $query->dateSearch(
                    new Horde_Date($options['sincedate']),
                    Horde_Imap_Client_Search_Query::DATE_SINCE);
            }
            $query->flag(Horde_Imap_Client::FLAG_DELETED, false);
            $results = $imap->search(
                $mbox,
                $query,
                array('results' => array(Horde_Imap_Client::SEARCH_RESULTS_MATCH, Horde_Imap_Client::SEARCH_RESULTS_MIN)));

            if ($qresync && $modseq > 0) {
                // Support QRESYNC, but this is initial priming - set the results.
                $folder->setChanges($results['match']->ids);
                $status[Horde_ActiveSync_Folder_Imap::MINUID] = $results['min'];
            } else {
                // No QRESYNC, perform some magic.
                $uids = $folder->messages();
                $deleted = array_diff($uids, $results['match']);
                $changed = array_diff($uids, $deleted);
                // All changes in AS are a change in /seen. Get the flags only
                // for the messages we think have been changed and set them in
                // the folder. We don't care about what state the flag is in
                // on the device currently. We will assume that all flag changes
                // from the server are authoritative. There is no other sane way
                // to do this without killing the server.
                $query = new Horde_Imap_Client_Fetch_Query();
                $query->flags();
                $messages = $imap->fetch($mbox, $query, array('uids' => $changed));
                $flags = array();
                foreach ($messages as $uid => $message) {
                    $flags[$uid] = array(
                        'read' => (array_search('\seen', $message->getFlags()) !== false) ? 1 : 0
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
    public function mail_moveMessage($folderid, $id, $newfolderid)
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
            $results = $imap->copy($from, $to, array('ids' => $ids, 'move' => true));
        } catch (Horde_Imap_Client_Exception $e) {
            throw new Horde_Exception($e);
        }
        if (is_array($results)) {
            return $results;
        }

        return array($id => $uidnext);
    }

    /**
     * Permanently delete a mail message.
     *
     * @param array $uids     The message UIDs
     * @param string $folder  The folder id.
     */
    public function mail_deleteMessages($uids, $folder)
    {
        $imap = $this->_getImapOb();
        $mbox = new Horde_Imap_Client_Mailbox($folder);
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
     * @param Horde_ActiveSync_Message_Folder $folder  The mailbox folder.
     * @param array $messages                        List of IMAP message UIDs
     * @param array $options                         Additional Options:
     *   -truncation:  (integer)  Truncate body of email to this length.
     *                            DEFAULT: false (No truncation).
     *
     * @return array  An array of Horde_ActiveSync_Message_Mail objects.
     * @throws Horde_Exception
     */
    public function mail_getMessages(
        Horde_ActiveSync_Message_Folder $folder, array $messages, array $options = array())
    {
        $imap = $this->_getImapOb();
        $query = new Horde_Imap_Client_Fetch_Query();
        $query->structure();
        $query->uid;
        $ids = new Horde_Imap_Client_Ids($messages);
        $mbox = new Horde_Imap_Client_Mailbox($folder->serverid);
        $messages = array();
        try {
            $results = $imap->fetch($mbox, $query, array('ids' => $ids));
        } catch (Horde_Imap_Client_Exception $e) {
            throw new Horde_Exception($e);
        }
        foreach ($results as $result) {
            $messages[] = $this->_buildMailMessage($mbox, $result, $options);
        }

        return $messages;
    }

    public function mail_getSpecialFolders()
    {
        return $this->_getSpecialMailboxes();
    }

    public function mail_setReadFlag($mbox, $uid, $flag)
    {
        $imap = $this->_getImapOb();
        $mbox = new Horde_Imap_Client_Mailbox($mbox);
        $options = array(
            'ids' => new Horde_Imap_Client_Ids(array($uid)),
        );
        if ($flag == Horde_ActiveSync_Message_Mail::FLAG_READ_SEEN) {
            $options['add'] = array(Horde_Imap_Client::FLAG_SEEN);
        } else if ($flag == Horde_ActiveSync_Message_Mail::FLAG_READ_UNSEEN) {
            $options['remove'] = array(Horde_Imap_Client::FLAG_SEEN);
        }
        $imap->store($mbox, $options);
    }

    /**
     * Builds a proper AS mail message object.
     *
     * @param Horde_Imap_Client_Mailbox    $mbox  The IMAP mailbox.
     * @param Horde_Imap_Client_Data_Fetch $data  The fetch results.
     * @param array $options                      Additional Options:
     *   -truncation  Truncate the message body to this length.
     *
     * @return Horde_ActiveSync_Mail_Message
     * @throws Horde_Exception
     */
    protected function _buildMailMessage(
        Horde_Imap_Client_Mailbox $mbox,
        Horde_Imap_Client_Data_Fetch $data,
        $options = array())
    {
        $part = $data->getStructure();
        $id = $part->findBody();
        $body = $part->getPart($id);
        $charset = $body->getCharset();
        $imap = $this->_getImapOb();
        $query = new Horde_Imap_Client_Fetch_Query();
        $query->envelope();
        $query->flags();
        $qopts = array(
            'decode' => true,
            'peek' => true
        );
        // Figure out if we need the body, and if so, how to truncate it.
        if (isset($options['truncation']) && $options['truncation'] > 0) {
            $qopts['length'] = $options['truncation'];
        }
        if ((isset($options['truncation']) && $options['truncation'] > 0) ||
            !isset($options['truncation'])) {
            $query->bodyPart($id, $qopts);
        }

        try {
            $messages = $imap->fetch(
                $mbox,
                $query,
                array('ids' => new Horde_Imap_Client_Ids(array($data->getUid()))));
        } catch (Horde_Imap_Client_Exception $e) {
            throw new Horde_Exception($e);
        }
        $data = array_pop($messages);
        $envelope = $data->getEnvelope();

        // Get the plaintext part.
        $text = $data->getBodyPart($id);
        if (!$data->getBodyPartDecode($id)) {
            $body->setContents($data->getBodyPart($id));
            $text = $body->getContents();
        }

        $message = new Horde_ActiveSync_Message_Mail();
        $message->body = Horde_String::convertCharset($text, $charset, 'UTF-8');
        $message->bodysize = Horde_String::length($message->body);
        $message->bodytruncated = isset($options['truncation']) ? 1 : 0;

        // Parse To: header
        $to = $envelope->to->addresses;
        $tos = array();
        foreach ($to as $r) {
            $a = new Horde_Mail_Rfc822_Address($r);
            $tos[] = $a->writeAddress(true);
            $dtos[] = $a->personal;
        }
        $message->to = implode(',', $tos);
        $message->displayto = implode(',', $dtos);

        // Parse From: header
        $from = array_pop($envelope->from->addresses);
        $a = new Horde_Mail_Rfc822_Address($from);
        $message->from = $a->writeAddress(true);

        $message->subject = $envelope->subject;
        $message->datereceived = new Horde_Date((string)$envelope->date);


        // @TODO: Parse out/detect at least meeting requests and notifications.
        $message->messageclass = 'IPM.Note';

        // Seen flag
        if (array_search('\seen', $data->getFlags()) !== false) {
            $message->read = 1;
        } else {
            $message->read = 0;
        }

        return $message;
    }

    /**
     * Clear the authentication and destroy the current session.
     */
    public function clearAuth()
    {
        $this->_registry->clearAuth(true);
    }

    /**
     * Helper for getting an imap object.
     *
     * @return Horde_Imap_Client_Base
     */
    protected function _getImapOb()
    {
        if (!empty($this->_imap)) {
            return $this->_imap;
        }
        $this->_imap = $this->_registry->mail->imapOb();

        return $this->_imap;
    }

    /**
     * Helper for getting a list of special mailboxes.
     *
     * @return array
     */
    protected function _getSpecialMailboxes()
    {
        if (!empty($this->_specialMailboxes)) {
            return $this->_specialMailboxes;
        }
        $this->_specialMailboxes = $this->_registry->mail->getSpecialMailboxes();

        return $this->_specialMailboxes;
    }

    /**
     * Helper for getting folderlist.
     *
     * @var array
     */
    protected function _getFolderlist()
    {
        if (!empty($this->_folderlist)) {
            return $this->_folderlist;
        }

        $this->_folderlist = $this->_registry->mail->folderlist();

        return $this->_folderlist;
    }

}
