<?php
/**
 * Horde_ActiveSync_Driver_Base::
 *
 * @license   http://www.horde.org/licenses/gpl GPLv2
 *            NOTE: According to sec. 8 of the GENERAL PUBLIC LICENSE (GPL),
 *            Version 2, the distribution of the Horde_ActiveSync module in or
 *            to the United States of America is excluded from the scope of this
 *            license.
 * @copyright 2010-2014 Horde LLC (http://www.horde.org)
 * @author    Michael J Rubinsky <mrubinsk@horde.org>
 * @package   ActiveSync
 */
/**
 * Base ActiveSync Driver backend. Provides communication with the actual
 * server backend that ActiveSync will be syncing devices with. This is an
 * abstract class, servers must implement their own backend to provide
 * the needed data.
 *
 * @license   http://www.horde.org/licenses/gpl GPLv2
 *            NOTE: According to sec. 8 of the GENERAL PUBLIC LICENSE (GPL),
 *            Version 2, the distribution of the Horde_ActiveSync module in or
 *            to the United States of America is excluded from the scope of this
 *            license.
 * @copyright 2010-2014 Horde LLC (http://www.horde.org)
 * @author    Michael J Rubinsky <mrubinsk@horde.org>
 * @package   ActiveSync
 */
abstract class Horde_ActiveSync_Driver_Base
{
    /**
     * The username to sync with the backend as
     *
     * @var string
     */
    protected $_user;

    /**
     * Authenticating user
     *
     * @var string
     */
    protected $_authUser;

    /**
     * User password
     *
     * @var string
     */
    protected $_authPass;

    /**
     * Logger instance
     *
     * @var Horde_Log_Logger
     */
    protected $_logger;

    /**
     * Parameters
     *
     * @var array
     */
    protected $_params;

    /**
     * Protocol version
     *
     * @var float
     */
    protected $_version = Horde_ActiveSync::VERSION_FOURTEENONE;

    /**
     * The state driver for this request. Needs to be injected into this class.
     *
     * @var Horde_ActiveSync_State_Base
     */
    protected $_state;

    /**
     * The device object
     *
     * @var Horde_ActiveSync_Device
     * @since  2.12.0
     */
    protected $_device;

    /**
     * Temporary serverid to uid map. Used when creating the hierarchy
     * for the first time
     *
     * @var array
     */
    protected $_tempMap = array();

    protected $_typeMap = array(
        'F' => Horde_ActiveSync::CLASS_EMAIL,
        'C' => Horde_ActiveSync::CLASS_CONTACTS,
        'A' => Horde_ActiveSync::CLASS_CALENDAR,
        'T' => Horde_ActiveSync::CLASS_TASKS,
        'N' => Horde_ActiveSync::CLASS_NOTES
    );

    /**
     * Const'r
     *
     * @param array $params  Any configuration parameters or injected objects
     *                       the concrete driver may need.
     *  - logger: (Horde_Log_Logger) The logger.
     *            DEFAULT: none (No logging).
     *  - state: (Horde_ActiveSync_State_Base) The state driver.
     *           DEFAULT: none (REQUIRED).
     *
     * @return Horde_ActiveSync_Driver
     */
    public function __construct($params = array())
    {
        $this->_params = $params;
        if (empty($params['state']) ||
            !($params['state'] instanceof Horde_ActiveSync_State_Base)) {

            throw new InvalidArgumentException('Missing required state object');
        }

        /* Create a stub if we don't have a useable logger. */
        if (isset($params['logger'])
            && is_callable(array($params['logger'], 'log'))) {
            $this->_logger = $params['logger'];
            unset($params['logger']);
        } else {
            $this->_logger = new Horde_Support_Stub;
        }

        $this->_state = $params['state'];
        $this->_state->setLogger($this->_logger);
        $this->_state->setBackend($this);
    }

