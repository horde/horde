<?php
/**
 * Horde_ActiveSync_Wbxml::
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
 * @copyright 2009-2012 Horde LLC (http://www.horde.org)
 * @author    Michael J Rubinsky <mrubinsk@horde.org>
 * @package   ActiveSync
 */

/**
 * Wbxml handler
 *
 * @license   http://www.horde.org/licenses/gpl GPLv2
 *            NOTE: According to sec. 8 of the GENERAL PUBLIC LICENSE (GPL),
 *            Version 2, the distribution of the Horde_ActiveSync module in or
 *            to the United States of America is excluded from the scope of this
 *            license.
 * @copyright 2009-2012 Horde LLC (http://www.horde.org)
 * @author    Michael J Rubinsky <mrubinsk@horde.org>
 * @package   ActiveSync
 */
class Horde_ActiveSync_Wbxml
{
    const SWITCH_PAGE =     0x00;
    const END =             0x01;
    const ENTITY =          0x02;
    const STR_I =           0x03;
    const LITERAL =         0x04;
    const EXT_I_0 =         0x40;
    const EXT_I_1 =         0x41;
    const EXT_I_2 =         0x42;
    const PI =              0x43;
    const LITERAL_C =       0x44;
    const EXT_T_0 =         0x80;
    const EXT_T_1 =         0x81;
    const EXT_T_2 =         0x82;
    const STR_T =           0x83;
    const LITERAL_A =       0x84;
    const EXT_0 =           0xC0;
    const EXT_1 =           0xC1;
    const EXT_2 =           0xC2;
    const OPAQUE =          0xC3;
    const LITERAL_AC =      0xC4;

    const EN_TYPE =                1;
    const EN_TAG =                 2;
    const EN_CONTENT =             3;
    const EN_FLAGS =               4;
    const EN_ATTRIBUTES =          5;

    const EN_TYPE_STARTTAG =       1;
    const EN_TYPE_ENDTAG =         2;
    const EN_TYPE_CONTENT =        3;

    const EN_FLAGS_CONTENT =       1;
    const EN_FLAGS_ATTRIBUTES =    2;

