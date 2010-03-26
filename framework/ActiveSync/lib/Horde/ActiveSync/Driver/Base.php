<?php
/**
 * Base class for ActiveSync backends. Provides the communication between
 * the ActiveSync classes and the actual backend data that is being sync'd.
 *
 * Also responsible for providing objects to the command objects that can
 * generate the delta between the PIM and server.
 *
 * Based, in part, on code by the Z-Push project. Original copyright notices
 * appear below.
 *
 * Copyright 2010 The Horde Project (http://www.horde.org)
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Horde_ActiveSync
 */
/**
 * File      :   diffbackend.php
 * Project   :   Z-Push
 * Descr     :   We do a standard differential
 *               change detection by sorting both
 *               lists of items by their unique id,
 *               and then traversing both arrays
 *               of items at once. Changes can be
 *               detected by comparing items at
 *               the same position in both arrays.
 *
 * Created   :   01.10.2007
 *
 * Zarafa Deutschland GmbH, www.zarafaserver.de
 * This file is distributed under GPL v2.
 * Consult LICENSE file for details
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
     * The state object for this request. Needs to be injected into this class.
     * Different Sync objects may require more then one type of stateObject.
     * For instance, Horde can sync contacts and caledar data with a history
     * based state engine, but cannot due the same for email.
     *
     * @var Horde_ActiveSync_State_Base
     */
    protected $_stateObject;

    /**
     * Const'r
     *
     * @param array $params  Any configuration parameters or injected objects
     *                       the concrete driver may need.
     *  <pre>
     *     (optional) logger       Horde_Log_Logger instance
     *     (required) state_basic  A Horde_ActiveSync_State_Base object that is
     *                             capable of handling all collections except
     *                             email.
     *     (optional) state_email  A Horde_ActiveSync_State_Base object that is
     *                             capable of handling email collections.
     *  </pre>
     *
     * @return Horde_ActiveSync_Driver
     */
    public function __construct($params = array())
    {
        $this->_params = $params;

        if (empty($params['state_basic']) ||
            !($params['state_basic'] instanceof Horde_ActiveSync_State_Base)) {

            throw new Horde_ActiveSync_Exception('Missing required state object');
        }

        // Create a stub if we don't have a useable logger.
        if (isset($params['logger'])
            && is_callable(array($params['logger'], 'log'))) {
            $this->_logger = $params['logger'];
            unset($params['logger']);
        } else {
            $this->_logger = new Horde_Support_Stub;
        }

        $this->_stateObject = $params['state_basic'];
        $this->_stateObject->setLogger($this->_logger);
        $this->_stateObject->setBackend($this);
    }

    /**
     * Setter for the logger instance
     *
     * @param Horde_Log_Logger $logger  The logger
     *
     * @void
     */
    public function setLogger(Horde_Log_Logger $logger)
    {
        $this->_logger = $logger;
    }

    /**
     * Get folder stat
     *  "id" => The server ID that will be used to identify the folder.
     *          It must be unique, and not too long. How long exactly is not
     *          known, but try keeping it under 20 chars or so.
     *          It must be a string.
     *  "parent" => The server ID of the parent of the folder. Same restrictions
     *              as 'id' apply.
     *  "mod" => This is the modification signature. It is any arbitrary string
     *           which is constant as long as the folder has not changed. In
     *           practice this means that 'mod' can be equal to the folder name
     *           as this is the only thing that ever changes in folders.
     */
    abstract public function statFolder($id);

    /**
     * Get a folder from the backend
     *
     * To be implemented by concrete backend driver.
     */
    abstract public function getFolder($id);

    /**
     * Get the list of folders from the backend.
     */
    abstract public function getFolderList();

    /**
     * Get a full list of messages on the server
     *
     * @param string $folderId       The folder id
     * @param timestamp $cutOffDate  The timestamp of the earliest date for
     *                               calendar or mail entries
     *
     * @return array  A list of messages
     */
    abstract public function GetMessageList($folderId, $cutOffDate);

    /**
     * Get a list of server changes that occured during the specified time
     * period.
     *
     * @param string $folderId    The server id of the collection to check.
     * @param timestamp $from_ts  The starting timestamp
     * @param timestamp $to_ts    The ending timestamp
     *
     * @return array A list of messge uids that have chnaged in the specified
     *               time period.
     */
    abstract public function getServerChanges($folderId, $from_ts, $to_ts);

    /**
     * Get a message stat.
     *
     * @param string $folderId  The folder id
     * @param string $id        The message id (??)
     *
     * @return hash with 'id', 'mod', and 'flags' members
     */
    abstract public function StatMessage($folderId, $id);

    /**
     *
     * @param $folderid
     * @param $id
     * @param $truncsize
     * @param $mimesupport
     *
     * @return Horde_ActiveSync_Message_Base The message data
     */
    abstract public function GetMessage($folderid, $id, $truncsize, $mimesupport = 0);

    /**
     * Delete a message
     *
     * @param string $folderId  Folder id
     * @param string $id        Message id
     *
     * @return boolean
     */
    abstract public function DeleteMessage($folderid, $id);

    /**
     * Change (i.e. add or edit) a message on the backend
     *
     * @param string $folderId  Folderid
     * @param string $id        Message id (maybe reorder parameteres since this may be null)
     * @param Horde_ActiveSync_Message_Base $message
     *
     * @return a stat array of the new message
     */
    abstract public function ChangeMessage($folderid, $id, $message);

    /**
     * Any code needed to authenticate to backend as the actual user.
     *
     * @param string $username  The username to authenticate as
     * @param string $password  The password
     * @param string $domain    The user domain
     *
     * @return boolean
     */
    public function logon($username, $password, $domain = null)
    {
        $this->_authUser = $username;
        $this->_authPass = $password;

        return true;
    }

    /**
     * Any code to run on log off
     *
     * @return boolean
     */
    public function Logoff()
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
     * Return the helper for importing hierarchy changes from the PIM.
     *
     * @TODO: Probably not functional, as methods were missing from original
     * codebase.
     *
     * @return Horde_ActiveSync_DiffState_ImportHierarchy
     */
    public function GetHierarchyImporter()
    {
        $importer = new Horde_ActiveSync_DiffState_ImportHierarchy($this);
        $importer->setLogger($this->_logger);

        return $importer;
    }

    /**
     * Return the helper for importing message changes from the PIM.
     *
     * @param string $folderid
     *
     * @return Horde_ActiveSync_DiffState_ImportContents
     */
    public function GetContentsImporter($folderId)
    {
        $importer = new Horde_ActiveSync_DiffState_ImportContents($this, $folderId);
        $importer->setLogger($this->_logger);

        return $importer;
    }

    /**
     * @TODO: This will replace the above two methods
     * @return Horde_ActiveSync_Importer
     */
    public function getImporter()
    {
        $importer = new Horde_ActiveSync_Importer($this);
        //$importer->setLogger($this->_logger);
        return $importer;
    }

    /**
     * Return helper for performing the actual sync operation.
     *
     * @param string $folderId
     * @return unknown_type
     *
     */
    public function getExporter()
    {
        $exporter = new Horde_ActiveSync_Exporter($this);
        $exporter->setLogger($this->_logger);

        return $exporter;
    }

    /**
     * Will (eventually) return an appropriate state object based on the class
     * being sync'd.
     * @param <type> $collection
     */
    public function &getStateObject($collection = array())
    {
        $this->_stateObject->init($collection);
        $this->_stateObject->setLogger($this->_logger);
        return $this->_stateObject;
    }

    /**
     * Get the full folder hierarchy from the backend.
     *
     * @return array
     */
    public function GetHierarchy()
    {
        $folders = array();

        $fl = $this->getFolderList();
        foreach ($fl as $f) {
            $folders[] = $this->getFolder($f['id']);
        }

        return $folders;
    }

    /**
     * Obtain a message from the backend.
     *
     * @TODO: Not sure why we have this *and* GetMessage()??
     *
     * @param string $folderid
     * @param string $id
     * @param ?? $mimesupport  (Not sure what this was supposed to do)
     *
     * @return Horde_ActiveSync_Message_Base The message data
     */
    public function Fetch($folderid, $id, $mimesupport = 0)
    {
        // Forces entire message (up to 1Mb)
        return $this->GetMessage($folderid, $id, 1024 * 1024, $mimesupport);
    }

    /**
     *
     * @param $attname
     * @return unknown_type
     */
    public function GetAttachmentData($attname)
    {
        return false;
    }

    /**
     * @param $rfc822
     * @param $forward
     * @param $reply
     * @param $parent
     * @return unknown_type
     */
    public function SendMail($rfc822, $forward = false, $reply = false, $parent = false)
    {
        return true;
    }

    /**
     * @return unknown_type
     */
    public function GetWasteBasket()
    {
        return false;
    }

    /**
     * @TODO: Missing method from Z-Push
     *
     * @param $parent
     * @param $id
     * @return unknown_type
     */
    public function DeleteFolder($parent, $id)
    {
        throw new Horde_ActiveSync_Exception('DeleteFolder not yet implemented');
    }

    /**
     *
     * @param $folderid
     * @param $id
     * @param $flags
     * @return unknown_type
     */
    function SetReadFlag($folderid, $id, $flags)
    {
        return false;
    }

    /**
     * @TODO: This method was missing from Z-Push
     *
     * @param unknown_type $parent
     * @param unknown_type $id
     * @param unknown_type $displayname
     * @param unknown_type $type
     * @return unknown_type
     */
    public function changeFolder($parent, $id, $displayname, $type)
    {
        throw new Horde_ActiveSync_Exception('changeFolder not yet implemented.');
    }

    /**
     * @todo
     *
     * @param $folderid
     * @param $id
     * @param $newfolderid
     * @return unknown_type
     */
    public function MoveMessage($folderid, $id, $newfolderid)
    {
        throw new Horde_ActiveSync_Exception('moveMessage not yet implemented.');
    }

    /**
     * @todo
     *
     * @param $requestid
     * @param $folderid
     * @param $error
     * @param $calendarid
     * @return unknown_type
     */
    public function MeetingResponse($requestid, $folderid, $error, &$calendarid)
    {
        throw new Horde_ActiveSync_Exception('meetingResponse not yet implemented.');
    }

    /**
     * Returns array of items which contain contact information
     *
     * @param string $searchquery
     *
     * @return array
     */
    public function getSearchResults($searchquery)
    {
        throw new Horde_ActiveSync_Exception('getSearchResults not implemented.');
    }

    /**
     * Checks if the sent policykey matches the latest policykey on the server
     * TODO: Revisit this once we have refactored state storage
     * @param string $policykey
     * @param string $devid
     *
     * @return status flag
     */
    public function CheckPolicy($policyKey, $devId)
    {
        $status = SYNC_PROVISION_STATUS_SUCCESS;
//        $user_policykey = $this->getPolicyKey($this->_authUser, $this->_authPass, $devId);
//        if ($user_policykey != $policyKey) {
//            $status = SYNC_PROVISION_STATUS_POLKEYMISM;
//        }
//
        return $status;
    }

    /**
     * Return a policy key for given user with a given device id.
     * If there is no combination user-deviceid available, a new key
     * should be generated.
     *
     * @param string $user
     * @param string $pass
     * @param string $devid
     *
     * @return unknown
     */
    public function getPolicyKey($user, $pass, $devid)
    {
        return false;
    }

    /**
     * Generate a random policy key. Right now it's a 10-digit number.
     *
     * @return unknown
     */
    public function generatePolicyKey()
    {
        return mt_rand(1000000000, 9999999999);
    }

    /**
     * Set a new policy key for the given device id.
     *
     * @param string $policykey
     * @param string $devid
     * @return unknown
     */
    public function setPolicyKey($policykey, $devid)
    {
        return false;
    }

    /**
     * Return a device wipe status
     *
     * @param string $user
     * @param string $pass
     * @param string $devid
     * @return int
     */
    public function getDeviceRWStatus($devid)
    {
        return false;
    }

    /**
     * Set a new rw status for the device
     *
     * @param string $user
     * @param string $pass
     * @param string $devid
     * @param string $status
     *
     * @return boolean
     */
    public function setDeviceRWStatus($devid, $status)
    {
        return false;
    }

    /**
     *
     * @return unknown_type
     */
    public function AlterPing()
    {
        return false;
    }

    public function AlterPingChanges($folderid, &$syncstate)
    {
        return array();
    }

}