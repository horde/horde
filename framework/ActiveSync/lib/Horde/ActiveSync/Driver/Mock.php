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
 * class, servers must implement their own backend to provide
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
class Horde_ActiveSync_Driver_Mock extends Horde_ActiveSync_Driver_Base
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

    protected $_auth;
    protected $_connector;
    protected $_imap;
    protected $_displayMap = array(
        self::APPOINTMENTS_FOLDER_UID => 'Calendar',
        self::CONTACTS_FOLDER_UID     => 'Contacts',
        self::TASKS_FOLDER_UID        => 'Tasks',
        self::NOTES_FOLDER_UID        => 'Notes',
    );

    public function __construct($params = array())
    {
        parent::__construct($params);
        $this->_connector = $params['connector'];
        //$this->_auth = $params['auth'];
        $this->_imap = $params['imap'];
    }

    /**
     * Delete a folder on the server.
     *
     * @param string $id  The server's folder id.
     * @param string $parent  The folder's parent, if needed.
     */
    public function deleteFolder($id, $parent = Horde_ActiveSync::FOLDER_ROOT) {  }

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
     */
    public function changeFolder($id, $displayname, $parent, $uid = null)
    {
        return $uid;
    }

    /**
     * Move message
     *
     * @param string $folderid     Existing folder id
     * @param array $ids           Message UIDs
     * @param string $newfolderid  The new folder id
     *
     * @return array  The new uids for the message.
     */
    public function moveMessage($folderid, array $ids, $newfolderid)
    {
        return $ids;
    }

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
    public function getSearchResults($type, array $query)
    {
        return array();
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
        $folder['serverid'] = !empty($serverid) ? $serverid : $id;

        return $folder;
    }

    /**
     * Return the ActiveSync message object for the specified folder.
     *
     * @param string $id  The folder's server id.
     *
     * @return Horde_ActiveSync_Message_Folder object.
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
            throw new Horde_ActiveSync_Exception('Folder ' . $id . ' unknown');
        }

        return $folder;
    }

   /**
     * Return the list of mail server folders.
     *
     * @return array  An array of Horde_ActiveSync_Message_Folder objects.
     */
    protected function _getMailFolders()
    {
        if (empty($this->_imap)) {
            $this->_mailFolders = array($this->_buildDummyFolder(self::SPECIAL_INBOX));
            $this->_mailFolders[] = $this->_buildDummyFolder(self::SPECIAL_TRASH);
            $this->_mailFolders[] = $this->_buildDummyFolder(self::SPECIAL_SENT);
        } else {
            $folders = array();
            $imap_folders = $this->_imap->getMailboxes();

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

    protected function _getFolderUidForBackendId($sid, $type = null, $old_id = null)
    {
        switch ($sid) {
        case 'INBOX':
            return '519422f1-4c5c-4547-946a-1701c0a8015f';
        default:
            return $sid;
        }
    }

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
     * Get the list of folder stat arrays @see self::statFolder()
     *
     * @return array  An array of folder stat arrays.
     */
    public function getFolderList()
    {
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
            try {
                $supported = $this->_connector->listApis();
            } catch (Exception $e) {
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
                    return array();
                }
            }
            $this->_folders = $folders;
        }

        return $this->_folders;
    }

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
    public function getServerChanges($folderId, $from_ts, $to_ts, $cutoffdate, $ping)
    {

        $changes = array(
            'add' => array(),
            'delete' => array(),
            'modify' => array()
        );
        if ($from_ts == 0 && !$ignoreFirstSync) {
            $startstamp = (int)$cutoffdate;
            $endstamp = time() + 32140800; //60 * 60 * 24 * 31 * 12 == one year
            $changes['add'] = $this->_connector->listUids($startstamp, $endstamp);
        } else {
            $changes = $this->_connector->getChanges($folderId, $from_ts, $to_ts);
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

        return $results;
    }

    /**
     * Get a message stat.
     *
     * @param string $folderId  The folder id
     * @param string $id        The message id (??)
     *
     * @return hash with 'id', 'mod', and 'flags' members
     */
    public function statMessage($folderId, $id)
    {
        $mod = $this->_connector->getActionTimestamp($id, 'modify');
        $message = array();
        $message['id'] = $id;
        $message['mod'] = $mod;
        $message['flags'] = 1;

        return $message;
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
        return $this->_connector->export($id, array());
    }

    /**
     * Delete a message
     *
     * @param string $folderid  The folder id containing the messages.
     * @param array $ids        An array of message ids to delete.
     */
    public function deleteMessage($folderid, array $ids)
    {
        return $ids;
    }

    /**
     * Get the wastebasket folder.
     *
     * @param string $class  The collection class.
     *
     * @return string|boolean  Returns name of the trash folder, or false
     *                         if not using a trash folder.
     */
    public function getWasteBasket($class)
    {
        return false;
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
     * @param Horde_ActiveSync_Device $device  The device information
     *
     * @return array|boolean    A stat array if successful, otherwise false.
     */
    public function changeMessage($folderid, $id, Horde_ActiveSync_Message_Base $message, $device) {  }

    /**
     * Set the read (\seen) flag on the specified message.
     *
     * @param string $folderid  The folder id containing the message.
     * @param integer $uid      The message IMAP UID.
     * @param integer $flag     The value to set the flag to.
     * @deprecated Will be removed in 3.0, use changeMessage() instead.
     */
    public function setReadFlag($folderid, $uid, $flag) {  }

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
        return true;
    }

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
    public function getAttachment($name, array $options = array()) {  }

    /**
     * Return the specified attachement data for an ITEMOPERATIONS request.
     *
     * @param string $filereference  The attachment identifier.
     *
     * @return
     */
    public function itemOperationsGetAttachmentData($filereference) {  }

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
    public function itemOperationsFetchMailbox($longid, array $bodyprefs, $mimesupport) {  }

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
    public function itemOperationsGetDocumentLibraryLink($linkid, $cred) {  }

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
        return array(
            'id' => $id,
            'mod' => 0,
            'flags' => false);
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
     * Return the security policies.
     *
     * @param boolean|array $device  The device information sent by EAS 14.1
     *                               set to false otherwise. @since 3.0
     * @return array  An array of provisionable properties and values.
     */
    public function getCurrentPolicy() {  }

    /**
     * Return settings from the backend for a SETTINGS request.
     *
     * @param array $settings   An array of settings to return.
     * @param Horde_ActiveSync_Device $device  The device to obtain settings for.
     *
     * @return array  The requested settings.
     */
    public function getSettings(array $settings, $device) {  }

    /**
     * Set backend settings from a SETTINGS request.
     *
     * @param array $settings   The settings to store.
     * @param Horde_ActiveSync_Device $device  The device to store settings for.
     *
     * @return array  An array of status responses for each set request. e.g.,:
     *   array('oof' => Horde_ActiveSync_Request_Settings::STATUS_SUCCESS,
     *         'deviceinformation' => Horde_ActiveSync_Request_Settings::STATUS_SUCCESS) {  }
     */
    public function setSettings(array $settings, $device) {  }

    /**
     * Return properties for an AUTODISCOVER request.
     *
     * @return array  An array of properties.
     */
    public function autoDiscover() {  }

    /**
     * Attempt to guess a username based on the email address passed from
     * EAS Autodiscover requests.
     *
     * @param string $email  The email address
     *
     * @return string  The username to use to authenticate to Horde with.
     */
    public function getUsernameFromEmail($email) {  }

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
    public function resolveRecipient($type, $search, array $options = array()) {  }

    /**
     * Returns the provisioning support for the current request.
     *
     * @return mixed  The value of the provisiong support flag.
     */
    public function getProvisioning() {  }

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
    public function meetingResponse(array $response) {  }

    /**
     * Request freebusy information from the server
     *
     * @param string $user    The user to request FB information for.
     * @param array $options  Options.
     *
     * @return mixed boolean|array  The FB information, if available. Otherwise
     *                              false.
     */
    public function getFreebusy($user, array $options = array()) { }

    public function getHeartbeatConfig()
    {
        return array(
            'heartbeatmin' => 60,
            'heartbeatmax' => 2700,
            'heartbeatdefault' => 480,
            'deviceping' => true,
            'waitinterval' => 10);
    }

}
