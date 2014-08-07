<?php
/**
 * Horde_ActiveSync_Connector_Importer::
 *
 * Portions of this class were ported from the Z-Push project:
 *   File      :   wbxml.php
 *   Project   :   Z-Push
 *   Descr     :   WBXML mapping file
 *
 *   Created   :   01.10.2007
 *
 *   � Zarafa Deutschland GmbH, www.zarafaserver.de
 *   This file is distributed under GPL-2.0.
 *   Consult COPYING file for details
 *
 * @license   http://www.horde.org/licenses/gpl GPLv2
 *            NOTE: According to sec. 8 of the GENERAL PUBLIC LICENSE (GPL),
 *            Version 2, the distribution of the Horde_ActiveSync module in or
 *            to the United States of America is excluded from the scope of this
 *            license.
 * @copyright 2011-2014 Horde LLC (http://www.horde.org)
 * @author    Michael J Rubinsky <mrubinsk@horde.org>
 * @package   ActiveSync
 */
/**
 * Horde_ActiveSync_Connector_Imports:: Receives Wbxml from device.
 *
 * @license   http://www.horde.org/licenses/gpl GPLv2
 *            NOTE: According to sec. 8 of the GENERAL PUBLIC LICENSE (GPL),
 *            Version 2, the distribution of the Horde_ActiveSync module in or
 *            to the United States of America is excluded from the scope of this
 *            license.
 * @copyright 2011-2014 Horde LLC (http://www.horde.org)
 * @author    Michael J Rubinsky <mrubinsk@horde.org>
 * @package   ActiveSync
 */
class Horde_ActiveSync_Connector_Importer
{
    /**
     * State machine
     *
     * @var Horde_ActiveSync_State_Base
     */
    protected $_state;

    /**
     * The server object.
     *
     * @var Horde_ActiveSync
     */
    protected $_as;

    /**
     * Conflict resolution flags
     *
     * @var integer
     */
    protected $_flags;

    /**
     * The backend specific folder id
     *
     * @var string
     */
    protected $_folderId;

    /**
     * The EAS folder uid
     *
     * @var string
     */
    protected $_folderUid;

    /**
     * Logger
     *
     * @var Horde_Log_Logger
     */
    protected $_logger;

    /**
     * Process id for logging.
     *
     * @var integer
     */
    protected $_procid;

    /**
     * Const'r
     *
     * @param Horde_ActiveSync $as  The server object.
     */
    public function __construct(Horde_ActiveSync $as)
    {
        $this->_as = $as;
        $this->_procid = getmypid();
    }

    /**
     * Initialize the exporter for this collection
     *
     * @param Horde_ActiveSync_State_Base $state  The state machine.
     * @param string $folderId                    The collection's uid.
     * @param integer $flags                      Conflict resolution flags.
     */
    public function init(Horde_ActiveSync_State_Base $state, $folderId = null, $flags = 0)
    {
        $this->_state = $state;
        $this->_flags = $flags;
        if (!empty($folderId)) {
            $this->_folderId = $this->_as->getCollectionsObject()->getBackendIdForFolderUid($folderId);
            $this->_folderUid = $folderId;
        }
    }

    /**
     * Setter for a logger instance
     *
     * @param Horde_Log_Logger $logger  The logger
     */
    public function setLogger($logger)
    {
        $this->_logger = $logger;
    }