    /**
     * Prevent circular dependency issues.
     */
    public function __destruct()
    {
        unset($this->_state);
        unset($this->_logger);
    }

    /**
     * Setter for the logger instance
     *
     * @param Horde_Log_Logger $logger  The logger
     */
    public function setLogger($logger)
    {
        $this->_logger = $logger;
    }

    /**
     * Set the protocol version. Can't do it in constructer since we
     * don't know the version at the time this driver is instantiated.
     *
     * @param float $version  The EAS protocol version to use.
     */
    public function setProtocolVersion($version)
    {
        $this->_version = $version;
    }

    /**
     * Obtain the ping heartbeat settings
     *
     * @return array
     */
    public function getHeartbeatConfig()
    {
        return $this->_params['ping'];
    }

    /**
     * Any code needed to authenticate to backend as the actual user.
     *
     * @param string $username  The username to authenticate as
     * @param string $password  The password
     * @param string $domain    The user domain (unused in this driver).
     *
     * @return mixed  Boolean true on success, boolean false on credential
     *                failure or Horde_ActiveSync::AUTH_REASON_*
     *                constant on policy failure.
     */
    public function authenticate($username, $password, $domain = null)
    {
        $this->_authUser = $username;
        $this->_authPass = $password;

        return true;
    }

    /**
     * Get the username for this request.
     *
     * @return string  The current username
     */
    public function getUser()
    {
        return $this->_authUser;
    }

    /**
     * Clear authentication
     *
     * @return boolean
     */
    public function clearAuthentication()
    {
        return true;
    }

    /**
     * Setup sync parameters. The user provided here is the user the backend
     * will sync with. This allows you to authenticate as one user, and sync as
     * another, if the backend supports this.
     *
     * @param string $user The username to sync as on the backend.
     *
     * @return boolean
     */
    public function setup($user)
    {
        $this->_user = $user;

        return true;
    }

    /**
     * Obtain a message from the backend.
     *
     * @param string $folderid   Folder id containing data to fetch.
     * @param string $id         Server id of data to fetch.
     * @param array $collection  The collection data.
     *
     * @return Horde_ActiveSync_Message_Base The message data
     */
    public function fetch($folderid, $id, array $collection)
    {
        // Forces entire message
        $collection['truncation'] = 0;
        if (!empty($collection['bodyprefs'])) {
            foreach ($collection['bodyprefs'] as &$bodypref) {
                if (isset($bodypref['truncationsize'])) {
                    $bodypref['truncationsize'] = 0;
                }
            }
        }
        return $this->getMessage($folderid, $id, $collection);
    }

    /**
     * Add default truncation values for this driver.
     *
     * @param array $bodyprefs  BODYPREFERENCE data.
     *
     * @return array  THe BODYPREFERENCE data, with default truncationsize values.
     */
    public function addDefaultBodyPrefTruncation(array $bodyprefs)
    {
        if (isset($bodyprefs[Horde_ActiveSync::BODYPREF_TYPE_PLAIN]) &&
            !isset($bodyprefs[Horde_ActiveSync::BODYPREF_TYPE_PLAIN]['truncationsize'])) {
            $bodyprefs[Horde_ActiveSync::BODYPREF_TYPE_PLAIN]['truncationsize'] = 1048576; // 1024 * 1024
        }
        if (isset($bodyprefs[Horde_ActiveSync::BODYPREF_TYPE_HTML]) &&
            !isset($bodyprefs[Horde_ActiveSync::BODYPREF_TYPE_HTML]['truncationsize'])) {
            $bodyprefs[Horde_ActiveSync::BODYPREF_TYPE_HTML]['truncationsize'] = 1048576; // 1024 * 1024
        }
        if (isset($bodyprefs[Horde_ActiveSync::BODYPREF_TYPE_RTF]) &&
            !isset($bodyprefs[Horde_ActiveSync::BODYPREF_TYPE_RTF]['truncationsize'])) {
            $bodyprefs[Horde_ActiveSync::BODYPREF_TYPE_RTF]['truncationsize'] = 1048576; // 1024 * 1024
        }
        if (isset($bodyprefs[Horde_ActiveSync::BODYPREF_TYPE_MIME]) &&
            !isset($bodyprefs[Horde_ActiveSync::BODYPREF_TYPE_MIME]['truncationsize'])) {
            $bodyprefs[Horde_ActiveSync::BODYPREF_TYPE_MIME]['truncationsize'] = 1048576; // 1024 * 1024
        }

        return $bodyprefs;
    }

