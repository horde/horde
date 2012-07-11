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
            $this->_imap->setLogger($logger);
        }
    }

    /**
     * Authenticate to Horde
     *
     * @see framework/ActiveSync/lib/Horde/ActiveSync/Driver/Horde_ActiveSync_Driver_Base#authenticate
     */
    public function authenticate($username, $password, $domain = null)
    {
        $this->_logger->info('Horde_ActiveSync_Driver_Horde::logon attempt for: ' . $username);
        parent::authenticate($username, $password, $domain);

        if (!$this->_auth->authenticate($username, array('password' => $password))) {
            return false;
        }

        if ($GLOBALS['injector']->getInstance('Horde_Perms')->exists('horde:activesync')) {
            // Check permissions to ActiveSync
            $perms = $GLOBALS['injector']
                ->getInstance('Horde_Perms')
                ->getPermissions('horde:activesync', $username);

            return $this->_getPolicyValue('activesync', $perms);
        }

        return true;
    }

    /**
     * Clean up
     *
     * @see framework/ActiveSync/lib/Horde/ActiveSync/Driver/Horde_ActiveSync_Driver_Base#removeAuthentication()
     */
    public function removeAuthentication()
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
     * @param mixed $parent  The parent folder (or 0 if none).
     * @param mixed $mod     Modification indicator. For folders, this is the
     *                       name of the folder, since that's the only thing
     *                       that can change.
     * @return a stat hash
     */
    public function statFolder($id, $parent = 0, $mod = null)
    {
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
                    $folder = $this->_imap->getMessageChanges(
                        $folder,
                        array(
                            'sincedate' => (int)$cutoffdate,
                            'protocolversion' => $this->_version));
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
     *   - mimesupport: (boolean) Indicates if the device has MIME support.
     *                  DEFAULT: false (No MIME support)
     *   - truncation: (integer)  The truncation constant, if sent by the device.
     *                 DEFAULT: 0 (No truncation)
     *   - bodyprefs: (array)  The bodypref array from the device.
     *
     * @return Horde_ActiveSync_Message_Base The message data
     * @throws Horde_ActiveSync_Exception, Horde_Exception_NotFound
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
                throw $e;
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
                throw $e;
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
                throw $e;
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
                throw new Horde_Exception_NotFound($e);
                // throw $e;
            }
            $this->_endBuffer();
            if (empty($messages)) {
                throw new Horde_Exception_NotFound();
            }
            return current($messages);
            break;
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
     *
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
     * @param string $longid   The unique search result identifier.
     * @param array $bodypref  The bodypreference array.
     * @param boolean $mime    Mimesupport flag.
     *
     * @return Horde_ActiveSync_Message_Base  The message requested.
     */
    public function itemOperationsFetchMailbox($searchlongid, array $bodypreference, $mimesupport)
    {
        list($mailbox, $uid) = explode(':', $searchlongid);
        return $this->getMessage(
            $mailbox,
            $uid,
            array(
                'bodyprefs' => $bodypreference,
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
        $airatt = new Horde_ActiveSync_Message_AirSyncBaseFileAttachment(array('logger' => $this->_logger));
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
            // Email?
            if ($message instanceof Horde_ActiveSync_Message_Mail) {
                $this->_imap->setMessageFlag($folderid, $id, $message->flag);
                $stat = array('id' => $id, 'flags' => array('flagged' => $message->flag->flagstatus), 'mod' => 0);
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
    public function sendMail(
        $rfc822, $forward = null, $reply = null, $parent = null, $save = true)
    {
        $raw_message = new Horde_ActiveSync_Rfc822($rfc822);
        $headers = $raw_message->getHeaders();

        // Add From, but only if needed.
        if (!$headers->getValue('From')) {
            $headers->addHeader('From', $this->_getIdentityFromAddress());
        }

        // Use the raw base part parsed from the rfc822 message if we don't
        // need a smart reply or smart forward. The device will NOT send a
        // smart reply/forward request if it is a s/mime signed.
        if (!$parent) {
            $mailer = $GLOBALS['injector']->getInstance('Horde_Mail');
            $recipients = new Horde_Mail_Rfc822_List();
            foreach (array('To', 'Cc') as $header) {
               $recipients->add($headers->getOb($header));
            }
            $h_array = $headers->toArray();
            if (!empty($h_array['bcc'])) {
                $recipients->add($headers->getOb('bcc'));
                unset($h_array['bcc']);
            }
            if (is_array($h_array['From'])) {
                $h_array['From'] = current($h_array['From']);
            }
            try {
                $mailer->send($recipients->writeAddress(), $h_array, $raw_message->getMessage());
            } catch (Horde_Mail_Exception $e) {
                $this->_logger->err($e->getMessage());
                throw new Horde_Exception($e);
            }
        } else {
            $message = $raw_message->getMimeObject();
            $mail = new Horde_Mime_Mail();
            $mail->addHeaders($headers->toArray());
            $body_id = $message->findBody();
            $smart_body = $this->_getSmartText($message);

            // Handle smartReplies and smartForward requests.
            if ($reply) {
                $this->_logger->debug('Preparing SMART_REPLY');
                $imap_message = array_pop($this->_imap->getImapMessage($parent, $reply, array('headers' => true)));
                if (empty($imap_message)) {
                    return false;
                }
                $part = $imap_message->getStructure();
                $plain_id = $part->findBody('plain');
                $html_id = $part->findBody('html');
                if ($html_id) {
                    $smart_text = $smart_body[0] == 'plain'
                        ? self::text2html($smart_body[1])
                        : $smart_body[1];
                    $newbody_text_html = $smart_text . $this->_replyText($imap_message, $html_id, true);
                }
                if ($plain_id) {
                    $smart_text = $smart_body[0] == 'html'
                        ? self::html2text($smart_body[1])
                        : $smart_body[1];
                    $newbody_text_plain .= $smart_text . $this->_replyText($imap_message, $plain_id);
                }
            } elseif ($forward) {
                $this->_logger->debug('Preparing SMART_FORWARD');
                $imap_message = array_pop(
                    $this->_imap->getImapMessage($parent, $forward, array('headers' => true)));
                if (empty($imap_message)) {
                    return false;
                }
                $from = $imap_message->getFromAddress();
                $part = $imap_message->getStructure();
                $plain_id = $part->findBody('plain');
                $html_id = $part->findBody('html');
                if ($html_id) {
                    $smart_text = $smart_body[0] == 'plain'
                        ? self::text2html($smart_body[1])
                        : $smart_body[1];
                    $newbody_text_html = $smart_text . $this->_forwardText($imap_message, $html_id, true);
                }
                if ($plain_id) {
                    $smart_text = $smart_body[0] == 'html'
                        ? self::html2text($smart_body[1])
                        : $smart_body[1];
                    $newbody_text_plain = $smart_text . $this->_forwardText($imap_message, $plain_id);
                }
                foreach ($part->contentTypeMap() as $mid => $type) {
                    if ($imap_message->isAttachment($type)) {
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

            foreach ($message->contentTypeMap() as $mid => $type) {
                if ($mid != 0 && $mid != $body_id) {
                    $part = $message->getPart($mid);
                    $mail->addMimePart($part);
                }
            }
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
        }

        return true;
    }

    /**
     * Return the text sent from the device in the SMART[FORWARD|REPLY] request.
     *
     * @param Horde_Mime_Part $part  The mime part containing the message body.
     *
     * @return array  An array containing the body type [plain|html] and the
     *                message body.
     */
    protected function _getSmartText(Horde_Mime_Part $part)
    {
        $id = $part->findBody('html');
        if ($id) {
            $type = 'html';
        } else {
            $id = $part->findBody();
            $type = 'plain';
        }

        return array($type, $part->getPart($id)->getContents());
    }

    /**
     * Return the body of the forwarded message in the appropriate type.
     *
     * @param Horde_ActiveSync_Imap_Message $message  The imap message object.
     * @param integer $partID                         The body's mime id.
     * @param boolean $html                           Is this an html part?
     *
     * @return string  The propertly formatted forwarded body text.
     */
    protected function _forwardText(Horde_ActiveSync_Imap_Message $message, $partId, $html = false)
    {
        $part = $message->getMimePart($partId);
        $fwd_headers = $message->getHeaders();
        $from = $message->getFromAddress();
        $msg = $this->_msgBody($part, $html);
        $msg_pre = "\n----- "
            . ($from ? sprintf(_("Forwarded message from %s"), $from) : _("Forwarded message"))
            . " -----\n" . $fwd_headers . "\n";
        $msg_post = "\n\n----- " . _("End forwarded message") . " -----\n";

        return ($html ? self::text2html($msg_pre) : $msg_pre)
            . $msg
            . ($html ? self::text2html($msg_post) : $msg_post);
    }

    /**
     * Return the body of the replied message in the appropriate type.
     *
     * @param Horde_ActiveSync_Imap_Message $message  The imap message object.
     * @param integer $partID                         The body's mime id.
     * @param boolean $html                           Is this an html part?
     *
     * @return string  The propertly formatted replied body text.
     */
    protected function _replyText(Horde_ActiveSync_Imap_Message $message, $partId, $html)
    {
        $part = $message->getMimePart($partId);
        $headers = $message->getHeaders();
        $from = strval($headers->getOb('from'));
        $msg_pre = $from ? sprintf(_("Quoting %s"), $from) : _("Quoted") . "\n\n";
        $msg = $this->_msgBody($part, $html, true);
        if (!empty($msg) && $html) {
            $msg = '<p>' . $this->text2html($msg_pre) . '</p>'
                . self::HTML_BLOCKQUOTE . $msg . '</blockquote><br /><br />';
        } else {
            $msg = empty($msg)
                ? '[' . _("No message body text") . ']'
                : $msg_pre . $msg;
        }

        return $msg;
    }

    /**
     * Return the body text of the original email from a smart request.
     *
     * @param Horde_Mime_Part $part  The message's main mime part.
     * @param boolean $html          Do we want an html body?
     * @param boolean $flow          Should the body be flowed?
     *
     * @return string  The properly formatted/flowed message body.
     */
    protected function _msgBody(Horde_Mime_Part $part, $html, $flow = false)
    {
        $msg = Horde_String::convertCharset($part->getContents(), $part->getCharset(), 'UTF-8');
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
     * @param string $folderid  The folder id containing the message.
     * @param string $uid       The message uid.
     * @param integer $flag     The value to set the flag to.
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
        // I can't think of a time when we would actually need to hit the
        // server. As long as 'mod' is 0, this should be fine as we don't
        // track flag conflicts.
        // $messages = $this->_imap->getImapMessage(
        //     $folderid, array($id), array('structure' => false));
        return array(
            'id' => $id,
            'mod' => 0,
            'flags' => false);//$messages[$id]->getFlag(Horde_Imap_Client::FLAG_SEEN));
    }

    /**
     * Return the security policies
     *
     * @return array  An array of provisionable properties and values.
     */
    public function getCurrentPolicy()
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
     * @return array  The requested settigns.
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
     *   array('oof' => Horde_ActiveSync_Request_Settings::STATUS_SUCCESS,
     *         'deviceinformation' => Horde_ActiveSync_Request_Settings::STATUS_SUCCESS);
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
            case 'deviceinformation':
                try {
                    $state = $GLOBALS['injector']->getInstance('Horde_ActiveSyncState');
                    $state->setDeviceProperties($setting, $device);
                    $res['deviceinformation'] = Horde_ActiveSync_Request_Settings::STATUS_SUCCESS;
                } catch (Horde_Exception $e) {
                    $res['deviceinformation'] = Horde_ActiveSync_Request_Settings::STATUS_ERROR;
                }
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
     * @return array
     */
    public function autoDiscover()
    {
        $results = array();

        // Attempt to get a username from the email address.
        $ident = $GLOBALS['injector']
            ->getInstance('Horde_Core_Factory_Identity')
            ->create($GLOBALS['registry']->getAuth());
        $results['display_name'] = $ident->getValue('fullname');
        $results['email'] = $ident->getValue('from_addr');
        $url = parse_url((string)Horde::url(null, true));
        $results['url'] = $url['scheme'] . '://' . $url['host'] . '/Microsoft-Server-ActiveSync';
        // As of Exchange 2007, this always returns en:en
        $results['culture'] = 'en:en';
        return $results;
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
            return substr($email, 0, strpos($email, '@'));
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
     * @param array $opts  Any options required to perform the resolution.
     *
     * @return array  The results.
     */
    public function resolveRecipient($type, $search, array $opts = array())
    {
        $return = array();
        $gal = $this->_connector->contacts_getGal();
        $results = $this->_connector->resolveRecipient($search);
        if (count($results) && isset($results[$search])) {
            foreach ($results[$search] as $result) {
                // Do maxabiguous filtering etc...
                $return[] = array(
                    'displayname' => $result['name'],
                    'emailaddress' => $result['email'],
                    'entries' => array($this->_mungeCert($result['smimePublicKey'])),
                    'type' => $result['source'] == $gal ? 1 : 2
                );
            }
        }

        return $return;
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
        case Horde_ActiveSync_Request_MeetingResponse::RESPONSE_DENIED:
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
            $this->_logger->debug('Successfully sent iTip response.');
        } catch (Horde_Itip_Exception $e) {
            $this->_logger->err('Error sending response: ' . $e->getMessage());
            throw new Horde_ActiveSync_Exception($e);
        }

        // Save to SENT
        $sf = $this->getSpecialFolderNameByType(self::SPECIAL_SENT);
        if (!empty($sf)) {
            $this->_logger->debug(sprintf("Preparing to copy to '%s'", $sf));
            list($headers, $body) = $itip_response->getMultiPartMessage(
                $type, $options);
            $flags = array(Horde_Imap_Client::FLAG_SEEN);
            $msg = $body->toString(array('headers' => $headers));
            $this->_imap->appendMessage($sf, $msg, $flags);
        }

        // Delete the original request. EAS Specs require this. Most clients
        // will remove the email from the UI as soon as the response is sent.
        // Failure to remove it from the server will result in an inconsistent
        // sync state.
        $this->_logger->debug('Deleting');
        $this->_imap->deleteMessages(array($response['requestid']), $response['folderid']);

        return $uid;
    }

    /**
     * Request freebusy information from the server
     */
    public function getFreebusy($user, array $options = array())
    {
        throw new Horde_ActiveSync_Exception('Not supported');
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

   /**
     * Return the list of mail server folders.
     *
     * @return array  An array of Horde_ActiveSync_Message_Folder objects.
     */
    protected function _getMailFolders()
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
    protected function _getMailFolder($sid, array $f)
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
            $results = $this->_connector->contacts_search($query['query']);
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
        $this->_logger->info('Horde::getSearchResults found ' . $count . ' matches.');

        preg_match('/(.*)\-(.*)/', $query['range'], $matches);
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
     * @param string $mime_part  The MIME type.
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
     * @return array
     */
    protected function _getPolicyFromPerms()
    {
        $prefix = 'activesync:provisioning:';
        $policy = array();
        $perms = $GLOBALS['injector']->getInstance('Horde_Perms');
        if (!$provisioning = $perms->exists('horde:activesync:provisioning')) {
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

}
