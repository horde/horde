<?php
/**
 * Horde backend. Provides the communication between horde data and
 * ActiveSync server.
 *
 * Copyright 2010-2013 Horde LLC (http://www.horde.org/)
 *
 * @author  Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Core
 */
class Horde_Core_ActiveSync_Driver extends Horde_ActiveSync_Driver_Base
{
    /**
     *  Server folder ids for non-email folders.
     *  We use the @ modifiers to avoid issues in the (fringe) case of
     *  having email folders named like contacts etc...
     */
    const APPOINTMENTS_FOLDER_UID = '@Calendar@';
    const CONTACTS_FOLDER_UID     = '@Contacts@';
    const TASKS_FOLDER_UID        = '@Tasks@';
    const NOTES_FOLDER_UID        = '@Notes@';

    const SPECIAL_SENT   = 'sent';
    const SPECIAL_SPAM   = 'spam';
    const SPECIAL_TRASH  = 'trash';
    const SPECIAL_DRAFTS = 'drafts';
    const SPECIAL_INBOX  = 'inbox';

    const HTML_BLOCKQUOTE = '<blockquote type="cite" style="border-left:2px solid blue;margin-left:2px;padding-left:12px;">';


    /**
     * Mappings for server uids -> display names. Populated in the const'r
     * so we can use localized text.
     *
     * @var array
     */
    protected $_displayMap = array();

    /**
     * Cache message stats
     *
     * @var array  An array of stat hashes
     */
    protected $_modCache;

    /**
     * Horde connector instance
     *
     * @var Horde_Core_ActiveSync_Connector
     */
    protected $_connector;

    /**
     * Folder cache
     *
     * @var array
     */
    protected $_folders = array();

    /**
     * Imap client adapter
     *
     * @var Horde_ActiveSync_Imap_Adapter
     */
    protected $_imap;

    /**
     * Authentication object
     *
     * @var Horde_Auth_Base
     */
    protected $_auth;

    /**
     * Current process id
     *
     * @var integer
     */
    protected $_pid;

    /**
     * Local cache of last verb searches.
     *
     * @var array
     */
    protected $_verbs = array();

    /**
     * Const'r
     *
     * @param array $params  Configuration parameters:
     *   - logger: (Horde_Log_Logger) The logger.
     *             DEFAULT: none (No logging).
     *
     *   - state: (Horde_ActiveSync_State_Base) The state driver.
     *            DEFAULT: none (REQUIRED).
     *   - connector: (Horde_Core_ActiveSync_Connector) The connector object
     *                for communicating with the registry.
     *                DEFAULT: none (REQUIRED)
     *   - auth: (Horde_Auth) The auth object.
     *           DEFAULT: none (REQUIRED).
     *   - imap: (Horde_ActiveSync_Imap_Adapter) The IMAP adapter if email
     *           support is desired.
     *           DEFAULT: none (No email support will be provided).
     */
    public function __construct(array $params = array())
    {
        parent::__construct($params);

        $this->_pid = getmypid();

        if (empty($this->_params['connector']) ||
            !($this->_params['connector'] instanceof Horde_Core_ActiveSync_Connector)) {
            throw new InvalidArgumentException('Missing required connector object.');
        }

        if (empty($this->_params['auth']) ||
            !($this->_params['auth'] instanceof Horde_Auth_Base)) {
            throw new InvalidArgumentException('Missing required Auth object');
        }

        $this->_connector = $params['connector'];
        $this->_auth = $params['auth'];
        unset($this->_params['connector']);
        unset($this->_params['auth']);
        if (!empty($this->_params['imap'])) {
            $this->_imap = $this->_params['imap'];
            unset($this->_params['imap']);
        }

        // Build the displaymap
        $this->_displayMap = array(
            self::APPOINTMENTS_FOLDER_UID => Horde_ActiveSync_Translation::t('Calendar'),
            self::CONTACTS_FOLDER_UID     => Horde_ActiveSync_Translation::t('Contacts'),
            self::TASKS_FOLDER_UID        => Horde_ActiveSync_Translation::t('Tasks'),
            self::NOTES_FOLDER_UID        => Horde_ActiveSync_Translation::t('Notes'),
        );
    }

    /**
     * Set the logger.
     *
     * @param Horde_Log_Logger $logger  The logger.
     */
    public function setLogger($logger)
    {
        parent::setLogger($logger);
        $this->_connector->setLogger($logger);
        if (!empty($this->_imap)) {
            $this->_imap->setLogger($logger);
        }
    }

    /**
     * Authenticate to Horde
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
        global $injector;

        $this->_logger->info(sprintf(
            '[%s] Horde_Core_ActiveSync_Driver::authenticate() attempt for %s',
            $this->_pid,
            $username));

        if (!$this->_auth->authenticate($username, array('password' => $password))) {
            $injector->getInstance('Horde_Log_Logger')->err(sprintf('Login failed from ActiveSync client for user %s.', $username));
            return false;
        }

        // Get the username from the registry so we capture it after any
        // hooks were run on it.
        $username = $GLOBALS['registry']->getAuth();
        $perms = $injector->getInstance('Horde_Perms');
        if ($perms->exists('horde:activesync')) {
            // Check permissions to ActiveSync
            if (!$this->_getPolicyValue('activesync', $perms->getPermissions('horde:activesync', $username))) {
                $this->_logger->info(sprintf(
                    "Access denied for user %s per policy settings.",
                    $username)
                );
                return Horde_ActiveSync::AUTH_REASON_USER_DENIED;
            }
        }

        return parent::authenticate($username, $password, $domain);
    }

    /**
     * Clean up
     *
     * @see framework/ActiveSync/lib/Horde/ActiveSync/Driver/Horde_ActiveSync_Driver_Base#clearAuthentication()
     */
    public function clearAuthentication()
    {
        $this->_connector->clearAuth();
        $this->_logger->info(sprintf(
            "[%s] User %s logged off",
            $this->_pid,
            $this->_user));
        return true;
    }

    /**
     * Setup sync parameters. The user provided here is the user the backend
     * will sync with. This allows you to authenticate as one user, and sync as
     * another, if the backend supports this (Horde does not).
     *
     * @param string $user      The username to sync as on the backend.
     *
     * @return boolean
     */
    public function setup($user)
    {
        parent::setup($user);
        $this->_modCache = array();
        return true;
    }

    /**
     * Get the wastebasket folder. If this returns false, imap deletions are
     * permanent. If it returns a valid mailbox, deletions are treated as moves
     * to this mailbox. Note that any collection class other than
     * Horde_ActiveSync::CLASS_EMAIL will return false.
     *
     * @return string|boolean  Returns name of the trash folder, or false
     *                         if not using a trash folder.
     */
    public function getWasteBasket($class)
    {
        if ($class != Horde_ActiveSync::CLASS_EMAIL) {
            return false;
        }
        $folders = $this->_getMailFolders();
        foreach ($folders as $folder) {
            if ($folder->type == Horde_ActiveSync::FOLDER_TYPE_WASTEBASKET) {
                return $folder->serverid;
            }
        }

        return false;
    }

    /**
     * Return an array of stats for the server's folder list.
     *
     * @return array  An array of folder stats @see self::statFolder()
     * @todo Horde 6 move to base class
     */
    public function getFolderList()
    {
        $this->_logger->info(sprintf(
            "[%s] Horde_Core_ActiveSync_Driver::getFolderList()",
            $this->_pid));
        $folderlist = $this->getFolders();
        $folders = array();
        foreach ($folderlist as $f) {
            $folders[] = $this->statFolder($f->serverid, $f->parentid, $f->displayname, $f->_serverid);
        }

        return $folders;
    }

    /**
     * Return an array of the server's folder objects.
     *
     * @return array  An array of Horde_ActiveSync_Message_Folder objects.
     */
    public function getFolders()
    {
        if (empty($this->_folders)) {
            ob_start();
            try {
                $supported = $this->_connector->horde_listApis();
            } catch (Exception $e) {
                $this->_logger->err($e->getMessage());
                $this->_endBuffer();
                return array();
            }
            $folders = array();
            if (array_search('calendar', $supported) !== false) {
                $folders[] = $this->getFolder(self::APPOINTMENTS_FOLDER_UID);
            }

            if (array_search('contacts', $supported) !== false) {
                $folders[] = $this->getFolder(self::CONTACTS_FOLDER_UID);
            }

            if (array_search('tasks', $supported) !== false) {
                $folders[] = $this->getFolder(self::TASKS_FOLDER_UID);
            }

            if (array_search('notes', $supported) !== false) {
                $folders[] = $this->getFolder(self::NOTES_FOLDER_UID);
            }

            if (array_search('mail', $supported) !== false) {
                try {
                    $folders = array_merge($folders, $this->_getMailFolders());
                } catch (Horde_ActiveSync_Exception $e) {
                    $this->_logger->err($e->getMessage());
                    $this->_endBuffer();
                    return array();
                }
            }

            $this->_endBuffer();

            $this->_folders = $folders;
        }

        return $this->_folders;
    }

    /**
     * Factory for Horde_ActiveSync_Message_Folder objects.
     *
     * @param string $id   The folder's server id.
     *
     * @return Horde_ActiveSync_Message_Folder
     * @throws Horde_ActiveSync_Exception
     */
    public function getFolder($id)
    {
        switch ($id) {
        case self::APPOINTMENTS_FOLDER_UID:
            $folder = $this->_buildNonMailFolder(
                $id,
                0,
                Horde_ActiveSync::FOLDER_TYPE_APPOINTMENT,
                $this->_displayMap[self::APPOINTMENTS_FOLDER_UID]);
            break;
        case self::CONTACTS_FOLDER_UID:
            $folder = $this->_buildNonMailFolder(
               $id,
               0,
               Horde_ActiveSync::FOLDER_TYPE_CONTACT,
               $this->_displayMap[self::CONTACTS_FOLDER_UID]);
            break;
        case self::TASKS_FOLDER_UID:
            $folder = $this->_buildNonMailFolder(
                $id,
                0,
                Horde_ActiveSync::FOLDER_TYPE_TASK,
                $this->_displayMap[self::TASKS_FOLDER_UID]);
            break;
        case self::NOTES_FOLDER_UID:
            $folder = $this->_buildNonMailFolder(
                $id,
                0,
                Horde_ActiveSync::FOLDER_TYPE_NOTE,
                $this->_displayMap[self::NOTES_FOLDER_UID]);
                break;
        default:
            // Must be a mail folder
            $folders = $this->_getMailFolders();
            foreach ($folders as $folder) {
                if ($folder->_serverid == $id) {
                    return $folder;
                }
            }
            $this->_logger->err('Folder ' . $id . ' unknown');
            throw new Horde_ActiveSync_Exception('Folder ' . $id . ' unknown');
        }

        return $folder;
    }

    /**
     * Return the foldertype given a folder id. ONLY for use when the exact
     * type of email collection is not needed. I.e., only the fact that it is
     * some type of email collection vs another collection type.
     *
     * @param string $id  The folder id.
     *
     * @return string  The folder type
     */
    protected function _getFolderType($id)
    {
        switch ($id) {
        case self::APPOINTMENTS_FOLDER_UID:
            return Horde_ActiveSync::FOLDER_TYPE_APPOINTMENT;
        case self::CONTACTS_FOLDER_UID:
            return Horde_ActiveSync::FOLDER_TYPE_CONTACT;
        case self::TASKS_FOLDER_UID:
            return Horde_ActiveSync::FOLDER_TYPE_TASK;
        case self::NOTES_FOLDER_UID:
            return Horde_ActiveSync::FOLDER_TYPE_NOTE;
        default:
            return Horde_ActiveSync::FOLDER_TYPE_USER_MAIL;
        }
    }