    /**
     * Set the currently connected device
     *
     * @param Horde_ActiveSync_Device $device  The device object.
     */
    public function setDevice(Horde_ActiveSync_Device $device)
    {
        $this->_device = $device;
    }

    /**
     * Build a EAS style FB string. Essentially, each digit represents 1/2 hour.
     * The values are as follows:
     *  0 - Free
     *  1 - Tentative
     *  2 - Busy
     *  3 - OOF
     *  4 - No data available.
     *
     * Though currently we only provide a Free/Busy/Unknown differentiation.
     *
     * @param stdClass $fb  The fb information. An object containing:
     *   - s: The start of the period covered.
     *   - e: The end of the period covered.
     *   - b: An array of busy periods.
     *
     * @param Horde_Date $start  The start of the period requested by the client.
     * @param Horde_Date $end    The end of the period requested by the client.
     *
     * @return string   The EAS freebusy string.
     * @since 2.4.0
     */
    static public function buildFbString($fb, Horde_Date $start, Horde_Date $end)
    {
        if (empty($fb)) {
            return false;
        }

        // Calculate total time span.
        $end_ts = $end->timestamp();
        $start_ts = $start->timestamp();
        $sec = $end_ts - $start_ts;

        $fb_start = new Horde_Date($fb->s);
        $fb_end = new Horde_Date($fb->e);

        // Number of 30 minute periods.
        $period_cnt = ceil($sec / 1800);

        // Requested range is completely out of the available range.
        if ($start_ts >= $fb_end->timestamp() || $end_ts < $fb_start->timestamp()) {
            return str_repeat('4', $period_cnt);
        }

        // We already know we don't have any busy periods.
        if (empty($fb->b) && $fb_end->timestamp() <= $end_ts) {
            return str_repeat('0', $period_cnt);
        }

        $eas_fb = '';
        // Move $start to the start of the available data.
        while ($start_ts < $fb_start->timestamp() && $start_ts <= $end_ts) {
            $eas_fb .= '4';
            $start_ts += 1800; // 30 minutes
        }
        // The rest is assumed free up to $fb->e
        while ($start_ts <= $fb_end->timestamp() && $start_ts <= $end_ts) {
            $eas_fb .= '0';
            $start_ts += 1800;
        }
        // The remainder is also unavailable
        while ($start_ts <= $end_ts) {
            $eas_fb .= '4';
            $start_ts += 1800;
        }

        // Now put in the busy blocks.
        while (list($b_start, $b_end) = each($fb->b)) {
            $offset = $b_start - $start->timestamp();
            $duration = ceil(($b_end - $b_start) / 1800);
            if ($offset > 0) {
                $eas_fb = substr_replace($eas_fb, str_repeat('2', $duration), floor($offset / 1800), $duration);
            }
        }

        return $eas_fb;
    }

