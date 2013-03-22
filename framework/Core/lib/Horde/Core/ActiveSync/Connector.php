<?php
/**
 * Registry connector for Horde backend.
 *
 * @copyright 2010-2013 Horde LLC (http://www.horde.org/)
 * @license http://www.horde.org/licenses/lgpl21 LGPL
 * @author  Michael J Rubinsky <mrubinsk@horde.org>
 * @package Core
 */
/**
 * Registry connector for Horde backend. Provides the communication between
 * the Horde Registry on the local machine and the ActiveSync Horde driver.
 *
 * @copyright 2010-2013 Horde LLC (http://www.horde.org/)
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
    protected $_logger;

    /**
     * Cache the GAL to avoid hitting the contacts API multiple times.
     *
     * @var string
     */
    protected $_gal;

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
     * @param array $options       Options to pass to the backend exporter.
     *   - protocolversion: (float)  The EAS version to support
     *                      DEFAULT: 2.5
     *   - bodyprefs: (array)  A BODYPREFERENCE array.
     *                DEFAULT: none (No body prefs enforced).
     *   - truncation: (integer)  Truncate event body to this length
     *                 DEFAULT: none (No truncation).
     *
     * @return Horde_ActiveSync_Message_Appointment  The requested event.
     */
    public function calendar_export($uid, array $options = array())
    {
        return $this->_registry->calendar->export($uid, 'activesync', $options);
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
     * Import a Horde_Icalendar_vEvent into a user's calendar. Used for creating
     * events from meeting invitations.
     *
     * @param Horde_Icalendar_vEvent $vEvent  The event data.
     *
     * @return string The event's UID.
     */
    public function calendar_import_vevent(Horde_Icalendar_vEvent $vEvent)
    {
        return $this->_registry->calendar->import($vEvent, 'text/calendar');
    }

    /**
     * Import an event response into a user's calendar. Used for updating
     * attendee information from a meeting response.
     *
     * @param Horde_Icalendar_vEvent $vEvent  The event data.
     * @param string $attendee                The attendee.
     */
    public function calendar_import_attendee(Horde_Icalendar_vEvent $vEvent,
                                             $attendee)
    {
        if ($this->_registry->hasMethod('calendar/updateAttendee')) {
            // If the mail interface (i.e., IMP) provides a mime driver for
            // iTips, check if we are allowed to autoupdate. If we have no
            // configuration, err on the side of caution and DO NOT auto import.
            $config = $GLOBALS['injector']
                ->getInstance('Horde_Core_Factory_MimeViewer')
                ->getViewerConfig('text/calendar', $GLOBALS['registry']->hasInterface('mail'));

            if ($config[1]['driver'] == 'Itip' && !empty($config[1]['auto_update_eventreply'])) {
                if (is_array($config[1]['auto_update_eventreply'])) {
                    $adr = new Horde_Mail_Rfc822_Address($attendee);
                    $have_match = false;
                    foreach ($config[1]['auto_update_eventreply'] as $val) {
                        if ($adr->matchDomain($val)) {
                            $have_match = true;
                            break;
                        }
                    }
                    if (!$have_match) {
                        return;
                    }
                }

                try {
                   $this->_registry->calendar->updateAttendee($vEvent, $attendee);
                } catch (Horde_Exception $e) {
                    $this->_logger->err($e->getMessage());
                }
            }
        }
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
     * @param string $uid     The contact's UID
     * @param array $options  Exporter options:
     *   - protocolversion: (float)  The EAS version to support
     *                      DEFAULT: 2.5
     *   - bodyprefs: (array)  A BODYPREFERENCE array.
     *                DEFAULT: none (No body prefs enforced).
     *   - truncation: (integer)  Truncate event body to this length
     *                 DEFAULT: none (No truncation).
     *
     * @return Horde_ActiveSync_Message_Contact  The contact object.
     */
    public function contacts_export($uid, array $options = array())
    {
        return $this->_registry->contacts->export($uid, 'activesync', null, null, $options);
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
        $this->_registry->contacts->replace($uid, $content, 'activesync');
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

    /**
     * Search the contacts store.
     *
     * @param string $query   The search string.
     * @param array $options  Additional options:
     *   - photos: (boolean) Include photos in results.
     *             DEFAULT: false (Do not include photos).
     *
     * @return array  The search results.
     */
    public function contacts_search($query, array $options = array())
    {
        $gal = $this->contacts_getGal();
        if (!empty($gal)) {
            $fields = array($gal => array('firstname', 'lastname', 'alias', 'name', 'email', 'office'));
            if (!empty($options['photos'])) {
                $fields[$gal][] = 'photo';
            }
            $opts = array(
                'fields' => $fields,
                'matchBegin' => true,
                'forceSource' => true,
                'sources' => array($gal)
            );

            return $this->_registry->contacts->search($query, $opts);
        }
    }

    /**
     * Resolve a recipient
     *
     * @param string $query  The search string. Ususally an email address.
     * @param array $opts    Any additional options:
     *  - maxcerts: (integer)     The maximum number of certificates to return
     *                             as provided by the client.
     *  - maxambiguous: (integer) The maximum number of ambiguous results. If
     *                            set to zero, we MUST have an exact match.
     *  - starttime: (Horde_Date) The start time for the availability window if
     *                            requesting AVAILABILITY.
     *  - endtime: (Horde_Date)   The end of the availability window if
     *                            requesting AVAILABILITY.
     *
     * @return array  The search results, keyed by the $query.
     */
    public function resolveRecipient($query, array $opts = array())
    {
        if (!empty($opts['starttime'])) {
            try {
                return array($query => $this->_registry->calendar->lookupFreeBusy($query, true));
            } catch (Horde_Exception $e) {
                return false; // ?
            }
        }

        $gal = $this->contacts_getGal();
        $sources = array_keys($this->_registry->contacts->sources(false, true));
        if (!in_array($sources, $gal)) {
            $sources[] = $gal;
        }
        foreach ($sources as $source) {
            $fields[$source] = array('name', 'email', 'alias', 'smimePublicKey');
        }
        $options = array(
            'matchBegin' => true,
            'sources' => $sources,
            'fields' => $fields
        );
        if (isset($opts['maxAmbiguous']) && $opts['maxAmbiguous'] == 0) {
            $options['customStrict'] = array('email', 'name', 'alias');
        }
        return $this->_registry->contacts->search($query, $options);
    }

    /**
     * Get the GAL source uid.
     *
     * @return string | boolean  The address book id of the GAL, or false if
     *                           not available.
     */
    public function contacts_getGal()
    {
        if (empty($this->_gal)) {
            $this->_gal = $this->_registry->contacts->getGalUid();
        }
        return $this->_gal;
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

    /**
     * Export a single task from the backend.
     *
     * @param string $uid     The task uid
     * @param array $options  Options to pass to the backend exporter.
     *
     * @return Horde_ActiveSync_Message_Task  The task message object
     */
    public function tasks_export($uid, array $options = array())
    {
        return $this->_registry->tasks->export($uid, 'activesync', $options);
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
     * List note lists.
     *
     * @return array
     * @since 5.1
     */
    public function tasks_listNoteLists()
    {
        return $this->_registry->notes->listTaskLists();
    }

    /**
     * List all notes in the user's default notepad.
     *
     * @return array  An array of note uids.
     * @since 5.1
     */
    public function notes_listUids()
    {
        return $this->_registry->notes->listUids();
    }

    /**
     * Export a single note from the backend.
     *
     * @param string $uid     The note uid
     * @param array $options  Options to pass to the backend exporter.
     *
     * @return Horde_ActiveSync_Message_Note  The note message object
     * @since 5.1
     */
    public function notes_export($uid, array $options = array())
    {
        return $this->_registry->notes->export($uid, 'activesync', $options);
    }

    /**
     * Importa a single note into the backend.
     *
     * @param Horde_ActiveSync_Message_Note $message  The note message object
     *
     * @return string  The newly added notes's uid.
     * @since 5.1
     */
    public function notes_import(Horde_ActiveSync_Message_Note $message)
    {
        return $this->_registry->notes->import($message, 'activesync');
    }

    /**
     * Replace an existing task with the provided task.
     *
     * @param string $uid  The existing tasks's uid
     * @param Horde_ActiveSync_Message_Note $message  The task object
     * @since 5.1
     */
    public function notes_replace($uid, Horde_ActiveSync_Message_Note $message)
    {
        $this->_registry->notes->replace($uid, $message, 'activesync');
    }

    /**
     * Delete a note from the backend.
     *
     * @param string $id  The task's uid
     * @since 5.1
     */
    public function notes_delete($id)
    {
        $this->_registry->notes->delete($id);
    }

    /**
     * Return the timestamp for the last time $action was performed.
     *
     * @param string $uid     The UID of the task we are interested in.
     * @param string $action  The action we are interested in (add, modify...)
     *
     * @return integer
     * @since 5.1
     */
    public function notes_getActionTimestamp($uid, $action)
    {
        return $this->_registry->notes->getActionTimestamp($uid, $action);
    }

    /**
     * Get a list of note uids that have had $action happen since $from_ts.
     *
     * @param string $action    The action to check for (add, modify, delete)
     * @param integer $from_ts  The timestamp to start checking from
     * @param integer $to_ts    The ending timestamp
     *
     * @return array  An array of note uids
     * @since 5.1
     */
    public function notes_listBy($action, $from_ts, $to_ts)
    {
        return $this->_registry->notes->listBy($action, $from_ts, null, $to_ts);
    }

    /**
     * Return all active api interfaces.
     *
     * @return array  An array of interface names.
     */
    public function horde_listApis()
    {
        $apps = $this->_registry->horde->listAPIs();

        // Note support not added until 5.1. Need to check the feature.
        // @TODO: H6, add this check to all apps. BC break to check it now,
        // since we didn't have this feature earlier.
        if ($key = array_search('notes', $apps)) {
            if (!$this->_registry->hasFeature('activesync', $this->_registry->hasInterface('notes'))) {
                unset($apps[$key]);
            }
        }

        return $apps;
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
     * Return the currently set vacation message details.
     *
     * @return array  The vacation rule properties.
     */
    public function filters_getVacation()
    {
        return $this->_registry->filter->getVacation();
    }

    /**
     * Set vacation message properties.
     *
     * @param array $setting  The vacation details.
     */
    public function filters_setVacation(array $setting)
    {
        if ($setting['oofstate'] == Horde_ActiveSync_Request_Settings::OOF_STATE_ENABLED) {
            // Only support a single message, the APPLIESTOINTERNAL message.
            foreach ($setting['oofmsgs'] as $msg) {
                if ($msg['appliesto'] == Horde_ActiveSync_Request_Settings::SETTINGS_APPLIESTOINTERNAL) {
                    $vacation = array(
                        'reason' => $msg['replymessage'],
                        'subject' => Horde_Core_Translation::t('Out Of Office')
                    );
                    $this->_registry->filter->setVacation($vacation);
                    return;
                }
            }
        } else {
            $this->_registry->filter->disableVacation();
        }
    }

    /**
     * Return a Maillog entry for the specified Message-ID.
     *
     * @param string $mid  The Message-ID of the message.
     *
     * @return Horde_History_Log|false  The history log or false if not found.
     */
    public function mail_getMaillog($mid)
    {
        if ($GLOBALS['registry']->hasMethod('getMaillog', $GLOBALS['registry']->hasInterface('mail'))) {
            return $GLOBALS['registry']->mail->getMaillog($mid);
        }

        return false;
    }

    /**
     * Log a forward/reply action to the maillog.
     *
     * @param string $action      The action to log. One of: 'forward', 'reply',
     *                            'reply_all'.
     * @param string $mid         The Message-ID to log.
     * @param string $recipients  The recipients the mail was forwarded/replied
     *                            to.
     */
    public function mail_logMaillog($action, $mid, $recipients = null)
    {
        if ($GLOBALS['registry']->hasMethod('logMaillog', $GLOBALS['registry']->hasInterface('mail'))) {
            $GLOBALS['registry']->mail->logMaillog($action, $mid, $recipients);
        }
    }

    /**
     * Poll the maillog for changes since the specified timestamp.
     *
     * @param integer $ts  The timestamp to check since.
     *
     * @return array  An array of Message-IDs that have changed since $ts.
     */
    public function mail_getMaillogChanges($ts)
    {
        if ($GLOBALS['registry']->hasMethod('getMaillogChanges', $GLOBALS['registry']->hasInterface('mail'))) {
            return $GLOBALS['registry']->mail->getMaillogChanges($ts);
        }
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
        if (!in_array($collection, array('calendar', 'contacts', 'tasks', 'notes'))) {
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
     * Clear the authentication and destroy the current session.
     */
    public function clearAuth()
    {
        $this->_registry->clearAuth(true);
    }

}
