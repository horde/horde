<?php
/**
 * ActiveSync Server - ported from ZPush
 *
 * Refactoring and other changes are
 * Copyright 2009 - 2010 The Horde Project (http://www.horde.org)
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

// POOMMAIL
define("SYNC_POOMMAIL_ATTACHMENT","POOMMAIL:Attachment");
define("SYNC_POOMMAIL_ATTACHMENTS","POOMMAIL:Attachments");
define("SYNC_POOMMAIL_ATTNAME","POOMMAIL:AttName");
define("SYNC_POOMMAIL_ATTSIZE","POOMMAIL:AttSize");
define("SYNC_POOMMAIL_ATTOID","POOMMAIL:AttOid");
define("SYNC_POOMMAIL_ATTMETHOD","POOMMAIL:AttMethod");
define("SYNC_POOMMAIL_ATTREMOVED","POOMMAIL:AttRemoved");
define("SYNC_POOMMAIL_BODY","POOMMAIL:Body");
define("SYNC_POOMMAIL_BODYSIZE","POOMMAIL:BodySize");
define("SYNC_POOMMAIL_BODYTRUNCATED","POOMMAIL:BodyTruncated");
define("SYNC_POOMMAIL_DATERECEIVED","POOMMAIL:DateReceived");
define("SYNC_POOMMAIL_DISPLAYNAME","POOMMAIL:DisplayName");
define("SYNC_POOMMAIL_DISPLAYTO","POOMMAIL:DisplayTo");
define("SYNC_POOMMAIL_IMPORTANCE","POOMMAIL:Importance");
define("SYNC_POOMMAIL_MESSAGECLASS","POOMMAIL:MessageClass");
define("SYNC_POOMMAIL_SUBJECT","POOMMAIL:Subject");
define("SYNC_POOMMAIL_READ","POOMMAIL:Read");
define("SYNC_POOMMAIL_TO","POOMMAIL:To");
define("SYNC_POOMMAIL_CC","POOMMAIL:Cc");
define("SYNC_POOMMAIL_FROM","POOMMAIL:From");
define("SYNC_POOMMAIL_REPLY_TO","POOMMAIL:Reply-To");
define("SYNC_POOMMAIL_ALLDAYEVENT","POOMMAIL:AllDayEvent");
define("SYNC_POOMMAIL_CATEGORIES","POOMMAIL:Categories");
define("SYNC_POOMMAIL_CATEGORY","POOMMAIL:Category");
define("SYNC_POOMMAIL_DTSTAMP","POOMMAIL:DtStamp");
define("SYNC_POOMMAIL_ENDTIME","POOMMAIL:EndTime");
define("SYNC_POOMMAIL_INSTANCETYPE","POOMMAIL:InstanceType");
define("SYNC_POOMMAIL_BUSYSTATUS","POOMMAIL:BusyStatus");
define("SYNC_POOMMAIL_LOCATION","POOMMAIL:Location");
define("SYNC_POOMMAIL_MEETINGREQUEST","POOMMAIL:MeetingRequest");
define("SYNC_POOMMAIL_ORGANIZER","POOMMAIL:Organizer");
define("SYNC_POOMMAIL_RECURRENCEID","POOMMAIL:RecurrenceId");
define("SYNC_POOMMAIL_REMINDER","POOMMAIL:Reminder");
define("SYNC_POOMMAIL_RESPONSEREQUESTED","POOMMAIL:ResponseRequested");
define("SYNC_POOMMAIL_RECURRENCES","POOMMAIL:Recurrences");
define("SYNC_POOMMAIL_RECURRENCE","POOMMAIL:Recurrence");
define("SYNC_POOMMAIL_TYPE","POOMMAIL:Type");
define("SYNC_POOMMAIL_UNTIL","POOMMAIL:Until");
define("SYNC_POOMMAIL_OCCURRENCES","POOMMAIL:Occurrences");
define("SYNC_POOMMAIL_INTERVAL","POOMMAIL:Interval");
define("SYNC_POOMMAIL_DAYOFWEEK","POOMMAIL:DayOfWeek");
define("SYNC_POOMMAIL_DAYOFMONTH","POOMMAIL:DayOfMonth");
define("SYNC_POOMMAIL_WEEKOFMONTH","POOMMAIL:WeekOfMonth");
define("SYNC_POOMMAIL_MONTHOFYEAR","POOMMAIL:MonthOfYear");
define("SYNC_POOMMAIL_STARTTIME","POOMMAIL:StartTime");
define("SYNC_POOMMAIL_SENSITIVITY","POOMMAIL:Sensitivity");
define("SYNC_POOMMAIL_TIMEZONE","POOMMAIL:TimeZone");
define("SYNC_POOMMAIL_GLOBALOBJID","POOMMAIL:GlobalObjId");
define("SYNC_POOMMAIL_THREADTOPIC","POOMMAIL:ThreadTopic");
define("SYNC_POOMMAIL_MIMEDATA","POOMMAIL:MIMEData");
define("SYNC_POOMMAIL_MIMETRUNCATED","POOMMAIL:MIMETruncated");
define("SYNC_POOMMAIL_MIMESIZE","POOMMAIL:MIMESize");
define("SYNC_POOMMAIL_INTERNETCPID","POOMMAIL:InternetCPID");

// AIRNOTIFY
define("SYNC_AIRNOTIFY_NOTIFY","AirNotify:Notify");
define("SYNC_AIRNOTIFY_NOTIFICATION","AirNotify:Notification");
define("SYNC_AIRNOTIFY_VERSION","AirNotify:Version");
define("SYNC_AIRNOTIFY_LIFETIME","AirNotify:Lifetime");
define("SYNC_AIRNOTIFY_DEVICEINFO","AirNotify:DeviceInfo");
define("SYNC_AIRNOTIFY_ENABLE","AirNotify:Enable");
define("SYNC_AIRNOTIFY_FOLDER","AirNotify:Folder");
define("SYNC_AIRNOTIFY_SERVERENTRYID","AirNotify:ServerEntryId");
define("SYNC_AIRNOTIFY_DEVICEADDRESS","AirNotify:DeviceAddress");
define("SYNC_AIRNOTIFY_VALIDCARRIERPROFILES","AirNotify:ValidCarrierProfiles");
define("SYNC_AIRNOTIFY_CARRIERPROFILE","AirNotify:CarrierProfile");
define("SYNC_AIRNOTIFY_STATUS","AirNotify:Status");
define("SYNC_AIRNOTIFY_REPLIES","AirNotify:Replies");
define("SYNC_AIRNOTIFY_VERSION='1.1'","AirNotify:Version='1.1'");
define("SYNC_AIRNOTIFY_DEVICES","AirNotify:Devices");
define("SYNC_AIRNOTIFY_DEVICE","AirNotify:Device");
define("SYNC_AIRNOTIFY_ID","AirNotify:Id");
define("SYNC_AIRNOTIFY_EXPIRY","AirNotify:Expiry");
define("SYNC_AIRNOTIFY_NOTIFYGUID","AirNotify:NotifyGUID");

// Move
define("SYNC_MOVE_MOVES","Move:Moves");
define("SYNC_MOVE_MOVE","Move:Move");
define("SYNC_MOVE_SRCMSGID","Move:SrcMsgId");
define("SYNC_MOVE_SRCFLDID","Move:SrcFldId");
define("SYNC_MOVE_DSTFLDID","Move:DstFldId");
define("SYNC_MOVE_RESPONSE","Move:Response");
define("SYNC_MOVE_STATUS","Move:Status");
define("SYNC_MOVE_DSTMSGID","Move:DstMsgId");

// MeetingResponse
define("SYNC_MEETINGRESPONSE_CALENDARID","MeetingResponse:CalendarId");
define("SYNC_MEETINGRESPONSE_FOLDERID","MeetingResponse:FolderId");
define("SYNC_MEETINGRESPONSE_MEETINGRESPONSE","MeetingResponse:MeetingResponse");
define("SYNC_MEETINGRESPONSE_REQUESTID","MeetingResponse:RequestId");
define("SYNC_MEETINGRESPONSE_REQUEST","MeetingResponse:Request");
define("SYNC_MEETINGRESPONSE_RESULT","MeetingResponse:Result");
define("SYNC_MEETINGRESPONSE_STATUS","MeetingResponse:Status");
define("SYNC_MEETINGRESPONSE_USERRESPONSE","MeetingResponse:UserResponse");
define("SYNC_MEETINGRESPONSE_VERSION","MeetingResponse:Version");

// POOMTASKS
define("SYNC_POOMTASKS_BODY","POOMTASKS:Body");
define("SYNC_POOMTASKS_BODYSIZE","POOMTASKS:BodySize");
define("SYNC_POOMTASKS_BODYTRUNCATED","POOMTASKS:BodyTruncated");
define("SYNC_POOMTASKS_CATEGORIES","POOMTASKS:Categories");
define("SYNC_POOMTASKS_CATEGORY","POOMTASKS:Category");
define("SYNC_POOMTASKS_COMPLETE","POOMTASKS:Complete");
define("SYNC_POOMTASKS_DATECOMPLETED","POOMTASKS:DateCompleted");
define("SYNC_POOMTASKS_DUEDATE","POOMTASKS:DueDate");
define("SYNC_POOMTASKS_UTCDUEDATE","POOMTASKS:UtcDueDate");
define("SYNC_POOMTASKS_IMPORTANCE","POOMTASKS:Importance");
define("SYNC_POOMTASKS_RECURRENCE","POOMTASKS:Recurrence");
define("SYNC_POOMTASKS_TYPE","POOMTASKS:Type");
define("SYNC_POOMTASKS_START","POOMTASKS:Start");
define("SYNC_POOMTASKS_UNTIL","POOMTASKS:Until");
define("SYNC_POOMTASKS_OCCURRENCES","POOMTASKS:Occurrences");
define("SYNC_POOMTASKS_INTERVAL","POOMTASKS:Interval");
define("SYNC_POOMTASKS_DAYOFWEEK","POOMTASKS:DayOfWeek");
define("SYNC_POOMTASKS_DAYOFMONTH","POOMTASKS:DayOfMonth");
define("SYNC_POOMTASKS_WEEKOFMONTH","POOMTASKS:WeekOfMonth");
define("SYNC_POOMTASKS_MONTHOFYEAR","POOMTASKS:MonthOfYear");
define("SYNC_POOMTASKS_REGENERATE","POOMTASKS:Regenerate");
define("SYNC_POOMTASKS_DEADOCCUR","POOMTASKS:DeadOccur");
define("SYNC_POOMTASKS_REMINDERSET","POOMTASKS:ReminderSet");
define("SYNC_POOMTASKS_REMINDERTIME","POOMTASKS:ReminderTime");
define("SYNC_POOMTASKS_SENSITIVITY","POOMTASKS:Sensitivity");
define("SYNC_POOMTASKS_STARTDATE","POOMTASKS:StartDate");
define("SYNC_POOMTASKS_UTCSTARTDATE","POOMTASKS:UtcStartDate");
define("SYNC_POOMTASKS_SUBJECT","POOMTASKS:Subject");
define("SYNC_POOMTASKS_RTF","POOMTASKS:Rtf");

// ResolveRecipients
define("SYNC_RESOLVERECIPIENTS_RESOLVERECIPIENTS","ResolveRecipients:ResolveRecipients");
define("SYNC_RESOLVERECIPIENTS_RESPONSE","ResolveRecipients:Response");
define("SYNC_RESOLVERECIPIENTS_STATUS","ResolveRecipients:Status");
define("SYNC_RESOLVERECIPIENTS_TYPE","ResolveRecipients:Type");
define("SYNC_RESOLVERECIPIENTS_RECIPIENT","ResolveRecipients:Recipient");
define("SYNC_RESOLVERECIPIENTS_DISPLAYNAME","ResolveRecipients:DisplayName");
define("SYNC_RESOLVERECIPIENTS_EMAILADDRESS","ResolveRecipients:EmailAddress");
define("SYNC_RESOLVERECIPIENTS_CERTIFICATES","ResolveRecipients:Certificates");
define("SYNC_RESOLVERECIPIENTS_CERTIFICATE","ResolveRecipients:Certificate");
define("SYNC_RESOLVERECIPIENTS_MINICERTIFICATE","ResolveRecipients:MiniCertificate");
define("SYNC_RESOLVERECIPIENTS_OPTIONS","ResolveRecipients:Options");
define("SYNC_RESOLVERECIPIENTS_TO","ResolveRecipients:To");
define("SYNC_RESOLVERECIPIENTS_CERTIFICATERETRIEVAL","ResolveRecipients:CertificateRetrieval");
define("SYNC_RESOLVERECIPIENTS_RECIPIENTCOUNT","ResolveRecipients:RecipientCount");
define("SYNC_RESOLVERECIPIENTS_MAXCERTIFICATES","ResolveRecipients:MaxCertificates");
define("SYNC_RESOLVERECIPIENTS_MAXAMBIGUOUSRECIPIENTS","ResolveRecipients:MaxAmbiguousRecipients");
define("SYNC_RESOLVERECIPIENTS_CERTIFICATECOUNT","ResolveRecipients:CertificateCount");

// ValidateCert
define("SYNC_VALIDATECERT_VALIDATECERT","ValidateCert:ValidateCert");
define("SYNC_VALIDATECERT_CERTIFICATES","ValidateCert:Certificates");
define("SYNC_VALIDATECERT_CERTIFICATE","ValidateCert:Certificate");
define("SYNC_VALIDATECERT_CERTIFICATECHAIN","ValidateCert:CertificateChain");
define("SYNC_VALIDATECERT_CHECKCRL","ValidateCert:CheckCRL");
define("SYNC_VALIDATECERT_STATUS","ValidateCert:Status");

//Search
define("SYNC_SEARCH_SEARCH", "Search:Search");
define("SYNC_SEARCH_STORE", "Search:Store");
define("SYNC_SEARCH_NAME", "Search:Name");
define("SYNC_SEARCH_QUERY", "Search:Query");
define("SYNC_SEARCH_OPTIONS", "Search:Options");
define("SYNC_SEARCH_RANGE", "Search:Range");
define("SYNC_SEARCH_STATUS", "Search:Status");
define("SYNC_SEARCH_RESPONSE", "Search:Response");
define("SYNC_SEARCH_RESULT", "Search:Result");
define("SYNC_SEARCH_PROPERTIES", "Search:Properties");
define("SYNC_SEARCH_TOTAL", "Search:Total");
define("SYNC_SEARCH_EQUALTO", "Search:EqualTo");
define("SYNC_SEARCH_VALUE", "Search:Value");
define("SYNC_SEARCH_AND", "Search:And");
define("SYNC_SEARCH_OR", "Search:Or");
define("SYNC_SEARCH_FREETEXT", "Search:FreeText");
define("SYNC_SEARCH_DEEPTRAVERSAL", "Search:DeepTraversal");
define("SYNC_SEARCH_LONGID", "Search:LongId");
define("SYNC_SEARCH_REBUILDRESULTS", "Search:RebuildResults");
define("SYNC_SEARCH_LESSTHAN", "Search:LessThan");
define("SYNC_SEARCH_GREATERTHAN", "Search:GreaterThan");
define("SYNC_SEARCH_SCHEMA", "Search:Schema");
define("SYNC_SEARCH_SUPPORTED", "Search:Supported");

//GAL
define("SYNC_GAL_DISPLAYNAME", "GAL:DisplayName");
define("SYNC_GAL_PHONE", "GAL:Phone");
define("SYNC_GAL_OFFICE", "GAL:Office");
define("SYNC_GAL_TITLE", "GAL:Title");
define("SYNC_GAL_COMPANY", "GAL:Company");
define("SYNC_GAL_ALIAS", "GAL:Alias");
define("SYNC_GAL_FIRSTNAME", "GAL:FirstName");
define("SYNC_GAL_LASTNAME", "GAL:LastName");
define("SYNC_GAL_HOMEPHONE", "GAL:HomePhone");
define("SYNC_GAL_MOBILEPHONE", "GAL:MobilePhone");
define("SYNC_GAL_EMAILADDRESS", "GAL:EmailAddress");

/**
 * Main ActiveSync class. Entry point for performing all ActiveSync operations
 *
 */
