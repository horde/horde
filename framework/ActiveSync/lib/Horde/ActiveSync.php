<?php
/**
 * Horde_ActiveSync::
 *
 * @license   http://www.horde.org/licenses/gpl GPLv2
 *            NOTE: According to sec. 8 of the GENERAL PUBLIC LICENSE (GPL),
 *            Version 2, the distribution of the Horde_ActiveSync module in or
 *            to the United States of America is excluded from the scope of this
 *            license.
 * @copyright 2009-2014 Horde LLC (http://www.horde.org)
 * @author    Michael J Rubinsky <mrubinsk@horde.org>
 * @package   ActiveSync
 */
/**
 * Horde_ActiveSync:: The Horde ActiveSync server. Entry point for performing
 * all ActiveSync operations.
 *
 * @license   http://www.horde.org/licenses/gpl GPLv2
 *            NOTE: According to sec. 8 of the GENERAL PUBLIC LICENSE (GPL),
 *            Version 2, the distribution of the Horde_ActiveSync module in or
 *            to the United States of America is excluded from the scope of this
 *            license.
 * @copyright 2009-2014 Horde LLC (http://www.horde.org)
 * @author    Michael J Rubinsky <mrubinsk@horde.org>
 * @package   ActiveSync
 *
 * @property-read Horde_ActiveSync_Wbxml_Encoder $encoder The Wbxml encoder.
 * @property-read Horde_ActiveSync_Wbxml_Decoder $decoder The Wbxml decoder.
 * @property-read Horde_ActiveSync_State_Base $state      The state object.
 * @property-read Horde_Controller_Reqeust_Http $request  The HTTP request object.
 * @property-read Horde_ActiveSync_Driver_Base $driver    The backend driver object.
 * @property-read boolean|string $provisioning Provisioning support: True, False, or 'loose'
 * @property-read boolean $multipart Indicate this is a multipart request.
 * @property-read string $certPath Local path to the certificate bundle.
 * @property-read Horde_ActiveSync_Device $device  The current device object.
 * @property-read Horde_Log_Logger $logger   The logger object.
 */
class Horde_ActiveSync
{
    /* Conflict resolution */
    const CONFLICT_OVERWRITE_SERVER             = 0;
    const CONFLICT_OVERWRITE_PIM                = 1;

    /* TRUNCATION Constants */
    const TRUNCATION_ALL                        = 0;
    const TRUNCATION_1                          = 1;
    const TRUNCATION_2                          = 2;
    const TRUNCATION_3                          = 3;
    const TRUNCATION_4                          = 4;
    const TRUNCATION_5                          = 5;
    const TRUNCATION_6                          = 6;
    const TRUNCATION_7                          = 7;
    const TRUNCATION_8                          = 8;
    const TRUNCATION_NONE                       = 9;

    /* FOLDERHIERARCHY */
    const FOLDERHIERARCHY_FOLDERS               = 'FolderHierarchy:Folders';
    const FOLDERHIERARCHY_FOLDER                = 'FolderHierarchy:Folder';
    const FOLDERHIERARCHY_DISPLAYNAME           = 'FolderHierarchy:DisplayName';
    const FOLDERHIERARCHY_SERVERENTRYID         = 'FolderHierarchy:ServerEntryId';
    const FOLDERHIERARCHY_PARENTID              = 'FolderHierarchy:ParentId';
    const FOLDERHIERARCHY_TYPE                  = 'FolderHierarchy:Type';
    const FOLDERHIERARCHY_RESPONSE              = 'FolderHierarchy:Response';
    const FOLDERHIERARCHY_STATUS                = 'FolderHierarchy:Status';
    const FOLDERHIERARCHY_CONTENTCLASS          = 'FolderHierarchy:ContentClass';
    const FOLDERHIERARCHY_CHANGES               = 'FolderHierarchy:Changes';
    const FOLDERHIERARCHY_SYNCKEY               = 'FolderHierarchy:SyncKey';
    const FOLDERHIERARCHY_FOLDERSYNC            = 'FolderHierarchy:FolderSync';
    const FOLDERHIERARCHY_COUNT                 = 'FolderHierarchy:Count';
    const FOLDERHIERARCHY_VERSION               = 'FolderHierarchy:Version';