    /**
     * The code page definitions for the wbxml encoder/decoders
     *
     * @var array
     */
    protected $_dtd = array(
        'codes' => array (
            0 => array (
                0x05 => 'Synchronize',
                0x06 => 'Replies',
                0x07 => 'Add',
                0x08 => 'Modify',
                0x09 => 'Remove',
                0x0a => 'Fetch',
                0x0b => 'SyncKey',
                0x0c => 'ClientEntryId',
                0x0d => 'ServerEntryId',
                0x0e => 'Status',
                0x0f => 'Folder',
                0x10 => 'FolderType',
                0x11 => 'Version',
                0x12 => 'FolderId',
                0x13 => 'GetChanges',
                0x14 => 'MoreAvailable',
                0x15 => 'WindowSize',
                0x16 => 'Commands',
                0x17 => 'Options',
                0x18 => 'FilterType',
                0x19 => 'Truncation',
                0x1a => 'RtfTruncation',
                0x1b => 'Conflict',
                0x1c => 'Folders',
                0x1d => 'Data',
                0x1e => 'DeletesAsMoves',
                0x1f => 'NotifyGUID',
                0x20 => 'Supported',
                0x21 => 'SoftDelete',
                0x22 => 'MIMESupport',
                0x23 => 'MIMETruncation',
                0x24 => 'Wait',
                0x25 => 'Limit',
                0x26 => 'Partial',
            ),
            /* POOMCONTACTS */
            1 => array (
                0x05 => 'Anniversary',
                0x06 => 'AssistantName',
                0x07 => 'AssistnamePhoneNumber',
                0x08 => 'Birthday',
                0x09 => 'Body',
                0x0a => 'BodySize',
                0x0b => 'BodyTruncated',
                0x0c => 'Business2PhoneNumber',
                0x0d => 'BusinessCity',
                0x0e => 'BusinessCountry',
                0x0f => 'BusinessPostalCode',
                0x10 => 'BusinessState',
                0x11 => 'BusinessStreet',
                0x12 => 'BusinessFaxNumber',
                0x13 => 'BusinessPhoneNumber',
                0x14 => 'CarPhoneNumber',
                0x15 => 'Categories',
                0x16 => 'Category',
                0x17 => 'Children',
                0x18 => 'Child',
                0x19 => 'CompanyName',
                0x1a => 'Department',
                0x1b => 'Email1Address',
                0x1c => 'Email2Address',
                0x1d => 'Email3Address',
                0x1e => 'FileAs',
                0x1f => 'FirstName',
                0x20 => 'Home2PhoneNumber',
                0x21 => 'HomeCity',
                0x22 => 'HomeCountry',
                0x23 => 'HomePostalCode',
                0x24 => 'HomeState',
                0x25 => 'HomeStreet',
                0x26 => 'HomeFaxNumber',
                0x27 => 'HomePhoneNumber',
                0x28 => 'JobTitle',
                0x29 => 'LastName',
                0x2a => 'MiddleName',
                0x2b => 'MobilePhoneNumber',
                0x2c => 'OfficeLocation',
                0x2d => 'OtherCity',
                0x2e => 'OtherCountry',
                0x2f => 'OtherPostalCode',
                0x30 => 'OtherState',
                0x31 => 'OtherStreet',
                0x32 => 'PagerNumber',
                0x33 => 'RadioPhoneNumber',
                0x34 => 'Spouse',
                0x35 => 'Suffix',
                0x36 => 'Title',
                0x37 => 'WebPage',
                0x38 => 'YomiCompanyName',
                0x39 => 'YomiFirstName',
                0x3a => 'YomiLastName',
                0x3b => 'Rtf',
                0x3c => 'Picture',
            ),
            /* POOMMAIL */
            2 => array (
                0x05 => 'Attachment',
                0x06 => 'Attachments',
                0x07 => 'AttName',
                0x08 => 'AttSize',
                0x09 => 'AttOid',
                0x0a => 'AttMethod',
                0x0b => 'AttRemoved',
                0x0c => 'Body',
                0x0d => 'BodySize',
                0x0e => 'BodyTruncated',
                0x0f => 'DateReceived',
                0x10 => 'DisplayName',
                0x11 => 'DisplayTo',
                0x12 => 'Importance',
                0x13 => 'MessageClass',
                0x14 => 'Subject',
                0x15 => 'Read',
                0x16 => 'To',
                0x17 => 'Cc',
                0x18 => 'From',
                0x19 => 'Reply-To',
                0x1a => 'AllDayEvent',
                0x1b => 'Categories',
                0x1c => 'Category',
                0x1d => 'DtStamp',
                0x1e => 'EndTime',
                0x1f => 'InstanceType',
                0x20 => 'BusyStatus',
                0x21 => 'Location',
                0x22 => 'MeetingRequest',
                0x23 => 'Organizer',
                0x24 => 'RecurrenceId',
                0x25 => 'Reminder',
                0x26 => 'ResponseRequested',
                0x27 => 'Recurrences',
                0x28 => 'Recurrence',
                0x29 => 'Type',
                0x2a => 'Until',
                0x2b => 'Occurrences',
                0x2c => 'Interval',
                0x2d => 'DayOfWeek',
                0x2e => 'DayOfMonth',
                0x2f => 'WeekOfMonth',
                0x30 => 'MonthOfYear',
                0x31 => 'StartTime',
                0x32 => 'Sensitivity',
                0x33 => 'TimeZone',
                0x34 => 'GlobalObjId',
                0x35 => 'ThreadTopic',
                0x36 => 'MIMEData',
                0x37 => 'MIMETruncated',
                0x38 => 'MIMESize',
                0x39 => 'InternetCPID',
                // EAS 12.0
                0x3a => 'Flag',
                0x3b => 'FlagStatus',
                0x3c => 'ContentClass',
                0x3d => 'FlagType',
                0x3e => 'CompleteTime',
            ),

            /* 3 == AirNotify == deprecated */

            /* POOMCAL */
            4 => array (
                0x05 => 'Timezone',
                0x06 => 'AllDayEvent',
                0x07 => 'Attendees',
                0x08 => 'Attendee',
                0x09 => 'Email',
                0x0a => 'Name',
                0x0b => 'Body',
                0x0c => 'BodyTruncated',
                0x0d => 'BusyStatus',
                0x0e => 'Categories',
                0x0f => 'Category',
                0x10 => 'Rtf',
                0x11 => 'DtStamp',
                0x12 => 'EndTime',
                0x13 => 'Exception',
                0x14 => 'Exceptions',
                0x15 => 'Deleted',
                0x16 => 'ExceptionStartTime',
                0x17 => 'Location',
                0x18 => 'MeetingStatus',
                0x19 => 'OrganizerEmail',
                0x1a => 'OrganizerName',
                0x1b => 'Recurrence',
                0x1c => 'Type',
                0x1d => 'Until',
                0x1e => 'Occurrences',
                0x1f => 'Interval',
                0x20 => 'DayOfWeek',
                0x21 => 'DayOfMonth',
                0x22 => 'WeekOfMonth',
                0x23 => 'MonthOfYear',
                0x24 => 'Reminder',
                0x25 => 'Sensitivity',
                0x26 => 'Subject',
                0x27 => 'StartTime',
                0x28 => 'UID',
                // EAS 12.0
                0x29 => 'AttendeeStatus',
                0x2A => 'AttendeeType',
                // EAS 12.1
                0x2B => 'Attachment',
                0x2C => 'Attachments',
                0x2D => 'AttName',
                0x2E => 'AttSize',
                0x2F => 'AttOid',
                0x30 => 'AttMethod',
                0x31 => 'AttRemoved',
                0x32 => 'DisplayName',
            ),
            /* MOVE */
            5 => array (
                0x05 => 'Moves',
                0x06 => 'Move',
                0x07 => 'SrcMsgId',
                0x08 => 'SrcFldId',
                0x09 => 'DstFldId',
                0x0a => 'Response',
                0x0b => 'Status',
                0x0c => 'DstMsgId',
            ),
            /* GETITEMESTIMATE */
            6 => array (
                0x05 => 'GetItemEstimate',
                0x06 => 'Version',
                0x07 => 'Folders',
                0x08 => 'Folder',
                0x09 => 'FolderType',
                0x0a => 'FolderId',
                0x0b => 'DateTime',
                0x0c => 'Estimate',
                0x0d => 'Response',
                0x0e => 'Status',
            ),
            /* FOLDERHIERARCHY */
            7 => array (
                0x05 => 'Folders',
                0x06 => 'Folder',
                0x07 => 'DisplayName',
                0x08 => 'ServerEntryId',
                0x09 => 'ParentId',
                0x0a => 'Type',
                0x0b => 'Response',
                0x0c => 'Status',
                0x0d => 'ContentClass',
                0x0e => 'Changes',
                0x0f => 'Add',
                0x10 => 'Remove',
                0x11 => 'Update',
                0x12 => 'SyncKey',
                0x13 => 'FolderCreate',
                0x14 => 'FolderDelete',
                0x15 => 'FolderUpdate',
                0x16 => 'FolderSync',
                0x17 => 'Count',
                0x18 => 'Version',
            ),
            /* MEETINGRESPONSE */
            8 => array (
                0x05 => 'CalendarId',
                0x06 => 'FolderId',
                0x07 => 'MeetingResponse',
                0x08 => 'RequestId',
                0x09 => 'Request',
                0x0a => 'Result',
                0x0b => 'Status',
                0x0c => 'UserResponse',
                0x0d => 'Version',
            ),
            /* POOMTASKS */
            9 => array (
                0x05 => 'Body',
                0x06 => 'BodySize',
                0x07 => 'BodyTruncated',
                0x08 => 'Categories',
                0x09 => 'Category',
                0x0a => 'Complete',
                0x0b => 'DateCompleted',
                0x0c => 'DueDate',
                0x0d => 'UtcDueDate',
                0x0e => 'Importance',
                0x0f => 'Recurrence',
                0x10 => 'Type',
                0x11 => 'Start',
                0x12 => 'Until',
                0x13 => 'Occurrences',
                0x14 => 'Interval',
                0x16 => 'DayOfWeek',
                0x15 => 'DayOfMonth',
                0x17 => 'WeekOfMonth',
                0x18 => 'MonthOfYear',
                0x19 => 'Regenerate',
                0x1a => 'DeadOccur',
                0x1b => 'ReminderSet',
                0x1c => 'ReminderTime',
                0x1d => 'Sensitivity',
                0x1e => 'StartDate',
                0x1f => 'UtcStartDate',
                0x20 => 'Subject',
                0x21 => 'Rtf',
                // EAS 12.0
                0x22 => 'OrdinalDate',
                0x23 => 'SubOrdinalDate'
            ),
            /* RESOLVERECIPIENTS */
            0xa => array (
                0x05 => 'ResolveRecipients',
                0x06 => 'Response',
                0x07 => 'Status',
                0x08 => 'Type',
                0x09 => 'Recipient',
                0x0a => 'DisplayName',
                0x0b => 'EmailAddress',
                0x0c => 'Certificates',
                0x0d => 'Certificate',
                0x0e => 'MiniCertificate',
                0x0f => 'Options',
                0x10 => 'To',
                0x11 => 'CertificateRetrieval',
                0x12 => 'RecipientCount',
                0x13 => 'MaxCertificates',
                0x14 => 'MaxAmbiguousRecipients',
                0x15 => 'CertificateCount',
            ),
            /* VALIDATECERT */
            0xb => array (
                0x05 => 'ValidateCert',
                0x06 => 'Certificates',
                0x07 => 'Certificate',
                0x08 => 'CertificateChain',
                0x09 => 'CheckCRL',
                0x0a => 'Status',
            ),
            /* POOMCONTACTS2*/
            0xc => array (
                0x05 => 'CustomerId',
                0x06 => 'GovernmentId',
                0x07 => 'IMAddress',
                0x08 => 'IMAddress2',
                0x09 => 'IMAddress3',
                0x0a => 'ManagerName',
                0x0b => 'CompanyMainPhone',
                0x0c => 'AccountName',
                0x0d => 'NickName',
                0x0e => 'MMS',
            ),
            /* PING */
            0xd => array (
                0x05 => 'Ping',
                0x06 => 'AutdState',
                0x07 => 'Status',
                0x08 => 'HeartbeatInterval',
                0x09 => 'Folders',
                0x0a => 'Folder',
                0x0b => 'ServerEntryId',
                0x0c => 'FolderType',
                0x0d => 'MaxFolders'
            ),
            /* PROVISION */
            0xe => array (
                0x05 => 'Provision',
                0x06 => 'Policies',
                0x07 => 'Policy',
                0x08 => 'PolicyType',
                0x09 => 'PolicyKey',
                0x0A => 'Data',
                0x0B => 'Status',
                0x0C => 'RemoteWipe',
                0x0D => 'EASProvisionDoc',
                // EAS 12.0
                0x0E => 'DevicePasswordEnabled',
                0x0F => 'AlphanumericDevicePasswordRequired',
                0x10 => 'DeviceEncryptionEnabled',
                0x11 => 'PasswordRecoveryEnabled',
                0x12 => 'DocumentBrowseEnabled',
                0x13 => 'AttachmentsEnabled',
                0x14 => 'MinDevicePasswordLength',
                0x15 => 'MaxInactivityTimeDeviceLock',
                0x16 => 'MaxDevicePasswordFailedAttempts',
                0x17 => 'MaxAttachmentSize',
                0x18 => 'AllowSimpleDevicePassword',
                0x19 => 'DevicePasswordExpiration',
                0x1A => 'DevicePasswordHistory',
                // EAS 12.1
                0x1B => 'AllowStorageCard',
                0x1C => 'AllowCamera',
                0x1D => 'RequireDeviceEncryption',
                0x1E => 'AllowUnsignedApplications',
                0x1F => 'AllowUnsignedInstallationPackages',
                0x20 => 'MinDevicePasswordComplexCharacters',
                0x21 => 'AllowWiFi',
                0x22 => 'AllowTextMessaging',
                0x23 => 'AllowPOPIMAPEmail',
                0x24 => 'AllowBluetooth',
                0x25 => 'AllowIrDA',
                0x26 => 'RequireManualSyncWhenRoaming',
                0x27 => 'AllowDesktopSync',
                0x28 => 'MaxCalendarAgeFilter',
                0x29 => 'AllowHTMLEmail',
                0x2A => 'MaxEmailAgeFilter',
                0x2B => 'MaxEmailBodyTruncationSize',
                0x2C => 'MaxHTMLBodyTruncationSize',
                0x2D => 'RequireSignedSMIMEMessages',
                0x2E => 'RequireEncryptedSMIMEMessages',
                0x2F => 'RequireSignedSMIMEAlgorithm',
                0x30 => 'RequireEncryptedSMIMEAlgorithm',
                0x31 => 'AllowSMIMEEncryptionAlgorithmNegotiation',
                0x32 => 'AllowSMIMESoftCerts',
                0x33 => 'AllowBrowser',
                0x34 => 'AllowConsumerEmail',
                0x35 => 'AllowRemoteDesktop',
                0x36 => 'AllowInternetSharing',
                0x37 => 'UnapprovedInROMApplicationList',
                0x38 => 'ApplicationName',
                0x39 => 'ApprovedApplicationList',
                0x3A => 'Hash',
            ),
            /* SEARCH */
            0xf => array(
                0x05 => 'Search',
                0x07 => 'Store',
                0x08 => 'Name',
                0x09 => 'Query',
                0x0A => 'Options',
                0x0B => 'Range',
                0x0C => 'Status',
                0x0D => 'Response',
                0x0E => 'Result',
                0x0F => 'Properties',
                0x10 => 'Total',
                0x11 => 'EqualTo',
                0x12 => 'Value',
                0x13 => 'And',
                0x14 => 'Or',
                0x15 => 'FreeText',
                0x17 => 'DeepTraversal',
                0x18 => 'LongId',
                0x19 => 'RebuildResults',
                0x1A => 'LessThan',
                0x1B => 'GreaterThan',
                0x1C => 'Schema',
                0x1D => 'Supported',
                // EAS 12.1
                0x1E => 'UserName',
                0x1F => 'Password',
                0x20 => 'ConversationId',
            ),
            /* GAL (Global Address List) */
            0x10 => array(
                0x05 => 'DisplayName',
                0x06 => 'Phone',
                0x07 => 'Office',
                0x08 => 'Title',
                0x09 => 'Company',
                0x0A => 'Alias',
                0x0B => 'FirstName',
                0x0C => 'LastName',
                0x0D => 'HomePhone',
                0x0E => 'MobilePhone',
                0x0F => 'EmailAddress',
            ),

            // EAS 12.0
            /* AIRSYNCBASE */
            0x11 => array(
                0x05 => 'BodyPreference',
                0x06 => 'Type',
                0x07 => 'TruncationSize',
                0x08 => 'AllOrNone',
                0x0A => 'Body',
                0x0B => 'Data',
                0x0C => 'EstimatedDataSize',
                0x0D => 'Truncated',
                0x0E => 'Attachments',
                0x0F => 'Attachment',
                0x10 => 'DisplayName',
                0x11 => 'FileReference',
                0x12 => 'Method',
                0x13 => 'ContentId',
                0x14 => 'ContentLocation',
                0x15 => 'IsInline',
                0x16 => 'NativeBodyType',
                0x17 => 'ContentType',
                0x18 => 'Preview',
            ),

            /* SETTINGS */
            0x12 => array(
                0x05 => 'Settings',
                0x06 => 'Status',
                0x07 => 'Get',
                0x08 => 'Set',
                0x09 => 'Oof',
                0x0A => 'OofState',
                0x0B => 'StartTime',
                0x0C => 'EndTime',
                0x0D => 'OofMessage',
                0x0E => 'AppliesToInternal',
                0x0F => 'AppliesToExternalKnown',
                0x10 => 'AppliesToExternalUnknown',
                0x11 => 'Enabled',
                0x12 => 'ReplyMessage',
                0x13 => 'BodyType',
                0x14 => 'DevicePassword',
                0x15 => 'Password',
                0x16 => 'DeviceInformation',
                0x17 => 'Model',
                0x18 => 'IMEI',
                0x19 => 'FriendlyName',
                0x1A => 'OS',
                0x1B => 'OSLanguage',
                0x1C => 'PhoneNumber',
                0x1D => 'UserInformation',
                0x1E => 'EmailAddresses',
                0x1F => 'SmtpAddress',
                // EAS 12.1
                0x20 => 'UserAgent',
            ),

            /* Document Library */
            0x13 => array(
                0x05 => 'LinkId',
                0x06 => 'DisplayName',
                0x07 => 'IsFolder',
                0x08 => 'CreationDate',
                0x09 => 'LastModifiedDate',
                0x0A => 'IsHidden',
                0x0B => 'ContentLength',
                0x0C => 'ContentType'
            ),

            /* ITEMOPERATIONS */
            0x14 => array(
                0x05 => 'ItemOperations',
                0x06 => 'Fetch',
                0x07 => 'Store',
                0x08 => 'Options',
                0x09 => 'Range',
                0x0A => 'Total',
                0x0B => 'Properties',
                0x0C => 'Data',
                0x0D => 'Status',
                0x0E => 'Response',
                0x0F => 'Version',
                0x10 => 'Schema',
                0x11 => 'Part',
                0x12 => 'EmptyFolderContent',
                0x13 => 'DeleteSubFolders',
                // EAS 12.1
                0x14 => 'UserName',
                0x15 => 'Password',
            ),

            /* POOMMAIL2 */
            0x16 => array(
                0x05 => 'UmCallerId',
                0x06 => 'UmUserNotes',
                0x07 => 'UmAttDuration',
                0x08 => 'UmAttOrder',
                0x09 => 'ConversationId',
                0x0A => 'ConversationIndex',
                0x0B => 'LastVerbExecuted',
                0x0C => 'LastVerbExecutionTime',
                0x0D => 'ReceivedAsBcc',
                0x0E => 'Sender',
                0x0F => 'CalendarType',
                0x10 => 'IsLeapMonth',
            )
        ),

        'namespaces' => array(
              1    => 'POOMCONTACTS',
              2    => 'POOMMAIL',
              4    => 'POOMCAL',
              5    => 'Move',
              6    => 'GetItemEstimate',
              7    => 'FolderHierarchy',
              8    => 'MeetingResponse',
              9    => 'POOMTASKS',
              0xA  => 'ResolveRecipients',
              0xB  => 'ValidateCert',
              0xC  => 'POOMCONTACTS2',
              0xD  => 'Ping',
              0xE  => 'Provision',
              0xF  => 'Search',
              0x10 => 'GAL',
              // EAS 12.0
              0x11 => 'AirSyncBase',
              0x12 => 'Settings',
              0x13 => 'DocumentLibrary',
              0x14 => 'ItemOperations',
              0x16 => 'POOMMAIL2',
        )
    );

    /**
     * Track the codepage for the currently output tag so we know when to
     * switch codepages.
     *
     * @var integer
     */
    protected $_tagcp = 0;

    /**
     * Used to hold log entries for each tag so we can only output the log
     * entries for the tags that are actually sent (@see $_stack).
     *
     * @var array
     */
    protected $_logStack = array();

    /**
     * Logger
     *
     * @var Horde_Log_Logger
     */
    protected $_logger;

    /**
     * Input or Output stream
     *
     * @var stream resource
     */
     protected $_stream;

    /**
     * [__construct description]
     */
    public function __construct($stream)
    {
        $this->_stream = $stream;
        $this->_logger = new Horde_Support_Stub();
    }

    public function getStream()
    {
        return $this->_stream;
    }

    /**
     * Set the logger instance
     *
     * @param Horde_Log_Logger $logger  The logger.
     */
    public function setLogger(Horde_Log_Logger $logger)
    {
        $this->_logger = $logger;
    }

}