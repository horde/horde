<?php
/**
 * Horde backend. Provides the communication between horde data and
 * ActiveSync server.
 *
 * Copyright 2010-2012 Horde LLC (http://www.horde.org/)
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

    const SPECIAL_SENT   = 'sent';
    const SPECIAL_SPAM   = 'spam';
    const SPECIAL_TRASH  = 'trash';
    const SPECIAL_DRAFTS = 'drafts';


    /**
     * Mappings for server uids -> display names. Populated in the const'r
     * so we can use localized text.
     *
     * @var array
     */
    private $_displayMap = array();

    /**
     * Cache message stats
     *
     * @var array  An array of stat hashes
     */
    private $_modCache;

    /**
     * Horde connector instance
     *
     * @var Horde_Core_ActiveSync_Connector
     */
    private $_connector;

    /**
     * Folder cache
     *
     * @var array
     */
    private $_folders = array();

    /**
     * Imap client adapter
     *
     * @var Horde_ActiveSync_Imap_Adapter
     */
    private $_imap;

    /**
     * Authentication object
     *
     * @var Horde_Auth_Base
     */
    private $_auth;

    /**
     * Const'r
     *
     * @param array $params  Configuration parameters:
     *   - logger: (Horde_Log_Logger) The logger.
     *             DEFAULT: none (No logging).
     *
     *   - state: (Horde_ActiveSync_State_Base) The state driver.
     *            DEFAULT: none (REQUIRED).
     *   - connector: (Horde_ActiveSync_Driver_Horde_Connector_Registry) The
     *                connector object for communicating with the registry.
     *                DEFAULT: none, REQUIRED
     *   - auth: (Horde_Auth) The auth object.
     *           DEFAULT: none, REQUIRED.
     *   - imap: (Horde_ActiveSync_Imap_Adapter) The IMAP adapter if email
     *           support is desired.
     *           DEFAULT: none (No email support will be provided).
     *
     * @return Horde_ActiveSync_Driver_Horde
     */
    public function __construct(array $params = array())
    {
        parent::__construct($params);
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
            self::TASKS_FOLDER_UID        => Horde_ActiveSync_Translation::t('Tasks')
        );
    }

    public function setLogger($logger)
    {
        parent::setLogger($logger);
        $this->_connector->setLogger($logger);
        if (!empty($this->_imap)) {
            $this->_imap->setLogger($this->_logger);
        }
    }

    /**
     * Authenticate to Horde
     *
     * @see framework/ActiveSync/lib/Horde/ActiveSync/Driver/Horde_ActiveSync_Driver_Base#Logon($username, $domain, $password)
     */
    public function logon($username, $password, $domain = null)
    {
        $this->_logger->info('Horde_ActiveSync_Driver_Horde::logon attempt for: ' . $username);
        parent::logon($username, $password, $domain);

        return $this->_auth->authenticate($username, array('password' => $password));
    }

    /**
     * Clean up
     *
     * @see framework/ActiveSync/lib/Horde/ActiveSync/Driver/Horde_ActiveSync_Driver_Base#Logoff()
     */
    public function logOff()
    {
        $this->_connector->clearAuth();
        $this->_logger->info('User ' . $this->_user . ' logged off');
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
        $specialFolders = $this->_imap->getSpecialMailboxes();
        if (!empty($specialFolders[self::SPECIAL_TRASH])) {
            return $specialFolders[self::SPECIAL_TRASH];
        }
        return false;
    }

    /**
     * Return an array of stats for the server's folder list.
     *
     * @return array  An array of folder stats
     */
    public function getFolderList()
    {
        $this->_logger->debug('Horde::getFolderList()');
        $folderlist = $this->getFolders();
        $folders = array();
        foreach ($folderlist as $f) {
            $folders[] = $this->statFolder($f->serverid, $f->parentid, $f->displayname);
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
            $this->_logger->debug('Horde::getFolders()');
            try {
                $supported = $this->_connector->horde_listApis();
            } catch (Exception $e) {
                $this->_logger->err($e->getMessage());
                $this->_endBuffer();
                return array();
            }
            $folders = array();
            if (array_search('calendar', $supported)) {
                $folders[] = $this->getFolder(self::APPOINTMENTS_FOLDER_UID);
            }

            if (array_search('contacts', $supported)) {
                $folders[] = $this->getFolder(self::CONTACTS_FOLDER_UID);
            }

            if (array_search('tasks', $supported)) {
                $folders[] = $this->getFolder(self::TASKS_FOLDER_UID);
            }

            if (array_search('mail', $supported)) {
                $folders = array_merge($folders, $this->_getMailFolders());
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
        default:
            // Must be a mail folder
            $folders = $this->_getMailFolders();
            foreach ($folders as $folder) {
                if ($folder->serverid == $id) {
                    return $folder;
                }
            }
            $this->_logger->err('Folder ' . $id . ' unknown');
            throw new Horde_Exception('Folder ' . $id . ' unknown');
        }

        return $folder;
    }

    /**
     * Change a folder on the server.
     *
     * @param string $id           The server's folder id
     * @param string $displayname  The new display name.
     * @param string $parent       The folder's parent, if needed.
     */
    public function changeFolder($id, $displayname, $parent)
    {
        if (!$id) {
            try {
                $this->_imap->createMailbox($displayname);
            } catch (Horde_ActiveSync_Exception $e) {
                $this->_logger->err($e->getMessage());
                throw new Horde_Exception($e);
            }
        } else {
            $this->_logger->err('Renaming IMAP folders not supported.');
            throw Horde_Exception('Renaming not supported.');
        }

        return $displayname;
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
            throw new Horde_Exception($e);
        }
    }

    /**
     * Stat folder. Note that since the only thing that can ever change for a
     * folder is the name, we use that as the 'mod' value.
     *
     * @param string $id     The folder id
     * @param mixed $parent  The parent folder (or 0 if none). @since 2.0
     * @param mixed $mod     Modification indicator. For folders, this is the
     *                       name of the folder, since that's the only thing
     *                       that can change. @since 2.0
     * @return a stat hash
     */
    public function statFolder($id, $parent = 0, $mod = null)
    {
        $this->_logger->debug('Horde::statFolder(' . $id . ')');

        $folder = array();
        $folder['id'] = $id;
        $folder['mod'] = empty($mod) ? $id : $mod;
        $folder['parent'] = $parent;

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
     *
     * @return array A list of messge uids that have changed in the specified
     *               time period.
     */
    public function getServerChanges($folder, $from_ts, $to_ts, $cutoffdate, $ping)
    {
        $this->_logger->debug(sprintf(
            "Horde_ActiveSync_Driver_Horde::getServerChanges(%s, %u, %u, %u, %d)",
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
            if ($from_ts == 0) {
                // Can't use History if it's a first sync
                $startstamp = (int)$cutoffdate;
                $endstamp = time() + 32140800; //60 * 60 * 24 * 31 * 12 == one year
                try {
                    $changes['add'] = $this->_connector->calendar_listUids($startstamp, $endstamp);
                } catch (Horde_Exception $e) {
                    $this->_logger->err($e->getMessage());
                    $this->_endBuffer();
                    return array();
                }
            } else {
                try {
                    $changes = $this->_connector->getChanges('calendar', $from_ts, $to_ts);
                } catch (Horde_Exception $e) {
                    $this->_logger->err($e->getMessage());
                    $this->_endBuffer();
                    return array();
                }
            }
            break;

        case Horde_ActiveSync::CLASS_CONTACTS:
            // Can't use History for first sync
            if ($from_ts == 0) {
                try {
                    $changes['add'] = $this->_connector->contacts_listUids();
                } catch (Horde_Exception $e) {
                    $this->_logger->err($e->getMessage());
                    $this->_endBuffer();
                    return array();
                }
                $edits = $deletes = array();
            } else {
                try {
                    $changes = $this->_connector->getChanges('contacts', $from_ts, $to_ts);
                } catch (Horde_Exception $e) {
                    $this->_logger->err($e->getMessage());
                    $this->_endBuffer();
                    return array();
                }
            }
            break;

        case Horde_ActiveSync::CLASS_TASKS:
            // Can't use History for first sync
            if ($from_ts == 0) {
                try {
                    $changes['add'] = $this->_connector->tasks_listUids();
                } catch (Horde_Exception $e) {
                    $this->_logger->err($e->getMessage());
                    $this->_endBuffer();
                    return array();
                }
            } else {
                try {
                    $changes = $this->_connector->getChanges('tasks', $from_ts, $to_ts);
                } catch (Horde_Exception $e) {
                    $this->_logger->err($e->getMessage());
                    $this->_endBuffer();
                    return array();
                }
            }
            break;
        case Horde_ActiveSync::CLASS_EMAIL:
            if (empty($this->_imap)) {
                return array();
            }
            if ($ping) {
                try {
                    $ping_res = $this->_imap->ping(
                        $folder,
                        array('sincedate' => (int)$cutoffdate));
                    if ($ping_res) {
                        $changes['add'] = array(1);
                    }
                } catch (Horde_ActiveSync_Exeption_StaleState $e) {
                    $this->_endBuffer();
                    throw $e;
                } catch (Horde_ActiveSync_Exception_FolderGone $e) {
                    $this->_endBuffer();
                    throw $e;
                } catch (Horde_Exception $e) {
                    $this->_logger->err($e->getMessage());
                    $this->_endBuffer();
                    return array();
                }
            } else {
                try {
                    $folder = &$this->_imap->getMessageChanges(
                        $folder,
                        array('sincedate' => (int)$cutoffdate));
                } catch (Horde_ActiveSync_Exception_StaleState $e) {
                    $this->_endBuffer();
                    throw $e;
                } catch (Horde_ActiveSync_Exception_FolderGone $e) {
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

        // Server changes
        // For CLASS_EMAIL, all changes are a change in read status. Might have
        // to revist this after 12.0 is implemented?
        if ($folder->collectionClass() == Horde_ActiveSync::CLASS_EMAIL) {
            $flags = $folder->flags();
            foreach ($changes['modify'] as $uid) {
                $results[] = array(
                    'id' => $uid,
                    'type' => Horde_ActiveSync::CHANGE_TYPE_FLAGS,
                    'flags' => $flags[$uid]['read']
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
     *   - mimesupport: (boolean) Indicates if the device has MIME support.
     *                  DEFAULT: false (No MIME support)
     *   - truncation: (integer)  The truncation constant, if sent by the device.
     *                 DEFAULT: 0 (No truncation)
     *   - bodyprefs: (array)  The bodypref array from the device.
     *
     * @return Horde_ActiveSync_Message_Base The message data
     * @throws Horde_ActiveSync_Exception
     */
    public function getMessage($folderid, $id, array $collection)
    {
        $this->_logger->debug('Horde::getMessage(' . $folderid . ', ' . $id . ')');
        ob_start();
        $message = false;
        $folder = $this->getFolder($folderid);
        switch ($folder->type) {
        case Horde_ActiveSync::FOLDER_TYPE_APPOINTMENT:
            try {
                $message = $this->_connector->calendar_export($id, $this->_version);
                // Nokia MfE requires the optional UID element.
                if (!$message->getUid()) {
                    $message->setUid($id);
                }
            } catch (Horde_Exception $e) {
                $this->_logger->err($e->getMessage());
                $this->_endBuffer();
                throw new Horde_ActiveSync_Exception('Not Found');
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
                throw new Horde_ActiveSync_Exception('Not Found');
            }
            break;

        case Horde_ActiveSync::FOLDER_TYPE_TASK:
            try {
                $message = $this->_connector->tasks_export($id, $this->_version);
            } catch (Horde_Exception $e) {
                $this->_logger->err($e->getMessage());
                $this->_endBuffer();
                throw new Horde_ActiveSync_Exception('Not Found');
            }
            break;

        case Horde_ActiveSync::FOLDER_TYPE_INBOX:
        case Horde_ActiveSync::FOLDER_TYPE_SENTMAIL:
        case Horde_ActiveSync::FOLDER_TYPE_WASTEBASKET:
        case Horde_ActiveSync::FOLDER_TYPE_DRAFTS:
        case Horde_ActiveSync::FOLDER_TYPE_USER_MAIL:
            try {
                $messages = $this->_imap->getMessages(
                    $folderid,
                    array($id),
                    array(
                        'protocolversion' => $this->_version,
                        'truncation' => $collection['truncation'],
                        'bodyprefs'  => $this->addDefaultBodyPrefTruncation($collection['bodyprefs']),
                        'mimesupport' => $collection['mimesupport']
                    )
                );
            } catch (Horde_Exception $e) {
                $this->_logger->err($e->getMessage());
                $this->_endBuffer();
                throw new Horde_ActiveSync_Exception('Not Found');
            }
            $this->_endBuffer();
            return current($messages);
            break;

        default:
            $this->_endBuffer();
            throw new Horde_ActiveSync_Exception('Unsupported type');
        }
        if (strlen($message->body) > $truncsize) {
            $message->body = Horde_String::substr($message->body, 0, $truncsize);
            $message->bodytruncated = 1;
        } else {
            // Be certain this is set.
            $message->bodytruncated = 0;
        }

        $this->_endBuffer();
        return $message;
    }

    /**
     * Return the specified attachment.
     *
     * @param string $name  The attachment identifier. For this driver, this
     *                      consists of 'mailbox:uid:mimepart'
     *
     * @return array  The attachment in the form of an array with the following
     *                structure:
     * array('content-type' => {the content-type of the attachement},
     *       'data'         => {the raw attachment data})
     */
    public function getAttachment($name)
    {
        list($mailbox, $uid, $part) = explode(':', $name);

        $atc = $this->_imap->getAttachment($mailbox, $uid, $part);

        return array($atc->getType(), $atc->getContents());
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
     * @param string $folderId  The folder id
     * @param array $ids        The message ids to delete
     */
    public function deleteMessage($folderid, array $ids)
    {
        $this->_logger->debug(sprintf(
            "DELETE %s: %s",
            $folderid,
            print_r($ids, true))
        );
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
        default:
            // Must be mail folder
            if (!is_array($id)) {
                $id = array($id);
            }
            try {
                $this->_imap->deleteMessages($ids, $folderid);
            } catch (Horde_Exception $e) {
                $this->_logger->err($e->getMessage());
            }
        }

        $this->_endBuffer();
    }

    /**
     * Move message
     *
     * @param string $folderid     Existing folder id.
     * @param array $ids           Message UIDs to move.
     * @param string $newfolderid  The new folder id to move to.
     *
     * @return array  An array of old uids as keys and new uids as values
     * @throws Horde_Exception
     */
    public function moveMessage($folderid, array $ids, $newfolderid)
    {
        $this->_logger->debug('Horde::moveMessage(' . implode(',', array($folderid, $id, $newfolderid)) . ')');
        ob_start();
        switch ($folderid) {
        case self::APPOINTMENTS_FOLDER_UID:
        case self::CONTACTS_FOLDER_UID:
        case self::TASKS_FOLDER_UID:
            $this->_endBuffer();
            throw new Horde_Exception('Not supported');
        default:
            $move_res = $this->_imap->moveMessage($folderid, $ids, $newfolderid);
        }
        $this->_endBuffer();

        return $move_res;
    }

    /**
     * Add/Edit a message
     *
     * @param string $folderid  The server id for the folder the message belongs
     *                          to.
     * @param string $id        The server's uid for the message if this is a
     *                          change to an existing message, null if new.
     * @param Horde_ActiveSync_Message_Base $message
     *                          The activesync message
     * @param stdClass $device  The device information
     *
     * @return array|boolean    A stat array if successful, otherwise false.
     */
    public function changeMessage($folderid, $id, Horde_ActiveSync_Message_Base $message, $device)
    {
        $this->_logger->debug('Horde::changeMessage(' . $folderid . ', ' . $id . ')');
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
                // There is no history entry for new messages, so use the
                // current time for purposes of remembering this is from the PIM
                $stat = $this->_smartStatMessage($folderid, $id, false);
                $stat['mod'] = time();
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
                $stat = $this->_smartStatMessage($folderid, $id, false);
                $stat['mod'] = time();
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
                $stat = $this->_smartStatMessage($folderid, $id, false);
                $stat['mod'] = time();
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

        default:
            $this->_endBuffer();
            return false;
        }

        $this->_endBuffer();
        return $stat;
    }

    /**
     * Returns array of items which contain contact information
     *
     * @param string $query  The text string to match against any textual ANR
     *                       (Automatic Name Resolution) properties. Exchange's
     *                       searchable ANR properties are currently:
     *                       firstname, lastname, alias, displayname, email
     * @param string $range  The range to return (for example, 1-50).
     *
     * @return array with 'rows' and 'range' keys
     */
    public function getSearchResults($query, $range)
    {
        $return = array('rows' => array(),
                        'range' => $range);

        ob_start();
        try {
            $results = $this->_connector->contacts_search($query);
        } catch (Horde_ActiveSync_Exception $e) {
            $this->_logger->err($e->getMessage());
            $this->_endBuffer();
            return $return;
        }

        /* Honor range, and don't bother if no results */
        $count = count($results);
        if (!$count) {
            return $return;
        }
        $this->_logger->info('Horde::getSearchResults found ' . $count . ' matches.');

        preg_match('/(.*)\-(.*)/', $range, $matches);
        $return_count = $matches[2] - $matches[1];
        $rows = array_slice($results, $matches[1], $return_count + 1, true);
        $rows = array_pop($rows);
        foreach ($rows as $row) {
            $return['rows'][] = array(
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
            );
        }

        $this->_endBuffer();
        return $return;
    }

    /**
     * Sends the email represented by the rfc822 string received by the PIM.
     *
     * @param string $rfc822            The rfc822 mime message
     * @param integer $forward  The UID of the message, if forwarding.
     * @param integer $reply    The UID of the message if replying.
     * @param string $parent    The collection id of parent message if
     *                          forwarding/replying.
     * @param boolean $save     Save in sent messages.
     *
     * @return boolean
     */
    public function sendMail(
        $rfc822, $forward = null, $reply = null, $parent = null, $save = true)
    {
        $headers = Horde_Mime_Headers::parseHeaders($rfc822);
        $message = Horde_Mime_Part::parseMessage($rfc822);

        // Message requests do not contain the From, since it is assumed to
        // be from the user of the AS account.
        $ident = $GLOBALS['injector']
            ->getInstance('Horde_Core_Factory_Identity')
            ->create($this->_user);
        $name = $ident->getValue('fullname');
        $from_addr = $ident->getValue('from_addr');
        $from = $name . '<' . $from_addr . '>';
        $headers->addHeader('From', $from);

        $this->_logger->debug(sprintf("Setting FROM to '%s'.", $from));
        $this->_logger->debug(sprintf("TO '%s'", $headers->getValue('To')));

        // Build the outgoing message.
        $mail = new Horde_Mime_Mail();
        $mail->addHeaders($headers->toArray());
        $id = $message->findBody();
        if ($id) {
            $newbody_text = $message->getPart($id)->getContents();
        } else {
            $newbody_text = '';
        }

        // Handle smartReplies and smartForward requests.
        // @TODO: Incorporate the reply position prefs?
        if ($reply && $parent) {
            $imap_message = array_pop($this->_imap->getImapMessage($parent, $reply));
            if (empty($imap_message)) {
                // Message gone
                return false;
            }
            $data = $imap_message->getMessageBody();
            if ($data['charset'] != 'UTF-8') {
                $quoted = Horde_String::convertCharset(
                    $data['text'],
                    $data['charset'],
                    'UTF-8'
                );
            } else {
                $quoted = $data['text'];
            }
            $newbody_text .= "\r\n" . $quoted;
        } elseif ($forward && $parent) {
            $imap_message = array_pop(
                $this->_imap->getImapMessage(
                    $parent, $forward, array('headers' => true))
            );
            if (empty($imap_message)) {
                // Message gone.
                return false;
            }

            // If forwarding as attachment (sadly most devices can't display
            // message/rfc822 content-type).
            $fwd = new Horde_Mime_Part();
            $fwd->setType('message/rfc822');
            $fwd->setContents($imap_message->getFullMsg());
            $mail->addMimePart($fwd);

            $from = $imap_message->getFromAddress();
            $part = $imap_message->getStructure();
            $id = $part->findBody();
            if ($id) {
                $obody_text = $imap_message->getMimePart($id)->getContents();
                $fwd_headers = $imap_message->getHeaders();
                $msg_pre = "\n----- "
                    . ($from ? sprintf(_("Forwarded message from %s"), $from) : _("Forwarded message"))
                    . " -----\n" . $fwd_headers . "\n";
                $msg_post = "\n\n----- " . _("End forwarded message") . " -----\n";
                $newbody_text .= $msg_pre . $obody_text . $msg_post;
            }
            foreach ($part->contentTypeMap() as $mid => $type) {
                if ($imap_message->isAttachment($type)) {
                    $apart = $imap_message->getMimePart($mid);
                    $mail->addMimePart($apart);
                }
            }
        }

        // Set the mail email body and add any uploaded attachements.
        $mail->setBody($newbody_text);
        foreach ($message->contentTypeMap() as $mid => $type) {
            if ($mid != 0 && $mid != $id) {
                $part = $message->getPart($mid);
                $mail->addMimePart($part);
            }
        }
        $this->_logger->debug('Sending Email.');
        try {
            $mail->send($GLOBALS['injector']->getInstance('Horde_Mail'));
        } catch (Horde_Mail_Exception $e) {
            $this->_logger->err($e->getMessage());
            throw new Horde_Exception($e);
        }
        if ($save) {
            $sf = $this->getSpecialFolderNameByType(self::SPECIAL_SENT);
            if (!empty($sf)) {
                $this->_logger->debug(sprintf("Preparing to copy to '%s'", $sf));
                $flags = array(Horde_Imap_Client::FLAG_SEEN);
                $msg = $message->toString(array('headers' => $headers));
                $this->_imap->appendMessage($sf, $msg, $flags);
            }
        }

        return true;
    }

    /**
     */
    public function setReadFlag($folderId, $id, $flags)
    {
        $this->_imap->setReadFlag($folderId, $id, $flags);
    }

    /**
     * Return the list of mail server folders.
     *
     * @return array  An array of Horde_ActiveSync_Message_Folder objects.
     */
    private function _getMailFolders()
    {
        if (empty($this->_mailFolders)) {
            if (empty($this->_imap)) {
                $this->_mailFolders = array($this->_getMailFolder('INBOX', array('label' => 'Inbox')));
            } else {
                $this->_logger->debug('Polling Horde_ActiveSync_Driver_Horde::_getMailFolders()');
                $folders = array();
                $imap_folders = $this->_imap->getMailboxes();
                foreach ($imap_folders as $folder) {
                    $folders[] = $this->_getMailFolder($folder['ob']->utf8, $folder);
                }
                $this->_mailFolders = $folders;
            }
        }

        return $this->_mailFolders;
    }

    /**
     * Return a folder object representing an email folder. Attempt to detect
     * special folders appropriately.
     *
     * @param string $sid   The server name.
     * @param array $f      An array describing the folder, as returned from
     *                      mail/folderlist.
     *
     * @return Horde_ActiveSync_Message_Folder
     */
    private function _getMailFolder($sid, $f)
    {
        $folder = new Horde_ActiveSync_Message_Folder();
        $folder->serverid = $sid;
        $folder->displayname = $f['label'];
        $folder->parentid = '0';
        // Short circuit for INBOX
        if (strcasecmp($sid, 'INBOX') === 0) {
            $folder->type = Horde_ActiveSync::FOLDER_TYPE_INBOX;
            return $folder;
        }
        $specialFolders = $this->_imap->getSpecialMailboxes();

        // Check for known, supported special folders.
        foreach ($specialFolders as $key => $value) {
            if (!is_array($value)) {
                $value = array($value);
            }
            foreach ($value as $mailbox) {
                if (!is_null($mailbox)) {
                    switch ($key) {
                    case self::SPECIAL_SENT:
                        if ($sid == $mailbox->basename) {
                            $folder->type = Horde_ActiveSync::FOLDER_TYPE_SENTMAIL;
                            return $folder;
                        }
                        break;
                    case self::SPECIAL_TRASH:
                        if ($sid == $mailbox->basename) {
                            $folder->type = Horde_ActiveSync::FOLDER_TYPE_WASTEBASKET;
                            return $folder;
                        }
                        break;

                    case self::SPECIAL_DRAFTS:
                        if ($sid == $mailbox->basename) {
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

    public function getSpecialFolderNameByType($type)
    {
        $folders = $this->_imap->getSpecialMailboxes();
        $folder = $folders[$type];
        if (!is_null($folder)) {
            if (is_array($folder)) {
                $folder = array_pop($folder);
            }

            return $folder->basename;
        } else {
            return $folder;
        }
    }

    /**
     * Build a stat structure for an email message.
     *
     * @param string $folderid   The mailbox name.
     * @param integer|array $id  The message(s) to stat
     *
     * @return array
     */
    public function statMailMessage($folderid, $id)
    {
        if (!is_array($id)) {
            $id = array($id);
        }
        $messages = $this->_imap->getImapMessage(
            $folderid, $id, array('structure' => false));

        $res = array();
        foreach ($messages as $message) {
            $res[$id] = array(
                'id' => $id,
                'mod' => 0,
                'flags' => $message->getFlag(Horde_Imap_Client::FLAG_SEEN));
        }

        return $res;
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
    private function _buildNonMailFolder($id, $parent, $type, $name)
    {
        $folder = new Horde_ActiveSync_Message_Folder();
        $folder->serverid = $id;
        $folder->parentid = $parent;
        $folder->type = $type;
        $folder->displayname = $name;

        return $folder;
    }

    /**
     *
     * @param string  $folderid  The folder id
     * @param string  $id        The message id
     * @param boolean $hint      Use the cached data, if available?
     *
     * @return message stat hash
     */
    private function _smartStatMessage($folderid, $id, $hint)
    {
        ob_start();
        $this->_logger->debug('ActiveSync_Driver_Horde::_smartStatMessage:' . $folderid . ':' . $id);
        $statKey = $folderid . $id;
        $mod = false;

        if ($hint && isset($this->_modCache[$statKey])) {
            $mod = $this->_modCache[$statKey];
        } else {
            try {
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

                default:
                    try {
                        return array_pop($this->statMailMessage($folderid, $id));
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

    private function _endBuffer()
    {
        if ($output = ob_get_clean()) {
            $this->_logger->err('Unexpected output: ' . $output);
        }
    }

}