    /**
     * Change a folder on the server.
     *
     * @param string $id           The server's folder id
     * @param string $displayname  The new display name.
     * @param string $parent       The folder's parent, if needed.
     * @param string $uid          The existing folder uid, if this is an edit.
     *                             @since 2.5.0 (@todo Look at this for H6. It's
     *                             here now to save an extra DB lookup for data
     *                             we already have.)
     *
     * @return string  The new folder uid.
     */
    public function changeFolder($id, $displayname, $parent, $uid = null)
    {
        if (!$id) {
            try {
                $this->_imap->createMailbox($displayname, $parent);
            } catch (Horde_ActiveSync_Exception $e) {
                $this->_logger->err($e->getMessage());
                throw $e;
            }
            $uid = $this->_getFolderUidForBackendId($displayname);
        } else {
            try {
                $this->_imap->renameMailbox($id, $displayname, $parent);
            } catch (Horde_ActiveSync_Exception $e) {
                $this->_logger->err($e->getMessage());
                throw $e;
            }
        }

        return $uid;
    }

    /**
     * Delete a folder on the server.
     *
     * @param string $id  The server's folder id.
     * @param string $parent  The folder's parent, if needed.
     */
    public function deleteFolder($id, $parent = Horde_ActiveSync::FOLDER_ROOT)
    {
        try {
            $this->_imap->deleteMailbox($id);
        } catch (Horde_ActiveSync_Exception $e) {
            $this->_logger->err($e->getMessage());
            throw $e;
        }
    }

    /**
     * Stat folder. Note that since the only thing that can ever change for a
     * folder is the name, we use that as the 'mod' value.
     *
     * @param string $id        The folder's EAS uid
     * @param mixed $parent     The parent folder (or 0 if none).
     * @param mixed $mod        Modification indicator. For folders, this is the
     *                          display name of the folder, since that's the
     *                          only thing that can change.
     * @param string $serverid  The backend serverid for this folder.
     * @return a stat hash:
     *   - id: The activesync folder identifier.
     *   - mod: The modification value.
     *   - parent: The folder's parent id.
     *   - serverid:  The backend server's folder name for this folder.
     *
     * @todo Horde 6, move to the base class.
     */
    public function statFolder($id, $parent = '0', $mod = null, $serverid = null)
    {
        $folder = array();
        $folder['id'] = $id;
        $folder['mod'] = empty($mod) ? $id : $mod;
        $folder['parent'] = $parent;
        $folder['serverid'] = !empty($serverid) ? $serverid : $id;

        return $folder;
    }

    /**
     * Get a list of server changes that occured during the specified time
     * period.
     *
     * @param Horde_ActiveSync_Folder_Base $folder
     *      The ActiveSync folder object to request changes for.
     * @param integer $from_ts     The starting timestamp
     * @param integer $to_ts       The ending timestamp
     * @param integer $cutoffdate  The earliest date to retrieve back to
     * @param boolean $ping        If true, returned changeset may
     *                             not contain the full changeset, but rather
     *                             only a single change, designed only to
     *                             indicate *some* change has taken place. The
     *                             value should not be used to determine *what*
     *                             change has taken place.
     * @param boolean $ignoreFirstSync  If true, will not trigger an initial sync
     *                                  if $from_ts is 0. Needed to avoid race
     *                                  conditions when we don't have any history
     *                                  data. @since 2.6.0
     *
     * @return array A list of messge uids that have changed in the specified
     *               time period.
     */
    public function getServerChanges($folder, $from_ts, $to_ts, $cutoffdate, $ping, $ignoreFirstSync = false)
    {
        $this->_logger->info(sprintf(
            "[%s] Horde_Core_ActiveSync_Driver::getServerChanges(%s, %u, %u, %u, %d)",
            $this->_pid,
            $folder->serverid(),
            $from_ts,
            $to_ts,
            $cutoffdate,
            $ping)
        );

        $changes = array(
            'add' => array(),
            'delete' => array(),
            'modify' => array()
        );

        ob_start();
        switch ($folder->collectionClass()) {
        case Horde_ActiveSync::CLASS_CALENDAR:
            if ($from_ts == 0 && !$ignoreFirstSync) {
                // Can't use History if it's a first sync
                $startstamp = (int)$cutoffdate;
                $endstamp = time() + 32140800; //60 * 60 * 24 * 31 * 12 == one year
                try {
                    $changes['add'] = $this->_connector->calendar_listUids($startstamp, $endstamp);
                } catch (Horde_Exception_AuthenticationFailure $e) {
                    $this->_endBuffer();
                    throw $e;
                } catch (Horde_Exception $e) {
                    $this->_logger->err($e->getMessage());
                    $this->_endBuffer();
                    return array();
                }
            } else {
                try {
                    $changes = $this->_connector->getChanges('calendar', $from_ts, $to_ts);
                } catch (Horde_Exception_AuthenticationFailure $e) {
                    $this->_endBuffer();
                    throw $e;
                } catch (Horde_Exception $e) {
                    $this->_logger->err($e->getMessage());
                    $this->_endBuffer();
                    return array();
                }
            }
            break;

        case Horde_ActiveSync::CLASS_CONTACTS:
            // Can't use History for first sync
            if ($from_ts == 0 && !$ignoreFirstSync) {
                try {
                    $changes['add'] = $this->_connector->contacts_listUids();
                } catch (Horde_Exception_AuthenticationFailure $e) {
                    $this->_endBuffer();
                    throw $e;
                } catch (Horde_Exception $e) {
                    $this->_logger->err($e->getMessage());
                    $this->_endBuffer();
                    return array();
                }
            } else {
                try {
                    $changes = $this->_connector->getChanges('contacts', $from_ts, $to_ts);
                } catch (Horde_Exception_AuthenticationFailure $e) {
                    $this->_endBuffer();
                    throw $e;
                } catch (Horde_Exception $e) {
                    $this->_logger->err($e->getMessage());
                    $this->_endBuffer();
                    return array();
                }
            }
            break;

        case Horde_ActiveSync::CLASS_TASKS:
            // Can't use History for first sync
            if ($from_ts == 0 && !$ignoreFirstSync) {
                try {
                    $changes['add'] = $this->_connector->tasks_listUids();
                } catch (Horde_Exception_AuthenticationFailure $e) {
                    $this->_endBuffer();
                    throw $e;
                } catch (Horde_Exception $e) {
                    $this->_logger->err($e->getMessage());
                    $this->_endBuffer();
                    return array();
                }
            } else {
                try {
                    $changes = $this->_connector->getChanges('tasks', $from_ts, $to_ts);
                } catch (Horde_Exception_AuthenticationFailure $e) {
                    $this->_endBuffer();
                    throw $e;
                } catch (Horde_Exception $e) {
                    $this->_logger->err($e->getMessage());
                    $this->_endBuffer();
                    return array();
                }
            }
            break;

        case Horde_ActiveSync::CLASS_NOTES:
            // Can't use History for first sync
            if ($from_ts == 0 && !$ignoreFirstSync) {
                try {
                    $changes['add'] = $this->_connector->notes_listUids();
                } catch (Horde_Exception_AuthenticationFailure $e) {
                    $this->_endBuffer();
                    throw $e;
                } catch (Horde_Exception $e) {
                    $this->_logger->err($e->getMessage());
                    $this->_endBuffer();
                    return array();
                }
            } else {
                try {
                    $changes = $this->_connector->getChanges('notes', $from_ts, $to_ts);
                } catch (Horde_Exception_AuthenticationFailure $e) {
                    $this->_endBuffer();
                    throw $e;
                } catch (Horde_Exception $e) {
                    $this->_logger->err($e->getMessage());
                    $this->_endBuffer();
                    return array();
                }
            }
            break;

        case Horde_ActiveSync::CLASS_EMAIL:
            if (empty($this->_imap)) {
                $this->_endBuffer();
                return array();
            }
            $this->_logger->info(sprintf(
                '[%s] MODSEQ: %d', $this->_pid, $folder->modseq()));
            if ($ping) {
                try {
                    $ping_res = $this->_imap->ping($folder);
                    if ($ping_res) {
                        $changes['add'] = array(1);
                    }
                } catch (Horde_ActiveSync_Exeption_StaleState $e) {
                    $this->_endBuffer();
                    throw $e;
                } catch (Horde_ActiveSync_Exception_FolderGone $e) {
                    $this->_endBuffer();
                    throw $e;
                } catch (Horde_Exception_AuthenticationFailure $e) {
                    $this->_endBuffer();
                    throw $e;
                } catch (Horde_Exception $e) {
                    $this->_logger->err($e->getMessage());
                    $this->_endBuffer();
                    return array();
                }
            } else {
                try {
                    // Poll IMAP server for changes.
                    $folder = $this->_imap->getMessageChanges(
                        $folder,
                        array(
                            'sincedate' => (int)$cutoffdate,
                            'protocolversion' => $this->_version));
                    // Poll the maillog for reply/forward state changes.
                    $folder = $this->_getMaillogChanges($folder, $from_ts);
                } catch (Horde_ActiveSync_Exception_StaleState $e) {
                    $this->_endBuffer();
                    throw $e;
                } catch (Horde_ActiveSync_Exception_FolderGone $e) {
                    $this->_endBuffer();
                    throw $e;
                } catch (Horde_Exception_AuthenticationFailure $e) {
                    $this->_endBuffer();
                    throw $e;
                } catch (Horde_Exception $e) {
                    $this->_logger->err($e->getMessage());
                    $this->_endBuffer();
                    return array();
                }
                $changes['add'] = $folder->added();
                $changes['delete'] = $folder->removed();
                $changes['modify'] = $folder->changed();
            }
        }

        $results = array();
        foreach ($changes['add'] as $add) {
            $results[] = array(
                'id' => $add,
                'type' => Horde_ActiveSync::CHANGE_TYPE_CHANGE,
                'flags' => Horde_ActiveSync::FLAG_NEWMESSAGE);
        }

        // For CLASS_EMAIL, all changes are a change in flags.
        if ($folder->collectionClass() == Horde_ActiveSync::CLASS_EMAIL) {
            $flags = $folder->flags();
            foreach ($changes['modify'] as $uid) {
                $results[] = array(
                    'id' => $uid,
                    'type' => Horde_ActiveSync::CHANGE_TYPE_FLAGS,
                    'flags' => $flags[$uid]
                );
            }
        } else {
            foreach ($changes['modify'] as $change) {
                $results[] = array(
                    'id' => $change,
                    'type' => Horde_ActiveSync::CHANGE_TYPE_CHANGE
                );
            }
        }

        // Server Deletions
        foreach ($changes['delete'] as $deleted) {
            $results[] = array(
                'id' => $deleted,
                'type' => Horde_ActiveSync::CHANGE_TYPE_DELETE);
        }
        $this->_endBuffer();

        return $results;
    }