    /**
     * Get an activesync uid for the given backend serverid. If we've seen this
     * serverid before, return the previously created uid, otherwise return
     * a new one.
     *
     * @param string $id      The server's current folder name E.g., INBOX
     * @param string $type    The folder type, a Horde_ActiveSync::FOLDER_TYPE_*
     *                        constant. If empty, assumes FOLDER_TYPE_USER_MAIL
     * @param string $old_id  The previous folder name for this folder, if the
     *                        folder is being renamed. @since 2.15.0
     *                        @todo This is tempoarary until 3.0 (H6) when we
     *                        will have the collection manager take care of ALL
     *                        of the folder name <-> UID mapping management.
     *
     * @return string  A unique identifier for the specified backend folder id.
     *                 The first character indicates the foldertype as such:
     *                 'F' - Email
     *                 'C' - Contact
     *                 'A' - Appointment
     *                 'T' - Task
     *                 'N' - Note
     * @since 2.4.0
     */
    protected function _getFolderUidForBackendId($id, $type = null, $old_id = null)
    {
        // Always use 'RI' for Recipient cache.
        if ($id == 'RI') {
            return 'RI';
        }
        $map = $this->_state->getFolderUidToBackendIdMap();

        // Rename?
        if (!empty($old_id) && !empty($map[$old_id])) {
            $this->_tempMap[$id] = $map[$old_id];
        }

        if (!empty($map[$id])) {
            return $map[$id];
        } elseif (!empty($this->_tempMap[$id])) {
            return $this->_tempMap[$id];
        }

        // Convert TYPE to CLASS
        $type = $this->_getClassFromType($type);
        $rMap = array_flip($this->_typeMap);
        $prefix = $rMap[$type];

        // None found, generate a new UID.
        $this->_tempMap[$id] = sprintf('%s%04x%04x', $prefix, mt_rand(0, 0xffff), mt_rand(0, 0xffff));
        $this->_logger->info(sprintf(
            '[%s] Creating new folder uuid for %s: %s',
            getmypid(),
            $id,
            $this->_tempMap[$id]));

        return $this->_tempMap[$id];
    }

    /**
     * Convert a TYPE constant into it's associated CLASS constant.
     *
     * @param integer $type  The TYPE.
     *
     * @return string  The CLASS
     */
    protected function _getClassFromType($type)
    {
        // @todo This is for BC. Assume we are asking for an email collection
        // if we didn't pass a type. Remove in H6.
        if (empty($type)) {
            return Horde_ActiveSync::CLASS_EMAIL;
        }

        switch ($type) {
        case Horde_ActiveSync::FOLDER_TYPE_APPOINTMENT:
        case Horde_ActiveSync::FOLDER_TYPE_USER_APPOINTMENT:
            return Horde_ActiveSync::CLASS_CALENDAR;

        case Horde_ActiveSync::FOLDER_TYPE_CONTACT:
        case Horde_ActiveSync::FOLDER_TYPE_USER_CONTACT:
            return Horde_ActiveSync::CLASS_CONTACTS;

        case Horde_ActiveSync::FOLDER_TYPE_TASK:
        case Horde_ActiveSync::FOLDER_TYPE_USER_TASK:
            return Horde_ActiveSync::CLASS_TASKS;

        case Horde_ActiveSync::FOLDER_TYPE_NOTE:
        case Horde_ActiveSync::FOLDER_TYPE_USER_NOTE:
            return Horde_ActiveSync::CLASS_NOTES;

        case Horde_ActiveSync::FOLDER_TYPE_INBOX:
        case Horde_ActiveSync::FOLDER_TYPE_DRAFTS:
        case Horde_ActiveSync::FOLDER_TYPE_WASTEBASKET:
        case Horde_ActiveSync::FOLDER_TYPE_SENTMAIL:
        case Horde_ActiveSync::FOLDER_TYPE_OUTBOX:
        case Horde_ActiveSync::FOLDER_TYPE_USER_MAIL:
            return Horde_ActiveSync::CLASS_EMAIL;

        }
    }

    /**
     * Return the SyncStamp - the value used to determine the end of the current
     * sync range. Default implementation uses timestamps. Concrete drivers
     * can override this to provide another method to do this, such as
     * using modification sequences.
     *
     * @param $collection string  The collection id we are currently requesting.
     *
     * @return integer  The SyncStamp
     */
    public function getSyncStamp($collection)
    {
        return time();
    }