    /* SYNC */
    const SYNC_SYNCHRONIZE                      = 'Synchronize';
    const SYNC_REPLIES                          = 'Replies';
    const SYNC_ADD                              = 'Add';
    const SYNC_MODIFY                           = 'Modify';
    const SYNC_REMOVE                           = 'Remove';
    const SYNC_FETCH                            = 'Fetch';
    const SYNC_SYNCKEY                          = 'SyncKey';
    const SYNC_CLIENTENTRYID                    = 'ClientEntryId';
    const SYNC_SERVERENTRYID                    = 'ServerEntryId';
    const SYNC_STATUS                           = 'Status';
    const SYNC_FOLDER                           = 'Folder';
    const SYNC_FOLDERTYPE                       = 'FolderType';
    const SYNC_VERSION                          = 'Version';
    const SYNC_FOLDERID                         = 'FolderId';
    const SYNC_GETCHANGES                       = 'GetChanges';
    const SYNC_MOREAVAILABLE                    = 'MoreAvailable';
    const SYNC_WINDOWSIZE                       = 'WindowSize';
    const SYNC_COMMANDS                         = 'Commands';
    const SYNC_OPTIONS                          = 'Options';
    const SYNC_FILTERTYPE                       = 'FilterType';
    const SYNC_TRUNCATION                       = 'Truncation';
    const SYNC_RTFTRUNCATION                    = 'RtfTruncation';
    const SYNC_CONFLICT                         = 'Conflict';
    const SYNC_FOLDERS                          = 'Folders';
    const SYNC_DATA                             = 'Data';
    const SYNC_DELETESASMOVES                   = 'DeletesAsMoves';
    const SYNC_NOTIFYGUID                       = 'NotifyGUID';
    const SYNC_SUPPORTED                        = 'Supported';
    const SYNC_SOFTDELETE                       = 'SoftDelete';
    const SYNC_MIMESUPPORT                      = 'MIMESupport';
    const SYNC_MIMETRUNCATION                   = 'MIMETruncation';
    const SYNC_NEWMESSAGE                       = 'NewMessage';
    const SYNC_PARTIAL                          = 'Partial';
    const SYNC_WAIT                             = 'Wait';
    const SYNC_LIMIT                            = 'Limit';
    // 14
    const SYNC_HEARTBEATINTERVAL                = 'HeartbeatInterval';
    const SYNC_CONVERSATIONMODE                 = 'ConversationMode';
    const SYNC_MAXITEMS                         = 'MaxItems';

    /* Document library */
    const SYNC_DOCUMENTLIBRARY_LINKID           = 'DocumentLibrary:LinkId';
    const SYNC_DOCUMENTLIBRARY_DISPLAYNAME      = 'DocumentLibrary:DisplayName';
    const SYNC_DOCUMENTLIBRARY_ISFOLDER         = 'DocumentLibrary:IsFolder';
    const SYNC_DOCUMENTLIBRARY_CREATIONDATE     = 'DocumentLibrary:CreationDate';
    const SYNC_DOCUMENTLIBRARY_LASTMODIFIEDDATE = 'DocumentLibrary:LastModifiedDate';
    const SYNC_DOCUMENTLIBRARY_ISHIDDEN         = 'DocumentLibrary:IsHidden';
    const SYNC_DOCUMENTLIBRARY_CONTENTLENGTH    = 'DocumentLibrary:ContentLength';
    const SYNC_DOCUMENTLIBRARY_CONTENTTYPE      = 'DocumentLibrary:ContentType';

    /* AIRSYNCBASE */
    const AIRSYNCBASE_BODYPREFERENCE            = 'AirSyncBase:BodyPreference';
    const AIRSYNCBASE_TYPE                      = 'AirSyncBase:Type';
    const AIRSYNCBASE_TRUNCATIONSIZE            = 'AirSyncBase:TruncationSize';
    const AIRSYNCBASE_ALLORNONE                 = 'AirSyncBase:AllOrNone';
    const AIRSYNCBASE_BODY                      = 'AirSyncBase:Body';
    const AIRSYNCBASE_DATA                      = 'AirSyncBase:Data';
    const AIRSYNCBASE_ESTIMATEDDATASIZE         = 'AirSyncBase:EstimatedDataSize';
    const AIRSYNCBASE_TRUNCATED                 = 'AirSyncBase:Truncated';
    const AIRSYNCBASE_ATTACHMENTS               = 'AirSyncBase:Attachments';
    const AIRSYNCBASE_ATTACHMENT                = 'AirSyncBase:Attachment';
    const AIRSYNCBASE_DISPLAYNAME               = 'AirSyncBase:DisplayName';
    const AIRSYNCBASE_FILEREFERENCE             = 'AirSyncBase:FileReference';
    const AIRSYNCBASE_METHOD                    = 'AirSyncBase:Method';
    const AIRSYNCBASE_CONTENTID                 = 'AirSyncBase:ContentId';
    const AIRSYNCBASE_CONTENTLOCATION           = 'AirSyncBase:ContentLocation';
    const AIRSYNCBASE_ISINLINE                  = 'AirSyncBase:IsInline';
    const AIRSYNCBASE_NATIVEBODYTYPE            = 'AirSyncBase:NativeBodyType';
    const AIRSYNCBASE_CONTENTTYPE               = 'AirSyncBase:ContentType';
    // 14.0
    const AIRSYNCBASE_PREVIEW                   = 'AirSyncBase:Preview';
    // 14.1
    const AIRSYNCBASE_BODYPARTPREFERENCE        = 'AirSyncBase:BodyPartPreference';
    const AIRSYNCBASE_BODYPART                  = 'AirSyncBase:BodyPart';
    const AIRSYNCBASE_STATUS                    = 'AirSyncBase:Status';

    /* Body type prefs */
    const BODYPREF_TYPE_PLAIN                   = 1;
    const BODYPREF_TYPE_HTML                    = 2;
    const BODYPREF_TYPE_RTF                     = 3;
    const BODYPREF_TYPE_MIME                    = 4;