    /**
     * Import a message change from the wbxml stream
     *
     * @param string|boolean $id                       A server message id or
     *                                                 false if a new message.
     * @param Horde_ActiveSync_Message_Base $message   A message object
     * @param Horde_ActiveSync_Device $device          A device descriptor
     * @param integer $clientid                        Client id sent from PIM
     *                                                 on message addition.
     * @param string $class   The collection class (only needed for SMS).
     *                        @since 2.6.0
     *
     * @todo Revisit passing $class for SMS. Probably pass class in the
     *       const'r.
     *
     * @return string|array|boolean The server message id, an array containing
     *                              the serverid and failure code, or false
     */
    public function importMessageChange(
        $id, Horde_ActiveSync_Message_Base $message,
        Horde_ActiveSync_Device $device, $clientid, $class = null)
    {
        // Don't support SMS, but can't tell client that. Send back a phoney
        // UID for any imported SMS objects.
        if ($class == Horde_ActiveSync::CLASS_SMS || strpos($id, 'IGNORESMS_') === 0) {
            return 'IGNORESMS_' . $clientid;
        }

        // Changing an existing object
        if ($id && $this->_flags == Horde_ActiveSync::CONFLICT_OVERWRITE_PIM) {
            $conflict = $this->_isConflict(
                Horde_ActiveSync::CHANGE_TYPE_CHANGE,
                $this->_folderId,
                $id);
            if ($conflict) {
                $this->_logger->notice(sprintf(
                    '[%s] Conflict when updating %s, will overwrite client version on next sync.',
                    $this->_procid, $id)
                );
                return array($id, Horde_ActiveSync_Request_Sync::STATUS_CONFLICT);
            }
        } elseif (!$id && $uid = $this->_state->isDuplicatePIMAddition($clientid)) {
            // Already saw this addition, but PIM never received UID
            $this->_logger->notice(sprintf(
                '[%s] Duplicate addition for %s',
                $this->_procid, $uid)
            );
            return $uid;
        }

        // Tell the backend about the change
        if (!$stat = $this->_as->driver->changeMessage($this->_folderId, $id, $message, $device)) {
            $this->_logger->err(sprintf(
                '[%s] Change message failed when updating %s',
                $this->_procid, $id)
            );
            return $id
                ? array($id, Horde_ActiveSync_Request_Sync::STATUS_NOTFOUND)
                : array(false, Horde_ActiveSync_Request_Sync::STATUS_SERVERERROR);
        }
        $stat['serverid'] = $this->_folderId;

        // Record the state of the message, but only if we aren't updating
        // categories. @todo This should be fixed, but for now we can't
        // differentiate between different flag changes. Note that categories
        // only exists for email changes so for non email this will still
        // work as before.
        if (!array_key_exists('categories', $stat) || $stat['categories'] === false) {
            $this->_state->updateState(
                ($message instanceof Horde_ActiveSync_Message_Mail
                    ? Horde_ActiveSync::CHANGE_TYPE_FLAGS
                    : Horde_ActiveSync::CHANGE_TYPE_CHANGE),
                $stat,
                Horde_ActiveSync::CHANGE_ORIGIN_PIM,
                $this->_as->driver->getUser(),
                $clientid);
        }

        return $stat['id'];
    }

    /**
     * Import message deletions. This may conflict if the local object has been
     * modified.
     *
     * @param array $ids          Server message uids to delete
     * @param string $class       The server collection class.
     *
     * @return array  An array containing ids of successfully deleted messages.
     */
    public function importMessageDeletion(array $ids, $class)
    {
        // Don't support SMS, but can't tell client that.
        if ($class == Horde_ActiveSync::CLASS_SMS) {
            return array();
        }

        // Ask the backend to delete the message.
        $mod = $this->_as->driver->getSyncStamp($this->_folderId);
        $ids = $this->_as->driver->deleteMessage($this->_folderId, $ids);
        foreach ($ids as $id) {
            // Ignore SMS changes.
             if (strpos($id, "IGNORESMS_") === 0) {
                continue;
             }
            $change = array();
            $change['id'] = $id;
            $change['mod'] = $mod;
            $change['serverid'] = $this->_folderId;
            $this->_state->updateState(
                Horde_ActiveSync::CHANGE_TYPE_DELETE,
                $change,
                Horde_ActiveSync::CHANGE_ORIGIN_PIM,
                $this->_as->driver->getUser());
        }

        return $ids;
    }

    /**
     * Import a change in 'read' flags. This can never conflict.
     *
     * @param integer $id   Server message id (The IMAP UID).
     * @param string $flag  The state of the /seen flag
     */
    public function importMessageReadFlag($id, $flag)
    {
        $change = array();
        $change['id'] = $id;
        $change['flags'] = array('read' => $flag);
        $change['parent'] = $this->_folderId;
        $this->_state->updateState(
            Horde_ActiveSync::CHANGE_TYPE_FLAGS,
            $change,
            Horde_ActiveSync::CHANGE_ORIGIN_PIM,
            $this->_as->driver->getUser());

        $this->_as->driver->setReadFlag($this->_folderId, $id, $flag);
    }

    /**
     * Perform a message move initiated on the PIM
     *
     * @param array $uids     The source message ids.
     * @param string $dst     The destination folder uid.
     * @param string $class   The collection class (only needed for SMS).
     *                        @since 2.10.0
     *
     * @return array  An array containing the following keys:
     *   - results: An array with old uids as keys and new uids as values.
     *   - missing: An array containing source uids that were not found on the
     *              IMAP server.
     */
    public function importMessageMove(array $uids, $dst, $class = null)
    {
        // Don't support SMS, but can't tell client that. Send back a phoney
        // UID for any imported SMS objects.
        if ($class == Horde_ActiveSync::CLASS_SMS) {
            return $uids;
        }
        // Filter out SMS if $class is not CLASS_SMS
        $uids = array_filter(
            $uids,
            function($e) { return strpos($e, 'IGNORESMS_') === false; }
        );
        $collections = $this->_as->getCollectionsObject();
        $dst = $collections->getBackendIdForFolderUid($dst);
        $results = $this->_as->driver->moveMessage($this->_folderId, $uids, $dst);

        // Check for any missing (not found) source messages.
        $missing = count($results) != count($uids)
            ? array_diff($uids, array_keys($results))
            : array();

        // Update client state. For MOVEITEMS, we are supposed to send
        // a DELETE and ADD command to the appropriate folders on the next
        // sync, but some broken clients don't like this. Save the import
        // in the map table in case we need it later.
        $mod = $this->_as->driver->getSyncStamp($this->_folderId);
        foreach ($uids as $uid) {
            if (empty($results[$uid])) {
                continue;
            }
            $change = array();
            $change['id'] = $results[$uid];
            $change['mod'] = $mod;
            $change['serverid'] = $dst;
            $change['class'] = Horde_ActiveSync::CLASS_EMAIL;
            $change['folderuid'] = $this->_folderUid;
            $this->_state->updateState(
                Horde_ActiveSync::CHANGE_TYPE_CHANGE,
                $change,
                Horde_ActiveSync::CHANGE_ORIGIN_PIM,
                $this->_as->driver->getUser());
        }

        return array('results' => $results, 'missing' => $missing);
    }