    /**
     * Obtain an ActiveSync message from the backend.
     *
     * @param string $folderid    The server's folder id this message is from
     * @param string $id          The server's message id
     * @param array  $collection  The colletion data. May contain things like:
     *   - mimesupport: (integer) Indicates if the device has MIME support.
     *                  DEFAULT: 0 (No MIME support)
     *   - truncation: (integer)  The truncation constant, if sent by the device.
     *                 DEFAULT: 0 (No truncation)
     *   - bodyprefs: (array)  The bodypref array from the device.
     *
     * @return Horde_ActiveSync_Message_Base The message data
     * @throws Horde_ActiveSync_Exception, Horde_Exception_NotFound
     */
    public function getMessage($folderid, $id, array $collection)
    {
        $this->_logger->info(sprintf(
            "[%s] Horde_Core_ActiveSync_Driver::getMessage(%s, %s)",
            $this->_pid,
            $folderid,
            $id));
        ob_start();
        $message = false;
        $foldertype = $this->_getFolderType($folderid);
        switch ($foldertype) {
        case Horde_ActiveSync::FOLDER_TYPE_APPOINTMENT:
            try {
                $message = $this->_connector->calendar_export($id, array(
                    'protocolversion' => $this->_version,
                    'truncation' => $collection['truncation'],
                    'bodyprefs' => $this->addDefaultBodyPrefTruncation($collection['bodyprefs']),
                    'mimesupport' => $collection['mimesupport']));

                // Nokia MfE requires the optional UID element.
                if (!$message->getUid()) {
                    $message->setUid($id);
                }
            } catch (Horde_Exception $e) {
                $this->_logger->err($e->getMessage());
                $this->_endBuffer();
                throw new Horde_ActiveSync_Exception($e->getMessage());
            }
            break;

        case Horde_ActiveSync::FOLDER_TYPE_CONTACT:
            try {
                $message = $this->_connector->contacts_export($id, array(
                    'protocolversion' => $this->_version,
                    'truncation' => $collection['truncation'],
                    'bodyprefs' => $this->addDefaultBodyPrefTruncation($collection['bodyprefs']),
                    'mimesupport' => $collection['mimesupport']));
            } catch (Horde_Exception $e) {
                $this->_logger->err($e->getMessage());
                $this->_endBuffer();
                throw new Horde_ActiveSync_Exception($e->getMessage());
            }
            break;

        case Horde_ActiveSync::FOLDER_TYPE_TASK:
            try {
                $message = $this->_connector->tasks_export($id, array(
                    'protocolversion' => $this->_version,
                    'truncation' => $collection['truncation'],
                    'bodyprefs' => $this->addDefaultBodyPrefTruncation($collection['bodyprefs']),
                    'mimesupport' => $collection['mimesupport']));
            } catch (Horde_Exception $e) {
                $this->_logger->err($e->getMessage());
                $this->_endBuffer();
                throw new Horde_ActiveSync_Exception($e->getMessage);
            }
            break;

        case Horde_ActiveSync::FOLDER_TYPE_NOTE:
            try {
                $message = $this->_connector->notes_export($id, array(
                    'protocolversion' => $this->_version,
                    'truncation' => $collection['truncation'],
                    'bodyprefs' => $this->addDefaultBodyPrefTruncation($collection['bodyprefs']),
                    'mimesupport' => $collection['mimesupport']));
            } catch (Horde_Exception $e) {
                $this->_logger->err($e->getMessage());
                $this->_endBuffer();
                throw new Horde_ActiveSync_Exception($e->getMessage);
            }
            break;

        case Horde_ActiveSync::FOLDER_TYPE_INBOX:
        case Horde_ActiveSync::FOLDER_TYPE_SENTMAIL:
        case Horde_ActiveSync::FOLDER_TYPE_WASTEBASKET:
        case Horde_ActiveSync::FOLDER_TYPE_DRAFTS:
        case Horde_ActiveSync::FOLDER_TYPE_USER_MAIL:
            // Get the message from the IMAP server.
            try {
                $messages = $this->_imap->getMessages(
                    $folderid,
                    array($id),
                    array(
                        'protocolversion' => $this->_version,
                        'truncation' => !empty($collection['truncation'])
                            ? $collection['truncation']
                            : (!empty($collection['mimetruncation']) ? $collection['mimetruncation'] : false),
                        'bodyprefs'  => $this->addDefaultBodyPrefTruncation($collection['bodyprefs']),
                        'mimesupport' => $collection['mimesupport']
                    )
                );
            } catch (Horde_ActiveSync_Exception $e) {
                $this->_logger->err($e->getMessage());
                $this->_endBuffer();
                throw $e;
            }
            if (empty($messages)) {
                $this->_endBuffer();
                throw new Horde_Exception_NotFound();
            }
            $msg = current($messages);

            // Check for verb status from the Maillog.
            if ($this->_version >= Horde_ActiveSync::VERSION_FOURTEEN) {
                $last = $this->_getLastVerb($msg->messageid);
                if (!empty($last)) {
                    switch ($last['action']) {
                    case 'reply':
                    case 'reply_list':
                        $msg->lastverbexecuted = Horde_ActiveSync_Message_Mail::VERB_REPLY_SENDER;
                        break;
                    case 'reply_all':
                        $msg->lastverbexecuted = Horde_ActiveSync_Message_Mail::VERB_REPLY_ALL;
                        break;
                    case 'forward':
                        $msg->lastverbexecuted = Horde_ActiveSync_Message_Mail::VERB_FORWARD;
                    }
                    $msg->lastverbexecutiontime = new Horde_Date($last['ts']);
                } else {
                    // No maillog found, double check the IMAP flags.
                    // We favor the Maillog since EAS allows for a complete log
                    // of actions - and it requires a timestamp.
                    if ($msg->answered) {
                        $msg->lastverbexecuted = Horde_ActiveSync_Message_Mail::VERB_REPLY_SENDER;
                        $msg->lastverbexecutiontime = new Horde_Date(time());
                    } elseif ($msg->forwarded) {
                        $msg->lastverbexecuted = Horde_ActiveSync_Message_Mail::VERB_FORWARD;
                        $msg->lastverbexecutiontime = new Horde_Date(time());
                    }
                }
            }

            $this->_endBuffer();

            // Should we import an iTip response if we have one?
            if ($this->_version >= Horde_ActiveSync::VERSION_TWELVE &&
                $msg->contentclass == 'urn:content-classes:calendarmessage') {
                switch ($msg->messageclass) {
                case 'IPM.Schedule.Meeting.Resp.Pos':
                case 'IPM.Schedule.Meeting.Resp.Neg':
                case 'IPM.Schedule.Meeting.Resp.Tent':
                    $addr = new Horde_Mail_Rfc822_Address($msg->from);
                    $rq = $msg->meetingrequest;
                    $this->_connector->calendar_import_attendee(
                        $rq->getvEvent(),
                        $addr->bare_address);
                }
            }
            return $msg;

        default:
            $this->_endBuffer();
            throw new Horde_ActiveSync_Exception('Unsupported type');
        }

        $this->_endBuffer();

        return $message;
    }

    /**
     * Return the specified attachment.
     *
     * @param string $name  The attachment identifier. For this driver, this
     *                      consists of 'mailbox:uid:mimepart'
     * @param array $options  Any options requested. Currently supported:
     *  - stream: (boolean) Return a stream resource for the mime contents.
     *            DEFAULT: true (Return a stream resource for the 'data' value).
     *
     * @return array  The attachment in the form of an array with the following
     *                structure:
     * array('content-type' => {the content-type of the attachement},
     *       'data'         => {the raw attachment data})
     */
    public function getAttachment($name, array $options = array())
    {
        $options = array_merge(array('stream' => true), $options);
        list($mailbox, $uid, $part) = explode(':', $name);
        $atc = $this->_imap->getAttachment($mailbox, $uid, $part);

        return array(
            'content-type' => $atc->getType(),
            'data' => $atc->getContents(array('stream' => $options['stream']))
        );
    }

    /**
     * Return Horde_Imap_Message_Mail object represented by the specified
     * longid. Used to fetch email objects from a search result, which only
     * returns a 'longid'.
     *
     * @param string $longid         The unique search result identifier.
     *                               Consists of mailbox:uid E.g, INBOX:110
     * @param array $bodyprefs       The bodypreference array.
     * @param integer $mimesupport   Mimesupport flag.
     *                               A Horde_ActiveSync::MIME_SUPPORT_* constant.
     *
     * @return Horde_ActiveSync_Message_Base  The message requested.
     */
    public function itemOperationsFetchMailbox(
        $longid, array $bodyprefs, $mimesupport = 0)
    {
        list($mailbox, $uid) = explode(':', $longid);
        return $this->getMessage(
            $mailbox,
            $uid,
            array(
                'bodyprefs' => $bodyprefs,
                'mimesupport' => $mimesupport)
        );
    }

    /**
     * Return the specified attachement data for an ITEMOPERATIONS request.
     *
     * @param string $filereference  The attachment identifier.
     *
     * @return Horde_ActiveSync_Message_AirSyncBaseFileAttachment
     */
    public function itemOperationsGetAttachmentData($filereference)
    {
        $att = $this->getAttachment($filereference);
        $airatt = Horde_ActiveSync::messageFactory('AirSyncBaseFileAttachment');
        $airatt->data = $att['data'];
        $airatt->contenttype = $att['content-type'];

        return $airatt;
    }

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
    public function itemOperationsGetDocumentLibraryLink($linkid, $cred)
    {
        throw new Horde_ActiveSync_Exception('Not Supported');
    }

    /**
     * Get message stat data
     *
     * @see framework/ActiveSync/lib/Horde/ActiveSync/Driver/Horde_ActiveSync_Driver_Base#statMessage($folderId, $id)
     */
    public function statMessage($folderid, $id)
    {
        return $this->_smartStatMessage($folderid, $id, true);
    }

    /**
     * Delete a message
     *
     * @param string $folderid  The folder id
     * @param array $ids        The message ids to delete
     *
     * @return array  An array of succesfully deleted messages (currently
     *                only guarenteed for email messages).
     */
    public function deleteMessage($folderid, array $ids)
    {
        $this->_logger->info(sprintf(
            "[%s] Horde_Core_ActiveSync_Driver::deleteMessage() %s: %s",
            $this->_pid,
            $folderid,
            print_r($ids, true))
        );
        // TODO: Need to have the various connector methods report back
        //       successfully deleted ids. Currently the APIs do not report
        //       this for anything other than email.
        $results = $ids;

        ob_start();
        switch ($folderid) {
        case self::APPOINTMENTS_FOLDER_UID:
            try {
                $this->_connector->calendar_delete($ids);
            } catch (Horde_Exception $e) {
                $this->_logger->err($e->getMessage());
            }
            break;

        case self::CONTACTS_FOLDER_UID:
            try {
                $this->_connector->contacts_delete($ids);
            } catch (Horde_Exception $e) {
                $this->_logger->err($e->getMessage());
            }
            break;

        case self::TASKS_FOLDER_UID:
            try {
                $this->_connector->tasks_delete($ids);
            } catch (Horde_Exception $e) {
                $this->_logger->err($e->getMessage());
            }
            break;

        case self::NOTES_FOLDER_UID:
            try {
                $this->_connector->notes_delete($ids);
            } catch (Horde_Exception $e) {
                $this->_logger->err($e->getMessage());
            }
            break;
        default:
            // Must be mail folder
            try {
                $results = $this->_imap->deleteMessages($ids, $folderid);
            } catch (Horde_ActiveSync_Exception $e) {
                $this->_logger->err($e->getMessage());
            }
        }
        $this->_endBuffer();

        return $results;
    }

    /**
     * Move message
     *
     * @param string $folderid     Existing folder id.
     * @param array $ids           Message UIDs to move.
     * @param string $newfolderid  The new folder id to move to.
     *
     * @return array  An array of successfully moved messages.
     * @throws Horde_Exception
     */
    public function moveMessage($folderid, array $ids, $newfolderid)
    {
        $this->_logger->info(sprintf(
            "[%s] Horde_Core_ActiveSync_Driver::moveMessage(%s, [%s], %s)",
            $this->_pid,
            $folderid,
            implode(',', $ids),
            $newfolderid));
        ob_start();
        switch ($folderid) {
        case self::APPOINTMENTS_FOLDER_UID:
        case self::CONTACTS_FOLDER_UID:
        case self::TASKS_FOLDER_UID:
        case self::NOTES_FOLDER_UID:
            $this->_endBuffer();
            throw new Horde_ActiveSync_Exception('Not supported');
        default:
            $move_res = $this->_imap->moveMessage($folderid, $ids, $newfolderid);
        }
        $this->_endBuffer();

        return $move_res;
    }