class Horde_ActiveSync
{
    /* Constants */
    const CONFLICT_OVERWRITE_SERVER = 0;
    const CONFLICT_OVERWRITE_PIM = 1;

    /* TRUNCATION Constants */
    const TRUNCATION_HEADERS = 0;
    const TRUNCATION_512B = 1;
    const TRUNCATION_1K = 2;
    const TRUNCATION_5K = 4;
    const TRUNCATION_SEVEN = 7;
    const TRUNCATION_ALL = 9;

    /* Request related constants that are used in multiple places */
    /* FOLDERHIERARCHY */ 
    const FOLDERHIERARCHY_FOLDERS = 'FolderHierarchy:Folders';
    const FOLDERHIERARCHY_FOLDER = 'FolderHierarchy:Folder';
    const FOLDERHIERARCHY_DISPLAYNAME = 'FolderHierarchy:DisplayName';
    const FOLDERHIERARCHY_SERVERENTRYID = 'FolderHierarchy:ServerEntryId';
    const FOLDERHIERARCHY_PARENTID = 'FolderHierarchy:ParentId';
    const FOLDERHIERARCHY_TYPE = 'FolderHierarchy:Type';
    const FOLDERHIERARCHY_RESPONSE = 'FolderHierarchy:Response';
    const FOLDERHIERARCHY_STATUS = 'FolderHierarchy:Status';
    const FOLDERHIERARCHY_CONTENTCLASS = 'FolderHierarchy:ContentClass';
    const FOLDERHIERARCHY_CHANGES = 'FolderHierarchy:Changes';
    const FOLDERHIERARCHY_ADD = 'FolderHierarchy:Add';
    const FOLDERHIERARCHY_REMOVE = 'FolderHierarchy:Remove';
    const FOLDERHIERARCHY_UPDATE = 'FolderHierarchy:Update';
    const FOLDERHIERARCHY_SYNCKEY = 'FolderHierarchy:SyncKey';
    const FOLDERHIERARCHY_FOLDERCREATE = 'FolderHierarchy:FolderCreate';
    const FOLDERHIERARCHY_FOLDERDELETE = 'FolderHierarchy:FolderDelete';
    const FOLDERHIERARCHY_FOLDERUPDATE = 'FolderHierarchy:FolderUpdate';
    const FOLDERHIERARCHY_FOLDERSYNC = 'FolderHierarchy:FolderSync';
    const FOLDERHIERARCHY_COUNT = 'FolderHierarchy:Count';
    const FOLDERHIERARCHY_VERSION = 'FolderHierarchy:Version';
    