    /**
     * Import a folder change from the wbxml stream
     *
     * @param string $uid          The folder uid
     * @param string $displayname  The folder display name
     * @param string $parent       The parent folder id.
     * @param integer $type        The EAS Folder type. @since 2.9.0
     *
     * @return Horde_ActiveSync_Message_Folder The new folder object.
     */
    public function importFolderChange($uid, $displayname, $parent = Horde_ActiveSync::FOLDER_ROOT, $type = null)
    {
        $this->_logger->info(sprintf(
            '[%s] Horde_ActiveSync_Connector_Importer::importFolderChange(%s, %s, %s, %s)',
            $this->_procid, $uid, $displayname, $parent, $type));

        // Convert the uids to serverids.
        $collections = $this->_as->getCollectionsObject();
        $parent_sid = !empty($parent)
            ? $collections->getBackendIdForFolderUid($parent)
            : $parent;
        $folderid = !empty($uid)
            ? $collections->getBackendIdForFolderUid($uid)
            : false;

        // Perform the creation in the backend.
        try {
            $results = $this->_as->driver->changeFolder(
                $folderid, $displayname, $parent_sid, $uid, $type);
        } catch (Horde_ActiveSync_Exception $e) {
            $this->_logger->err($e->getMessage());
            throw $e;
        }

        // @todo Horde 6 this should always return an object.
        if ($results instanceof Horde_ActiveSync_Message_Folder) {
            $folderid = $results->_serverid;
            $uid = $results->serverid;
        } else {
            // @TODO Remove for 3.0 Need to build a message folder object here
            // for BC reasons.
            $serverid = $results;
            $results = $this->_as->messageFactory('Folder');
            $results->serverid = $serverid;
            $results->_serverid = $folderid;
        }

        $change = array();
        $change['id'] = $uid;
        $change['folderid'] = $folderid;
        $change['mod'] = $displayname;
        $change['parent'] = $parent;
        $this->_state->updateState(
            Horde_ActiveSync::CHANGE_TYPE_CHANGE,
            $change,
            Horde_ActiveSync::CHANGE_ORIGIN_PIM);

        return $results;
    }

    /**
     * Imports a folder deletion from the PIM
     *
     * @param string $uid     The folder uid
     * @param string $parent  The folder id of the parent folder.
     */
    public function importFolderDeletion($uid, $parent = Horde_ActiveSync::FOLDER_ROOT)
    {
        $collections = $this->_as->getCollectionsObject();
        $parent_sid = !empty($parent)
            ? $collections->getBackendIdForFolderUid($parent)
            : $parent;
        $folderid = $collections->getBackendIdForFolderUid($uid);
        $change = array();
        $change['id'] = $uid;
        $this->_as->driver->deleteFolder($folderid, $parent_sid);
        $this->_state->updateState(
            Horde_ActiveSync::CHANGE_TYPE_DELETE,
            $change,
            Horde_ActiveSync::CHANGE_ORIGIN_NA);
    }

    /**
     *  Check if this change conflicts with server changes
     *  This is only true in the following situations:
     *
     *    Changed here and changed there
     *    Changed here and deleted there
     *    Deleted here and changed there
     *
     * Any other combination of operations can be done
     * (e.g. change flags & move or move & delete)
     *
     * @param string $type      The type of change('change', 'delete' etc...)
     * @param string $folderid  The id of the folder this change is from.
     * @param string $id        The uid for the changed message.
     *
     * @return boolean
     */
    protected function _isConflict($type, $folderid, $id)
    {
        $stat = $this->_as->driver->statMessage($folderid, $id);
        if (!$stat) {
            /* Message is gone, if type is change, this is a conflict */
            return $type == Horde_ActiveSync::CHANGE_TYPE_CHANGE;
        }

        return $this->_state->isConflict($stat, $type);
    }

}