    /**
     * Add/Edit a message
     *
     * @param string $folderid  The server id of the message's folder.
     * @param string $id        The server's uid for the message if this is a
     *                          change to an existing message, false if new.
     * @param Horde_ActiveSync_Message_Base $message The activesync message.
     * @param Horde_ActiveSync_Device $device        The device information
     *        @since 2.5.0
     *
     * @return array|boolean    A stat array if successful, otherwise false.
     */
    public function changeMessage($folderid, $id, Horde_ActiveSync_Message_Base $message, $device)
    {
        $this->_logger->info(sprintf(
            "[%s] Horde_Core_ActiveSync_Driver::changeMessage(%s, %s ...)",
            $this->_pid,
            $folderid,
            $id));
        ob_start();
        $stat = false;
        switch ($folderid) {
        case self::APPOINTMENTS_FOLDER_UID:
            if (!$id) {
                try {
                    $id = $this->_connector->calendar_import($message);
                } catch (Horde_Exception $e) {
                    $this->_logger->err($e->getMessage());
                    $this->_endBuffer();
                    return false;
                }
                $stat = array('mod' => $this->getSyncStamp($folderid), 'id' => $id, 'flags' => 1);
            } else {
                // ActiveSync messages do NOT contain the serverUID value, put
                // it in ourselves so we can have it during import/change.
                $message->setServerUID($id);
                if (!empty($device->supported[self::APPOINTMENTS_FOLDER_UID])) {
                    $message->setSupported($device->supported[self::APPOINTMENTS_FOLDER_UID]);
                }
                try {
                    $this->_connector->calendar_replace($id, $message);
                } catch (Horde_Exception $e) {
                    $this->_logger->err($e->getMessage());
                    $this->_endBuffer();
                    return false;
                }
                $stat = $this->_smartStatMessage($folderid, $id, false);
            }
            break;

        case self::CONTACTS_FOLDER_UID:
            if (!$id) {
                try {
                    $id = $this->_connector->contacts_import($message);
                } catch (Horde_Exception $e) {
                    $this->_logger->err($e->getMessage());
                    $this->_endBuffer();
                    return false;
                }
                $stat = array('mod' => $this->getSyncStamp($folderid), 'id' => $id, 'flags' => 1);
            } else {
                if (!empty($device->supported[self::CONTACTS_FOLDER_UID])) {
                    $message->setSupported($device->supported[self::CONTACTS_FOLDER_UID]);
                }
                try {
                    $this->_connector->contacts_replace($id, $message);
                } catch (Horde_Exception $e) {
                    $this->_logger->err($e->getMessage());
                    $this->_endBuffer();
                    return false;
                }
                $stat = $this->_smartStatMessage($folderid, $id, false);
            }
            break;

        case self::TASKS_FOLDER_UID:
            if (!$id) {
                try {
                    $id = $this->_connector->tasks_import($message);
                } catch (Horde_Exception $e) {
                    $this->_logger->err($e->getMessage());
                    $this->_endBuffer();
                    return false;
                }
                $stat = array('mod' => $this->getSyncStamp($folderid), 'id' => $id, 'flags' => 1);
            } else {
                if (!empty($device->supported[self::TASKS_FOLDER_UID])) {
                    $message->setSupported($device->supported[self::TASKS_FOLDER_UID]);
                }
                try {
                    $this->_connector->tasks_replace($id, $message);
                } catch (Horde_Exception $e) {
                    $this->_logger->err($e->getMessage());
                    $this->_endBuffer();
                    return false;
                }
                $stat = $this->_smartStatMessage($folderid, $id, false);
            }
            break;

        case self::NOTES_FOLDER_UID:
            if (!$id) {
                try {
                    $id = $this->_connector->notes_import($message);
                } catch (Horde_Exception $e) {
                    $this->_logger->err($e->getMessage());
                    $this->_endBuffer();
                    return false;
                }
                $stat = array('mod' => $this->getSyncStamp($folderid), 'id' => $id, 'flags' => 1);
            } else {
                if (!empty($device->supported[self::NOTES_FOLDER_UID])) {
                    $message->setSupported($device->supported[self::NOTES_FOLDER_UID]);
                }
                try {
                    $this->_connector->notes_replace($id, $message);
                } catch (Horde_Exception $e) {
                    $this->_logger->err($e->getMessage());
                    $this->_endBuffer();
                    return false;
                }
                $stat = $this->_smartStatMessage($folderid, $id, false);
            }
            break;

        default:
            if ($message instanceof Horde_ActiveSync_Message_Mail) {
                $stat = array(
                    'id' => $id,
                    'mod' => 0,
                    'flags' => array()
                );
                if ($message->read !== '') {
                    $this->setReadFlag($folderid, $id, $message->read);
                    $stat['flags'] = array_merge($stat['flags'], array('read' => $message->read));
                } elseif ($message->propertyExists('flag')) {
                    if (!$message->flag) {
                        $message->flag = Horde_ActiveSync::messageFactory('Flag');
                    }
                    $this->_imap->setMessageFlag($folderid, $id, $message->flag);
                    $stat['flags'] = array_merge($stat['flags'], array('flagged' => $message->flag->flagstatus));
                }
            } else {
                $this->_endBuffer();
                return false;
            }
        }
        $this->_endBuffer();

        return $stat;
    }

    /**
     * Returns array of items which contain contact information
     *
     * @param string $type   The search type; ['gal'|'mailbox']
     * @param array $query   The search query. An array containing:
     *  - query: array The search query. Contains at least:
     *                 'query' and 'range'. The rest depends on the type of
     *                 search being performed.
     *           DEFAULT: none, REQUIRED
     *  - range: (string)   A range limiter.
     *           DEFAULT: none (No range used).
     *
     * @return array  An array containing:
     *  - rows:   An array of search results
     *  - status: The search store status code.
     */
    public function getSearchResults($type, array $query)
    {
        switch (strtolower($type)) {
        case 'gal':
            return $this->_searchGal($query);
        case 'mailbox':
            return array(
                'rows' => $this->_searchMailbox($query),
                'status' => Horde_ActiveSync_Request_Search::STORE_STATUS_SUCCESS);
        }
    }

    /**
     * Sends the email represented by the rfc822 string received by the PIM.
     *
     * @param mixed $rfc822             The rfc822 mime message, a string or
     *                                  stream resource.
     * @param integer|boolean $forward  The UID of the message, if forwarding or
     *                                  true if forwarding and EAS >= 14.0
     * @param integer|boolean $reply    The UID of the message if replying or
     *                                  true if replying and EAS >= 14.0
     * @param string $parent            The collection id of parent message if
     *                                  forwarding/replying.
     * @param boolean $save             Save in sent messages.
     * @param Horde_ActiveSync_Message_SendMail $message  The entire message
     *                          object for EAS 14+ requests. @since 2.5.0
     * @todo H6 - Either make this take an options array or break it into two
     *            separate methods - one for EAS < 14 and one for EAS > 14.
     *
     * @return boolean
     * @throws Horde_ActiveSync_Exception
     */
    public function sendMail(
        $rfc822, $forward = false, $reply = false, $parent = false, $save = true,
        Horde_ActiveSync_Message_SendMail $message = null)
    {
        if (empty($rfc822) && !empty($message)) {
            // Get the raw message from the message object.
            $raw_message = new Horde_ActiveSync_Rfc822($message->mime);

            // Parse out any smart reply or forward requests.
            if ($forward) {
                $forward = $message->source->itemid;
                $parent = $message->source->folderid;
            } elseif ($reply) {
                $reply = $message->source->itemid;
                $parent = $message->source->folderid;
            }

            // Override the $save value since it's sent as part of the message
            // object in EAS 14+
            $save = $message->saveinsent;

            // Do we want to just replace the mime part?
            $replacemime = $message->replacemime;
        } else {
            $raw_message = new Horde_ActiveSync_Rfc822($rfc822);
            $replacemime = false;
        }

        $headers = $raw_message->getHeaders();

        // Add From, but only if needed.
        if (!$headers->getValue('From')) {
            $headers->addHeader('From', $this->_getIdentityFromAddress());
        }

        // Use the raw base part parsed from the rfc822 message if we don't
        // need a smart reply or smart forward. We MUST use the raw message body
        // as sent from the client since it may be s/mime signed.The device will
        // NOT send a smart reply/forward request if it is s/mime signed.
        // Note that we also cannot use Horde_Mime_Part::send or Horde_Mime_Mail
        // since these all rebuild the mime parts.
        if (!$parent || ($parent && $replacemime)) {
            $h_array = $headers->toArray(array('charset' => 'UTF-8'));
            if (is_array($h_array['From'])) {
                $h_array['From'] = current($h_array['From']);
            }
            $recipients = $h_array['To'];
            if (!empty($h_array['Cc'])) {
                $recipients .= ',' . $h_array['Cc'];
            }
            if (!empty($h_array['Bcc'])) {
                $recipients .= ',' . $h_array['Bcc'];
            }
            $GLOBALS['injector']->getInstance('Horde_Mail')->send($recipients, $h_array, $raw_message->getMessage()->stream);

            try {
                // Need the message if we are saving to sent.
                if ($save) {
                    $copy = $raw_message->getMimeObject();
                }
               // $mail->send($GLOBALS['injector']->getInstance('Horde_Mail'), true);
            } catch (Horde_Mail_Exception $e) {
                $this->_logger->err($e->getMessage());
                throw new Horde_ActiveSync_Exception($e);
            }
            if ($replacemime) {
                // Even though we don't need to the original body, we still need
                // to get the message headers so we have the message-id.
                $source_uid = empty($forward) ? $reply : $forward;
                $imap_message = array_pop($this->_imap->getImapMessage($parent, $source_uid, array('headers' => true)));
                if (empty($imap_message)) {
                    throw new Horde_Exception_NotFound('The forwarded/replied message was not found.');
                }
                $this->_logger->info(sprintf(
                    '[%s] Client sent a SMART request along with REPLACEMIME tag.',
                    $this->_pid));
            }
        } else {
            // Handle smartReplies and smartForward requests.
            $mime_message = $raw_message->getMimeObject();
            $mail = new Horde_Mime_Mail($headers->toArray());
            $source_uid = empty($forward) ? $reply : $forward;
            $imap_message = array_pop($this->_imap->getImapMessage($parent, $source_uid, array('headers' => true)));
            if (empty($imap_message)) {
                throw new Horde_Exception_NotFound('The forwarded/replied message was not found.');
            }
            $base_part = $imap_message->getStructure();
            $plain_id = $base_part->findBody('plain');
            $html_id = $base_part->findBody('html');
            $body_data = $imap_message->getMessageBodyData(array(
                'protocolversion' => $this->_version,
                'bodyprefs' => array(Horde_ActiveSync::BODYPREF_TYPE_MIME => true))
            );

            $this->_logger->info(sprintf(
                "[%s} Preparing %s for UID %s:%s",
                $this->_pid,
                (empty($forward) ? 'SMART_REPLY' : 'SMART_FORWARD'),
                $parent,
                $forward));

            // Reply top?
            $this->_params['reply_top'] = $GLOBALS['prefs']->getValue('activesync_replyposition') == 'top';

            // Do we need to add to the HTML part?
            if (!empty($html_id)) {
                if (!$id = $mime_message->findBody('html')) {
                    $smart_text = self::text2html($mime_message->getPart($mime_message->findBody('plain'))->getContents());
                } else {
                    $smart_text = $mime_message->getPart($id)->getContents();
                }
                if ($forward) {
                    $newbody_text_html = $smart_text . $this->_forwardText($imap_message, $body_data, $base_part->getPart($html_id), true);
               } else {
                    $newbody_text_html = ($this->_params['reply_top'] ? $smart_text : '')
                        . $this->_replyText($imap_message, $body_data, $base_part->getPart($html_id), true)
                        . ($this->_params['reply_top'] ? '' : $smart_text);
               }
            }

            // Do we need to add a PLAIN part?
            if (!empty($plain_id)) {
                if (!$id = $mime_message->findBody('plain')) {
                    $smart_text = self::html2text($mime_message->getPart($mime_message->findBody())->getContents());
                } else {
                    $smart_text = $mime_message->getPart($id)->getContents();
                }
                if ($forward) {
                    $newbody_text_plain = $smart_text . $this->_forwardText($imap_message, $body_data, $base_part->getPart($plain_id));
                } else {
                    $newbody_text_plain = ($this->_params['reply_top'] ? $smart_text : '')
                        . $this->_replyText($imap_message, $body_data, $base_part->getPart($plain_id))
                        . ($this->_params['reply_top'] ? '' : $smart_text);
                }
            }
            if ($forward) {
                foreach ($base_part->contentTypeMap() as $mid => $type) {
                    if ($imap_message->isAttachment($mid, $type)) {
                        $apart = $imap_message->getMimePart($mid);
                        $mail->addMimePart($apart);
                    }
                }
            }

            // Set the mail email body and add any uploaded attachments.
            if (!empty($newbody_text_html)) {
                $mail->setHtmlBody($newbody_text_html);
            }
            if (!empty($newbody_text_plain)) {
                $mail->setBody($newbody_text_plain);
            }

            foreach ($mime_message->contentTypeMap() as $mid => $type) {
                if ($mid != 0 && $mid != $mime_message->findBody('plain') && $mid != $mime_message->findBody('html')) {
                    $part = $mime_message->getPart($mid);
                    $mail->addMimePart($part);
                }
            }
            try {
                $mail->send($GLOBALS['injector']->getInstance('Horde_Mail'));
                if ($save) {
                    $copy = $mail->getBasePart();
                }
            } catch (Horde_Mail_Exception $e) {
                $this->_logger->err($e->getMessage());
                throw new Horde_ActiveSync_Exception($e);
            }
        }

        if ($save) {
            $sf = $this->getSpecialFolderNameByType(self::SPECIAL_SENT);
            if (!empty($sf)) {
                $this->_logger->info(sprintf(
                    "[%s] Preparing to copy to '%s'",
                    $this->_pid,
                    $sf));
                $flags = array(Horde_Imap_Client::FLAG_SEEN);
                if ($headers->getValue('Content-Transfer-Encoding')) {
                    $copy->setTransferEncoding($headers->getValue('Content-Transfer-Encoding'), array('send' => true));
                }
                $headers = $copy->addMimeHeaders(array('headers' => $headers));
                $msg = $copy->toString(array('headers' => $headers->toString(array('charset' => 'UTF-8')), 'stream' => true));

                // Ignore issues sending to sent, in case the folder isn't
                // available.
                try {
                    $this->_imap->appendMessage($sf, $msg, $flags);
                } catch (Horde_ActiveSync_Exception_FolderGone $e) {
                    $this->_logger->err($e->getMessage());
                }
            }
        }

        // Attempt to write forward/reply state.
        if ($this->_version > Horde_ActiveSync::VERSION_TWELVEONE) {
            if (empty($forward) && empty($reply) && !empty($message)) {
                if ($parentid = $raw_message->getHeaders()->getValue('In-Reply-To')) {
                    $this->_logger->info(sprintf(
                        'Logging LASTVERBEXECUTED to Maillog: reply, %s, %s',
                        $parentid,
                        $headers->getValue('To')));
                    $this->_connector->mail_logMaillog(
                        'reply',
                        $parentid,
                        $headers->getValue('To'));

                    $this->_logger->info('Attempting to set reply flag by searching for parent message.');
                    try {
                        $parent = $this->_imap->getUidFromMidInFolders($parentid, $this->_getMailFolders());
                        $this->_imap->setImapFlag($parent[0], $parent[1], Horde_ActiveSync::IMAP_FLAG_REPLY);
                    } catch (Horde_Exception_NotFound $e) {
                        $this->_logger->info($e->getMessage());
                    }
                }
            } elseif (!empty($forward) || !empty($reply)) {
                $this->_logger->info(sprintf(
                    'Logging LASTVERBEXECUTED to Maillog: %s, %s, %s',
                    !empty($reply) ? 'reply' : 'forward',
                    $imap_message->getHeaders()->getValue('Message-ID'),
                    $headers->getValue('To')));
                $this->_connector->mail_logMaillog(
                    !empty($reply) ? 'reply' : 'forward',
                    $imap_message->getHeaders()->getValue('Message-ID'),
                    $headers->getValue('To'));
                $this->_imap->setImapFlag(
                    $parent,
                    !empty($reply) ? $reply : $forward,
                    !empty($reply) ? Horde_ActiveSync::IMAP_FLAG_REPLY : Horde_ActiveSync::IMAP_FLAG_FORWARD);
            }
        }

        return true;
    }