    /* SYNC */
    const SYNC_SYNCHRONIZE = 'Synchronize';
    const SYNC_REPLIES = 'Replies';
    const SYNC_ADD = 'Add';
    const SYNC_MODIFY = 'Modify';
    const SYNC_REMOVE = 'Remove';
    const SYNC_FETCH = 'Fetch';
    const SYNC_SYNCKEY = 'SyncKey';
    const SYNC_CLIENTENTRYID = 'ClientEntryId';
    const SYNC_SERVERENTRYID = 'ServerEntryId';
    const SYNC_STATUS = 'Status';
    const SYNC_FOLDER = 'Folder';
    const SYNC_FOLDERTYPE = 'FolderType';
    const SYNC_VERSION = 'Version';
    const SYNC_FOLDERID = 'FolderId';
    const SYNC_GETCHANGES = 'GetChanges';
    const SYNC_MOREAVAILABLE = 'MoreAvailable';
    const SYNC_WINDOWSIZE = 'WindowSize';
    const SYNC_COMMANDS = 'Commands';
    const SYNC_OPTIONS = 'Options';
    const SYNC_FILTERTYPE = 'FilterType';
    const SYNC_TRUNCATION = 'Truncation';
    const SYNC_RTFTRUNCATION = 'RtfTruncation';
    const SYNC_CONFLICT = 'Conflict';
    const SYNC_FOLDERS = 'Folders';
    const SYNC_DATA = 'Data';
    const SYNC_DELETESASMOVES = 'DeletesAsMoves';
    const SYNC_NOTIFYGUID = 'NotifyGUID';
    const SYNC_SUPPORTED = 'Supported';
    const SYNC_SOFTDELETE = 'SoftDelete';
    const SYNC_MIMESUPPORT = 'MIMESupport';
    const SYNC_MIMETRUNCATION = 'MIMETruncation';

