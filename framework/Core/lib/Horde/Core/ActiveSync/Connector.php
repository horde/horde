<?php
/**
 * Registry connector for Horde backend.
 *
 * @copyright 2010-2014 Horde LLC (http://www.horde.org/)
 * @license http://www.horde.org/licenses/lgpl21 LGPL
 * @author  Michael J Rubinsky <mrubinsk@horde.org>
 * @package Core
 */
/**
 * Registry connector for Horde backend. Provides the communication between
 * the Horde Registry on the local machine and the ActiveSync Horde driver.
 *
 * @copyright 2010-2014 Horde LLC (http://www.horde.org/)
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
    protected $_registry;

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
     * Cache results of capability queries
     *
     * @var array
     */
    protected $_capabilities = array();

    /**
     * Cache list of folders
     *
     * @var array
     */
    protected $_folderCache = array();

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
     * @param string  $calendar      The calendar id. If null, uses multiplexed.
     *                               @since 2.12.0
     *
     * @return array
     */
    public function calendar_listUids($startstamp, $endstamp, $calendar)
    {
        try {
            return $this->_registry->calendar->listUids($calendar, $startstamp, $endstamp);
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
     * @param string $calendar       The calendar id. If null, uses multiplexed.
     *                               @since 2.12.0
     *
     * @return Horde_ActiveSync_Message_Appointment  The requested event.
     */
    public function calendar_export($uid, array $options = array(), $calendar = null)
    {
        $calendar = empty($calendar) ? null : array($calendar);
        return $this->_registry->calendar->export($uid, 'activesync', $options, $calendar);
    }

    /**
     * Import an event into the user's default calendar.
     *
     * @param Horde_ActiveSync_Message_Appointment $content  The event content
     * @param string $calendar                               The calendar id.
     *                                                       @since 2.12.0
     *
     * @return string  The event's UID.
     */
    public function calendar_import(
        Horde_ActiveSync_Message_Appointment $content, $calendar = null)
    {
        return $this->_registry->calendar->import($content, 'activesync', $calendar);
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
                ->getViewerConfig('text/calendar', $this->_registry->hasInterface('mail'));

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
     * @param string $uid  The UID of the event to replace.
     * @param Horde_ActiveSync_Message_Appointment $content
     *        The new event.
     * @param string $calendar  The calendar id. @since 2.12.0
     */
    public function calendar_replace($uid, Horde_ActiveSync_Message_Appointment $content, $calendar = null)
    {
        $this->_registry->calendar->replace($uid, $content, 'activesync', $calendar);
    }

    /**
     * Delete an event from Horde's calendar storage
     *
     * @param string $uid  The UID of the event to delete
     * @param string $calendar  The calendar id. @since 2.12.0
     */
    public function calendar_delete($uid, $calendar = null)
    {
        $this->_registry->calendar->delete($uid, null, $calendar);
    }

    /**
     * Return the timestamp for the last time $action was performed.
     *
     * @param string $uid       The UID of the event we are interested in.
     * @param string $action    The action we are interested in (add, modify...).
     * @param string $calendar  The calendar id, if not using multiplexed data.
     *
     * @return integer
     */
    public function calendar_getActionTimestamp($uid, $action, $calendar = null)
    {
        return $this->_registry->calendar->getActionTimestamp(
            $uid, $action, $calendar, $this->hasFeature('modseq', 'calendar'));
    }

    /**
     * Get a list of all contacts a user can see
     *
     * @param string $source  The source to list. If null, use multiplex.
     *                        @since 2.12.0
     *
     * @return array An array of contact UIDs
     */
    public function contacts_listUids($source = null)
    {
        return $this->_registry->contacts->listUids($source);
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
     *   - device: (Horde_ActiveSync_Device) The device object.
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
     * @param Horde_ActiveSync_Message_Contact $content  The contact data
     * @param string $addressbook                        The addessbook id.
     *                                                   @since 2.12.0
     *
     * @return mixed  string|boolean  The new UID or false on failure.
     */
    public function contacts_import(Horde_ActiveSync_Message_Contact $content, $addressbook = null)
    {
        return $this->_registry->contacts->import($content, 'activesync', $addressbook);
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
     * @param string|array $uid  The UID(s) of the contact(s) to remove.
     *
     * @return boolean
     */
    public function contacts_delete($uid)
    {
        return $this->_registry->contacts->delete($uid);
    }

    /**
     * Get the timestamp of the most recent occurance of $action for the
     * specifed contact
     *
     * @param string $uid     The UID of the contact to search.
     * @param string $action  The action to lookup.
     * @param string $addressbook  The addressbook id, if not using multiplex.
     *
     * @return integer
     */
    public function contacts_getActionTimestamp($uid, $action, $addressbook = null)
    {
        return $this->_registry->contacts->getActionTimestamp(
            $uid, $action, $addressbook, $this->hasFeature('modseq', 'contacts'));
    }

    /**
     * Returns the favouriteRecipients data for RI requests.
     *
     * @param integer $max  The maximum number of recipients to return.
     *
     * @return array  An array of email addresses.
     */
    public function getRecipientCache($max = 100)
    {
        $cache = $GLOBALS['injector']->getInstance('Horde_Cache');
        $cache_key = 'HCASC:' . $this->_registry->getAuth() . ':' . $max;
        if (!$cache->exists($cache_key, 3600)) {
            $results = $this->_registry->mail->favouriteRecipients($max);
            $cache->set($cache_key, json_encode($results));
        } else {
            $results = json_decode($cache->get($cache_key, 3600));
        }

        return $results;
    }

    /**
     * Search the contacts store.
     *
     * @param string $query   The search string.
     * @param array $options  Additional options:
     *   - pictures: (boolean) Include photos in results.
     *             DEFAULT: false (Do not include photos).
     *   - recipient_cache_search: (boolean) If true, this is a RI cache search,
     *       should only search the 'email' field and only return a small subset
     *       of fields.
     *
     * @return array  The search results.
     */
    public function contacts_search($query, array $options = array())
    {
        if ((!$gal = $this->contacts_getGal()) && empty($options['recipient_cache_search'])) {
            return array();
        }

        if (!empty($options['recipient_cache_search'])) {
            $sources = array_keys($this->_registry->contacts->sources(false, true));
            $return_fields = array('name', 'alias', 'email');
            foreach ($sources as $source) {
                $fields[$source] = array('email');
            }
        } else {
            $sources = array($gal);
            $fields = array();
            $return_fields = array('name', 'alias', 'email', 'firstname', 'lastname',
                'company', 'homePhone', 'workPhone', 'cellPhone', 'title',
                'office');
        }
        if (!empty($options['pictures'])) {
            $fields[$gal][] = 'photo';
        }
        $opts = array(
            'matchBegin' => true,
            'forceSource' => true,
            'sources' => $sources,
            'returnFields' => $return_fields,
            'fields' => $fields
        );

        return $this->_registry->contacts->search($query, $opts);
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
     *  - maxsize: (integer)      The maximum size of any pictures.
     *                            DEFAULT: 0 (No limit).
     *  - maxpictures: (integer)  The maximum count of images to return.
     *                            DEFAULT: - (No limit).
     *  - pictures: (boolean)     Return pictures.
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
        if (!in_array($gal, $sources)) {
            $sources[] = $gal;
        }
        foreach ($sources as $source) {
            $fields[$source] = array('name', 'email', 'alias', 'smimePublicKey');
            if (!empty($opts['pictures'])) {
                $fields[$source]['photo'];
            }
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
     * Browse VFS backend.
     *
     * @param string $path  The path to browse/fetch. This should be in UNC
     *                      format with the "server" portion specifying
     *                      backend name. e.g., \\file\mike\file.txt or
     *                      \\sql\mike\file.txt
     *
     * @return array  An array of data arrays with the following structure:
     *   linkid:         (string)  The UNC path for this resource.
     *   name:           (string)  The display name of the resource.
     *   content-length: (integer)  The byte size of the resource (if a file).
     *   modified:       (Horde_Date)  The modification time of the resource, if
     *                   available.
     *   create:         (Horde_Date)  The creation time of the resource, if
     *                   available.
     *   is_folder:      (boolean)  True if the resource is a folder.
     *   data:           (Horde_Stream)  The data, if resource is a file.
     *   content-type:   (string)  The MIME type of the file resource, if
     *                    available.
     *   @since 2.12.0
     */
    public function files_browse($path)
    {
        if (!$app = $this->_registry->hasInterface('files')) {
            return false;
        }

        // Save for later.
        $original_path = $path;

        // Normalize
        $path = str_replace('\\', '/', $path);

        // Get the "server" name.
        $regex = '=^//([a-zA-Z0-9-]+)/(.*)=';
        if (preg_match($regex, $path, $results) === false) {
            return false;
        }
        $backend = $app . '/' . $results[1];
        $path = $backend . '//' . $results[2];

        try {
            $results = $this->_registry->files->browse($path);
        } catch (Horde_Exception $e) {
            throw new Horde_ActiveSync_Exception($e);
        }

        $files = array();

        // An explicit file requested?
        if (!empty($results['data'])) {
            $data = new Horde_Stream();
            $data->add($results['data']);
            $files[] = array(
                'linkid' => $original_path,
                'name' => $results['name'],
                'content-length' => $results['contentlength'],
                'modified' => new Horde_Date($results['mtime']),
                'created' => new Horde_Date($results['mtime']), // No creation date?
                'is_folder' => false,
                'data' => $data);
        } else {
            foreach ($results as $id => $result) {
                $file = array('name' => $result['name']);
                $file['is_folder'] = $result['browseable'];
                $file['modified'] = new Horde_Date($result['modified']);
                $file['created'] = clone $file['modified'];
                $file['linkid'] = str_replace($backend, '', $id);
                if (!empty($result['contentlength'])) {
                    $file['content-length'] = $result['contentlength'];
                }
                $files[] = $file;
            }
        }

        return $files;
    }

    /**
     * List all tasks in the user's default tasklist.
     *
     * @param string $tasklist  The tasklist to check. If null, use multiplexed.
     *
     * @return array  An array of task uids.
     */
    public function tasks_listUids($tasklist = null)
    {
        return $this->_registry->tasks->listUids($tasklist);
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
     * @param string $tasklist  The tasklist id. @since 2.12.0
     *
     * @return string  The newly added task's uid.
     */
    public function tasks_import(Horde_ActiveSync_Message_Task $message, $tasklist = null)
    {
        return $this->_registry->tasks->import($message, 'activesync', $tasklist);
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
     * Return the timestamp or modseq for the last time $action was performed.
     *
     * @param string $uid       The UID of the task we are interested in.
     * @param string $action    The action we are interested in (add, modify...)
     * @param string $tasklike  The tasklist, if not using multiplexed data.
     *
     * @return integer
     */
    public function tasks_getActionTimestamp($uid, $action, $tasklist = null)
    {
        return $this->_registry->tasks->getActionTimestamp(
            $uid, $action, $tasklist, $this->hasFeature('modseq', 'tasks'));
    }

    /**
     * List notepads.
     *
     * @return array
     * @since 5.1
     * @deprecated - @todo was never used, remove in H6.
     */
    public function notes_listNotepads()
    {
        return $this->_registry->notes->listNotepads();
    }

    /**
     * List all notes in the user's default notepad.
     *
     * @param string $notepad  The notepad id to list. If null, use multiplexed.
     *                         @since 2.12.0
     *
     * @return array  An array of note uids.
     * @since 5.1
     */
    public function notes_listUids($notepad = null)
    {
        return $this->_registry->notes->listUids($notepad);
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
     * @param string $notebook                        The notebook id.
     *                                                @since 2.12.0
     *
     * @return string  The newly added notes's uid.
     * @since 5.1
     */
    public function notes_import(Horde_ActiveSync_Message_Note $message, $notebook = null)
    {
        return $this->_registry->notes->import($message, 'activesync', $notebook);
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
     * @param string $notpad  The notepad to use, if not using multiplex.
     *
     * @return integer
     * @since 5.1
     */
    public function notes_getActionTimestamp($uid, $action, $notepad = null)
    {
        return $this->_registry->notes->getActionTimestamp(
            $uid, $action, $notepad, $this->hasFeature('modseq', 'notes'));
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
            if (!$this->hasFeature('activesync', 'notes')) {
                unset($apps[$key]);
            }
        }

        return $apps;
    }

    /**
     * Return if the backend collection has the requested feature.
     *
     * @param string $feature     The requested feature.
     * @param string $collection  The requested collection id.
     *
     * @return boolean
     * @since 2.6.0
     */
    public function hasFeature($feature, $collection)
    {
        if (empty($this->_capabilities[$collection]) || !array_key_exists($feature, $this->_capabilities[$collection])) {
            $this->_capabilities[$collection][$feature] =
                $this->_registry->hasFeature($feature, $this->_getAppFromCollectionId($collection));
        }

        return $this->_capabilities[$collection][$feature];
    }

    /**
     * Return the highest modification sequence value for the specified
     * collection
     *
     * @return integer  The modseq value.
     * @since 2.6.0
     */
    public function getHighestModSeq($collection, $id = null)
    {
        return $this->_registry->{$this->_getInterfaceFromCollectionId($collection)}->getHighestModSeq($id);
    }

    /**
     * Convert a collection id to a horde app name.
     *
     * @param string $collection  The collection id e.g., @Notes@.
     *
     * @return string  The horde application name e.g., nag.
     */
    protected function _getAppFromCollectionId($collection)
    {
        return $this->_registry->hasInterface($this->_getInterfaceFromCollectionId($collection));
    }

    /**
     * Normalize the collection ids to interface names.
     *
     * @param string $collection The collection id e.g., @Notes@
     *
     * @return string  The Horde interface name e.g., notes
     */
    protected function _getInterfaceFromCollectionId($collection)
    {
        return strtolower(str_replace('@', '', $collection));
    }

    /**
     * Obtain a user's preference setting.
     *
     * @param string $app  The Horde application providing the setting.
     * @param string $pref The name of the preference setting.
     *
     * @return mixed  The preference value
     * @deprecated (unused)
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
     * @return array|boolean  The vacation rule properties or false if
     *                        interface unavailable.
     */
    public function filters_getVacation()
    {
        if ($this->horde_hasInterface('filter')) {
            return $this->_registry->filter->getVacation();
        } else {
            return false;
        }
    }

    /**
     * Set vacation message properties.
     *
     * @param array $setting  The vacation details.
     *
     * @throws Horde_Exception
     */
    public function filters_setVacation(array $setting)
    {
        if (!$this->horde_hasInterface('filter')) {
            throw new Horde_Exception('Filter interface unavailable.');
        }
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
        if ($this->_registry->hasMethod('getMaillog', $this->_registry->hasInterface('mail'))) {
            return $this->_registry->mail->getMaillog($mid);
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
        if ($this->_registry->hasMethod('logMaillog', $this->_registry->hasInterface('mail'))) {
            $this->_registry->mail->logMaillog($action, $mid, $recipients);
        }
    }

    public function mail_logRecipient($action, $recipients, $message_id)
    {
        if ($this->_registry->hasMethod('logRecipient', $this->_registry->hasInterface('mail'))) {
            $this->_registry->mail->logRecipient($action, $recipients, $message_id);
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
        if ($this->_registry->hasMethod('getMaillogChanges', $this->_registry->hasInterface('mail'))) {
            return $this->_registry->mail->getMaillogChanges($ts);
        }
    }

    /**
     * Get all server changes for the specified collection
     *
     * @param string $collection  The collection type (a Horde interface name -
     *                            calendar, contacts, tasks)
     * @param integer $from_ts    Starting timestamp or modification sequence.
     * @param integer $to_ts      Ending timestamp or modification sequence.
     * @param string $server_id   The server id of the collection. If null, uses
     *                            multiplexed.
     *
     * @return array  A hash of add, modify, and delete uids
     * @throws InvalidArgumentException
     */
    public function getChanges($collection, $from_ts, $to_ts, $server_id)
    {
        if (!in_array($collection, array('calendar', 'contacts', 'tasks', 'notes'))) {
            throw new InvalidArgumentException('collection must be one of calendar, contacts, tasks or notes');
        }

        // We can use modification sequences.
        if ($this->hasFeature('modseq', $collection)) {
            $this->_logger->info(sprintf(
                '[%s] Fetching changes for %s using MODSEQ.',
                getmypid(),
                $collection));
            try {
                return $this->_registry->{$collection}->getChangesByModSeq($from_ts, $to_ts, $server_id);
            } catch (Exception $e) {
                return array('add' => array(),
                             'modify' => array(),
                             'delete' => array());
            }
        }

        // Older API, use timestamps.
        $this->_logger->info(sprintf(
            '[%s] Fetching changes for %s using TIMESTAMPS.',
            getmypid(),
            $collection));
        try {
            return $this->_registry->{$collection}->getChanges($from_ts, $to_ts, false, $server_id);
        } catch (Exception $e) {
            return array('add' => array(),
                         'modify' => array(),
                         'delete' => array());
        }
    }

    /**
     * Return message UIDs that should be SOFTDELETEd from the client.
     *
     * @param string $collection  The collection type.
     * @param long $from_ts       The start of the time period to search.
     * @param long $to_ts         The end of the time period to search.
     * @param string $source      Limit to this source only. @since 2.12.0
     *
     * @return array  An array of message UIDs that occur within the $from_ts
     *                and $to_ts range that are to be SOFTDELETEd from the
     *                client.
     */
    public function softDelete($collection, $from_ts, $to_ts, $source = null)
    {
        $results = array();
        switch ($collection) {
        case 'calendar':
            if (empty($source)) {
                // @TODO: For Horde 6, add API calls to the calendar API to
                // get the default share and sync shares.  We need to hack this
                // logic here since the methods to return the default calendar
                // and sync calendars are not available in Kronolith 4's API.
                $calendars = unserialize(
                    $this->_registry->horde->getPreference(
                        $this->_registry->hasInterface('calendar'),
                        'sync_calendars'));
                if (empty($calendars)) {
                    $calendars = $this->_registry->calendar->listCalendars(true, Horde_Perms::EDIT);
                    $default_calendar = $this->_registry->horde->getPreference(
                        $this->_registry->hasInterface('calendar'),
                        'default_share');
                    if (empty($calendars[$default_calendar])) {
                        return array();
                    } else {
                        $calendars = array($default_calendar);
                    }
                }
            } else {
                $calendars = array($source);
            }

            // Need to use listEvents instead of listUids since we must
            // ignore recurring events when softdeleting or else we run
            // the risk of removing a still active recurrence.
            $events = $this->_registry->calendar->listEvents(
                $from_ts,
                $to_ts,
                $calendars,  // Calendars
                false,       // showRecurrence
                false,       // alarmsOnly
                false,       // showRemote
                true,        // hideExceptions
                false        // coverDates
            );

            foreach ($events as $day) {
                foreach ($day as $e) {
                    if (empty($e->recurrence)) {
                        $results[] = $e->uid;
                    }
                }
            }
        }

        return $results;
    }

    /**
     * Return the list of folders to sync for the specified collection.
     *
     * @param string $collection  The collection class
     *                            A Horde_ActiveSync::CLASS_* constant.
     * @param integer $multiplex  A bitmask flagging the collections that must
     *                            be multiplexed, regardless of horde's settings
     *
     * @return array|string  A list of folder uids or $collection if supporting
     *                       API is not found. If a list is returned, it is in
     *                       the following format:
     *                       'uid' => array('display' => "Display Name", 'primary' => boolean)
     * @since 2.12.0
     */
    public function getFolders($collection, $multiplex)
    {
        // @TODO: H6 remove the hasMethod checks.
        if (empty($this->_folderCache[$collection])) {
            switch ($collection) {
            case Horde_ActiveSync::CLASS_CALENDAR:
                if ($this->_registry->hasMethod('calendar/sources') &&
                    $this->_registry->horde->getPreference($this->_registry->hasInterface('calendar'), 'activesync_no_multiplex') &&
                    !($multiplex & Horde_ActiveSync_Device::MULTIPLEX_CALENDAR)) {

                    $folders = $this->_registry->calendar->sources(true, true);
                    $default = $this->_registry->calendar->getDefaultShare();
                } else {
                    $this->_folderCache[$collection] = Horde_Core_ActiveSync_Driver::APPOINTMENTS_FOLDER_UID;
                }
                break;

            case Horde_ActiveSync::CLASS_CONTACTS:
                if ($this->_registry->hasMethod('contacts/sources') &&
                    $this->_registry->horde->getPreference($this->_registry->hasInterface('contacts'), 'activesync_no_multiplex') &&
                    !($multiplex & Horde_ActiveSync_Device::MULTIPLEX_CONTACTS)) {

                    $folders = $this->_registry->contacts->sources(true, true);
                    $default = $this->_registry->contacts->getDefaultShare();
                } else {
                    $this->_folderCache[$collection] = Horde_Core_ActiveSync_Driver::CONTACTS_FOLDER_UID;
                }
                break;

            case Horde_ActiveSync::CLASS_TASKS:
                if ($this->_registry->hasMethod('tasks/sources') &&
                    $this->_registry->horde->getPreference($this->_registry->hasInterface('tasks'), 'activesync_no_multiplex') &&
                    !($multiplex & Horde_ActiveSync_Device::MULTIPLEX_TASKS)) {

                    $folders = $this->_registry->tasks->sources(true, true);
                    $default = $this->_registry->tasks->getDefaultShare();
                } else {
                    $this->_folderCache[$collection] = Horde_Core_ActiveSync_Driver::TASKS_FOLDER_UID;
                }
                break;

            case Horde_ActiveSync::CLASS_NOTES:
                if ($this->_registry->hasMethod('notes/sources') &&
                    $this->_registry->horde->getPreference($this->_registry->hasInterface('calendar'), 'activesync_no_multiplex') &&
                    !($multiplex & Horde_ActiveSync_Device::MULTIPLEX_NOTES)) {

                    $folders = $this->_registry->notes->sources(true, true);
                    $default = $this->_registry->notes->getDefaultShare();
                } else {
                    $this->_folderCache[$collection] = Horde_Core_ActiveSync_Driver::NOTES_FOLDER_UID;
                }
            }

            if (!empty($folders) && is_array($folders)) {
                $results = array();
                foreach ($folders as $id => $folder) {
                    $results[$id] = array('display' => $folder, 'primary' => ($id == $default));
                }
                $this->_folderCache[$collection] = $results;
            }
        }

        return $this->_folderCache[$collection];
    }


    /**
     * Create a new folder/source in the specified collection.
     *
     * @param string $class       The collection class.
     *                            A Horde_ActiveSync::CLASS_* constant.
     *
     * @param string $foldername  The name of the new folder.
     *
     * @return string|integer  The new folder serverid.
     * @throws Horde_ActiveSync_Exception
     * @since 2.12.0
     */
    public function createFolder($class, $foldername)
    {
        switch ($class) {
        case Horde_ActiveSync::CLASS_CALENDAR:
            // @todo Remove hasMethod checks in H6.
            if (!$this->_registry->hasMethod('calendar/addCalendar') ||
                !$this->_registry->horde->getPreference($this->_registry->hasInterface('calendar'), 'activesync_no_multiplex')) {
                throw new Horde_ActiveSync_Exception(
                    'Creating calendars not supported by the calendar API.',
                    Horde_ActiveSync_Exception::UNSUPPORTED
                );
            }
            return $this->_registry->calendar->addCalendar($foldername, array('synchronize' => true));

        case Horde_ActiveSync::CLASS_CONTACTS:
            // @todo Remove hasMethod check in H6
            if (!$this->_registry->hasMethod('contacts/addAddressbook') ||
                !$this->_registry->horde->getPreference($this->_registry->hasInterface('contacts'), 'activesync_no_multiplex')) {
                throw new Horde_ActiveSync_Exception(
                    'Creating addressbooks not supported by the contacts API.',
                    Horde_ActiveSync_Exception::UNSUPPORTED
                );
            }
            return $this->_registry->contacts->addAddressbook($foldername);

        case Horde_ActiveSync::CLASS_NOTES:
            // @todo Remove hasMethod checks in H6.
            if (!$this->_registry->hasMethod('notes/addNotepad') ||
                !$this->_registry->horde->getPreference($this->_registry->hasInterface('notes'), 'activesync_no_multiplex')) {
                throw new Horde_ActiveSync_Exception(
                    'Creating notepads not supported by the notes API.',
                    Horde_ActiveSync_Exception::UNSUPPORTED
                );
            }
            return $this->_registry->notes->addNotepad($foldername);

        case Horde_ActiveSync::CLASS_TASKS:
            if (!$this->_registry->horde->getPreference($this->_registry->hasInterface('tasks'), 'activesync_no_multiplex')) {
                throw new Horde_ActiveSync_Exception(
                    'Creating notepads not supported by the notes API.',
                    Horde_ActiveSync_Exception::UNSUPPORTED
                );
            }
            return $this->_registry->tasks->addTasklist($foldername);
        }
    }

    /**
     * Change an existing folder on the server.
     *
     * @param string $class  The collection class.
     *                       A Horde_ActiveSync::CLASS_* constant.
     * @param string $id     The existing serverid.
     * @param string $name   The new folder display name.
     *
     * @throws Horde_ActiveSync_Exception
     * @since 2.12.0
     */
    public function changeFolder($class, $id, $name)
    {
        switch ($class) {
        case Horde_ActiveSync::CLASS_CALENDAR:
            // @todo Remove hasMethod check
            if (!$this->_registry->hasMethod('calendar/getCalendar') ||
                !$this->_registry->horde->getPreference($this->_registry->hasInterface('calendar'), 'activesync_no_multiplex')) {
                throw new Horde_ActiveSync_Exception(
                    'Updating calendars not supported by the calendar API.',
                    Horde_ActiveSync_Exception::UNSUPPORTED
                );
            }
            $calendar = $this->_registry->calendar->getCalendar($id);
            $info = array(
                'name' => $name,
                'color' => $calendar->background(),
                'description' => $calendar->description()
            );
            $this->_registry->calendar->updateCalendar($id, $info);
            break;

        case Horde_ActiveSync::CLASS_CONTACTS:
            // @todo remove hasMethod check
            if (!$this->_registry->hasMethod('contacts/updateAddressbook') ||
                !$this->_registry->horde->getPreference($this->_registry->hasInterface('contacts'), 'activesync_no_multiplex')) {
                throw new Horde_ActiveSync_Exception(
                    'Updating addressbooks not supported by the contacts API.',
                    Horde_ActiveSync_Exception::UNSUPPORTED
                );
            }
            $this->_registry->contacts->updateAddressbook($id, array('name' => $name));
            break;

        case Horde_ActiveSync::CLASS_NOTES:
            // @todo remove hasMethod check
            if (!$this->_registry->hasMethod('notes/updateNotepad') ||
                !$this->_registry->horde->getPreference($this->_registry->hasInterface('notes'), 'activesync_no_multiplex')) {
                throw new Horde_ActiveSync_Exception(
                    'Updating notepads not supported by the notes API.',
                    Horde_ActiveSync_Exception::UNSUPPORTED
                );
            }
            $this->_registry->notes->updateNotepad($id, array('name' => $name));

        case Horde_ActiveSync::CLASS_TASKS:
            if (!$this->_registry->horde->getPreference($this->_registry->hasInterface('tasks'), 'activesync_no_multiplex')) {
                throw new Horde_ActiveSync_Exception(
                    'Updating notepads not supported by the notes API.',
                    Horde_ActiveSync_Exception::UNSUPPORTED
                );
            }
            $share = $this->_registry->tasks->getTasklist($id);
            $info = array(
                'name' => $name,
                'color' => $share->get('color'),
                'desc' => $share->get('desc')
            );
            $this->_registry->tasks->updateTasklist($id, $info);
            break;
        }
    }

    /**
     * Delete a folder.
     *
     * @param string $class  The EAS collection class.
     * @param string $id     The folder id
     *
     * @since 2.12.0
     */
    public function deleteFolder($class, $id)
    {
        switch ($class) {
        case Horde_ActiveSync::CLASS_TASKS:
            if (!$this->_registry->horde->getPrefs($this->_registry->hasInterface('tasks'), 'activesync_no_multiplex')) {
                throw new Horde_ActiveSync_Exception(
                    'Deleting addressbooks not supported by the contacts API.',
                    Horde_ActiveSync_Exception::UNSUPPORTED
                );
            }
            $this->_registry->tasks->deleteTasklist($id);
            break;

        case Horde_ActiveSync::CLASS_CONTACTS:
            if (!$this->_registry->hasMethod('contacts/deleteAddressbook') ||
                !$this->_registry->horde->getPrefs($this->_registry->hasInterface('contacts'), 'activesync_no_multiplex')) {
                throw new Horde_ActiveSync_Exception(
                    'Deleting addressbooks not supported by the contacts API.',
                    Horde_ActiveSync_Exception::UNSUPPORTED
                );
            }
            $this->_registry->contacts->deleteAddressbook($id);
            break;

        case Horde_ActiveSync::CLASS_CALENDAR:
            if (!$this->_registry->hasMethod('calendar/deleteCalendar') ||
                !$this->_registry->horde->getPrefs($this->_registry->hasInterface('calendar'), 'activesync_no_multiplex')) {
                throw new Horde_ActiveSync_Exception(
                    'Deleting calendars not supported by the calendar API.',
                    Horde_ActiveSync_Exception::UNSUPPORTED
                );
            }
            $this->_registry->calendar->deleteCalendar($id);
            break;

        case Horde_ActiveSync::CLASS_NOTES  :
            if (!$this->_registry->hasMethod('notes/deleteNotepad') ||
                !$this->_registry->horde->getPrefs($this->_registry->hasInterface('notes'), 'activesync_no_multiplex')) {
                throw new Horde_ActiveSync_Exception(
                    'Deleting notepads not supported by the notes API.',
                    Horde_ActiveSync_Exception::UNSUPPORTED
                );
            }
            $this->_registry->notes->deleteNotpad($id);
            break;
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