    /* PROVISION */
    const PROVISION_PROVISION                   =  'Provision:Provision';
    const PROVISION_POLICIES                    =  'Provision:Policies';
    const PROVISION_POLICY                      =  'Provision:Policy';
    const PROVISION_POLICYTYPE                  =  'Provision:PolicyType';
    const PROVISION_POLICYKEY                   =  'Provision:PolicyKey';
    const PROVISION_DATA                        =  'Provision:Data';
    const PROVISION_STATUS                      =  'Provision:Status';
    const PROVISION_REMOTEWIPE                  =  'Provision:RemoteWipe';
    const PROVISION_EASPROVISIONDOC             =  'Provision:EASProvisionDoc';

    /* Policy types */
    const POLICYTYPE_XML                        = 'MS-WAP-Provisioning-XML';
    const POLICYTYPE_WBXML                      = 'MS-EAS-Provisioning-WBXML';

    /* Flags */
    // @TODO: H6 Change this to CHANGE_TYPE_NEW
    const FLAG_NEWMESSAGE                       = 'NewMessage';

    /* Folder types */
    const FOLDER_TYPE_OTHER                     =  1;
    const FOLDER_TYPE_INBOX                     =  2;
    const FOLDER_TYPE_DRAFTS                    =  3;
    const FOLDER_TYPE_WASTEBASKET               =  4;
    const FOLDER_TYPE_SENTMAIL                  =  5;
    const FOLDER_TYPE_OUTBOX                    =  6;
    const FOLDER_TYPE_TASK                      =  7;
    const FOLDER_TYPE_APPOINTMENT               =  8;
    const FOLDER_TYPE_CONTACT                   =  9;
    const FOLDER_TYPE_NOTE                      =  10;
    const FOLDER_TYPE_JOURNAL                   =  11;
    const FOLDER_TYPE_USER_MAIL                 =  12;
    const FOLDER_TYPE_USER_APPOINTMENT          =  13;
    const FOLDER_TYPE_USER_CONTACT              =  14;
    const FOLDER_TYPE_USER_TASK                 =  15;
    const FOLDER_TYPE_USER_JOURNAL              =  16;
    const FOLDER_TYPE_USER_NOTE                 =  17;
    const FOLDER_TYPE_UNKNOWN                   =  18;
    const FOLDER_TYPE_RECIPIENT_CACHE           =  19;
    // @TODO, remove const definition in H6, not used anymore.
    const FOLDER_TYPE_DUMMY                     =  999999;

    /* Origin of changes **/
    const CHANGE_ORIGIN_PIM                     = 0;
    const CHANGE_ORIGIN_SERVER                  = 1;
    const CHANGE_ORIGIN_NA                      = 3;

    /* Remote wipe **/
    const RWSTATUS_NA                           = 0;
    const RWSTATUS_OK                           = 1;
    const RWSTATUS_PENDING                      = 2;
    const RWSTATUS_WIPED                        = 3;

    /* GAL **/
    const GAL_DISPLAYNAME                       = 'GAL:DisplayName';
    const GAL_PHONE                             = 'GAL:Phone';
    const GAL_OFFICE                            = 'GAL:Office';
    const GAL_TITLE                             = 'GAL:Title';
    const GAL_COMPANY                           = 'GAL:Company';
    const GAL_ALIAS                             = 'GAL:Alias';
    const GAL_FIRSTNAME                         = 'GAL:FirstName';
    const GAL_LASTNAME                          = 'GAL:LastName';
    const GAL_HOMEPHONE                         = 'GAL:HomePhone';
    const GAL_MOBILEPHONE                       = 'GAL:MobilePhone';
    const GAL_EMAILADDRESS                      = 'GAL:EmailAddress';
    // 14.1
    const GAL_PICTURE                           = 'GAL:Picture';
    const GAL_STATUS                            = 'GAL:Status';
    const GAL_DATA                              = 'GAL:Data';

    /* Request Type */
    const REQUEST_TYPE_SYNC                     = 'sync';
    const REQUEST_TYPE_FOLDERSYNC               = 'foldersync';

    /* Change Type */
    const CHANGE_TYPE_CHANGE                    = 'change';
    const CHANGE_TYPE_DELETE                    = 'delete';
    const CHANGE_TYPE_FLAGS                     = 'flags';
    const CHANGE_TYPE_MOVE                      = 'move';
    const CHANGE_TYPE_FOLDERSYNC                = 'foldersync';
    const CHANGE_TYPE_SOFTDELETE                = 'softdelete';

    /* Internal flags to indicate change is a change in reply/forward state */
    const CHANGE_REPLY_STATE                    = '@--reply--@';
    const CHANGE_REPLYALL_STATE                 = '@--replyall--@';
    const CHANGE_FORWARD_STATE                  = '@--forward--@';

    /* RM */
    const RM_SUPPORT                            = 'RightsManagement:RightsManagementSupport';
    const RM_TEMPLATEID                         = 'RightsManagement:TemplateId';

    /* Collection Classes */
    const CLASS_EMAIL                           = 'Email';
    const CLASS_CONTACTS                        = 'Contacts';
    const CLASS_CALENDAR                        = 'Calendar';
    const CLASS_TASKS                           = 'Tasks';
    const CLASS_NOTES                           = 'Notes';
    const CLASS_SMS                             = 'SMS';

