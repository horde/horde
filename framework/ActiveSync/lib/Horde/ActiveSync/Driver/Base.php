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
     * Secuirity Policies. These settings can be overridden by the backend
     * provider by passing in a 'policies' key in the const'r params array. This
     * way the server can provide user-specific policies.
     *
     * <pre>
     * Currently supported settings are:
     *   requirePin      - Device must have a pin lock enabled.
     *   computerUnlock  - Device can be unlocked by a computer.
     *   AEFrequencyType - AEFrequencyValue is set (1) or not (0)
     *   AEFrequencyValue - Time (in minutes) of inactivity before device locks
     *   DeviceWipeThreshold - Number of failed unlock attempts before the
     *                         device should wipe on devices that support this.
     *   CodewordFrequency   - Number of failed unlock attempts before needing
     *                         to verify that a person who can read and write is
     *                         using the PIM.
     *   MinimumPasswordLength
     *   PasswordComplexity     - 0 - alphanumeric, 1 - numeric, 2 - anything
     * </pre>
     */
    protected $_policies = array(
        'requirePin' => true,
        'computerUnlock' => true,
        'AEFrequencyType' => 1,
        'AEFrequencyValue' => 5,
        'DeviceWipeThreshold' => 10,
        'CodewordFrequency' => 5,
        'MinimumPasswordLength' => 5,
        'PasswordComplexity' => 2,
        );

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

        /* Create a stub if we don't have a useable logger. */
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

        /* Override any security policies */
        if (!empty($params['policies'])) {
            array_merge($this->_policies, $params['policies']);
        }
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
     * Get the username for this request.
     *
     * @return string  The current username
     */
    public function getUser()
    {
        return $this->_authUser;
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
     *
     * @param array $collection
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

    /**
     * Build a <wap-provisioningdoc> for the given security settings provided
     * by the backend.
     *
     * 4131 (Enforce password on device) 0: enabled 1: disabled
     * 4133 (Unlock from computer) 0: disabled 1: enabled
     * AEFrequencyType 0: no inactivity time 1: inactivity time is set
     * AEFrequencyValue inactivity time in minutes
     * DeviceWipeThreshold after how many worng password to device should get wiped
     * CodewordFrequency validate every 3 wrong passwords, that a person is using the device which is able to read and write. should be half of DeviceWipeThreshold
     * MinimumPasswordLength minimum password length
     * PasswordComplexity 0: Require alphanumeric 1: Require only numeric, 2: anything goes
     *
     * @param string  The type of policy to return.
     *
     * @return string
     */
    public function getCurrentPolicy($policyType = 'MS-WAP-Provisioning-XML')
    {
        return '<wap-provisioningdoc><characteristic type="SecurityPolicy">'
        . '<parm name="4131" value="' . ($this->_policies['requirePin'] ? 0 : 1) . '"/>'
        . '<parm name="4133" value="' . ($this->_policies['computerUnlock'] ? 1 : 0) . '"/>'
        . '</characteristic>'
        . '<characteristic type="Registry">'
        .   '<characteristic type="HKLM\Comm\Security\Policy\LASSD\AE\{50C13377-C66D-400C-889E-C316FC4AB374}">'
        .        '<parm name="AEFrequencyType" value="' . $this->_policies['AEFrequencyType'] . '"/>'
        .        (!empty($this->_policies['AEFrequencyValue']) ? '<parm name="AEFrequencyValue" value="' . $this->_policies['AEFrequencyValue'] . '"/>' : '')
        .    '</characteristic>'
        .    '<characteristic type="HKLM\Comm\Security\Policy\LASSD">'
        .        '<parm name="DeviceWipeThreshold" value="' . $this->_policies['DeviceWipeThreshold'] . '"/>'
        .    '</characteristic>'
        .    '<characteristic type="HKLM\Comm\Security\Policy\LASSD">'
        .        '<parm name="CodewordFrequency" value="' . $this->_policies['CodewordFrequency'] . '"/>'
        .    '</characteristic>'
        .    '<characteristic type="HKLM\Comm\Security\Policy\LASSD\LAP\lap_pw">'
        .        '<parm name="MinimumPasswordLength" value="' . $this->_policies['MinimumPasswordLength'] . '"/>'
        .    '</characteristic>'
        .    '<characteristic type="HKLM\Comm\Security\Policy\LASSD\LAP\lap_pw">'
        .        '<parm name="PasswordComplexity" value="' . $this->_policies['PasswordComplexity'] . '"/>'
        .    '</characteristic>'
        . '</characteristic>'
        . '</wap-provisioningdoc>';
    }

}