    /**
     * Return the body of the forwarded message in the appropriate type.
     *
     * @param Horde_ActiveSync_Imap_Message $message  The imap message object.
     * @param array $body_data         The body data array.
     * @param Horde_Mime_Part $partId  The body part (minus contents).
     * @param boolean $html            Is this an html part?
     *
     * @return string  The propertly formatted forwarded body text.
     */
    protected function _forwardText(Horde_ActiveSync_Imap_Message $message, array $body_data, Horde_Mime_Part $part, $html = false)
    {
        $fwd_headers = $message->getForwardHeaders();
        $from = $message->getFromAddress();

        $msg = $this->_msgBody($body_data, $part, $html);
        $msg_pre = "\n----- "
            . ($from ? sprintf(Horde_Core_Translation::t("Forwarded message from %s"), $from) : Horde_Core_Translation::t("Forwarded message"))
            . " -----\n" . $fwd_headers . "\n";
        $msg_post = "\n\n----- " . Horde_Core_Translation::t("End forwarded message") . " -----\n";

        return ($html ? self::text2html($msg_pre) : $msg_pre)
            . $msg
            . ($html ? self::text2html($msg_post) : $msg_post);
    }

    /**
     * Return the body of the replied message in the appropriate type.
     *
     * @param Horde_ActiveSync_Imap_Message $message  The imap message object.
     * @param array $body_data         The body data array.
     * @param Horde_Mime_Part $partId  The body part (minus contents).
     * @param boolean $html            Is this an html part?
     *
     * @return string  The propertly formatted replied body text.
     */
    protected function _replyText(Horde_ActiveSync_Imap_Message $message, array $body_data, Horde_Mime_Part $part, $html = false)
    {
        $headers = $message->getHeaders();
        $from = strval($headers->getOb('from'));
        $msg_pre = ($from ? sprintf(Horde_Core_Translation::t("Quoting %s"), $from) : Horde_Core_Translation::t("Quoted")) . "\n\n";
        $msg = $this->_msgBody($body_data, $part, $html, true);
        if (!empty($msg) && $html) {
            $msg = '<p>' . $this->text2html($msg_pre) . '</p>'
                . self::HTML_BLOCKQUOTE . $msg . '</blockquote><br /><br />';
        } else {
            $msg = empty($msg)
                ? '[' . Horde_Core_Translation::t("No message body text") . ']'
                : $msg_pre . $msg;
        }

        return $msg;
    }

    /**
     * Return the body text of the original email from a smart request.
     *
     * @param array $body_data       The message's main mime part.
     * @param Horde_Mime_Part $part  The body mime part (minus contents).
     * @param boolean $html          Do we want an html body?
     * @param boolean $flow          Should the body be flowed?
     *
     * @return string  The properly formatted/flowed message body.
     */
    protected function _msgBody(array $body_data, Horde_Mime_Part $part, $html, $flow = false)
    {
        $subtype = $html == true ? 'html' : 'plain';
        $msg = Horde_String::convertCharset(
            $body_data[$subtype]['body'],
            $body_data[$subtype]['charset'],
            'UTF-8');
        trim($msg);
        if (!$html) {
            if ($part->getContentTypeParameter('format') == 'flowed') {
                $flowed = new Horde_Text_Flowed($msg, 'UTF-8');
                if (Horde_String::lower($part->getContentTypeParameter('delsp')) == 'yes') {
                    $flowed->setDelSp(true);
                }
                $flowed->setMaxLength(0);
                $msg = $flowed->toFixed(false);
            } else {
                // If not flowed, remove padding at eol
                $msg = preg_replace("/\s*\n/U", "\n", $msg);
            }
            if ($flow) {
                $flowed = new Horde_Text_Flowed($msg, 'UTF-8');
                $msg = $flowed->toFlowed(true);
            }
        }

        return $msg;
    }

    /**
     * Shortcut function to convert text -> HTML.
     *
     * @param string $msg  The message text.
     *
     * @return string  HTML text.
     */
    static public function text2html($msg)
    {
        return $GLOBALS['injector']
            ->getInstance('Horde_Core_Factory_TextFilter')
            ->filter($msg, 'Text2html', array(
                'flowed' => self::HTML_BLOCKQUOTE,
                'parselevel' => Horde_Text_Filter_Text2html::MICRO)
        );
    }

    static public function html2text($msg)
    {
        return $GLOBALS['injector']->getInstance('Horde_Core_Factory_TextFilter')
            ->filter($msg, 'Html2text');
    }

    /**
     * Set the read (\seen) flag on the specified message.
     *
     * @param string $folderId  The folder id containing the message.
     * @param string $id        The message uid.
     * @param integer $flags    The value to set the flag to.
     *
     * @deprecated Will be removed in Horde 6. Here for BC with
     *             Horde_ActiveSync 2.4
     */
    public function setReadFlag($folderId, $id, $flags)
    {
        $this->_imap->setReadFlag($folderId, $id, $flags);
    }

    /**
     * Return the server id of the specified special folder type.
     *
     * @param string $type  The self::SPECIAL_* constant.
     *
     * @return string  The folder's server id.
     */
    public function getSpecialFolderNameByType($type)
    {
        $folders = $this->_imap->getSpecialMailboxes();
        $folder = $folders[$type];
        if (!is_null($folder)) {
            return $folder->value;
        }
    }

    /**
     * Build a stat structure for an email message.
     *
     * @param string $folderid   The mailbox name.
     * @param integer|array $id  The message(s) to stat (IMAP UIDs).
     *
     * @return array
     */
    public function statMailMessage($folderid, $id)
    {
        // I can't think of a time when we would actually need to hit the
        // server. As long as 'mod' is 0, this should be fine as we don't
        // track flag conflicts.
        return array(
            'id' => $id,
            'mod' => 0,
            'flags' => false);
    }

    /**
     * Return the security policies.
     *
     * @param boolean|array $device  The device information sent by EAS 14.1
     *                               set to false otherwise.
     * @return array  An array of provisionable properties and values.
     */
    public function getCurrentPolicy($device = false)
    {
        return $this->_getPolicyFromPerms();
    }

    /**
     * Returns the provisioning support for the current request.
     *
     * @return mixed  The value of the provisiong support flag.
     */
    public function getProvisioning()
    {
        $provisioning = $GLOBALS['injector']
            ->getInstance('Horde_Perms')
            ->getPermissions(
                'horde:activesync:provisioning',
                $this->_user);
        return $this->_getPolicyValue('provisioning', $provisioning);
    }

    /**
     * Return settings from the backend for a SETTINGS request.
     *
     * @param array $settings  An array of settings to return. Currently
     *                         supported:
     *  - oof: The out of office message information.
     *
     * @param stdClass $device  The device to obtain settings for.
     *
     * @return array  The requested settings.
     */
    public function getSettings(array $settings, $device)
    {
        $res = array();
        foreach ($settings as $key => $setting) {
            switch ($key) {
            case 'oof':
                $vacation = $this->_connector->filters_getVacation();
                $res['oof'] = array(
                    'status' => Horde_ActiveSync_Request_Settings::STATUS_SUCCESS,
                    'oofstate' => ($vacation['disabled']
                        ? Horde_ActiveSync_Request_Settings::OOF_STATE_DISABLED
                        : Horde_ActiveSync_Request_Settings::OOF_STATE_ENABLED),
                    'oofmsgs' => array()
                );
                $res['oof']['oofmsgs'][] = array(
                    'appliesto' => Horde_ActiveSync_Request_Settings::SETTINGS_APPLIESTOINTERNAL,
                    'replymessage' => $vacation['reason'],
                    'enabled' => !$vacation['disabled'],
                    'bodytype' => $setting['bodytype'],
                    'subject' => $vacation['subject']
                );
                break;
            case 'userinformation':
                $ident = $GLOBALS['injector']
                    ->getInstance('Horde_Core_Factory_Identity')
                    ->create($GLOBALS['registry']->getAuth());
                $res['userinformation'] = array(
                    'emailaddresses' => array_keys(array_flip($ident->getAll('from_addr'))),
                    'status' => Horde_ActiveSync_Request_Settings::STATUS_SUCCESS
                );
            }
        }

        return $res;
    }