    /* Filtertype constants */
    const FILTERTYPE_ALL                        = 0;
    const FILTERTYPE_1DAY                       = 1;
    const FILTERTYPE_3DAYS                      = 2;
    const FILTERTYPE_1WEEK                      = 3;
    const FILTERTYPE_2WEEKS                     = 4;
    const FILTERTYPE_1MONTH                     = 5;
    const FILTERTYPE_3MONTHS                    = 6;
    const FILTERTYPE_6MONTHS                    = 7;
    const FILTERTYPE_INCOMPLETETASKS            = 8;

    const PROVISIONING_FORCE                    = true;
    const PROVISIONING_LOOSE                    = 'loose';
    const PROVISIONING_NONE                     = false;

    const FOLDER_ROOT                           = 0;

    const VERSION_TWOFIVE                       = '2.5';
    const VERSION_TWELVE                        = '12.0';
    const VERSION_TWELVEONE                     = '12.1';
    const VERSION_FOURTEEN                      = '14.0';
    const VERSION_FOURTEENONE                   = '14.1';

    const MIME_SUPPORT_NONE                     = 0;
    const MIME_SUPPORT_SMIME                    = 1;
    const MIME_SUPPORT_ALL                      = 2;

    const IMAP_FLAG_REPLY                       = 'reply';
    const IMAP_FLAG_FORWARD                     = 'forward';

    /* Result Type */
    const RESOLVE_RESULT_GAL                    = 1;
    const RESOLVE_RESULT_ADDRESSBOOK            = 2;

    /* Auth failure reasons */
    const AUTH_REASON_USER_DENIED               = 'user';
    const AUTH_REASON_DEVICE_DENIED             = 'device';

    const LIBRARY_VERSION                       = '2.x.y-git';

    /**
     * Logger
     *
     * @var Horde_ActiveSync_Interface_LoggerFactory
     */
    protected $_loggerFactory;

    /**
     * The logger for this class.
     *
     * @var Horde_Log_Logger
     */
    static protected $_logger;

    /**
     * Provisioning support
     *
     * @var string
     */
    protected $_provisioning;

    /**
     * Highest version to support.
     *
     * @var float
     */
    protected $_maxVersion = self::VERSION_FOURTEENONE;

    /**
     * The actual version we are supporting.
     *
     * @var float
     */
    static protected $_version;

    /**
     * Multipart support?
     *
     * @var boolean
     */
    protected $_multipart = false;

    /**
     * Support gzip compression of certain data parts?
     *
     * @var boolean
     */
    protected $_compression = false;

    /**
     * Local cache of Get variables/decoded base64 uri
     *
     * @var array
     */
    protected $_get = array();

    /**
     * Path to root certificate bundle
     *
     * @var string
     */
    protected $_certPath;

    /**
     *
     * @var Horde_ActiveSync_Device
     */
    static protected $_device;

    /**
     * Wbxml encoder
     *
     * @var Horde_ActiveSync_Wbxml_Encoder
     */
    protected $_encoder;

    /**
     * Wbxml decoder
     *
     * @var Horde_ActiveSync_Wbxml_Decoder
     */
    protected $_decoder;

    /**
     * The singleton collections handler.
     *
     * @var Horde_ActiveSync_Collections
     */
    protected $_collectionsObj;

    /**
     * Global error flag.
     *
     * @var boolean
     */
    protected $_globalError = false;

    /**
     * Process id (used in logging).
     *
     * @var integer
     */
    protected $_procid;

    /**
     * Supported EAS versions.
     *
     * @var array
     */
    static protected $_supportedVersions = array(
        self::VERSION_TWOFIVE,
        self::VERSION_TWELVE,
        self::VERSION_TWELVEONE,
        self::VERSION_FOURTEEN,
        self::VERSION_FOURTEENONE
    );

    /**
     * Factory method for creating Horde_ActiveSync_Message objects.
     *
     * @param string $message  The message type.
     * @since 2.4.0
     *
     * @return Horde_ActiveSync_Message_Base   The concrete message object.
     * @todo For H6, move to Horde_ActiveSync_Message_Base::factory()
     */
    static public function messageFactory($message)
    {
        $class = 'Horde_ActiveSync_Message_' . $message;
        if (!class_exists($class)) {
            throw new InvalidArgumentException(sprintf('Class %s does not exist.', $class));
        }

        return new $class(array(
            'logger' => self::$_logger,
            'protocolversion' => self::$_version,
            'device' => self::$_device));
    }

    /**
     * Const'r
     *
     * @param Horde_ActiveSync_Driver_Base $driver      The backend driver.
     * @param Horde_ActiveSync_Wbxml_Decoder $decoder   The Wbxml decoder.
     * @param Horde_ActiveSync_Wbxml_Endcoder $encoder  The Wbxml encoder.
     * @param Horde_ActiveSync_State_Base $state        The state driver.
     * @param Horde_Controller_Request_Http $request    The HTTP request object.
     *
     * @return Horde_ActiveSync  The ActiveSync server object.
     */
    public function __construct(
        Horde_ActiveSync_Driver_Base $driver,
        Horde_ActiveSync_Wbxml_Decoder $decoder,
        Horde_ActiveSync_Wbxml_Encoder $encoder,
        Horde_ActiveSync_State_Base $state,
        Horde_Controller_Request_Http $request)
    {
        // The http request
        $this->_request = $request;

        // Backend driver
        $this->_driver = $driver;
        $this->_driver->setProtocolVersion($this->getProtocolVersion());

        // Device state manager
        $this->_state = $state;

        // Wbxml handlers
        $this->_encoder = $encoder;
        $this->_decoder = $decoder;

        $this->_procid = getmypid();
    }

