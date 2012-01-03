<?php
/**
 * Registry connector for Horde backend. Provides the communication between
 * the Horde Registry on the local machine and the ActiveSync Horde driver.
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * Copyright 2010-2012 Horde LLC (http://www.horde.org/)
 *
 * @author  Michael J. Rubinsky <mrubinsk@horde.org>
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
     * Const'r
     *
     * @param array $params  Configuration parameters. Requires:
     * <pre>
     *   'registry' - An instance of Horde_Registry
     * </pre>
     *
     * @return Horde_ActiveSync_Driver_Horde_Connector_Registry
     */
    public function __construct($params = array())
    {
        if (empty($params['registry'])) {
            throw new InvalidArgumentException('Missing required Horde_Registry object.');
        }

        $this->_registry = $params['registry'];
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
     * Get a list of event uids that have had $action happen since $from_ts.
     *
     * @param string $action    The action to check for (add, modify, delete)
     * @param integer $from_ts  The timestamp to start checking from
     * @param integer $to_ts    The ending timestamp
     *
     * @return array  An array of event uids
     */
    public function calendar_listBy($action, $from_ts, $to_ts)
    {
        try {
            $uids = $this->_registry->calendar->listBy($action, $from_ts, null, $to_ts);
        } catch (Exception $e) {
            return array();
        }
    }

    /**
     * Export the specified event as an ActiveSync message
     *
     * @param string $uid          The calendar id
     *
     * @return Horde_ActiveSync_Message_Appointment
     */
    public function calendar_export($uid)
    {
        return $this->_registry->calendar->export($uid, 'activesync');
    }

    /**
     * Import an event into Horde's calendar store.
     *
     * @param Horde_ActiveSync_Message_Appointment $content  The event content
     * @param string $calendar                               The calendar to import event into
     *
     * @return string  The event's UID
     */
    public function calendar_import($content)
    {
        return $this->_registry->calendar->import($content, 'activesync');
    }

    /**
     * Replace the event with new data
     *
     * @param string $uid                                    The UID of the event to replace
     * @param Horde_ActiveSync_Message_Appointment $content  The new event content
     *
     * @return boolean
     */
    public function calendar_replace($uid, $content)
    {
        return $this->_registry->calendar->replace($uid, $content, 'activesync');
    }

    /**
     * Delete an event from Horde's calendar storage
     *
     * @param string $uid  The UID of the event to delete
     *
     * @return boolean
     */
    public function calendar_delete($uid)
    {
        return $this->_registry->calendar->delete($uid);
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
     * @return array of contact UIDs
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
     * @param string $content      The contact data
     * @param string $source       The contact source to import to
     *
     * @return boolean
     */
    public function contacts_import($content, $import_source = null)
    {
        return $this->_registry->contacts->import($content, 'activesync', $import_source);
    }

    /**
     * Replace the specified contact with the data provided.
     *
     * @param string $uid          The UID of the contact to replace
     * @param string $content      The contact data
     * @param string $sources      The sources where UID will be replaced
     *
     * @return boolean
     */
    public function contacts_replace($uid, $content, $sources = null)
    {
        return $this->_registry->contacts->replace($uid, $content, 'activesync', $sources);
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
     * @return string | boolean
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
    public function tasks_import($message)
    {
        return $this->_registry->tasks->import($message, 'activesync');
    }

    /**
     * Replace an existing task with the provided task.
     *
     * @param string $uid  The existing tasks's uid
     * @param Horde_ActiveSync_Message_Task $message  The task object
     *
     * @return boolean
     */
    public function tasks_replace($uid, $message)
    {
        return $this->_registry->tasks->replace($uid, $message, 'activesync');
    }

    /**
     * Delete a task from the backend.
     *
     * @param string $id  The task's uid
     *
     * @return boolean
     */
    public function tasks_delete($id)
    {
        return $this->_registry->tasks->delete($id);
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
    public function tasks_listBy($action, $from_ts)
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
        return $this->_registry->mail->folderlist();
    }

    /**
     * Get the full list of messages in the requested folder, optionally
     * restricting to a SINCE date.
     *
     * @param Horde_ActiveSync_Folder_Imap $folder  The mailbox folder.
     * @param array $options                        Additional Options:
     *   -sincedate  (integer)  Timestamp of earliest message to retrieve.
     *                          DEFAULT: 0 (Don't filter)
     *
     * @return Horde_ActiveSync_Folder_Imap  The folder object representing this
     *                                       IMAP folder.
     */
    public function mail_getMessageList(
        Horde_ActiveSync_Folder_Imap $folder, $options = array())
    {
        $imap = $this->_registry->mail->imapOb();
        $mbox = new Horde_Imap_Client_Mailbox($folder->serverid());
        $query = new Horde_Imap_Client_Search_Query();
        $query->dateSearch(
            new Horde_Date($options['sincedate']),
            Horde_Imap_Client_Search_Query::DATE_SINCE);
        $query->modSeq($folder->modSeq());
        $results = $imap->search($mbox, $query);

        $query = new Horde_Imap_Client_Fetch_Query();
        $query->flags();
        $results = $imap->fetch($mbox, $query, array('ids' => $results['match']));

        $newFolder = new Horde_ActiveSync_Folder_Imap(
            $folder->serverid(),
            $x,
            $y
        );

        return array(
            'count' => $results['count'],
            'ids'   => $results['match']->ids,
        );
    }

    /**
     * Return a AS mail messages, from the given IMAP UIDs.
     *
     * @param Horde_ActiveSync_Message_Folder $folder  The mailbox folder.
     * @param array $messages                          List of IMAP message UIDs
     * @param array $options                           Additional Options:
     *   -truncation:  (integer)  Truncate body of email to this length.
     *                            DEFAULT: false (No truncation).
     *
     * @return array  An array of Horde_ActiveSync_Message_Mail objects.
     * @throws Horde_Exception
     */
    public function mail_getMessages($folder, $messages, $options = array())
    {
        $imap = $this->_registry->mail->imapOb();
        $query = new Horde_Imap_Client_Fetch_Query();
        $query->getStructure();
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
            $messages[] = $this->_buildMailMessage($result, $options);
        }

        return $messages;
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
        Horde_Imap_Client_Mailbox $mailbox,
        Horde_Imap_Client_Data_Fetch $data,
        $options = array())
    {
        $part = $data->getStructure();
        $id = $part->findBody();
        $body = $part->getPart($id);

        $imap = $this->_registry->mail->imapOb();
        $query = new Horde_Imap_Client_Fetch_Query();
        $query->envelope();
        $qopts = array(
            'decode' => true,
            'peek' => true
        );
        if ($options['truncation']) {
            $qopts['length'] = $options['truncation'];
        }
        $query->bodyPart($id, $qopts);
        try {
            $messages = $imap->fetch(
                $mbox,
                $query,
                new Horde_Imap_Client_Ids(array($data->uid)));
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
        $message->body = $text;
        $message->bodysize = strlen($message->body);
        $message->bodytruncated = $options['truncation'] ? 1 : 0;

        // Parse To: header
        $to = $envelope->to_decoded;
        $tos = array();
        foreach ($to as $r) {
            $tos[] = Horde_Mime_Address::writeAddress($r['mailbox'], $r['host'], $r['personal']);
            $dtos[] = $to['personal'];
        }
        $message->to = implode(',', $tos);
        $message->displayto = implode(',', $dtos);

        // Parse From: header
        $from = array_pop($envelope->from_decoded);
        $message->from = Horde_Mime_Address::writeAddress($from['mailbox'], $from['host'], $from['personal']);

        $message->subject = $envelope->subject_decoded;
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

}