    /**
     * Delete a folder on the server.
     *
     * @param string $id  The server's folder id.
     * @param string $parent  The folder's parent, if needed.
     */
    abstract public function deleteFolder($id, $parent = Horde_ActiveSync::FOLDER_ROOT);

    /**
     * Change a folder on the server.
     *
     * @param string $id           The server's folder id
     * @param string $displayname  The new display name.
     * @param string $parent       The folder's parent, if needed.
     * @param string $uid          The existing folder uid, if this is an edit.
     *                             @since 2.9.0 (@todo Look at this for H6. It's
     *                             here now to save an extra DB lookup for data
     *                             we already have.)
     *
     * @return Horde_ActiveSync_Message_Folder
     * @throws  Horde_ActiveSync_Exception
     */
    abstract public function changeFolder($id, $displayname, $parent, $uid = null);

    /**
     * Move message
     *
     * @param string $folderid     Existing folder id
     * @param array $ids           Message UIDs
     * @param string $newfolderid  The new folder id
     *
     * @return array  The new uids for the message.
     */
    abstract public function moveMessage($folderid, array $ids, $newfolderid);

    /**
     * Returns array of items which contain contact information
     *
     * @param string $type   The search type; ['gal'|'mailbox']
     * @param array $query   The search query. An array containing:
     *  - query: (string) The search term.
     *           DEFAULT: none, REQUIRED
     *  - range: (string)   A range limiter.
     *           DEFAULT: none (No range used).
     *
     * @return array  An array containing:
     *  - rows:   An array of search results
     *  - status: The search store status code.
     */
    abstract public function getSearchResults($type, array $query);

    /**
     * Stat folder. Note that since the only thing that can ever change for a
     * folder is the name, we use that as the 'mod' value.
     *
     * @param string $id     The folder id
     * @param mixed $parent  The parent folder (or 0 if none).
     * @param mixed $mod     Modification indicator. For folders, this is the
     *                       name of the folder, since that's the only thing
     *                       that can change.
     * @return a stat hash
     */
    abstract public function statFolder($id, $parent = 0, $mod = null);

    /**
     * Return the ActiveSync message object for the specified folder.
     *
     * @param string $id  The folder's server id.
     *
     * @return Horde_ActiveSync_Message_Folder object.
     */
    abstract public function getFolder($id);

    /**
     * Get the list of folder stat arrays @see self::statFolder()
     *
     * @return array  An array of folder stat arrays.
     */
    abstract public function getFolderList();

    /**
     * Return an array of folder objects.
     *
     * @return array  An array of Horde_ActiveSync_Message_Folder objects.
     */
    abstract public function getFolders();

    /**
     * Get a list of server changes that occured during the specified time
     * period.
     *
     * @param string $folderId     The server id of the collection to check.
     * @param integer $from_ts     The starting timestamp.
     * @param integer $to_ts       The ending timestamp.
     * @param integer $cutoffdate  The earliest date to retrieve back to.
     * @param boolean $ping        If true, returned changeset may
     *                             not contain the full changeset, may only
     *                             contain a single change, designed only to
     *                             indicate *some* change has taken place. The
     *                             value should not be used to determine *what*
     *                             change has taken place.
     *
     * @return array A list of messge uids that have chnaged in the specified
     *               time period.
     */
    abstract public function getServerChanges(
        $folderId, $from_ts, $to_ts, $cutoffdate, $ping);

    /**
     * Get a message stat.
     *
     * @param string $folderId  The folder id
     * @param string $id        The message id (??)
     *
     * @return hash with 'id', 'mod', and 'flags' members
     */
    abstract public function statMessage($folderId, $id);