    /**
     * Return a collections singleton.
     *
     * @return Horde_ActiveSync_Collections
     * @since 2.4.0
     */
    public function getCollectionsObject()
    {
        if (empty($this->_collectionsObj)) {
            $this->_collectionsObj = new Horde_ActiveSync_Collections($this->getSyncCache(), $this);
        }

        return $this->_collectionsObj;
    }

    /**
     * Return a new, fully configured SyncCache.
     *
     * @return Horde_ActiveSync_SyncCache
     * @since 2.4.0
     */
    public function getSyncCache()
    {
        return new Horde_ActiveSync_SyncCache(
            $this->_state,
            self::$_device->id,
            self::$_device->user,
            self::$_logger
        );
    }

    /**
     * Return an Importer object.
     *
     * @return Horde_ActiveSync_Connector_Importer
     * @since 2.4.0
     */
    public function getImporter()
    {
        $importer = new Horde_ActiveSync_Connector_Importer($this);
        $importer->setLogger(self::$_logger);

        return $importer;
    }

    /**
     * Authenticate to the backend.
     *
     * @param Horde_ActiveSync_Credentials $credentials  The credentials object.
     *
     * @return boolean  True on successful authentication to the backend.
     * @throws Horde_ActiveSync_Exception
     */
    public function authenticate(Horde_ActiveSync_Credentials $credentials)
    {
        if (!$credentials->username) {
            // No provided username or Authorization header.
            self::$_logger->notice(sprintf(
                '[%s] Client did not provide authentication data.',
                $this->_procid)
            );
            return false;
        }

        $user = $this->_driver->getUsernameFromEmail($credentials->username);
        $pos = strrpos($user, '\\');
        if ($pos !== false) {
            $domain = substr($user, 0, $pos);
            $user = substr($user, $pos + 1);
        } else {
            $domain = null;
        }

        // Authenticate
        if ($result = $this->_driver->authenticate($user, $credentials->password, $domain)) {
            if ($result === self::AUTH_REASON_USER_DENIED) {
                $this->_globalError = Horde_ActiveSync_Status::SYNC_NOT_ALLOWED;
            } elseif ($result === self::AUTH_REASON_DEVICE_DENIED) {
                $this->_globalError = Horde_ActiveSync_Status::DEVICE_BLOCKED_FOR_USER;
            } elseif ($result !== true) {
                $this->_globalError = Horde_ActiveSync_Status::DENIED;
            }
        } else {
            return false;
        }

        if (!$this->_driver->setup($user)) {
            return false;
        }

        return true;
    }

    /**
     * Allow to force the highest version to support.
     *
     * @param float $version  The highest version
     */
    public function setSupportedVersion($version)
    {
        $this->_maxVersion = $version;
    }

    /**
     * Set the local path to the root certificate bundle.
     *
     * @param string $path  The local path to the bundle.
     */
    public function setRootCertificatePath($path)
    {
        $this->_certPath = $path;
    }

    /**
     * Getter
     *
     * @param string $property  The property to return.
     *
     * @return mixed  The value of the requested property.
     */
    public function __get($property)
    {
        switch ($property) {
        case 'encoder':
        case 'decoder':
        case 'state':
        case 'request':
        case 'driver':
        case 'provisioning':
        case 'multipart':
        case 'certPath':
            $property = '_' . $property;
            return $this->$property;
        case 'logger':
            return self::$_logger;
        case 'device':
            return self::$_device;
        default:
            throw new InvalidArgumentException(sprintf(
                'The property %s does not exist',
                $property)
            );
        }
    }

    /**
     * Setter for the logger
     *
     * @param Horde_Log_Logger $logger  The logger object.
     *
     * @return void
     */
    public function setLogger(Horde_ActiveSync_Interface_LoggerFactory $logger)
    {
        $this->_loggerFactory = $logger;
    }

    protected function _setLogger(array $options)
    {
        if (!empty($this->_loggerFactory)) {
            self::$_logger = $this->_loggerFactory->create($options);
            $this->_encoder->setLogger(self::$_logger);
            $this->_decoder->setLogger(self::$_logger);
            $this->_driver->setLogger(self::$_logger);
            $this->_state->setLogger(self::$_logger);
        }
    }

    /**
     * Setter for provisioning support
     *
     */
    public function setProvisioning($provision)
    {
        $this->_provisioning = $provision;
    }

    /**
     * Send the headers indicating that provisioning is required.
     */
    public function provisioningRequired()
    {
        $this->provisionHeader();
        $this->activeSyncHeader();
        $this->versionHeader();
        $this->commandsHeader();
        header('Cache-Control: private');
    }