    /**
     * Set backend settings from a SETTINGS request.
     *
     * @param array $settings   The settings to store. Currently supported:
     *  - oof: (array) The Out of Office message.
     *
     * @param stdClass $device  The device to store settings for.
     *
     * @return array  An array of status responses for each set request. e.g.,:
     *   array('oof' => Horde_ActiveSync_Request_Settings::STATUS_SUCCESS);
     */
    public function setSettings(array $settings, $device)
    {
        $res = array();
        foreach ($settings as $key => $setting) {
            switch ($key) {
            case 'oof':
                try {
                    $this->_connector->filters_setVacation($setting);
                    $res['oof'] = Horde_ActiveSync_Request_Settings::STATUS_SUCCESS;
                } catch (Horde_Exception $e) {
                    $res['oof'] = Horde_ActiveSync_Request_Settings::STATUS_ERROR;
                }
                break;
            }
        }

        return $res;
    }

    /**
     * Attempt to autodiscover. Autodiscovery happens before the user is
     * authenticated, and ALWAYS uses the user's email address. We have to
     * do our best to translate email address to username. If this fails, the
     * device simply falls back to requiring full user configuration.
     *
     * @param array $params  Optional array of parameters.
     *
     * @return array  Either an array of autodiscover parameters that the
     *                ActiveSync server will use to build the response, or
     *                the raw XML response contained in the raw_xml key.
     */
    public function autoDiscover($params = array())
    {
        // Attempt to get a username from the email address.
        $ident = $GLOBALS['injector']
            ->getInstance('Horde_Core_Factory_Identity')
            ->create($GLOBALS['registry']->getAuth());
        $params['display_name'] = $ident->getValue('fullname');
        $params['email'] = $ident->getValue('from_addr');
        $url = parse_url((string)Horde::url(null, true));
        $params['url'] = $url['scheme'] . '://' . $url['host'] . '/Microsoft-Server-ActiveSync';
        // As of Exchange 2007, this always returns en:en
        $params['culture'] = 'en:en';
        $params['username'] = $this->getUsernameFromEmail($params['email']);
        try {
            $xml = Horde::callHook('activesync_autodiscover_xml', array($params), 'horde');
            return array('raw_xml' => $xml);
        } catch (Horde_Exception_HookNotSet $e) {}

        // Bring in the host configuration if needed.
        if (!empty($GLOBALS['conf']['activesync']['outlookdiscovery'])) {
            $params = array_merge($params, $GLOBALS['conf']['activesync']['hosts']);
        }

        try {
            $params = Horde::callHook('activesync_autodisover_parameters', array($params), 'horde');
        } catch (Horde_Exception_HookNotSet $e) {}

        return $params;
    }

    /**
     * Attempt to guess a username based on the email address passed from
     * EAS Autodiscover requests.
     *
     * @param string $email  The email address
     *
     * @return string  The username to use to authenticate to Horde with.
     */
    public function getUsernameFromEmail($email)
    {
        switch ($GLOBALS['conf']['activesync']['autodiscovery']) {
        case 'full':
            return $email;
        case 'user':
            if (strpos($email, '@') !== false) {
                return substr($email, 0, strpos($email, '@'));
            } else {
                return $email;
            }
        case 'hook':
            try {
                return Horde::callHook('activesync_get_autodiscover_username', array($email));
            } catch (Horde_Exception_HookNotSet $e) {
                return $email;
            }
        default:
            return $email;
        }
    }

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
    public function resolveRecipient($type, $search, array $opts = array())
    {
        $return = array();

        if ($type == 'certificate') {
            $results = $this->_connector->resolveRecipient($search, $opts);

            if (count($results) && isset($results[$search])) {
                $gal = $this->_connector->contacts_getGal();
                $picture_count = 0;
                foreach ($results[$search] as $result) {
                    if (!empty($opts['pictures'])) {
                        $picture = new Horde_ActiveSync_Message_ResolveRecipientsPicture(
                            array('protocolversion' => $this->_version, 'logger' => $this->_logger));
                        if (empty($result['photo'])) {
                            $picture->status = Horde_ActiveSync_Status::NO_PICTURE;
                        } elseif (!empty($opts['maxpictures']) &&
                                  $picture_count > $opts['maxpictures']) {
                            $picture->status = Horde_ActiveSync_Status::PICTURE_LIMIT_REACHED;
                        } elseif (!empty($opts['maxsize']) &&
                                  strlen($result['photo']) > $query['maxsize']) {
                            $picture->status = Horde_ActiveSync_Status::PICTURE_TOO_LARGE;
                        } else {
                            $picture->data = $result['photo'];
                            $picture->status = Horde_ActiveSync_Status::PICTURE_SUCCESS;
                            ++$picture_count;
                        }
                        $entry[Horde_ActiveSync::GAL_PICTURE] = $picture;
                    }
                    $result = array(
                        'displayname' => $result['name'],
                        'emailaddress' => $result['email'],
                        'entries' => array($this->_mungeCert($result['smimePublicKey'])),
                        'type' => $result['source'] == $gal ? Horde_ActiveSync::RESOLVE_RESULT_GAL : Horde_ActiveSync::RESOLVE_RESULT_ADDRESSBOOK,
                        'picture' => !empty($picture) ? $picture : null
                    );
                    $return[] = $result;
                }
            }

        } else {
            $options = array(
                'maxcerts' => 0,
                'maxambiguous' => $opts['maxambiguous'],
                'maxsize' => !empty($opts['maxsize']) ? $opts['maxsize'] : null,
                'maxpictures' => !empty($opts['maxpictures']) ? $opts['maxpictures'] : null,
                'pictures' => !empty($opts['pictures'])
            );
            $entry = current($this->resolveRecipient('certificate', $search, $options));
            $opts['starttime']->setTimezone(date_default_timezone_get());
            $opts['endtime']->setTimezone(date_default_timezone_get());
            if (!empty($entry)) {
                $fb = $this->_connector->resolveRecipient($search, $opts);
                $entry['availability'] = self::buildFbString($fb[$search], $opts['starttime'], $opts['endtime']);
                $return[] = $entry;
            }
        }

        return $return;
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

        // Convert all to UTC
        $start->setTimezone('UTC');
        $end->setTimezone('UTC');

        // Calculate total time span (end timestamp in non-inclusive).
        $end_ts = $end->timestamp() - 1;
        $start_ts = $start->timestamp();
        $sec = $end_ts - $start_ts;

        $fb_start = new Horde_Date($fb->s);
        $fb_end = new Horde_Date($fb->e);
        $fb_start->setTimezone('UTC');
        $fb_end->setTimezone('UTC');

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

        // Now put in the busy blocks. Need to convert to UTC here too since
        // all fb data is returned as local tz.
        while (list($b_start, $b_end) = each($fb->b)) {
            $b_start = new Horde_Date($b_start);
            $b_start->setTimezone('UTC');
            $b_end = new Horde_Date($b_end);
            $b_end->setTimezone('UTC');
            if ($b_start->timestamp() > $end->timestamp()) {
                continue;
            }

            $offset = $b_start->timestamp() - $start->timestamp();
            $duration = ceil(($b_end->timestamp() - $b_start->timestamp()) / 1800);
            if ($offset > 0) {
                $eas_fb = substr_replace($eas_fb, str_repeat('2', $duration), floor($offset / 1800), $duration);
            }
        }

        return $eas_fb;
    }

    /**
     * Handle meeting responses.
     *
     * @param array $response  The response data. Contains:
     *   - requestid: The identifier of the meeting request. Used by the server
     *                to fetch the original meeting request details.
     *   - response:  The user's response to the request. One of the response
     *                code constants.
     *   - folderid:  The collection id that contains the meeting request.
     *   -
     *
     * @return string  The UID of any created calendar entries, otherwise false.
     * @throws Horde_ActiveSync_Exception, Horde_Exception_NotFound
     */
    public function meetingResponse(array $response)
    {
        if (empty($response['folderid']) || empty($response['requestid']) ||
            empty($response['response'])) {
            throw new Horde_ActiveSync_Exception('Invalid meeting response.');
        }

        // First thing we need is to obtain the meeting request.
        $imap_message = $this->_imap->getImapMessage($response['folderid'], $response['requestid']);
        if (empty($imap_message)) {
            throw new Horde_Exception_NotFound();
        }
        $imap_message = $imap_message[$response['requestid']];

        // Find the request
        if (!$part = $imap_message->hasiCalendar()) {
            $this->_logger->err('Unable to find the meeting request.');
            throw new Horde_Exception_NotFound();
        }

        // Parse the vCal
        $vCal = new Horde_Icalendar();
        $data = $part->getContents();
        if (!$vCal->parsevCalendar($data, 'VCALENDAR', $part->getCharset())) {
            throw new Horde_ActiveSync_Exception('Unknown error parsing vCal data.');
        }
        if (!$vEvent = $vCal->findComponent('vEvent')) {
            throw new Horde_ActiveSync_Exception('Unknown error locating vEvent.');
        }

        // Create an event from the vEvent.
        // Note we don't use self::changeMessage since we don't want to treat
        // this as an incoming message addition from the PIM. Otherwise, the
        // message may not get synched back to the PIM.
        try {
            $uid = $this->_connector->calendar_import_vevent($vEvent);
        } catch (Horde_Exception $e) {
            $this->_logger->err($e->getMessage());
            throw new Horde_ActiveSync_Exception($e);
        }

        // Start building the iTip response email.
        try {
            $organizer = parse_url($vEvent->getAttribute('ORGANIZER'));
            $organizer = $organizer['path'];
        } catch (Horde_Icalendar_Exception $e) {
            $this->_logger->err('Unable to find organizer.');
            throw new Horde_ActiveSync_Exception($e);
        }
        $ident = $GLOBALS['injector']
            ->getInstance('Horde_Core_Factory_Identity')
            ->create($this->_user);
        $cn= $ident->getValue('fullname');
        $email = $ident->getValue('from_addr');

        // Can't use Horde_Itip_Resource_Identity since it takes an IMP identity
        $resource = new Horde_Itip_Resource_Base($email, $cn);

        switch ($response['response']) {
        case Horde_ActiveSync_Request_MeetingResponse::RESPONSE_ACCEPTED:
            $type = new Horde_Itip_Response_Type_Accept($resource);
            break;
        case Horde_ActiveSync_Request_MeetingResponse::RESPONSE_DECLINED:
            $type = new Horde_Itip_Response_Type_Decline($resource);
            break;
        case Horde_ActiveSync_Request_MeetingResponse::RESPONSE_TENTATIVE:
            $type = new Horde_Itip_Response_Type_Tentative($resource);
            break;
        }

        // Note we don't use the Itip factory because we need to access the
        // Horde_Itip_Response directly in order to save the response email to
        // the sent items folder.
        $itip_response = new Horde_Itip_Response(
            new Horde_Itip_Event_Vevent($vEvent),
            $resource
        );
        $itip_handler = new Horde_Itip($itip_response);
        $options = new Horde_Itip_Response_Options_Horde(
            'UTF-8',
            array(
                'dns' => $GLOBALS['injector']->getInstance('Net_DNS2_Resolver'),
                'server' => $GLOBALS['conf']['server']['name']
            )
        );
        try {
            // Send the response email
            $itip_handler->sendMultiPartResponse(
                $type, $options, $GLOBALS['injector']->getInstance('Horde_Mail'));
            $this->_logger->info(sprintf(
                "[%s] Successfully sent iTip response.",
                $this->_pid));
        } catch (Horde_Itip_Exception $e) {
            $this->_logger->err('Error sending response: ' . $e->getMessage());
            throw new Horde_ActiveSync_Exception($e);
        }

        // Save to SENT
        $sf = $this->getSpecialFolderNameByType(self::SPECIAL_SENT);
        if (!empty($sf)) {
            $this->_logger->info(sprintf(
                "[%s] Preparing to copy to '%s'",
                $this->_pid,
                $sf));
            list($headers, $body) = $itip_response->getMultiPartMessage(
                $type, $options);
            $flags = array(Horde_Imap_Client::FLAG_SEEN);
            $msg = $body->toString(array('headers' => $headers));
            try {
                $this->_imap->appendMessage($sf, $msg, $flags);
            } catch (Horde_ActiveSync_Exception_FolderGone $e) {
                $this->_logger->err('No Sent Folder. Could not copy.');
            }
        }

        // Delete the original request. EAS Specs require this. Most clients
        // will remove the email from the UI as soon as the response is sent.
        // Failure to remove it from the server will result in an inconsistent
        // sync state.
        $this->_imap->deleteMessages(array($response['requestid']), $response['folderid']);

        return $uid;
    }