    /* PROVISION */
    const PROVISION_PROVISION =  'Provision:Provision';
    const PROVISION_POLICIES =  'Provision:Policies';
    const PROVISION_POLICY =  'Provision:Policy';
    const PROVISION_POLICYTYPE =  'Provision:PolicyType';
    const PROVISION_POLICYKEY =  'Provision:PolicyKey';
    const PROVISION_DATA =  'Provision:Data';
    const PROVISION_STATUS =  'Provision:Status';
    const PROVISION_REMOTEWIPE =  'Provision:RemoteWipe';
    const PROVISION_EASPROVISIONDOC =  'Provision:EASProvisionDoc';

    /* Flags */
    const FLAG_NEWMESSAGE = 'NewMessage';

    /* Folder types */
    // Other constants
    const FOLDER_TYPE_OTHER =  1;
    const FOLDER_TYPE_INBOX =  2;
    const FOLDER_TYPE_DRAFTS =  3;
    const FOLDER_TYPE_WASTEBASKET =  4;
    const FOLDER_TYPE_SENTMAIL =  5;
    const FOLDER_TYPE_OUTBOX =  6;
    const FOLDER_TYPE_TASK =  7;
    const FOLDER_TYPE_APPOINTMENT =  8;
    const FOLDER_TYPE_CONTACT =  9;
    const FOLDER_TYPE_NOTE =  10;
    const FOLDER_TYPE_JOURNAL =  11;
    const FOLDER_TYPE_USER_MAIL =  12;
    const FOLDER_TYPE_USER_APPOINTMENT =  13;
    const FOLDER_TYPE_USER_CONTACT =  14;
    const FOLDER_TYPE_USER_TASK =  15;
    const FOLDER_TYPE_USER_JOURNAL =  16;
    const FOLDER_TYPE_USER_NOTE =  17;
    const FOLDER_TYPE_UNKNOWN =  18;
    const FOLDER_TYPE_RECIPIENT_CACHE =  19;
    const FOLDER_TYPE_DUMMY =  '__dummy.Folder.Id__';
 
    /**
     * Logger
     *
     * @var Horde_Log_Logger
     */
    protected $_logger;

    /**
     * Provisioning support
     *
     * @var string (TODO _constant this)
     */
    protected $_provisioning;

    /**
     * Const'r
     *
     * @param Horde_ActiveSync_Driver $driver            The backend driver
     * @param Horde_ActiveSync_StateMachine $state       The state machine
     * @param Horde_ActiveSync_Wbxml_Decoder $decoder    The Wbxml decoder
     * @param Horde_ActiveSync_Wbxml_Endcodder $encdoer  The Wbxml encoder
     *
     * @return Horde_ActiveSync
     */
    public function __construct(Horde_ActiveSync_Driver_Base $driver,
                                Horde_ActiveSync_Wbxml_Decoder $decoder,
                                Horde_ActiveSync_Wbxml_Encoder $encoder,
                                Horde_Controller_Request_Http $request)
    {
        /* Backend driver */
        $this->_driver = $driver;

        /* Wbxml handlers */
        $this->_encoder = $encoder;
        $this->_decoder = $decoder;

        /* The http request */
        $this->_request = $request;
    }