    /**
     * The heart of the server. Dispatch a request to the appropriate request
     * handler.
     *
     * @param string $cmd    The command we are requesting.
     * @param string $devId  The device id making the request. @deprecated
     *
     * @return string|boolean  false if failed, true if succeeded and response
     *                         content is wbxml, otherwise the
     *                         content-type string to send in the response.
     * @throws Horde_ActiveSync_Exception
     * @throws Horde_ActiveSync_Exception_InvalidRequest
     * @throws Horde_ActiveSync_PermissionDenied
     */
    public function handleRequest($cmd, $devId)
    {
        $get = $this->getGetVars();
        if (empty($cmd)) {
            $cmd = $get['Cmd'];
        }
        if (empty($devId)) {
            $devId = !empty($get['DeviceId']) ? strtoupper($get['DeviceId']) : null;
        } else {
            $devId = strtoupper($devId);
        }
        $this->_setLogger($get);

        // @TODO: Remove is_callable check for H6.
        // Callback to give the backend the option to limit EAS version based
        // on user/device/etc...
        if (is_callable(array($this->_driver, 'versionCallback'))) {
            $this->_driver->versionCallback($this);
        }

        // Autodiscovery handles authentication on it's own.
        if ($cmd == 'Autodiscover') {
            $request = new Horde_ActiveSync_Request_Autodiscover($this, new Horde_ActiveSync_Device($this->_state));

            if (!empty(self::$_logger)) {
                $request->setLogger(self::$_logger);
            }

            $result = $request->handle($this->_request);
            $this->_driver->clearAuthentication();
            return $result;
        }
        if (!$this->authenticate(new Horde_ActiveSync_Credentials($this))) {
            $this->activeSyncHeader();
            $this->versionHeader();
            $this->commandsHeader();
            throw new Horde_Exception_AuthenticationFailure();
        }

        // Set provisioning support now that we are authenticated.
        $this->setProvisioning($this->_driver->getProvisioning());

        self::$_logger->info(sprintf(
            '[%s] %s request received for user %s',
            $this->_procid,
            strtoupper($cmd),
            $this->_driver->getUser())
        );

        // These are all handled in the same class.
        if ($cmd == 'FolderDelete' || $cmd == 'FolderUpdate') {
            $cmd = 'FolderCreate';
        }

        // Device id is REQUIRED
        if (empty($devId)) {
            if ($cmd == 'Options') {
                $this->_doOptionsRequest();
                $this->_driver->clearAuthentication();
                return true;
            }
            $this->_driver->clearAuthentication();
            throw new Horde_ActiveSync_Exception_InvalidRequest('Device failed to send device id.');
        }

        // EAS Version
        $version = $this->getProtocolVersion();

        // Does device exist AND does the user have an account on the device?
        if (!$this->_state->deviceExists($devId, $this->_driver->getUser())) {
            // Device might exist, but with a new (additional) user account
            if ($this->_state->deviceExists($devId)) {
                self::$_device = $this->_state->loadDeviceInfo($devId);
            } else {
                self::$_device = new Horde_ActiveSync_Device($this->_state);
            }
            self::$_device->policykey = 0;
            self::$_device->userAgent = $this->_request->getHeader('User-Agent');
            self::$_device->deviceType = !empty($get['DeviceType']) ? $get['DeviceType'] : '';
            self::$_device->rwstatus = self::RWSTATUS_NA;
            self::$_device->user = $this->_driver->getUser();
            self::$_device->id = $devId;
            self::$_device->needsVersionUpdate($this->getSupportedVersions());
            self::$_device->version = $version;
            // @TODO: Remove is_callable check for H6.
            //        Combine this with the modifyDevice callback? Allow $device
            //        to be modified here?
            if (is_callable(array($this->_driver, 'createDeviceCallback'))) {
                $callback_ret = $this->_driver->createDeviceCallback(self::$_device);
                if ($callback_ret !== true) {
                    $msg = sprintf(
                        'The device %s was disallowed for user %s per policy settings.',
                        self::$_device->id,
                        self::$_device->user);
                    self::$_logger->err($msg);
                    if ($version > self::VERSION_TWELVEONE) {
                        $this->_globalError = $callback_ret;
                    } else {
                        throw new Horde_ActiveSync_Exception($msg);
                    }
                } else {
                    // Give the driver a chance to modify device properties.
                    if (is_callable(array($this->_driver, 'modifyDeviceCallback'))) {
                        self::$_device = $this->_driver->modifyDeviceCallback(self::$_device);
                    }
                }
            }
        } else {
            self::$_device = $this->_state->loadDeviceInfo($devId, $this->_driver->getUser());

            // If the device state was removed from storage, we may lose the
            // device properties, so try to repopulate what we can. userAgent
            // is ALWAYS available, so if it's missing, the state is gone.
            if (empty(self::$_device->userAgent)) {
                self::$_device->userAgent = $this->_request->getHeader('User-Agent');
                self::$_device->deviceType = !empty($get['DeviceType']) ? $get['DeviceType'] : '';
                self::$_device->user = $this->_driver->getUser();
            }

            if (empty(self::$_device->version)) {
                self::$_device->version = $version;
            }
            if (self::$_device->version < $this->_maxVersion &&
                self::$_device->needsVersionUpdate($this->getSupportedVersions())) {
                $needMsRp = true;
            }

            // Give the driver a chance to modify device properties.
            if (is_callable(array($this->_driver, 'modifyDeviceCallback'))) {
                self::$_device = $this->_driver->modifyDeviceCallback(self::$_device);
            }
        }

        self::$_device->save();
        if (is_callable(array($this->_driver, 'deviceCallback'))) {
            $callback_ret = $this->_driver->deviceCallback(self::$_device);
            if ($callback_ret !== true) {
                $msg = sprintf(
                    'The device %s was disallowed for user %s per policy settings.',
                    self::$_device->id,
                    self::$_device->user);
                self::$_logger->err($msg);
                if ($version > self::VERSION_TWELVEONE) {
                    $this->_globalError = $callback_ret;
                } else {
                    throw new Horde_ActiveSync_Exception($msg);
                }
            }
        }

        // Lastly, check if the device has been set to blocked.
        if (self::$_device->blocked) {
            $msg = sprintf(
                'The device %s was blocked.',
                self::$_device->id);
            self::$_logger->err($msg);
            if ($version > self::VERSION_TWELVEONE) {
                $this->_globalError = Horde_ActiveSync_Status::DEVICE_BLOCKED_FOR_USER;
            } else {
                throw new Horde_ActiveSync_Exception($msg);
            }
        }

        // Don't bother with everything else if all we want are Options
        if ($cmd == 'Options') {
            $this->_doOptionsRequest();
            $this->_driver->clearAuthentication();
            return true;
        }

        // Read the initial Wbxml header
        $this->_decoder->readWbxmlHeader();

        // Support Multipart response for ITEMOPERATIONS requests?
        $headers = $this->_request->getHeaders();
        if ((!empty($headers['ms-asacceptmultipart']) && $headers['ms-asacceptmultipart'] == 'T') ||
            !empty($get['AcceptMultiPart'])) {
            $this->_multipart = true;
            self::$_logger->info(sprintf(
                '[%s] Requesting multipart data.',
                $this->_procid)
            );
        }

        // Load the request handler to handle the request
        // We must send the eas header here, since some requests may start
        // output and be large enough to flush the buffer (e.g., GetAttachment)
        // See Bug: 12486
        $this->activeSyncHeader();
        if ($cmd != 'GetAttachment') {
            $this->contentTypeHeader();
        }

        // Should we announce a new version is available to the client?
        if (!empty($needMsRp)) {
            self::$_logger->info(sprintf(
                '[%s] Announcing X-MS-RP to client.',
                $this->_procid)
            );
            header("X-MS-RP: ". $this->getSupportedVersions());
        }

        // @TODO: Look at getting rid of having to set the version in the driver
        //        and get it from the device object for H6.
        $this->_driver->setDevice(self::$_device);
        $class = 'Horde_ActiveSync_Request_' . basename($cmd);
        if (class_exists($class)) {
            $request = new $class($this);
            $request->setLogger(self::$_logger);
            $result = $request->handle();
            self::$_logger->info(sprintf(
                '[%s] Maximum memory usage for ActiveSync request: %d bytes.',
                $this->_procid,
                memory_get_peak_usage())
            );

            return $result;
        }

        $this->_driver->clearAuthentication();
        throw new Horde_ActiveSync_Exception_InvalidRequest(basename($cmd) . ' not supported.');
    }