    /**
     * Callback method called before new device is created for a user. Allows
     * final check of permissions.
     *
     * @return boolean|integer  True on success (device allowed to be created)
     *                          or error code on failure.
     */
    public function createDeviceCallback(Horde_ActiveSync_Device $device)
    {
        global $registry;

        $perms = $GLOBALS['injector']->getInstance('Horde_Perms');
        // Check max_device
        if ($perms->exists('horde:activesync:max_devices')) {
            $max_devices = $this->_getPolicyValue('max_devices', $perms->getPermissions('horde:activesync:max_devices', $registry->getAuth()));
            $state = $GLOBALS['injector']->getInstance('Horde_ActiveSyncState');
            $devices = $state->listDevices($registry->getAuth(), false);
            if (count($devices) >= $max_devices) {
                $this->_logger->info(sprintf(
                    'Maximum number of devices of %d reached for %s.',
                    $max_devices,
                    $registry->getAuth()));
                return Horde_ActiveSync_Status::MAXIMUM_DEVICES_REACHED;
            }
        }
        try {
            return Horde::callHook('activesync_create_device', array($device));
        } catch (Horde_Exception_HookNotSet $e) {}

        return true;
    }

    /**
     * Callback that allows custom policy checking on the device before allowing
     * it to connect. Useful for things like limiting the types of devices
     * that can connect e.g., not allowing iOS devices etc..
     *
     * @param Horde_ActiveSync_Device $device  The device object.
     *
     * @return boolean|integer  True on success (device allowed to be created)
     *                          or error code on failure.
     */
    public function deviceCallback(Horde_ActiveSync_Device $device)
    {
        try {
            return Horde::callHook('activesync_device_check', array($device));
        } catch (Horde_Exception_HookNotSet $e) {}

        return true;
    }

    /**
     * Request freebusy information from the server
     *
     * @deprecated Will be removed in H6 - this is provided via
     *             self::resolveRecipients().
     */
    public function getFreebusy($user, array $options = array())
    {
        throw new Horde_ActiveSync_Exception('Not supported');
    }

    /**
     * Return the SyncStamp - the value used to determine the end of the current
     * sync range. If the collection backend supports modification sequences,
     * we will use that, otherwise return the current timestamp.
     *
     * @param string $collection  The collection id we are currently requesting.
     * @param integer $last       The last syncstamp, if known. Used to help
     *                            sanity check the state.
     *
     * @return integer|boolean  The SyncStamp or false if an error is encountered.
     * @since 2.6.0
     */
    public function getSyncStamp($collection, $last = null)
    {
        // For FolderSync (empty $collection) or Email collections, we don't care.
        if (empty($collection) ||
            !in_array($collection, array(self::APPOINTMENTS_FOLDER_UID, self::NOTES_FOLDER_UID, self::CONTACTS_FOLDER_UID, self::TASKS_FOLDER_UID))) {
            return time();
        }

        if ($this->_connector->hasFeature('modseq', $collection)) {
            $modseq = $this->_connector->getHighestModSeq($collection);
            // Sanity check - if the last syncstamp is higher then the
            // current modification sequence, something is wrong. Could be
            // the history backend just happend to have deleted the most recent
            // entry or (more likely) we are transitioning from using
            // timestamps to using sequences. In this case the difference would
            // be VERY large, so try to detect that.
            if (!empty($last) && $last > $modseq && (($last - $modseq) > 1000000000)) {
                return false;
            }

            return intval($modseq);
        }

        return time();
    }

    /**
     * Helper to build a folder object for non-email folders.
     *
     * @param string $id      The folder's server id.
     * @param stirng $parent  The folder's parent id.
     * @param integer $type   The folder type.
     * @param string $name    The folder description.
     *
     * @return  Horde_ActiveSync_Message_Folder  The folder object.
     */
    protected function _buildNonMailFolder($id, $parent, $type, $name)
    {
        $folder = new Horde_ActiveSync_Message_Folder();
        $folder->serverid = $id;
        $folder->parentid = $parent;
        $folder->type = $type;
        $folder->displayname = $name;

        return $folder;
    }

    /**
     * Stat a backend item, optionally using the cached value if available.
     *
     * @param string  $folderid  The folder id
     * @param string  $id        The message id
     * @param boolean $hint      Use the cached data, if available?
     *
     * @return message stat hash
     */
    protected function _smartStatMessage($folderid, $id, $hint)
    {
        ob_start();
        $this->_logger->info(sprintf(
            "[%s] Horde_Core_ActiveSync_Driver::_smartStatMessage(%s, %s)",
            $this->_pid,
            $folderid,
            $id));
        $statKey = $folderid . $id;
        $mod = false;

        if ($hint && isset($this->_modCache[$statKey])) {
            $mod = $this->_modCache[$statKey];
        } else {
            try {
                // @TODO Horde 6 - combine into single getActionTimestamp method
                // and pass the folderid.
                switch ($folderid) {
                case self::APPOINTMENTS_FOLDER_UID:
                    $mod = $this->_connector->calendar_getActionTimestamp($id, 'modify');
                    break;

                case self::CONTACTS_FOLDER_UID:
                    $mod = $this->_connector->contacts_getActionTimestamp($id, 'modify');
                    break;

                case self::TASKS_FOLDER_UID:
                    $mod = $this->_connector->tasks_getActionTimestamp($id, 'modify');
                    break;

                case self::NOTES_FOLDER_UID:
                    $mod = $this->_connector->notes_getActionTimestamp($id, 'modify');
                    break;
                default:
                    try {
                        return $this->statMailMessage($folderid, $id);
                    } catch (Horde_ActiveSync_Exception $e) {
                        $this->_endBuffer();
                        return false;
                    }

                }
            } catch (Horde_Exception $e) {
                $this->_logger->err($e->getMessage());
                $this->_endBuffer();
                return array('id' => '', 'mod' => 0, 'flags' => 1);
            }
            $this->_modCache[$statKey] = $mod;
        }

        $message = array();
        $message['id'] = $id;
        $message['mod'] = $mod;
        $message['flags'] = 1;

        $this->_endBuffer();
        return $message;
    }

   /**
     * Return the list of mail server folders.
     *
     * @return array  An array of Horde_ActiveSync_Message_Folder objects.
     */
    protected function _getMailFolders()
    {
        if (empty($this->_mailFolders)) {
            if (empty($this->_imap)) {
                $this->_mailFolders = array($this->_buildDummyFolder(self::SPECIAL_INBOX));
                $this->_mailFolders[] = $this->_buildDummyFolder(self::SPECIAL_TRASH);
                $this->_mailFolders[] = $this->_buildDummyFolder(self::SPECIAL_SENT);
            } else {
                $this->_logger->info(sprintf(
                    "[%s] Polling Horde_Core_ActiveSync_Driver::_getMailFolders()",
                    $this->_pid));
                $folders = array();
                try {
                    $imap_folders = $this->_imap->getMailboxes();
                } catch (Horde_ActiveSync_Exception $e) {
                    $this->_logger->err(sprintf(
                        "[%s] Problem loading mail folders from IMAP server: %s",
                        $this->_pid,
                        $e->getMessage())
                    );
                    throw $e;
                }

                // Build the folder tree, making sure the lower levels are
                // added first.
                $level = 0;
                $cnt = 0;
                while ($cnt < count($imap_folders)) {
                    foreach ($imap_folders as $id => $folder) {
                        if ($folder['level'] == $level) {
                            try {
                                $folders[] = $this->_getMailFolder($id, $imap_folders, $folder);
                                ++$cnt;
                            } catch (Horde_ActiveSync_Exception $e) {
                                $this->_logger->err(sprintf(
                                    "[%s] Problem retrieving %s mail folder",
                                    $this->_pid,
                                    $id)
                                );
                            }
                        }
                    }
                    ++$level;
                }

                $this->_mailFolders = $folders;
            }
        }

        return $this->_mailFolders;
    }

    protected function _buildDummyFolder($id)
    {
        $folder = new Horde_ActiveSync_Message_Folder();
        $folder->parentid = '0';
        switch ($id) {
        case self::SPECIAL_TRASH:
            $folder->type = Horde_ActiveSync::FOLDER_TYPE_WASTEBASKET;
            $folder->serverid = $folder->_serverid = 'Trash';
            $folder->displayname = Horde_Core_Translation::t("Trash");
            break;
        case self::SPECIAL_SENT:
            $folder->type = Horde_ActiveSync::FOLDER_TYPE_SENTMAIL;
            $folder->serverid = $folder->_serverid = 'Sent';
            $folder->displayname = Horde_Core_Translation::t("Sent");
            break;
        case self::SPECIAL_INBOX:
            $folder->type = Horde_ActiveSync::FOLDER_TYPE_INBOX;
            $folder->serverid = $folder->_serverid = 'INBOX';
            $folder->displayname = Horde_Core_Translation::t("Inbox");
            break;
        }

        return $folder;
    }

    /**
     * Return a folder object representing an email folder. Attempt to detect
     * special folders appropriately.
     *
     * @param string $sid   The server name.
     * @param array $fl     The complete folder list.
     * @param array $f      An array describing the folder.
     *
     * @return Horde_ActiveSync_Message_Folder
     */
    protected function _getMailFolder($sid, array $fl, array $f)
    {
        $folder = new Horde_ActiveSync_Message_Folder();
        $folder->_serverid = $sid;
        $folder->serverid = $this->_getFolderUidForBackendId($sid);
        $folder->parentid = '0';
        $folder->displayname = $f['label'];

        // Check for nested folders. $fl will NEVER contain containers so we
        // can assume that any entry in $fl is an actual mailbox. EAS does
        // not support containers so we only do this if the parent is an
        // actual mailbox.
        if ($f['level'] != 0) {
            $parts = explode($f['d'], $sid);
            $displayname = array_pop($parts);
            if (!empty($fl[implode($f['d'], $parts)])) {
                $folder->parentid = $this->_getFolderUidForBackendId(implode($f['d'], $parts));
                $folder->_parentid = implode($f['d'], $parts);
                $folder->displayname = $displayname;
            }
        }

        if (strcasecmp($sid, 'INBOX') === 0) {
            $folder->type = Horde_ActiveSync::FOLDER_TYPE_INBOX;
            return $folder;
        }

        try {
            $specialFolders = $this->_imap->getSpecialMailboxes();
        } catch (Horde_ActiveSync_Exception $e) {
            $this->_logger->err(sprintf(
                "[%s] Problem retrieving special folders: %s",
                $this->_pid,
                $e->getMessage()));
            throw $e;
        }

        // Check for known, supported special folders.
        foreach ($specialFolders as $key => $value) {
            if (!is_array($value)) {
                $value = array($value);
            }
            foreach ($value as $mailbox) {
                if (!is_null($mailbox)) {
                    switch ($key) {
                    case self::SPECIAL_SENT:
                        if ($sid == $mailbox->value) {
                            $folder->type = Horde_ActiveSync::FOLDER_TYPE_SENTMAIL;
                            return $folder;
                        }
                        break;
                    case self::SPECIAL_TRASH:
                        if ($sid == $mailbox->value) {
                            $folder->type = Horde_ActiveSync::FOLDER_TYPE_WASTEBASKET;
                            return $folder;
                        }
                        break;

                    case self::SPECIAL_DRAFTS:
                        if ($sid == $mailbox->value) {
                            $folder->type = Horde_ActiveSync::FOLDER_TYPE_DRAFTS;
                            return $folder;
                        }
                        break;
                    }
                }
            }
        }

        // Not a known folder, set it to user mail.
        $folder->type = Horde_ActiveSync::FOLDER_TYPE_USER_MAIL;

        return $folder;
    }