    /**
     * Obtain an ActiveSync message from the backend.
     *
     * @param string $folderid    The server's folder id this message is from
     * @param string $id          The server's message id
     * @param array  $collection  The colletion data. May contain things like:
     *   - mimesupport: (boolean) Indicates if the device has MIME support.
     *                  DEFAULT: false (No MIME support)
     *   - truncation: (integer)  The truncation constant, if sent by the device.
     *                 DEFAULT: 0 (No truncation)
     *   - bodyprefs: (array)  The bodypref array from the device.
     *
     * @return Horde_ActiveSync_Message_Base The message data
     * @throws Horde_ActiveSync_Exception
     */
    abstract public function getMessage($folderid, $id, array $collection);

    /**
     * Delete a message
     *
     * @param string $folderid  The folder id containing the messages.
     * @param array $ids        An array of message ids to delete.
     */
    abstract public function deleteMessage($folderid, array $ids);

    /**
     * Get the wastebasket folder.
     *
     * @param string $class  The collection class.
     *
     * @return string|boolean  Returns name of the trash folder, or false
     *                         if not using a trash folder.
     */
    abstract public function getWasteBasket($class);

    /**
     * Add/Edit a message
     *
     * @param string $folderid  The server id for the folder the message belongs
     *                          to.
     * @param string $id        The server's uid for the message if this is a
     *                          change to an existing message, null if new.
     * @param Horde_ActiveSync_Message_Base $message
     *                          The activesync message
     * @param Horde_ActiveSync_Device $device  The device information
     *
     * @return array|boolean    A stat array if successful, otherwise false.
     */
    abstract public function changeMessage($folderid, $id, Horde_ActiveSync_Message_Base $message, $device);

    /**
     * Set the read (\seen) flag on the specified message.
     *
     * @param string $folderid  The folder id containing the message.
     * @param integer $uid      The message IMAP UID.
     * @param integer $flag     The value to set the flag to.
     * @deprecated Will be removed in 3.0, use changeMessage() instead.
     */
    abstract public function setReadFlag($folderid, $uid, $flag);

    /**
     * Sends the email represented by the rfc822 string received by the PIM.
     *
     * @param mixed $rfc822     The rfc822 mime message, a string or stream
     *                          resource.
     * @param integer $forward  The UID of the message, if forwarding.
     * @param integer $reply    The UID of the message if replying.
     * @param string $parent    The collection id of parent message if
     *                          forwarding/replying.
     * @param boolean $save     Save in sent messages.
     *
     * @return boolean
     */
    abstract public function sendMail(
        $rfc822, $forward = null, $reply = null, $parent = null, $save = true);

    /**
     * Return the specified attachment.
     *
     * @param string $name  The attachment identifier. For this driver, this
     *                      consists of 'mailbox:uid:mimepart'
     *
     * @param array $options  Any options requested. Currently supported:
     *  - stream: (boolean) Return a stream resource for the mime contents.
     *
     * @return array  The attachment in the form of an array with the following
     *                structure:
     * array('content-type' => {the content-type of the attachement},
     *       'data'         => {the raw attachment data})
     */
    abstract public function getAttachment($name, array $options = array());

    /**
     * Return the specified attachement data for an ITEMOPERATIONS request.
     *
     * @param string $filereference  The attachment identifier.
     *
     * @return
     */
    abstract public function itemOperationsGetAttachmentData($filereference);

    /**
     * Returnmail object represented by the specified longid. Used to fetch
     * email objects from a search result, which only returns a 'longid'.
     *
     * @param string $longid        The unique search result identifier.
     * @param array $bodyprefs      The bodypreference array.
     * @param boolean $mimesupport  Mimesupport flag.
     *
     * @return Horde_ActiveSync_Message_Base  The message requested.
     */
    abstract public function itemOperationsFetchMailbox($longid, array $bodyprefs, $mimesupport);

    /**
     * Return a documentlibrary item.
     *
     * @param string $linkid  The linkid
     * @param array $cred     A credential array:
     *   - username: A hash with 'username' and 'domain' key/values.
     *   - password: User password
     *
     * @return array An array containing the data and metadata:
     */
    abstract public function itemOperationsGetDocumentLibraryLink($linkid, $cred);