    /**
     * Send the MS_Server-ActiveSync header.
     *
     */
    public function activeSyncHeader()
    {
        header('Allow: OPTIONS,POST');
        header('Server: Horde_ActiveSync Library v' . self::LIBRARY_VERSION);
        header('Public: OPTIONS,POST');

        switch ($this->_maxVersion) {
        case self::VERSION_TWOFIVE:
            header('MS-Server-ActiveSync: 6.5.7638.1');
            break;
        case self::VERSION_TWELVE:
            header('MS-Server-ActiveSync: 12.0');
            break;
        case self::VERSION_TWELVEONE:
            header('MS-Server-ActiveSync: 12.1');
            break;
        case self::VERSION_FOURTEEN:
            header('MS-Server-ActiveSync: 14.0');
            break;
        case self::VERSION_FOURTEENONE:
            header('MS-Server-ActiveSync: 14.2');
        }
    }

    /**
     * Send the protocol versions header.
     *
     */
    public function versionHeader()
    {
        header('MS-ASProtocolVersions: ' . $this->getSupportedVersions());
    }

    /**
     * Return supported versions in a comma delimited string suitable for
     * sending as the MS-ASProtocolVersions header.
     *
     * @return string
     */
    public function getSupportedVersions()
    {
        return implode(',', array_slice(self::$_supportedVersions, 0, (array_search($this->_maxVersion, self::$_supportedVersions) + 1)));
    }

    /**
     * Send protocol commands header.
     *
     */
    public function commandsHeader()
    {
        header('MS-ASProtocolCommands: ' . $this->getSupportedCommands());
    }

