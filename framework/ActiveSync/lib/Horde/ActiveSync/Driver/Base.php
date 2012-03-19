<?php
/**
 * Horde_ActiveSync_Driver_Base::
 *
 * PHP 5
 *
 * @license   http://www.horde.org/licenses/gpl GPLv2
 * @copyright 2010-2012 Horde LLC (http://www.horde.org/)
 * @author Michael J Rubinsky <mrubinsk@horde.org>
 * @package ActiveSync
 */
/**
 * Base ActiveSync Driver backend. Provides communication with the actual
 * server backend that ActiveSync will be syncing devices with. This is an
 * abstract class, servers must implement their own backend to provide
 * the needed data.
 *
 * @license   http://www.horde.org/licenses/gpl GPLv2
 * @copyright 2010-2012 Horde LLC (http://www.horde.org/)
 * @author Michael J Rubinsky <mrubinsk@horde.org>
 * @package ActiveSync
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
     * Currently supported settings are:
     *  - pin: (boolean) Device must have a pin lock enabled.
     *         DEFAULT: true (Device must have a PIN lock enabled).
     *
     *  - AEFrequencyValue: (integer) Time (in minutes) of inactivity before
     *                                device locks.
     *         DEFAULT: none (Device will not be force locked by EAS).
     *
     *  - DeviceWipeThreshold: (integer) Number of failed unlock attempts before
     *                                   the device should wipe on devices that
     *                                   support this.
     *         DEFAULT: none (Device will not be auto wiped by EAS).
     *
     *  - CodewordFrequency: (integer)  Number of failed unlock attempts before
     *                                  needing to verify that a person who can
     *                                  read and write is using the device.
     *         DEFAULT: 0
     *
     *  - MinimumPasswordLength: (integer)  Minimum length of PIN
     *         DEFAULT: 5
     *
     *  - PasswordComplexity: (integer)   0 - alphanumeric, 1 - numeric, 2 - anything
     *         DEFAULT: 2 (anything the device supports).
     */
    protected $_policies = array(
        'pin'               => true,
        'extended_policies' => true,
        'inactivity'        => 5,
        'wipethreshold'     => 10,
        'codewordfrequency' => 0,
        'minimumlength'     => 5,
        'complexity'        => 2,
    );

    /**
     * The state driver for this request. Needs to be injected into this class.
     *
     * @var Horde_ActiveSync_State_Base
     */
    protected $_stateDriver;

    /**
     * Const'r
     *
     * @param array $params  Any configuration parameters or injected objects
     *                       the concrete driver may need.
     *  - logger: (Horde_Log_Logger) The logger.
     *            DEFAULT: none (No logging).
     *
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

        $this->_stateDriver = $params['state'];
        $this->_stateDriver->setLogger($this->_logger);
        $this->_stateDriver->setBackend($this);

        /* Override any security policies */
        if (!empty($params['policies'])) {
            $this->_policies = array_merge($this->_policies, $params['policies']);
        }
    }

    /**
     * Prevent circular dependency issues.
     */
    public function __destruct()
    {
        unset($this->_stateDriver);
        unset($this->_logger);
    }

    /**
     * Setter for the logger instance
     *
     * @param Horde_Log_Logger $logger  The logger
     */
    public function setLogger(Horde_Log_Logger $logger)
    {
        $this->_logger = $logger;
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
    public function logOff()
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
     * Get the full folder hierarchy from the backend.
     *
     * @return array
     */
    public function getHierarchy()
    {
        $folders = array();

        // @TODO, use self::getFolders() once we bump the min required version
        $fl = $this->getFolderList();
        foreach ($fl as $f) {
            $folders[] = $this->getFolder($f['id']);
        }

        return $folders;
    }

    /**
     * Obtain a message from the backend.
     *
     * @param string $folderid
     * @param string $id
     * @param ?? $mimesupport  (Not sure what this was supposed to do)
     *
     * @return Horde_ActiveSync_Message_Base The message data
     */
    public function fetch($folderid, $id, $mimesupport = 0)
    {
        // Forces entire message (up to 1Mb)
        return $this->getMessage($folderid, $id, 1024 * 1024, $mimesupport);
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
     * Delete a folder on the server.
     *
     * @param string $parent  The parent folder.
     * @param string $id      The folder to delete.
     *
     * @return boolean
     * @throws Horde_ActiveSync_Exception
     */
    public function deleteFolder($parent, $id)
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
    public function setReadFlag($folderid, $id, $flags)
    {
        return false;
    }

    /**
     * Change the name and/or type of a folder.
     *
     * @param string $parent
     * @param string $id
     * @param string $displayname
     * @param string $type
     *
     * @return boolean
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
    public function moveMessage($folderid, $id, $newfolderid)
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
    public function meetingResponse($requestid, $folderid, $error, &$calendarid)
    {
        throw new Horde_ActiveSync_Exception('meetingResponse not yet implemented.');
    }

    /**
     * Returns array of items which contain contact information
     *
     * @param string $query
     * @param string $range
     *
     * @return array
     */
    public function getSearchResults($query, $range)
    {
        throw new Horde_ActiveSync_Exception('getSearchResults not implemented.');
    }

    /**
     * Specifies if this driver has an alternate way of checking for changes
     * when PING is used.
     *
     * @return boolean
     */
    public function alterPing()
    {
        return false;
    }

    /**
     * Build a <wap-provisioningdoc> for the given security settings provided
     * by the backend.
     *
     * 4131 (Enforce password on device) 0: enabled 1: disabled
     * AEFrequencyType 0: no inactivity time 1: inactivity time is set
     * AEFrequencyValue inactivity time in minutes
     * DeviceWipeThreshold after how many wrong password to device should get wiped
     * CodewordFrequency validate every x wrong passwords, that a person is using the device which is able to read and write. should be half of DeviceWipeThreshold
     * MinimumPasswordLength minimum password length
     * PasswordComplexity 0: Require alphanumeric 1: Require only numeric, 2: anything goes
     *
     * @param string  The type of policy to return.
     *
     * @return string
     */
    public function getCurrentPolicy($policyType = 'MS-WAP-Provisioning-XML')
    {
        $xml = '<wap-provisioningdoc><characteristic type="SecurityPolicy">'
            . '<parm name="4131" value="' . ($this->_policies['pin'] ? 0 : 1) . '"/>'
            . '</characteristic>';

        if ($this->_policies['pin'] && $this->_policies['extended_policies']) {
            $xml .= '<characteristic type="Registry">'
            .   '<characteristic type="HKLM\Comm\Security\Policy\LASSD\AE\{50C13377-C66D-400C-889E-C316FC4AB374}">'
            .        '<parm name="AEFrequencyType" value="' . (!empty($this->_policies['inactivity']) ? 1 : 0) . '"/>'
            .        (!empty($this->_policies['AEFrequencyValue']) ? '<parm name="AEFrequencyValue" value="' . $this->_policies['inactivity'] . '"/>' : '')
            .    '</characteristic>';

            if (!empty($this->_policies['wipethreshold'])) {
                $xml .= '<characteristic type="HKLM\Comm\Security\Policy\LASSD"><parm name="DeviceWipeThreshold" value="' . $this->_policies['wipethreshold'] . '"/></characteristic>';
            }
            if (!empty($this->_policies['codewordfrequency'])) {
                $xml .= '<characteristic type="HKLM\Comm\Security\Policy\LASSD"><parm name="CodewordFrequency" value="' . $this->_policies['codewordfrequency'] . '"/></characteristic>';
            }
            if (!empty($this->_policies['minimumlength'])) {
                $xml .= '<characteristic type="HKLM\Comm\Security\Policy\LASSD\LAP\lap_pw"><parm name="MinimumPasswordLength" value="' . $this->_policies['minimumlength'] . '"/></characteristic>';
            }
            if ($this->_policies['complexity'] !== false) {
                $xml .= '<characteristic type="HKLM\Comm\Security\Policy\LASSD\LAP\lap_pw"><parm name="PasswordComplexity" value="' . $this->_policies['complexity'] . '"/></characteristic>';
            }
            $xml .= '</characteristic>';
        }

        $xml .= '</wap-provisioningdoc>';

        return $xml;
    }

    /**
     * Get folder stat
     *
     * @param string $id  The folder server id.
     *
     * @return array  An array defined like:
     *<pre>
     *  -id      The server ID that will be used to identify the folder.
     *           It must be unique, and not too long. How long exactly is not
     *           known, but try keeping it under 20 chars or so.
     *           It must be a string.
     *  -parent  The server ID of the parent of the folder. Same restrictions
     *           as 'id' apply.
     *  -mod     This is the modification signature. It is any arbitrary string
     *           which is constant as long as the folder has not changed. In
     *           practice this means that 'mod' can be equal to the folder name
     *           as this is the only thing that ever changes in folders.
     *</pre>
     */
    abstract public function statFolder($id);

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
     * @since TODO
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
     * @param string $folderid      The server's folder id this message is from
     * @param string $id            The server's message id
     * @param integer $truncsize    A TRUNCATION_* constant
     * @param integer $mimesupport  Mime support for this message
     *
     * @return Horde_ActiveSync_Message_Base The message data
     */
    abstract public function getMessage($folderid, $id, $truncsize, $mimesupport = 0);

    /**
     * Delete a message
     *
     * @param string $folderId  Folder id
     * @param string $id        Message id
     */
    abstract public function deleteMessage($folderid, $id);

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
    abstract public function changeMessage($folderid, $id, Horde_ActiveSync_Message_Base $message, $device);

    /**
     * Sends the email represented by the rfc822 string received by the PIM.
     *
     * @param string $rfc822    The rfc822 mime message
     * @param boolean $forward  Is this a message forward?
     * @param boolean $reply    Is this a reply?
     * @param boolean $parent   Parent message in thread.
     *
     * @return boolean
     */
    abstract function sendMail($rfc822, $forward = false, $reply = false, $parent = false);

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
    abstract public function getAttachment($name);

    /**
     * Build a stat structure for an email message.
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

}