    /**
     * Setter for the logger
     *
     * @param Horde_Log_Logger $logger  The logger object.
     *
     * @return void
     */
    public function setLogger(Horde_Log_Logger $logger)
    {
        $this->_logger = $logger;
        $this->_encoder->setLogger($logger);
        $this->_decoder->setLogger($logger);
        $this->_driver->setLogger($logger);
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
     *
     * @param $protocolversion
     *
     * @return true
     */
    public function handleMoveItems($protocolversion)
    {
        if (!$this->_decoder->getElementStartTag(SYNC_MOVE_MOVES)) {
            return false;
        }

        $moves = array();
        while ($this->_decoder->getElementStartTag(SYNC_MOVE_MOVE)) {
            $move = array();
            if ($this->_decoder->getElementStartTag(SYNC_MOVE_SRCMSGID)) {
                $move['srcmsgid'] = $this->_decoder->getElementContent();
                if(!$this->_decoder->getElementEndTag())
                    break;
            }
            if ($this->_decoder->getElementStartTag(SYNC_MOVE_SRCFLDID)) {
                $move['srcfldid'] = $this->_decoder->getElementContent();
                if (!$this->_decoder->getElementEndTag()) {
                    break;
                }
            }
            if ($this->_decoder->getElementStartTag(SYNC_MOVE_DSTFLDID)) {
                $move['dstfldid'] = $this->_decoder->getElementContent();
                if (!$this->_decoder->getElementEndTag()) {
                    break;
                }
            }
            array_push($moves, $move);

            if (!$this->_decoder->getElementEndTag()) {
                return false;
            }
        }

        if (!$this->_decoder->getElementEndTag())
            return false;

        $this->_encoder->StartWBXML();

        $this->_encoder->startTag(SYNC_MOVE_MOVES);

        foreach ($moves as $move) {
            $this->_encoder->startTag(SYNC_MOVE_RESPONSE);
            $this->_encoder->startTag(SYNC_MOVE_SRCMSGID);
            $this->_encoder->content($move['srcmsgid']);
            $this->_encoder->endTag();

            $importer = $this->_driver->GetContentsImporter($move['srcfldid']);
            $result = $importer->ImportMessageMove($move['srcmsgid'], $move['dstfldid']);

            // We discard the importer state for now.
            $this->_encoder->startTag(SYNC_MOVE_STATUS);
            $this->_encoder->content($result ? 3 : 1);
            $this->_encoder->endTag();

            $this->_encoder->startTag(SYNC_MOVE_DSTMSGID);
            $this->_encoder->content(is_string($result) ? $result : $move['srcmsgid']);
            $this->_encoder->endTag();
            $this->_encoder->endTzg();
        }
        $this->_encoder->endTag();

        return true;
    }

    /**
     * @param $protocolversion
     *
     * @return boolean
     */
    public function handleNotify($protocolversion)
    {
        if (!$this->_decoder->getElementStartTag(SYNC_AIRNOTIFY_NOTIFY)) {
            return false;
        }

        if (!$this->_decoder->getElementStartTag(SYNC_AIRNOTIFY_DEVICEINFO)) {
            return false;
        }

        if (!$this->_decoder->getElementEndTag()) {
            return false;
        }

        if (!$this->_decoder->getElementEndTag()) {
            return false;
        }
        $this->_encoder->StartWBXML();
        $this->_encoder->startTag(SYNC_AIRNOTIFY_NOTIFY);
        $this->_encoder->startTag(SYNC_AIRNOTIFY_STATUS);
        $this->_encoder->content(1);
        $this->_encoder->endTag();
        $this->_encoder->startTag(SYNC_AIRNOTIFY_VALIDCARRIERPROFILES);
        $this->_encoder->endTag();
        $this->_encoder->endTag();

        return true;
    }

    /**
     * handle GetHierarchy method - simply returns current hierarchy of all
     * folders
     *
     * @param string $protocolversion
     * @param string $devid
     *
     * @return boolean
     */
    public function handleGetHierarchy($protocolversion, $devid)
    {
        $folders = $this->_driver->GetHierarchy();
        if (!$folders) {
            return false;
        }

        // save folder-ids for fourther syncing
        $this->_stateMachine->setFolderData($devid, $folders);

        $this->_encoder->StartWBXML();
        $this->_encoder->startTag(self::FOLDERHIERARCHY_FOLDERS);

        foreach ($folders as $folder) {
            $this->_encoder->startTag(self::FOLDERHIERARCHY_FOLDER);
            $folder->encodeStream($this->_encoder);
            $this->_encoder->endTag();
        }
        $this->_encoder->endTag();

        return true;
    }

    /**
     * @param $protocolversion
     * @return unknown_type
     */
    public function handleGetAttachment($protocolversion)
    {
        $get = $this->_request->getGetParams();
        $attname = $get('AttachmentName');
        if (!isset($attname)) {
            return false;
        }

        header("Content-Type: application/octet-stream");
        $this->_driver->GetAttachmentData($attname);

        return true;
    }

    /**
     *
     * @param $protocolversion
     * @return unknown_type
     */
    public function handleSmartForward($protocolversion)
    {
        // SmartForward is a normal 'send' except that you should attach the
        // original message which is specified in the URL

        $rfc822 = $this->readStream();

        if (isset($_GET["ItemId"])) {
            $orig = $_GET["ItemId"];
        } else {
            $orig = false;
        }
        if (isset($_GET["CollectionId"])) {
            $parent = $_GET["CollectionId"];
        } else {
            $parent = false;
        }

        return $this->_driver->sendMail($rfc822, $orig, false, $parent);
    }

    /**
     * @TODO: use Horde_Controller_Request_Http for the GET
     *
     * @param unknown_type $protocolversion
     * @return unknown_type
     */
    public function handleSmartReply($protocolversion)
    {
        // Smart reply should add the original message to the end of the message body
        $rfc822 = $this->readStream();

        if (isset($_GET["ItemId"])) {
            $orig = $_GET["ItemId"];
        } else {
            $orig = false;
        }

        if (isset($_GET["CollectionId"])) {
            $parent = $_GET["CollectionId"];
        } else {
            $parent = false;
        }

        return $this->_driver->sendMail($rfc822, false, $orig, $parent);
    }

    /**
     * @param $protocolversion
     * @return unknown_type
     */
    public function handleFolderCreate($protocolversion)
    {
        $el = $this->_decoder->getElement();
        if ($el[Horde_ActiveSync_Wbxml::EN_TYPE] != Horde_ActiveSync_Wbxml::EN_TYPE_STARTTAG) {
            return false;
        }

        $create = $update = $delete = false;

        if ($el[Horde_ActiveSync_Wbxml::EN_TAG] == self::FOLDERHIERARCHY_FOLDERCREATE) {
            $create = true;
        } elseif ($el[Horde_ActiveSync_Wbxml::EN_TAG] == self::FOLDERHIERARCHY_FOLDERUPDATE) {
            $update = true;
        } elseif ($el[Horde_ActiveSync_Wbxml::EN_TAG] == self::FOLDERHIERARCHY_FOLDERDELETE) {
            $delete = true;
        }

        if (!$create && !$update && !$delete) {
            return false;
        }

        // SyncKey
        if (!$this->_decoder->getElementStartTag(self::FOLDERHIERARCHY_SYNCKEY)) {
            return false;
        }
        $synckey = $this->_decoder->getElementContent();
        if (!$this->_decoder->getElementEndTag()) {
            return false;
        }

        // ServerID
        $serverid = false;
        if ($this->_decoder->getElementStartTag(self::FOLDERHIERARCHY_SERVERENTRYID)) {
            $serverid = $this->_decoder->getElementContent();
            if (!$this->_decoder->getElementEndTag()) {
                return false;
            }
        }

        // when creating or updating more information is necessary
        if (!$delete) {
            // Parent
            $parentid = false;
            if ($this->_decoder->getElementStartTag(self::FOLDERHIERARCHY_PARENTID)) {
                $parentid = $this->_decoder->getElementContent();
                if (!$this->_decoder->getElementEndTag()) {
                    return false;
                }
            }

            // Displayname
            if (!$this->_decoder->getElementStartTag(self::FOLDERHIERARCHY_DISPLAYNAME)) {
                return false;
            }
            $displayname = $this->_decoder->getElementContent();
            if (!$this->_decoder->getElementEndTag()) {
                return false;
            }

            // Type
            $type = false;
            if ($this->_decoder->getElementStartTag(self::FOLDERHIERARCHY_TYPE)) {
                $type = $this->_decoder->getElementContent();
                if (!$this->_decoder->getElementEndTag()) {
                    return false;
                }
            }
        }

        if (!$this->_decoder->getElementEndTag()) {
            return false;
        }

        // Get state of hierarchy
        $syncstate = $this->_stateMachine->loadState($synckey);
        $newsynckey = $this->_stateMachine->getNewSyncKey($synckey);

        // additional information about already seen folders
        $seenfolders = unserialize($this->_stateMachine->loadState('s' . $synckey));
        if (!$seenfolders) {
            $seenfolders = array();
        }
        // Configure importer with last state
        $importer = $this->_driver->GetHierarchyImporter();
        $importer->Config($syncstate);

        if (!$delete) {
            // Send change
            $serverid = $importer->ImportFolderChange($serverid, $parentid, $displayname, $type);
        } else {
            // delete folder
            $deletedstat = $importer->ImportFolderDeletion($serverid, 0);
        }

        $this->_encoder->startWBXML();
        if ($create) {
            // add folder id to the seen folders
            $seenfolders[] = $serverid;

            $this->_encoder->startTag(self::FOLDERHIERARCHY_FOLDERCREATE);


            $this->_encoder->startTag(self::FOLDERHIERARCHY_STATUS);
            $this->_encoder->content(1);
            $this->_encoder->endTag();

            $this->_encoder->startTag(self::FOLDERHIERARCHY_SYNCKEY);
            $this->_encoder->content($newsynckey);
            $this->_encoder->endTag();

            $this->_encoder->startTag(self::FOLDERHIERARCHY_SERVERENTRYID);
            $this->_encoder->content($serverid);
            $this->_encoder->endTag();

            $this->_encoder->endTag();

            $this->_encoder->endTag();
        } elseif ($update) {

            $this->_encoder->startTag(self::FOLDERHIERARCHY_FOLDERUPDATE);

            $this->_encoder->startTag(self::FOLDERHIERARCHY_STATUS);
            $this->_encoder->content(1);
            $this->_encoder->endTag();

            $this->_encoder->startTag(self::FOLDERHIERARCHY_SYNCKEY);
            $this->_encoder->content($newsynckey);
            $this->_encoder->endTag();

            $this->_encoder->endTag();
        } elseif ($delete) {
            $this->_encoder->startTag(self::FOLDERHIERARCHY_FOLDERDELETE);

            $this->_encoder->startTag(self::FOLDERHIERARCHY_STATUS);
            $this->_encoder->content($deletedstat);
            $this->_encoder->endTag();

            $this->_encoder->startTag(self::FOLDERHIERARCHY_SYNCKEY);
            $this->_encoder->content($newsynckey);
            $this->_encoder->endTag();

            $this->_encoder->endTag();

            // remove folder from the folderflags array
            if (($sid = array_search($serverid, $seenfolders)) !== false) {
                unset($seenfolders[$sid]);
                $seenfolders = array_values($seenfolders);
                $this->_logger->debug('Deleted from seenfolders: ' . $serverid);
            }
        }

        $this->_encoder->endTag();
        // Save the sync state for the next time
        $this->_stateMachine->setState($newsynckey, $importer->GetState());
        $this->_stateMachine->setState('s' . $newsynckey, serialize($seenfolders));
        $this->_stateMachine->save();

        return true;
    }

    /**
     * handle meetingresponse method
     */
    public function handleMeetingResponse($protocolversion)
    {
        $requests = Array();
        if (!$this->_decoder->getElementStartTag(SYNC_MEETINGRESPONSE_MEETINGRESPONSE)) {
            return false;
        }

        while ($this->_decoder->getElementStartTag(SYNC_MEETINGRESPONSE_REQUEST)) {
            $req = Array();

            if ($this->_decoder->getElementStartTag(SYNC_MEETINGRESPONSE_USERRESPONSE)) {
                $req['response'] = $this->_decoder->getElementContent();
                if (!$this->_decoder->getElementEndTag()) {
                    return false;
                }
            }

            if ($this->_decoder->getElementStartTag(SYNC_MEETINGRESPONSE_FOLDERID)) {
                $req['folderid'] = $this->_decoder->getElementContent();
                if (!$this->_decoder->getElementEndTag()) {
                    return false;
                }
            }

            if ($this->_decoder->getElementStartTag(SYNC_MEETINGRESPONSE_REQUESTID)) {
                $req['requestid'] = $this->_decoder->getElementContent();
                if (!$this->_decoder->getElementEndTag()) {
                    return false;
                }
            }

            if (!$this->_decoder->getElementEndTag()) {
                return false;
            }

            array_push($requests, $req);
        }

        if (!$this->_decoder->getElementEndTag()) {
            return false;
        }

        // Start output, simply the error code, plus the ID of the calendar item that was generated by the
        // accept of the meeting response
        $this->_encoder->StartWBXML();
        $this->_encoder->startTag(SYNC_MEETINGRESPONSE_MEETINGRESPONSE);

        foreach ($requests as $req) {
            $calendarid = '';
            $ok = $this->_driver->MeetingResponse($req['requestid'], $req['folderid'], $req['response'], $calendarid);
            $this->_encoder->startTag(SYNC_MEETINGRESPONSE_RESULT);
            $this->_encoder->startTag(SYNC_MEETINGRESPONSE_REQUESTID);
            $this->_encoder->content($req['requestid']);
            $this->_encoder->endTag();
            $this->_encoder->startTag(SYNC_MEETINGRESPONSE_STATUS);
            $this->_encoder->content($ok ? 1 : 2);
            $this->_encoder->endTag();
            if ($ok) {
                $this->_encoder->startTag(SYNC_MEETINGRESPONSE_CALENDARID);
                $this->_encoder->content($calendarid);
                $this->_encoder->endTag();
            }
            $this->_encoder->endTag();
        }

        $this->_encoder->endTag();

        return true;
    }


    /**
     * @param $protocolversion
     * @return unknown_type
     */
    public function handleFolderUpdate($protocolversion)
    {
        return $this->handleFolderCreate($protocolversion);
    }

    /**
     *
     * @param $protocolversion
     * @return unknown_type
     */
    public function handleFolderDelete($protocolversion) {
        return $this->handleFolderCreate($this->_driver, $protocolversion);
    }

    static public function provisioningRequired()
    {
        self::provisionHeader();
        self::activeSyncHeader();
        self::versionHeader();
        self::commandsHeader();
        header("Cache-Control: private");
    }

    /**
     * @param $devid
     * @param $protocolversion
     * @return unknown_type
     */
    public function handleSearch($devid, $protocolversion)
    {
        $searchrange = '0';
        if (!$this->_decoder->getElementStartTag(SYNC_SEARCH_SEARCH)) {
            return false;
        }

        if (!$this->_decoder->getElementStartTag(SYNC_SEARCH_STORE)) {
            return false;
        }

        if (!$this->_decoder->getElementStartTag(SYNC_SEARCH_NAME)) {
            return false;
        }
        $searchname = $this->_decoder->getElementContent();
        if (!$this->_decoder->getElementEndTag()) {
            return false;
        }

        if (!$this->_decoder->getElementStartTag(SYNC_SEARCH_QUERY)) {
            return false;
        }
        $searchquery = $this->_decoder->getElementContent();
        if (!$this->_decoder->getElementEndTag()) {
            return false;
        }

        if ($this->_decoder->getElementStartTag(SYNC_SEARCH_OPTIONS)) {
            while(1) {
                if ($this->_decoder->getElementStartTag(SYNC_SEARCH_RANGE)) {
                    $searchrange = $this->_decoder->getElementContent();
                    if (!$this->_decoder->getElementEndTag()) {
                        return false;
                    }
                }
                $e = $this->_decoder->peek();
                if ($e[Horde_ActiveSync_Wbxml::EN_TYPE] == Horde_ActiveSync_Wbxml::EN_TYPE_ENDTAG) {
                    $this->_decoder->getElementEndTag();
                    break;
                }
            }
        }
        if (!$this->_decoder->getElementEndTag()) {//store
            return false;
        }

        if (!$this->_decoder->getElementEndTag()) {//search
            return false;
        }

        if (strtoupper($searchname) != "GAL") {
            $this->_logger->err('Searchtype ' . $searchname . 'is not supported');
            return false;
        }
        //get search results from backend
        $rows = $this->_driver->getSearchResults($searchquery, $searchrange);

        $this->_encoder->startWBXML();
        $this->_encoder->startTag(SYNC_SEARCH_SEARCH);

            $this->_encoder->startTag(SYNC_SEARCH_STATUS);
            $this->_encoder->content(1);
            $this->_encoder->endTag();

            $this->_encoder->startTag(SYNC_SEARCH_RESPONSE);
                $this->_encoder->startTag(SYNC_SEARCH_STORE);

                    $this->_encoder->startTag(SYNC_SEARCH_STATUS);
                    $this->_encoder->content(1);
                    $this->_encoder->endTag();

                    if (is_array($rows) && !empty($rows)) {
                        $searchrange = $rows['range'];
                        unset($rows['range']);
                        foreach ($rows as $u) {
                            $this->_encoder->startTag(SYNC_SEARCH_RESULT);
                                $this->_encoder->startTag(SYNC_SEARCH_PROPERTIES);

                                    $this->_encoder->startTag(SYNC_GAL_DISPLAYNAME);
                                    $this->_encoder->content($u["fullname"]);
                                    $this->_encoder->endTag();

                                    $this->_encoder->startTag(SYNC_GAL_PHONE);
                                    $this->_encoder->content($u["businessphone"]);
                                    $this->_encoder->endTag();

                                    $this->_encoder->startTag(SYNC_GAL_ALIAS);
                                    $this->_encoder->content($u["username"]);
                                    $this->_encoder->endTag();

                                    //it's not possible not get first and last name of an user
                                    //from the gab and user functions, so we just set fullname
                                    //to lastname and leave firstname empty because nokia needs
                                    //first and lastname in order to display the search result
                                    $this->_encoder->startTag(SYNC_GAL_FIRSTNAME);
                                    $this->_encoder->content("");
                                    $this->_encoder->endTag();

                                    $this->_encoder->startTag(SYNC_GAL_LASTNAME);
                                    $this->_encoder->content($u["fullname"]);
                                    $this->_encoder->endTag();

                                    $this->_encoder->startTag(SYNC_GAL_EMAILADDRESS);
                                    $this->_encoder->content($u["emailaddress"]);
                                    $this->_encoder->endTag();

                                $this->_encoder->endTag();//result
                            $this->_encoder->endTag();//properties
                        }
                        $this->_encoder->startTag(SYNC_SEARCH_RANGE);
                        $this->_encoder->content($searchrange);
                        $this->_encoder->endTag();

                        $this->_encoder->startTag(SYNC_SEARCH_TOTAL);
                        $this->_encoder->content(count($rows));
                        $this->_encoder->endTag();
                    }

                $this->_encoder->endTag();//store
            $this->_encoder->endTag();//response
        $this->_encoder->endTag();//search


        return true;
    }

    /**
     * The heart of the server. Dispatch a request to the request object to
     * handle.
     *
     * @param string $cmd    The command we are requesting.
     * @param string $devId  The device id making the request.
     *
     * @return boolean
     */
    public function handleRequest($cmd, $devId)
    {
        /* Check that this device is known */
        $state = $this->_driver->getStateObject();
        if (!empty($devId) && !$state->deviceExists($devId)) {
            $get = $this->_request->getGetParams();
            $device = new StdClass();
            $device->userAgent = $this->_request->getHeader('User-Agent');
            $device->deviceType = !empty($get['DeviceType']) ? $get['DeviceType'] : '';
            $device->policykey = 0;
            $device->rwstatus = 0;
            $state->setDeviceInfo($devId, $device);
        }

        /* Load the request handler to handle the request */
        $class = 'Horde_ActiveSync_Request_' . basename($cmd);
        $version = $this->getProtocolVersion();
        if (class_exists($class)) {
            $request = new $class($this->_driver,
                                  $this->_decoder,
                                  $this->_encoder,
                                  $this->_request,
                                  $this->_provisioning);
            $request->setLogger($this->_logger);

            return $request->handle($this, $devId);
        }

        // @TODO: Leave the following in place until all are refactored...then throw
        // an error if the class does not exist.
        switch($cmd) {
            case 'SmartForward':
                $status = $this->handleSmartForward($version);
                break;
            case 'SmartReply':
                $status = $this->handleSmartReply($version);
                break;
            case 'GetAttachment':
                $status = $this->handleGetAttachment($version);
                break;
            case 'GetHierarchy':
                $status = $this->handleGetHierarchy($version, $devId);
                break;
            case 'CreateCollection':
                $status = $this->handleCreateCollection($version);
                break;
            case 'DeleteCollection':
                $status = $this->handleDeleteCollection($version);
                break;
            case 'MoveCollection':
                $status = $this->handleMoveCollection($version);
                break;
            case 'FolderCreate':
                $status = $this->handleFolderCreate($version);
                break;
            case 'FolderDelete':
                $status = $this->handleFolderDelete($version);
                break;
            case 'FolderUpdate':
                $status = $this->handleFolderUpdate($version);
                break;
            case 'MoveItems':
                $status = $this->handleMoveItems($version);
                break;
            case 'GetItemEstimate':
                $status = $this->handleGetItemEstimate($version, $devId);
                break;
            case 'MeetingResponse':
                $status = $this->handleMeetingResponse($version);
                break;
            case 'Notify': // Used for sms-based notifications (pushmail)
                $status = $this->handleNotify($version);
                break;
            case 'Search':
                $status = $this->handleSearch($devId, $version);
                break;

            default:
                $this->_logger->err('Unknown command - not implemented');
                $status = false;
                break;
        }

        return $status;
    }

    /**
     * Send the MS_Server-ActiveSync header
     * (This is the version Exchange 2003 implements)
     *
     * @return void
     */
    static public function activeSyncHeader()
    {
        header("MS-Server-ActiveSync: 6.5.7638.1");
    }

    /**
     * Send the protocol versions header
     *
     * @return void
     */
    static public function versionHeader()
    {
        header("MS-ASProtocolVersions: 1.0,2.0,2.1,2.5");
    }

    /**
     * send protocol commands header
     *
     * @return void
     */
    static public function commandsHeader()
    {
        header("MS-ASProtocolCommands: Sync,SendMail,SmartForward,SmartReply,GetAttachment,GetHierarchy,CreateCollection,DeleteCollection,MoveCollection,FolderSync,FolderCreate,FolderDelete,FolderUpdate,MoveItems,GetItemEstimate,MeetingResponse,ResolveRecipients,ValidateCert,Provision,Search,Ping");
    }

    /**
     * Send provision header
     *
     * @return void
     */
    static public function provisionHeader()
    {
        header("HTTP/1.1 449 Retry after sending a PROVISION command");
    }

    /**
     * Obtain the policy key header from the request.
     *
     * @return int  The policy key or zero if not set.
     */
    public function getPolicyKey()
    {
        /* Policy key headers may be sent in either of these forms: */
        $this->_policykey = $this->_request->getHeader('X-Ms-Policykey');
        if (empty($this->_policykey)) {
            $this->_policykey = $this->_request->getHeader('X-MS-PolicyKey');
        }

        if (empty($this->_policykey)) {
            $this->_policykey = 0;
        }

        return $this->_policykey;
    }

    /**
     * Obtain the ActiveSync protocol version
     */
    public function getProtocolVersion()
    {
        if (isset($this->_version)) {
            return $this->_version;
        }

        $this->_version = $this->_request->getHeader('Ms-Asprotocolversion');
        if (empty($this->_version)) {
            $this->_version = $this->_request->getHeader('MS-ASProtocolVersion');
        }
        if (empty($this->_version)) {
            $this->_version = '1.0';
        }
    }

}