    /**
     * Perform a search of the email store.
     *
     * @param array $query  A query array. @see self::getSearchResults()
     *
     * @return array  The results array. @see self::getSearchResults()
     */
    protected function _searchMailbox(array $query)
    {
        return $this->_imap->queryMailbox($query);
    }

    /**
     * Perform a search of the Global Address Book.
     *
     * @param array $query  A query array. @see self::getSearchResults()
     *
     * @return array  The results array. @see self::getSearchResults()
     */
    protected function _searchGal(array $query)
    {
        ob_start();
        $return = array(
            'rows' => array(),
            'status' => Horde_ActiveSync_Request_Search::STORE_STATUS_SUCCESS
        );
        try {
            $results = $this->_connector->contacts_search(
                $query['query'],
                array('pictures' => !empty($query[Horde_ActiveSync_Request_Search::SEARCH_PICTURE])));
        } catch (Horde_ActiveSync_Exception $e) {
            $this->_logger->err($e);
            $this->_endBuffer();
            return $return;
        }

        // Honor range, and don't bother if no results
        $count = count($results);
        if (!$count) {
            $this->_endBuffer();
            return $return;
        }
        $this->_logger->info(sprintf(
            "[%s] Horde_Core_ActiveSync_Driver::_searchGal() found %d matches.",
            $this->_pid,
            $count));

        if (!empty($query['range'])) {
            preg_match('/(.*)\-(.*)/', $query['range'], $matches);
            $return_count = $matches[2] - $matches[1];
            $rows = array_slice($results, $matches[1], $return_count + 1, true);
            $rows = array_pop($rows);
        } else {
            $rows = array_pop($results);
        }

        $picture_count = 0;
        foreach ($rows as $row) {
            $entry = array(
                Horde_ActiveSync::GAL_ALIAS => !empty($row['alias']) ? $row['alias'] : '',
                Horde_ActiveSync::GAL_DISPLAYNAME => $row['name'],
                Horde_ActiveSync::GAL_EMAILADDRESS => !empty($row['email']) ? $row['email'] : '',
                Horde_ActiveSync::GAL_FIRSTNAME => $row['firstname'],
                Horde_ActiveSync::GAL_LASTNAME => $row['lastname'],
                Horde_ActiveSync::GAL_COMPANY => !empty($row['company']) ? $row['company'] : '',
                Horde_ActiveSync::GAL_HOMEPHONE => !empty($row['homePhone']) ? $row['homePhone'] : '',
                Horde_ActiveSync::GAL_PHONE => !empty($row['workPhone']) ? $row['workPhone'] : '',
                Horde_ActiveSync::GAL_MOBILEPHONE => !empty($row['cellPhone']) ? $row['cellPhone'] : '',
                Horde_ActiveSync::GAL_TITLE => !empty($row['title']) ? $row['title'] : '',
                Horde_ActiveSync::GAL_OFFICE => !empty($row['office']) ? $row['office'] : '',
            );
            if (!empty($query[Horde_ActiveSync_Request_Search::SEARCH_PICTURE])) {
                $picture = new Horde_ActiveSync_Message_GalPicture(
                    array('protocolversion' => $this->_version, 'logger' => $this->_logger));
                if (empty($row['photo'])) {
                    $picture->status = Horde_ActiveSync_Status::NO_PICTURE;
                } elseif (!empty($query[Horde_ActiveSync_Request_Search::SEARCH_MAXPICTURES]) &&
                          $picture_count > $query[Horde_ActiveSync_Request_Search::SEARCH_MAXPICTURES]) {
                    $picture->status = Horde_ActiveSync_Status::PICTURE_LIMIT_REACHED;
                } elseif (!empty($query[Horde_ActiveSync_Request_Search::SEARCH_MAXSIZE]) &&
                          strlen($row['photo']) > $query[Horde_ActiveSync_Request_Search::SEARCH_MAXSIZE]) {
                    $picture->status = Horde_ActiveSync_Status::PICTURE_TOO_LARGE;
                } else {
                    $picture->data = $row['photo'];
                    $picture->status = Horde_ActiveSync_Status::PICTURE_SUCCESS;
                    ++$picture_count;
                }
                $entry[Horde_ActiveSync::GAL_PICTURE] = $picture;
            }
            $return['rows'][] = $entry;
        }
        $this->_endBuffer();

        return $return;
    }

    /**
     * End output buffering, log any unexpected output.
     *
     */
    protected function _endBuffer()
    {
        if ($output = ob_get_clean()) {
            $this->_logger->err('Unexpected output: ' . $output);
        }
    }

    /**
     * Return if the specified mime part has attachments.
     *
     * @param Horde_Mime_Part $mime_part  The mime part.
     *
     * @return boolean
     */
    protected function _hasAttachments(Horde_Mime_Part $mime_part)
    {
        foreach ($mime_part->contentTypeMap() as $type) {
            if ($this->_isAttachment($type)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determines if a MIME type is an attachment.
     * For our purposes, an attachment is any MIME part that can be
     * downloaded by itself (i.e. all the data needed to view the part is
     * contained within the download data).
     *
     * @param string $mime_type  The MIME type.
     *
     * @return boolean  True if an attachment.
     */
    protected function _isAttachment($mime_type)
    {
        switch ($mime_type) {
        case 'text/plain':
        case 'application/ms-tnef':
        case 'text/html':
        case 'application/pkcs7-signature':
            return false;
        }

        list($ptype,) = explode('/', $mime_type, 2);

        switch ($ptype) {
        case 'message':
            return in_array($mime_type, array('message/rfc822', 'message/disposition-notification'));

        case 'multipart':
            return false;

        default:
            return true;
        }
    }

    /**
     * Removes the beginning/ending delimiters from the certificate.
     *
     * @param string $cert  The certificate text.
     *
     * @return string  The certificate text, with delimiters removed.
     */
    protected function _mungeCert($cert)
    {
        $cert = str_replace("-----BEGIN CERTIFICATE-----", '', $cert);
        $cert = str_replace("-----END CERTIFICATE-----", '', $cert);

        return $cert;
    }

    /**
     * Return a policy array suitable for transforming into either wbxml or xml
     * to send to the device in the provision response.
     *
     * @param boolean $deviceinfo  EAS 14.1 DEVICESETTINGS sent with PROVISION.
     *
     * @return array
     */
    protected function _getPolicyFromPerms($deviceinfo = false)
    {
        $prefix = 'horde:activesync:provisioning:';
        $policy = array();
        $perms = $GLOBALS['injector']->getInstance('Horde_Perms');
        if (!$perms->exists('horde:activesync:provisioning')) {
            return $policy;
        }

        $policies = new Horde_ActiveSync_Policies(null, $this->_version);
        $properties = $policies->getAvailablePolicies();
        foreach ($properties as $property) {
            if ($perms->exists($prefix . $property)) {
                $p = $perms->getPermissions($prefix . $property, $this->_user);
                $policy[$property] = $this->_getPolicyValue($property, $p);
            }
        }

        return $policy;
    }

    protected function _getPolicyValue($policy, $allowed)
    {
        if (is_array($allowed)) {
            switch ($policy) {
            case 'activesync':
            case 'max_devices':
            case Horde_ActiveSync_Policies::POLICY_ATC:
            case Horde_ActiveSync_Policies::POLICY_PIN:
            case Horde_ActiveSync_Policies::POLICY_COMPLEXITY:
            case Horde_ActiveSync_Policies::POLICY_MAXFAILEDATTEMPTS:
            case Horde_ActiveSync_Policies::POLICY_CODEFREQ:
            case Horde_ActiveSync_Policies::POLICY_AEFVALUE:
            case Horde_ActiveSync_Policies::POLICY_MAXATCSIZE:
            case Horde_ActiveSync_Policies::POLICY_ALLOW_SDCARD:
            case Horde_ActiveSync_Policies::POLICY_ALLOW_CAMERA:
            case Horde_ActiveSync_Policies::POLICY_ALLOW_SMS:
            case Horde_ActiveSync_Policies::POLICY_ALLOW_WIFI:
            case Horde_ActiveSync_Policies::POLICY_ALLOW_BLUETOOTH:
            case Horde_ActiveSync_Policies::POLICY_ALLOW_POPIMAP:
            case Horde_ActiveSync_Policies::POLICY_ALLOW_BROWSER:
            case Horde_ActiveSync_Policies::POLICY_ALLOW_HTML:
            case Horde_ActiveSync_Policies::POLICY_MAX_EMAIL_AGE:
            case Horde_ActiveSync_Policies::POLICY_DEVICE_ENCRYPTION:
            case Horde_ActiveSync_Policies::POLICY_ENCRYPTION:
                $allowed = max($allowed);
                break;
            case Horde_ActiveSync_Policies::POLICY_MINLENGTH:
            case Horde_ActiveSync_Policies::POLICY_ROAMING_NOPUSH:
                $allowed = min($allowed);
                break;
            case 'provisioning':
                if (array_search('false', $allowed) !== false) {
                    $allowed = Horde_ActiveSync::PROVISIONING_NONE;
                } elseif (array_search('allow', $allowed) !== false) {
                    $allowed = Horde_ActiveSync::PROVISIONING_LOOSE;
                } elseif (array_search('true', $allowed) !== false) {
                    $allowed = Horde_ActiveSync::PROVISIONING_FORCE;
                }
                break;
            }
        }

        return $allowed;
    }

    /**
     * Return the current user's From/Reply_To address.
     *
     * @return string
     */
    protected function _getIdentityFromAddress()
    {
        $ident = $GLOBALS['injector']
            ->getInstance('Horde_Core_Factory_Identity')
            ->create($this->_user);

        $name = $ident->getValue('fullname');
        $from_addr = $ident->getValue('from_addr');

        return $name . ' <' . $from_addr . '>';
    }

    /**
     * Get verb changes from the maillog.
     *
     * @param Horde_ActiveSync_Folder_Imap $folder  The folder to search.
     * @param integer $ts                           The timestamp to start from.
     *
     * @return Horde_ActiveSync_Folder_Imap  The folder object, with any changes
     *                                       added accordingly.
     */
    protected function _getMaillogChanges(Horde_ActiveSync_Folder_Imap $folder, $ts)
    {
        if ($ts == 0) {
            // For initial sync we don't need to poll for these changes since
            // when we send the new message, we poll the maillog for last verb
            // anyway.
            return $folder;
        }

        $changes = $this->_connector->mail_getMaillogChanges($ts);
        $flags = array();
        $s_changes = array();
        foreach ($changes as $mid) {
            try {
                $uid = $this->_imap->getUidFromMid($mid, $folder);
            } catch (Horde_Exception_NotFound $e) {
                continue;
            }
            $s_changes[] = $uid;
            $verb = $this->_getLastVerb($mid);
            if (!empty($verb)) {
                switch ($verb['action']) {
                case 'reply':
                case 'reply_list':
                    $flags[$uid] = array(Horde_ActiveSync::CHANGE_REPLY_STATE => $verb['ts']);
                    break;
                case 'reply_all':
                   $flags[$uid] = array(Horde_ActiveSync::CHANGE_REPLYALL_STATE => $verb['ts']);
                    break;
                case 'forward':
                    $flags[$uid] = array(Horde_ActiveSync::CHANGE_FORWARD_STATE => $verb['ts']);
                }
            }
        }
        if (!empty($s_changes)) {
            $folder->setChanges($s_changes, $flags);
        }

        return $folder;
    }

    /**
     * Return the last verb executed for the specified Message-ID.
     *
     * @param string $mid  The Message-ID.
     *
     * @return array  The most recent history log entry array for $mid.
     */
    protected function _getLastVerb($mid)
    {
        if (!array_key_exists($mid, $this->_verbs)) {
            $this->_logger->info('FETCHING VERB');
            $log = $this->_connector->mail_getMaillog($mid);
            $last = array();
            foreach ($log as $entry) {
                if (empty($last) || $last['ts'] < $entry['ts']) {
                    $last = $entry;
                }
            }
            $this->_verbs[$mid] = $last;
        }
        $this->_logger->info('RETURNING VERB');

        return $this->_verbs[$mid];
    }

}