    /**
     * Build a stat structure for an email message.
     *
     * @param string $folderid   The mailbox name.
     * @param integer|array $id  The message(s) to stat (IMAP UIDs).
     *
     * @return array
     */
    abstract public function statMailMessage($folderid, $id);

    /**
     * Return the server id of the specified special folder type.
     *
     * @param string $type  The self::SPECIAL_* constant.
     *
     * @return string  The folder's server id.
     */
    abstract public function getSpecialFolderNameByType($type);

    /**
     * Return the security policies.
     *
     * @param boolean|array $device  The device information sent by EAS 14.1
     *                               set to false otherwise. @since 3.0
     * @return array  An array of provisionable properties and values.
     */
    abstract public function getCurrentPolicy();

    /**
     * Return settings from the backend for a SETTINGS request.
     *
     * @param array $settings   An array of settings to return.
     * @param Horde_ActiveSync_Device $device  The device to obtain settings for.
     *
     * @return array  The requested settings.
     */
    abstract public function getSettings(array $settings, $device);

    /**
     * Set backend settings from a SETTINGS request.
     *
     * @param array $settings   The settings to store.
     * @param Horde_ActiveSync_Device $device  The device to store settings for.
     *
     * @return array  An array of status responses for each set request. e.g.,:
     *   array('oof' => Horde_ActiveSync_Request_Settings::STATUS_SUCCESS,
     *         'deviceinformation' => Horde_ActiveSync_Request_Settings::STATUS_SUCCESS);
     */
    abstract public function setSettings(array $settings, $device);

    /**
     * Return properties for an AUTODISCOVER request.
     *
     * @return array  An array of properties.
     */
    abstract public function autoDiscover();

    /**
     * Attempt to guess a username based on the email address passed from
     * EAS Autodiscover requests.
     *
     * @param string $email  The email address
     *
     * @return string  The username to use to authenticate to Horde with.
     */
    abstract public function getUsernameFromEmail($email);

    /**
     * Handle ResolveRecipient requests
     *
     * @param string $type    The type of recipient request. e.g., 'certificate'
     * @param string $search  The email to resolve.
     * @param array $opts     Any options required to perform the resolution.
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
     * @return array  An array of results containing any of the following:
     *   - type: (string)  The type of result a GAL entry or personal
     *                     address book entry. A
     *                     Horde_ActiveSync::RESOLVE_RESULT constant.
     *   - displayname: (string)   The display name of the contact.
     *   - emailaddress: (string)  The emailaddress.
     *   - entries: (array)        An array of certificates.
     *   - availability: (string)  A EAS style FB string.
     *   - picture: (Horde_ActiveSync_Message_ResolveRecipientsPicture)
     */
    abstract public function resolveRecipient($type, $search, array $options = array());

    /**
     * Returns the provisioning support for the current request.
     *
     * @return mixed  The value of the provisiong support flag.
     */
    abstract public function getProvisioning();

    /**
     * Hanlde meeting responses.
     *
     * @param array $response  The response data. Contains:
     *   - requestid: The identifier of the meeting request. Used by the server
     *                to fetch the original meeting request details.
     *   - response:  The user's response to the request. One of the response
     *                code constants.
     *   - folderid:  The collection id that contains the meeting request.
     *
     *
     * @return string  The UID of any created calendar entries, otherwise false.
     * @throws Horde_ActiveSync_Exception, Horde_Exception_NotFound
     */
    abstract public function meetingResponse(array $response);

    /**
     * Request freebusy information from the server
     *
     * @param string $user    The user to request FB information for.
     * @param array $options  Options.
     *
     * @return mixed boolean|array  The FB information, if available. Otherwise
     *                              false.
     * @deprecated Will be removed in 3.0 - this is provided by resolveRecipients
     */
    abstract public function getFreebusy($user, array $options = array());

}