    /**
     * Return the supported commands in a comma delimited string suitable for
     * sending as the MS-ASProtocolCommands header.
     *
     * @return string
     */
    public function getSupportedCommands()
    {
        switch ($this->_maxVersion) {
        case self::VERSION_TWOFIVE:
            return 'Sync,SendMail,SmartForward,SmartReply,GetAttachment,GetHierarchy,CreateCollection,DeleteCollection,MoveCollection,FolderSync,FolderCreate,FolderDelete,FolderUpdate,MoveItems,GetItemEstimate,MeetingResponse,ResolveRecipients,ValidateCert,Provision,Search,Ping';

        case self::VERSION_TWELVE:
        case self::VERSION_TWELVEONE:
        case self::VERSION_FOURTEEN:
        case self::VERSION_FOURTEENONE:
            return 'Sync,SendMail,SmartForward,SmartReply,GetAttachment,GetHierarchy,CreateCollection,DeleteCollection,MoveCollection,FolderSync,FolderCreate,FolderDelete,FolderUpdate,MoveItems,GetItemEstimate,MeetingResponse,Search,Settings,Ping,ItemOperations,Provision,ResolveRecipients,ValidateCert';
        }
    }

    /**
     * Send provision header
     *
     */
    public function provisionHeader()
    {
        header('HTTP/1.1 449 Retry after sending a PROVISION command');
    }

    /**
     * Obtain the policy key header from the request.
     *
     * @return integer  The policy key or '0' if not set.
     */
    public function getPolicyKey()
    {
        // Policy key can come from header or encoded request parameters.
        $this->_policykey = $this->_request->getHeader('X-MS-PolicyKey');
        if (empty($this->_policykey)) {
            $get = $this->getGetVars();
            if (!empty($get['PolicyKey'])) {
                $this->_policykey = $get['PolicyKey'];
            } else {
                $this->_policykey = 0;
            }
        }

        return $this->_policykey;
    }

    /**
     * Obtain the ActiveSync protocol version requested by the client headers.
     *
     * @return string  The EAS version requested by the client.
     */
    public function getProtocolVersion()
    {
        if (!isset(self::$_version)) {
            self::$_version = $this->_request->getHeader('MS-ASProtocolVersion');
            if (empty(self::$_version)) {
                $get = $this->getGetVars();
                self::$_version = empty($get['ProtVer']) ? '1.0' : $get['ProtVer'];
            }
        }
        return self::$_version;
    }

    /**
     * Return the GET variables passed from the device, decoding from
     * base64 if needed.
     *
     * @return array  A hash of get variables => values.
     */
    public function getGetVars()
    {
        if (!empty($this->_get)) {
            return $this->_get;
        }

        $results = array();
        $get = $this->_request->getGetVars();

        // Do we need to decode the request parameters?
        if (!isset($get['Cmd']) && !isset($get['DeviceId']) && !isset($get['DeviceType'])) {
            $serverVars = $this->_request->getServerVars();
            if (isset($serverVars['QUERY_STRING']) && strlen($serverVars['QUERY_STRING']) >= 10) {
                $results = Horde_ActiveSync_Utils::decodeBase64($serverVars['QUERY_STRING']);
                // Normalize values.
                switch ($results['DeviceType']) {
                case 'PPC':
                    $results['DeviceType'] = 'PocketPC';
                    break;
                case 'SP':
                    $results['DeviceType'] = 'SmartPhone';
                    break;
                case 'WP':
                case 'WP8':
                    $results['DeviceType'] = 'WindowsPhone';
                    break;
                case 'android':
                case 'android40':
                    $results['DeviceType'] = 'android';
                }
                $this->_get = $results;
            }
        } else {
            $this->_get = $get;
        }

        return $this->_get;
    }

    /**
     * Return any global errors that occured during initial connection.
     *
     * @since 2.4.0
     * @return mixed  A Horde_ActiveSync_Status:: constant of boolean false if
     *                no errors.
     */
    public function checkGlobalError()
    {
        return $this->_globalError;
    }

    /**
     * Send the content type header.
     *
     */
    public function contentTypeHeader($content_type = null)
    {
        if (!empty($content_type)) {
            header('Content-Type: ' . $content_type);
            return;
        }
        if ($this->_multipart) {
            header('Content-Type: application/vnd.ms-sync.multipart');
        } else {
            header('Content-Type: application/vnd.ms-sync.wbxml');
        }
    }

    /**
     * Send the OPTIONS request response headers.
     *
     */
    protected function _doOptionsRequest()
    {
        $this->activeSyncHeader();
        $this->versionHeader();
        $this->commandsHeader();
    }

    /**
     * Return the number of bytes corresponding to the requested trunction
     * constant.
     *
     * @param integer $truncation  The constant.
     *
     * @return integer|boolean  Either the size, in bytes, to truncate or
     *                          falso if no truncation.
     */
    static public function getTruncSize($truncation)
    {
        switch($truncation) {
        case Horde_ActiveSync::TRUNCATION_ALL:
            return 0;
        case Horde_ActiveSync::TRUNCATION_1:
            return 4096;
        case Horde_ActiveSync::TRUNCATION_2:
            return 5120;
        case Horde_ActiveSync::TRUNCATION_3:
            return 7168;
        case Horde_ActiveSync::TRUNCATION_4:
            return 10240;
        case Horde_ActiveSync::TRUNCATION_5:
            return 20480;
        case Horde_ActiveSync::TRUNCATION_6:
            return 51200;
        case Horde_ActiveSync::TRUNCATION_7:
            return 102400;
        case Horde_ActiveSync::TRUNCATION_8:
        case Horde_ActiveSync::TRUNCATION_NONE:
            return false;
        default:
            return 1024; // Default to 1Kb
        }
    }

}
