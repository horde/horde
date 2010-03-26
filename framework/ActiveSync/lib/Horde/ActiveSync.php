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
// TODO Class constant these:
define("SYNC_SYNCHRONIZE","Synchronize");
define("SYNC_REPLIES","Replies");
define("SYNC_ADD","Add");
define("SYNC_MODIFY","Modify");
define("SYNC_REMOVE","Remove");
define("SYNC_FETCH","Fetch");
define("SYNC_SYNCKEY","SyncKey");
define("SYNC_CLIENTENTRYID","ClientEntryId");
define("SYNC_SERVERENTRYID","ServerEntryId");
define("SYNC_STATUS","Status");
define("SYNC_FOLDER","Folder");
define("SYNC_FOLDERTYPE","FolderType");
define("SYNC_VERSION","Version");
define("SYNC_FOLDERID","FolderId");
define("SYNC_GETCHANGES","GetChanges");
define("SYNC_MOREAVAILABLE","MoreAvailable");
define("SYNC_WINDOWSIZE","WindowSize");
define("SYNC_COMMANDS","Commands");
define("SYNC_OPTIONS","Options");
define("SYNC_FILTERTYPE","FilterType");
define("SYNC_TRUNCATION","Truncation");
define("SYNC_RTFTRUNCATION","RtfTruncation");
define("SYNC_CONFLICT","Conflict");
define("SYNC_FOLDERS","Folders");
define("SYNC_DATA","Data");
define("SYNC_DELETESASMOVES","DeletesAsMoves");
define("SYNC_NOTIFYGUID","NotifyGUID");
define("SYNC_SUPPORTED","Supported");
define("SYNC_SOFTDELETE","SoftDelete");
define("SYNC_MIMESUPPORT","MIMESupport");
define("SYNC_MIMETRUNCATION","MIMETruncation");
define("SYNC_NEWMESSAGE","NewMessage");

// POOMCONTACTS
define("SYNC_POOMCONTACTS_ANNIVERSARY","POOMCONTACTS:Anniversary");
define("SYNC_POOMCONTACTS_ASSISTANTNAME","POOMCONTACTS:AssistantName");
define("SYNC_POOMCONTACTS_ASSISTNAMEPHONENUMBER","POOMCONTACTS:AssistnamePhoneNumber");
define("SYNC_POOMCONTACTS_BIRTHDAY","POOMCONTACTS:Birthday");
define("SYNC_POOMCONTACTS_BODY","POOMCONTACTS:Body");
define("SYNC_POOMCONTACTS_BODYSIZE","POOMCONTACTS:BodySize");
define("SYNC_POOMCONTACTS_BODYTRUNCATED","POOMCONTACTS:BodyTruncated");
define("SYNC_POOMCONTACTS_BUSINESS2PHONENUMBER","POOMCONTACTS:Business2PhoneNumber");
define("SYNC_POOMCONTACTS_BUSINESSCITY","POOMCONTACTS:BusinessCity");
define("SYNC_POOMCONTACTS_BUSINESSCOUNTRY","POOMCONTACTS:BusinessCountry");
define("SYNC_POOMCONTACTS_BUSINESSPOSTALCODE","POOMCONTACTS:BusinessPostalCode");
define("SYNC_POOMCONTACTS_BUSINESSSTATE","POOMCONTACTS:BusinessState");
define("SYNC_POOMCONTACTS_BUSINESSSTREET","POOMCONTACTS:BusinessStreet");
define("SYNC_POOMCONTACTS_BUSINESSFAXNUMBER","POOMCONTACTS:BusinessFaxNumber");
define("SYNC_POOMCONTACTS_BUSINESSPHONENUMBER","POOMCONTACTS:BusinessPhoneNumber");
define("SYNC_POOMCONTACTS_CARPHONENUMBER","POOMCONTACTS:CarPhoneNumber");
define("SYNC_POOMCONTACTS_CATEGORIES","POOMCONTACTS:Categories");
define("SYNC_POOMCONTACTS_CATEGORY","POOMCONTACTS:Category");
define("SYNC_POOMCONTACTS_CHILDREN","POOMCONTACTS:Children");
define("SYNC_POOMCONTACTS_CHILD","POOMCONTACTS:Child");
define("SYNC_POOMCONTACTS_COMPANYNAME","POOMCONTACTS:CompanyName");
define("SYNC_POOMCONTACTS_DEPARTMENT","POOMCONTACTS:Department");
define("SYNC_POOMCONTACTS_EMAIL1ADDRESS","POOMCONTACTS:Email1Address");
define("SYNC_POOMCONTACTS_EMAIL2ADDRESS","POOMCONTACTS:Email2Address");
define("SYNC_POOMCONTACTS_EMAIL3ADDRESS","POOMCONTACTS:Email3Address");
define("SYNC_POOMCONTACTS_FILEAS","POOMCONTACTS:FileAs");
define("SYNC_POOMCONTACTS_FIRSTNAME","POOMCONTACTS:FirstName");
define("SYNC_POOMCONTACTS_HOME2PHONENUMBER","POOMCONTACTS:Home2PhoneNumber");
define("SYNC_POOMCONTACTS_HOMECITY","POOMCONTACTS:HomeCity");
define("SYNC_POOMCONTACTS_HOMECOUNTRY","POOMCONTACTS:HomeCountry");
define("SYNC_POOMCONTACTS_HOMEPOSTALCODE","POOMCONTACTS:HomePostalCode");
define("SYNC_POOMCONTACTS_HOMESTATE","POOMCONTACTS:HomeState");
define("SYNC_POOMCONTACTS_HOMESTREET","POOMCONTACTS:HomeStreet");
define("SYNC_POOMCONTACTS_HOMEFAXNUMBER","POOMCONTACTS:HomeFaxNumber");
define("SYNC_POOMCONTACTS_HOMEPHONENUMBER","POOMCONTACTS:HomePhoneNumber");
define("SYNC_POOMCONTACTS_JOBTITLE","POOMCONTACTS:JobTitle");
define("SYNC_POOMCONTACTS_LASTNAME","POOMCONTACTS:LastName");
define("SYNC_POOMCONTACTS_MIDDLENAME","POOMCONTACTS:MiddleName");
define("SYNC_POOMCONTACTS_MOBILEPHONENUMBER","POOMCONTACTS:MobilePhoneNumber");
define("SYNC_POOMCONTACTS_OFFICELOCATION","POOMCONTACTS:OfficeLocation");
define("SYNC_POOMCONTACTS_OTHERCITY","POOMCONTACTS:OtherCity");
define("SYNC_POOMCONTACTS_OTHERCOUNTRY","POOMCONTACTS:OtherCountry");
define("SYNC_POOMCONTACTS_OTHERPOSTALCODE","POOMCONTACTS:OtherPostalCode");
define("SYNC_POOMCONTACTS_OTHERSTATE","POOMCONTACTS:OtherState");
define("SYNC_POOMCONTACTS_OTHERSTREET","POOMCONTACTS:OtherStreet");
define("SYNC_POOMCONTACTS_PAGERNUMBER","POOMCONTACTS:PagerNumber");
define("SYNC_POOMCONTACTS_RADIOPHONENUMBER","POOMCONTACTS:RadioPhoneNumber");
define("SYNC_POOMCONTACTS_SPOUSE","POOMCONTACTS:Spouse");
define("SYNC_POOMCONTACTS_SUFFIX","POOMCONTACTS:Suffix");
define("SYNC_POOMCONTACTS_TITLE","POOMCONTACTS:Title");
define("SYNC_POOMCONTACTS_WEBPAGE","POOMCONTACTS:WebPage");
define("SYNC_POOMCONTACTS_YOMICOMPANYNAME","POOMCONTACTS:YomiCompanyName");
define("SYNC_POOMCONTACTS_YOMIFIRSTNAME","POOMCONTACTS:YomiFirstName");
define("SYNC_POOMCONTACTS_YOMILASTNAME","POOMCONTACTS:YomiLastName");
define("SYNC_POOMCONTACTS_RTF","POOMCONTACTS:Rtf");
define("SYNC_POOMCONTACTS_PICTURE","POOMCONTACTS:Picture");

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

// POOMCAL
define("SYNC_POOMCAL_TIMEZONE","POOMCAL:Timezone");
define("SYNC_POOMCAL_ALLDAYEVENT","POOMCAL:AllDayEvent");
define("SYNC_POOMCAL_ATTENDEES","POOMCAL:Attendees");
define("SYNC_POOMCAL_ATTENDEE","POOMCAL:Attendee");
define("SYNC_POOMCAL_EMAIL","POOMCAL:Email");
define("SYNC_POOMCAL_NAME","POOMCAL:Name");
define("SYNC_POOMCAL_BODY","POOMCAL:Body");
define("SYNC_POOMCAL_BODYTRUNCATED","POOMCAL:BodyTruncated");
define("SYNC_POOMCAL_BUSYSTATUS","POOMCAL:BusyStatus");
define("SYNC_POOMCAL_CATEGORIES","POOMCAL:Categories");
define("SYNC_POOMCAL_CATEGORY","POOMCAL:Category");
define("SYNC_POOMCAL_RTF","POOMCAL:Rtf");
define("SYNC_POOMCAL_DTSTAMP","POOMCAL:DtStamp");
define("SYNC_POOMCAL_ENDTIME","POOMCAL:EndTime");
define("SYNC_POOMCAL_EXCEPTION","POOMCAL:Exception");
define("SYNC_POOMCAL_EXCEPTIONS","POOMCAL:Exceptions");
define("SYNC_POOMCAL_DELETED","POOMCAL:Deleted");
define("SYNC_POOMCAL_EXCEPTIONSTARTTIME","POOMCAL:ExceptionStartTime");
define("SYNC_POOMCAL_LOCATION","POOMCAL:Location");
define("SYNC_POOMCAL_MEETINGSTATUS","POOMCAL:MeetingStatus");
define("SYNC_POOMCAL_ORGANIZEREMAIL","POOMCAL:OrganizerEmail");
define("SYNC_POOMCAL_ORGANIZERNAME","POOMCAL:OrganizerName");
define("SYNC_POOMCAL_RECURRENCE","POOMCAL:Recurrence");
define("SYNC_POOMCAL_TYPE","POOMCAL:Type");
define("SYNC_POOMCAL_UNTIL","POOMCAL:Until");
define("SYNC_POOMCAL_OCCURRENCES","POOMCAL:Occurrences");
define("SYNC_POOMCAL_INTERVAL","POOMCAL:Interval");
define("SYNC_POOMCAL_DAYOFWEEK","POOMCAL:DayOfWeek");
define("SYNC_POOMCAL_DAYOFMONTH","POOMCAL:DayOfMonth");
define("SYNC_POOMCAL_WEEKOFMONTH","POOMCAL:WeekOfMonth");
define("SYNC_POOMCAL_MONTHOFYEAR","POOMCAL:MonthOfYear");
define("SYNC_POOMCAL_REMINDER","POOMCAL:Reminder");
define("SYNC_POOMCAL_SENSITIVITY","POOMCAL:Sensitivity");
define("SYNC_POOMCAL_SUBJECT","POOMCAL:Subject");
define("SYNC_POOMCAL_STARTTIME","POOMCAL:StartTime");
define("SYNC_POOMCAL_UID","POOMCAL:UID");
define("SYNC_POOMCAL_RESPONSETYPE", "POOMCAL:ResponseType");

// Move
define("SYNC_MOVE_MOVES","Move:Moves");
define("SYNC_MOVE_MOVE","Move:Move");
define("SYNC_MOVE_SRCMSGID","Move:SrcMsgId");
define("SYNC_MOVE_SRCFLDID","Move:SrcFldId");
define("SYNC_MOVE_DSTFLDID","Move:DstFldId");
define("SYNC_MOVE_RESPONSE","Move:Response");
define("SYNC_MOVE_STATUS","Move:Status");
define("SYNC_MOVE_DSTMSGID","Move:DstMsgId");

// GetItemEstimate
define("SYNC_GETITEMESTIMATE_GETITEMESTIMATE","GetItemEstimate:GetItemEstimate");
define("SYNC_GETITEMESTIMATE_VERSION","GetItemEstimate:Version");
define("SYNC_GETITEMESTIMATE_FOLDERS","GetItemEstimate:Folders");
define("SYNC_GETITEMESTIMATE_FOLDER","GetItemEstimate:Folder");
define("SYNC_GETITEMESTIMATE_FOLDERTYPE","GetItemEstimate:FolderType");
define("SYNC_GETITEMESTIMATE_FOLDERID","GetItemEstimate:FolderId");
define("SYNC_GETITEMESTIMATE_DATETIME","GetItemEstimate:DateTime");
define("SYNC_GETITEMESTIMATE_ESTIMATE","GetItemEstimate:Estimate");
define("SYNC_GETITEMESTIMATE_RESPONSE","GetItemEstimate:Response");
define("SYNC_GETITEMESTIMATE_STATUS","GetItemEstimate:Status");

// FolderHierarchy
define("SYNC_FOLDERHIERARCHY_FOLDERS","FolderHierarchy:Folders");
define("SYNC_FOLDERHIERARCHY_FOLDER","FolderHierarchy:Folder");
define("SYNC_FOLDERHIERARCHY_DISPLAYNAME","FolderHierarchy:DisplayName");
define("SYNC_FOLDERHIERARCHY_SERVERENTRYID","FolderHierarchy:ServerEntryId");
define("SYNC_FOLDERHIERARCHY_PARENTID","FolderHierarchy:ParentId");
define("SYNC_FOLDERHIERARCHY_TYPE","FolderHierarchy:Type");
define("SYNC_FOLDERHIERARCHY_RESPONSE","FolderHierarchy:Response");
define("SYNC_FOLDERHIERARCHY_STATUS","FolderHierarchy:Status");
define("SYNC_FOLDERHIERARCHY_CONTENTCLASS","FolderHierarchy:ContentClass");
define("SYNC_FOLDERHIERARCHY_CHANGES","FolderHierarchy:Changes");
define("SYNC_FOLDERHIERARCHY_ADD","FolderHierarchy:Add");
define("SYNC_FOLDERHIERARCHY_REMOVE","FolderHierarchy:Remove");
define("SYNC_FOLDERHIERARCHY_UPDATE","FolderHierarchy:Update");
define("SYNC_FOLDERHIERARCHY_SYNCKEY","FolderHierarchy:SyncKey");
define("SYNC_FOLDERHIERARCHY_FOLDERCREATE","FolderHierarchy:FolderCreate");
define("SYNC_FOLDERHIERARCHY_FOLDERDELETE","FolderHierarchy:FolderDelete");
define("SYNC_FOLDERHIERARCHY_FOLDERUPDATE","FolderHierarchy:FolderUpdate");
define("SYNC_FOLDERHIERARCHY_FOLDERSYNC","FolderHierarchy:FolderSync");
define("SYNC_FOLDERHIERARCHY_COUNT","FolderHierarchy:Count");
define("SYNC_FOLDERHIERARCHY_VERSION","FolderHierarchy:Version");

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

// POOMCONTACTS2
define("SYNC_POOMCONTACTS2_CUSTOMERID","POOMCONTACTS2:CustomerId");
define("SYNC_POOMCONTACTS2_GOVERNMENTID","POOMCONTACTS2:GovernmentId");
define("SYNC_POOMCONTACTS2_IMADDRESS","POOMCONTACTS2:IMAddress");
define("SYNC_POOMCONTACTS2_IMADDRESS2","POOMCONTACTS2:IMAddress2");
define("SYNC_POOMCONTACTS2_IMADDRESS3","POOMCONTACTS2:IMAddress3");
define("SYNC_POOMCONTACTS2_MANAGERNAME","POOMCONTACTS2:ManagerName");
define("SYNC_POOMCONTACTS2_COMPANYMAINPHONE","POOMCONTACTS2:CompanyMainPhone");
define("SYNC_POOMCONTACTS2_ACCOUNTNAME","POOMCONTACTS2:AccountName");
define("SYNC_POOMCONTACTS2_NICKNAME","POOMCONTACTS2:NickName");
define("SYNC_POOMCONTACTS2_MMS","POOMCONTACTS2:MMS");

// Ping
define("SYNC_PING_PING","Ping:Ping");
define("SYNC_PING_STATUS","Ping:Status");
define("SYNC_PING_LIFETIME", "Ping:LifeTime");
define("SYNC_PING_FOLDERS", "Ping:Folders");
define("SYNC_PING_FOLDER", "Ping:Folder");
define("SYNC_PING_SERVERENTRYID", "Ping:ServerEntryId");
define("SYNC_PING_FOLDERTYPE", "Ping:FolderType");

//Provision
define("SYNC_PROVISION_PROVISION", "Provision:Provision");
define("SYNC_PROVISION_POLICIES", "Provision:Policies");
define("SYNC_PROVISION_POLICY", "Provision:Policy");
define("SYNC_PROVISION_POLICYTYPE", "Provision:PolicyType");
define("SYNC_PROVISION_POLICYKEY", "Provision:PolicyKey");
define("SYNC_PROVISION_DATA", "Provision:Data");
define("SYNC_PROVISION_STATUS", "Provision:Status");
define("SYNC_PROVISION_REMOTEWIPE", "Provision:RemoteWipe");
define("SYNC_PROVISION_EASPROVISIONDOC", "Provision:EASProvisionDoc");

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

// Other constants
define("SYNC_FOLDER_TYPE_OTHER", 1);
define("SYNC_FOLDER_TYPE_INBOX", 2);
define("SYNC_FOLDER_TYPE_DRAFTS", 3);
define("SYNC_FOLDER_TYPE_WASTEBASKET", 4);
define("SYNC_FOLDER_TYPE_SENTMAIL", 5);
define("SYNC_FOLDER_TYPE_OUTBOX", 6);
define("SYNC_FOLDER_TYPE_TASK", 7);
define("SYNC_FOLDER_TYPE_APPOINTMENT", 8);
define("SYNC_FOLDER_TYPE_CONTACT", 9);
define("SYNC_FOLDER_TYPE_NOTE", 10);
define("SYNC_FOLDER_TYPE_JOURNAL", 11);
define("SYNC_FOLDER_TYPE_USER_MAIL", 12);
define("SYNC_FOLDER_TYPE_USER_APPOINTMENT", 13);
define("SYNC_FOLDER_TYPE_USER_CONTACT", 14);
define("SYNC_FOLDER_TYPE_USER_TASK", 15);
define("SYNC_FOLDER_TYPE_USER_JOURNAL", 16);
define("SYNC_FOLDER_TYPE_USER_NOTE", 17);
define("SYNC_FOLDER_TYPE_UNKNOWN", 18);
define("SYNC_FOLDER_TYPE_RECIPIENT_CACHE", 19);
define("SYNC_FOLDER_TYPE_DUMMY", "__dummy.Folder.Id__");

define("SYNC_CONFLICT_OVERWRITE_SERVER", 0);
define("SYNC_CONFLICT_OVERWRITE_PIM", 1);

define("SYNC_TRUNCATION_HEADERS", 0);
define("SYNC_TRUNCATION_512B", 1);
define("SYNC_TRUNCATION_1K", 2);
define("SYNC_TRUNCATION_5K", 4);
define("SYNC_TRUNCATION_SEVEN", 7);
define("SYNC_TRUNCATION_ALL", 9);

define("SYNC_PROVISION_STATUS_SUCCESS", 1);
define("SYNC_PROVISION_STATUS_PROTERROR", 2);
define("SYNC_PROVISION_STATUS_SERVERERROR", 3);
define("SYNC_PROVISION_STATUS_DEVEXTMANAGED", 4);
define("SYNC_PROVISION_STATUS_POLKEYMISM", 5);

define("SYNC_PROVISION_RWSTATUS_NA", 0);
define("SYNC_PROVISION_RWSTATUS_OK", 1);
define("SYNC_PROVISION_RWSTATUS_PENDING", 2);
define("SYNC_PROVISION_RWSTATUS_WIPED", 3);

/**
 * Main ActiveSync class. Entry point for performing all ActiveSync operations
 *
 */
class Horde_ActiveSync
{
    /* SYNC Status response codes */
    const STATUS_SYNC_SUCCESS = 1;
    const STATUS_SYNC_VERSIONMISM = 2;
    const STATUS_SYNC_KEYMISM = 3;
    const STATUS_SYNC_PROTERROR = 4;
    const STATUS_SYNC_SERVERERROR = 5;

    const STATUS_PING_NOCHANGES = 1;
    const STATUS_PING_NEEDSYNC = 2;
    const STATUS_PING_MISSING = 3;
    const STATUS_PING_PROTERROR = 4;
    // Hearbeat out of bounds (TODO)
    const STATUS_PING_HBOUTOFBOUNDS = 5;

    // Requested more then the max folders (TODO)
    const STATUS_PING_MAXFOLDERS = 6;

    // Folder sync is required, hierarchy out of date.
    const STATUS_PING_FOLDERSYNCREQD = 7;
    const STATUS_PING_SERVERERROR = 8;


    /**
     * DTD
     */
    static public $zpushdtd = array(
                "codes" => array (
                    0 => array (
                        0x05 => "Synchronize",
                        0x06 => "Replies",
                        0x07 => "Add",
                        0x08 => "Modify",
                        0x09 => "Remove",
                        0x0a => "Fetch",
                        0x0b => "SyncKey",
                        0x0c => "ClientEntryId",
                        0x0d => "ServerEntryId",
                        0x0e => "Status",
                        0x0f => "Folder",
                        0x10 => "FolderType",
                        0x11 => "Version",
                        0x12 => "FolderId",
                        0x13 => "GetChanges",
                        0x14 => "MoreAvailable",
                        0x15 => "WindowSize",
                        0x16 => "Commands",
                        0x17 => "Options",
                        0x18 => "FilterType",
                        0x19 => "Truncation",
                        0x1a => "RtfTruncation",
                        0x1b => "Conflict",
                        0x1c => "Folders",
                        0x1d => "Data",
                        0x1e => "DeletesAsMoves",
                        0x1f => "NotifyGUID",
                        0x20 => "Supported",
                        0x21 => "SoftDelete",
                        0x22 => "MIMESupport",
                        0x23 => "MIMETruncation",
                    ),
                    1 => array (
                        0x05 => "Anniversary",
                        0x06 => "AssistantName",
                        0x07 => "AssistnamePhoneNumber",
                        0x08 => "Birthday",
                        0x09 => "Body",
                        0x0a => "BodySize",
                        0x0b => "BodyTruncated",
                        0x0c => "Business2PhoneNumber",
                        0x0d => "BusinessCity",
                        0x0e => "BusinessCountry",
                        0x0f => "BusinessPostalCode",
                        0x10 => "BusinessState",
                        0x11 => "BusinessStreet",
                        0x12 => "BusinessFaxNumber",
                        0x13 => "BusinessPhoneNumber",
                        0x14 => "CarPhoneNumber",
                        0x15 => "Categories",
                        0x16 => "Category",
                        0x17 => "Children",
                        0x18 => "Child",
                        0x19 => "CompanyName",
                        0x1a => "Department",
                        0x1b => "Email1Address",
                        0x1c => "Email2Address",
                        0x1d => "Email3Address",
                        0x1e => "FileAs",
                        0x1f => "FirstName",
                        0x20 => "Home2PhoneNumber",
                        0x21 => "HomeCity",
                        0x22 => "HomeCountry",
                        0x23 => "HomePostalCode",
                        0x24 => "HomeState",
                        0x25 => "HomeStreet",
                        0x26 => "HomeFaxNumber",
                        0x27 => "HomePhoneNumber",
                        0x28 => "JobTitle",
                        0x29 => "LastName",
                        0x2a => "MiddleName",
                        0x2b => "MobilePhoneNumber",
                        0x2c => "OfficeLocation",
                        0x2d => "OtherCity",
                        0x2e => "OtherCountry",
                        0x2f => "OtherPostalCode",
                        0x30 => "OtherState",
                        0x31 => "OtherStreet",
                        0x32 => "PagerNumber",
                        0x33 => "RadioPhoneNumber",
                        0x34 => "Spouse",
                        0x35 => "Suffix",
                        0x36 => "Title",
                        0x37 => "WebPage",
                        0x38 => "YomiCompanyName",
                        0x39 => "YomiFirstName",
                        0x3a => "YomiLastName",
                        0x3b => "Rtf",
                        0x3c => "Picture",
                    ),
                     2 => array (
                        0x05 => "Attachment",
                        0x06 => "Attachments",
                        0x07 => "AttName",
                        0x08 => "AttSize",
                        0x09 => "AttOid",
                        0x0a => "AttMethod",
                        0x0b => "AttRemoved",
                        0x0c => "Body",
                        0x0d => "BodySize",
                        0x0e => "BodyTruncated",
                        0x0f => "DateReceived",
                        0x10 => "DisplayName",
                        0x11 => "DisplayTo",
                        0x12 => "Importance",
                        0x13 => "MessageClass",
                        0x14 => "Subject",
                        0x15 => "Read",
                        0x16 => "To",
                        0x17 => "Cc",
                        0x18 => "From",
                        0x19 => "Reply-To",
                        0x1a => "AllDayEvent",
                        0x1b => "Categories",
                        0x1c => "Category",
                        0x1d => "DtStamp",
                        0x1e => "EndTime",
                        0x1f => "InstanceType",
                        0x20 => "BusyStatus",
                        0x21 => "Location",
                        0x22 => "MeetingRequest",
                        0x23 => "Organizer",
                        0x24 => "RecurrenceId",
                        0x25 => "Reminder",
                        0x26 => "ResponseRequested",
                        0x27 => "Recurrences",
                        0x28 => "Recurrence",
                        0x29 => "Type",
                        0x2a => "Until",
                        0x2b => "Occurrences",
                        0x2c => "Interval",
                        0x2d => "DayOfWeek",
                        0x2e => "DayOfMonth",
                        0x2f => "WeekOfMonth",
                        0x30 => "MonthOfYear",
                        0x31 => "StartTime",
                        0x32 => "Sensitivity",
                        0x33 => "TimeZone",
                        0x34 => "GlobalObjId",
                        0x35 => "ThreadTopic",
                        0x36 => "MIMEData",
                        0x37 => "MIMETruncated",
                        0x38 => "MIMESize",
                        0x39 => "InternetCPID",
                    ),
                     3 => array (
                        0x05 => "Notify",
                        0x06 => "Notification",
                        0x07 => "Version",
                        0x08 => "Lifetime",
                        0x09 => "DeviceInfo",
                        0x0a => "Enable",
                        0x0b => "Folder",
                        0x0c => "ServerEntryId",
                        0x0d => "DeviceAddress",
                        0x0e => "ValidCarrierProfiles",
                        0x0f => "CarrierProfile",
                        0x10 => "Status",
                        0x11 => "Replies",
//                        0x05 => "Version='1.1'",
                        0x12 => "Devices",
                        0x13 => "Device",
                        0x14 => "Id",
                        0x15 => "Expiry",
                        0x16 => "NotifyGUID",
                    ),
                     4 => array (
                        0x05 => "Timezone",
                        0x06 => "AllDayEvent",
                        0x07 => "Attendees",
                        0x08 => "Attendee",
                        0x09 => "Email",
                        0x0a => "Name",
                        0x0b => "Body",
                        0x0c => "BodyTruncated",
                        0x0d => "BusyStatus",
                        0x0e => "Categories",
                        0x0f => "Category",
                        0x10 => "Rtf",
                        0x11 => "DtStamp",
                        0x12 => "EndTime",
                        0x13 => "Exception",
                        0x14 => "Exceptions",
                        0x15 => "Deleted",
                        0x16 => "ExceptionStartTime",
                        0x17 => "Location",
                        0x18 => "MeetingStatus",
                        0x19 => "OrganizerEmail",
                        0x1a => "OrganizerName",
                        0x1b => "Recurrence",
                        0x1c => "Type",
                        0x1d => "Until",
                        0x1e => "Occurrences",
                        0x1f => "Interval",
                        0x20 => "DayOfWeek",
                        0x21 => "DayOfMonth",
                        0x22 => "WeekOfMonth",
                        0x23 => "MonthOfYear",
                        0x24 => "Reminder",
                        0x25 => "Sensitivity",
                        0x26 => "Subject",
                        0x27 => "StartTime",
                        0x28 => "UID",
                        0x36 => "ResponseType"
                    ), 5 => array (
                        0x05 => "Moves",
                        0x06 => "Move",
                        0x07 => "SrcMsgId",
                        0x08 => "SrcFldId",
                        0x09 => "DstFldId",
                        0x0a => "Response",
                        0x0b => "Status",
                        0x0c => "DstMsgId",
                    ), 6 => array (
                        0x05 => "GetItemEstimate",
                        0x06 => "Version",
                        0x07 => "Folders",
                        0x08 => "Folder",
                        0x09 => "FolderType",
                        0x0a => "FolderId",
                        0x0b => "DateTime",
                        0x0c => "Estimate",
                        0x0d => "Response",
                        0x0e => "Status",
                    ), 7 => array (
                        0x05 => "Folders",
                        0x06 => "Folder",
                        0x07 => "DisplayName",
                        0x08 => "ServerEntryId",
                        0x09 => "ParentId",
                        0x0a => "Type",
                        0x0b => "Response",
                        0x0c => "Status",
                        0x0d => "ContentClass",
                        0x0e => "Changes",
                        0x0f => "Add",
                        0x10 => "Remove",
                        0x11 => "Update",
                        0x12 => "SyncKey",
                        0x13 => "FolderCreate",
                        0x14 => "FolderDelete",
                        0x15 => "FolderUpdate",
                        0x16 => "FolderSync",
                        0x17 => "Count",
                        0x18 => "Version",
                    ), 8 => array (
                        0x05 => "CalendarId",
                        0x06 => "FolderId",
                        0x07 => "MeetingResponse",
                        0x08 => "RequestId",
                        0x09 => "Request",
                        0x0a => "Result",
                        0x0b => "Status",
                        0x0c => "UserResponse",
                        0x0d => "Version",
                    ), 9 => array (
                        0x05 => "Body",
                        0x06 => "BodySize",
                        0x07 => "BodyTruncated",
                        0x08 => "Categories",
                        0x09 => "Category",
                        0x0a => "Complete",
                        0x0b => "DateCompleted",
                        0x0c => "DueDate",
                        0x0d => "UtcDueDate",
                        0x0e => "Importance",
                        0x0f => "Recurrence",
                        0x10 => "Type",
                        0x11 => "Start",
                        0x12 => "Until",
                        0x13 => "Occurrences",
                        0x14 => "Interval",
                        0x16 => "DayOfWeek",
                        0x15 => "DayOfMonth",
                        0x17 => "WeekOfMonth",
                        0x18 => "MonthOfYear",
                        0x19 => "Regenerate",
                        0x1a => "DeadOccur",
                        0x1b => "ReminderSet",
                        0x1c => "ReminderTime",
                        0x1d => "Sensitivity",
                        0x1e => "StartDate",
                        0x1f => "UtcStartDate",
                        0x20 => "Subject",
                        0x21 => "Rtf",
                    ), 0xa => array (
                        0x05 => "ResolveRecipients",
                        0x06 => "Response",
                        0x07 => "Status",
                        0x08 => "Type",
                        0x09 => "Recipient",
                        0x0a => "DisplayName",
                        0x0b => "EmailAddress",
                        0x0c => "Certificates",
                        0x0d => "Certificate",
                        0x0e => "MiniCertificate",
                        0x0f => "Options",
                        0x10 => "To",
                        0x11 => "CertificateRetrieval",
                        0x12 => "RecipientCount",
                        0x13 => "MaxCertificates",
                        0x14 => "MaxAmbiguousRecipients",
                        0x15 => "CertificateCount",
                    ), 0xb => array (
                        0x05 => "ValidateCert",
                        0x06 => "Certificates",
                        0x07 => "Certificate",
                        0x08 => "CertificateChain",
                        0x09 => "CheckCRL",
                        0x0a => "Status",
                    ), 0xc => array (
                        0x05 => "CustomerId",
                        0x06 => "GovernmentId",
                        0x07 => "IMAddress",
                        0x08 => "IMAddress2",
                        0x09 => "IMAddress3",
                        0x0a => "ManagerName",
                        0x0b => "CompanyMainPhone",
                        0x0c => "AccountName",
                        0x0d => "NickName",
                        0x0e => "MMS",
                    ), 0xd => array (
                        0x05 => "Ping",
                        0x07 => "Status",
                        0x08 => "LifeTime",
                        0x09 => "Folders",
                        0x0a => "Folder",
                        0x0b => "ServerEntryId",
                        0x0c => "FolderType",
                    ), 0xe => array (
                        0x05 => "Provision",
                        0x06 => "Policies",
                        0x07 => "Policy",
                        0x08 => "PolicyType",
                        0x09 => "PolicyKey",
                        0x0A => "Data",
                        0x0B => "Status",
                        0x0C => "RemoteWipe",
                        0x0D => "EASProvisionDoc",
                        ),
                    0xf => array(
                        0x05 => "Search",
                        0x07 => "Store",
                        0x08 => "Name",
                        0x09 => "Query",
                        0x0A => "Options",
                        0x0B => "Range",
                        0x0C => "Status",
                        0x0D => "Response",
                        0x0E => "Result",
                        0x0F => "Properties",
                        0x10 => "Total",
                        0x11 => "EqualTo",
                        0x12 => "Value",
                        0x13 => "And",
                        0x14 => "Or",
                        0x15 => "FreeText",
                        0x17 => "DeepTraversal",
                        0x18 => "LongId",
                        0x19 => "RebuildResults",
                        0x1A => "LessThan",
                        0x1B => "GreaterThan",
                        0x1C => "Schema",
                        0x1D => "Supported",
                    ), 0x10 => array(
                        0x05 => "DisplayName",
                        0x06 => "Phone",
                        0x07 => "Office",
                        0x08 => "Title",
                        0x09 => "Company",
                        0x0A => "Alias",
                        0x0B => "FirstName",
                        0x0C => "LastName",
                        0x0D => "HomePhone",
                        0x0E => "MobilePhone",
                        0x0F => "EmailAddress",
                    )
              ), "namespaces" => array(
                  1 => "POOMCONTACTS",
                  2 => "POOMMAIL",
                  3 => "AirNotify",
                  4 => "POOMCAL",
                  5 => "Move",
                  6 => "GetItemEstimate",
                  7 => "FolderHierarchy",
                  8 => "MeetingResponse",
                  9 => "POOMTASKS",
                  0xA => "ResolveRecipients",
                  0xB => "ValidateCerts",
                  0xC => "POOMCONTACTS2",
                  0xD => "Ping",
                  0xE => "Provision",//
                  0xF => "Search",//
                  0x10 => "GAL",
              )
          );

    /**
     * Used to track what error code to send back to PIM on failure
     *
     * @var integer
     */
    protected $_statusCode = 0;

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
        $this->_encoder->startTag(SYNC_FOLDERHIERARCHY_FOLDERS);

        foreach ($folders as $folder) {
            $this->_encoder->startTag(SYNC_FOLDERHIERARCHY_FOLDER);
            $folder->encodeStream($this->_encoder);
            $this->_encoder->endTag();
        }
        $this->_encoder->endTag();

        return true;
    }

    /**
     *
     * @param $protocolversion
     * @param $devid
     * @return unknown_type
     */
    public function handleGetItemEstimate($protocolversion, $devid)
    {
        $collections = array();

        if (!$this->_decoder->getElementStartTag(SYNC_GETITEMESTIMATE_GETITEMESTIMATE)) {
            return false;
        }

        if (!$this->_decoder->getElementStartTag(SYNC_GETITEMESTIMATE_FOLDERS)) {
            return false;
        }

        while ($this->_decoder->getElementStartTag(SYNC_GETITEMESTIMATE_FOLDER)) {
            $collection = array();

            if (!$this->_decoder->getElementStartTag(SYNC_GETITEMESTIMATE_FOLDERTYPE)) {
                return false;
            }

            $class = $this->_decoder->getElementContent();

            if (!$this->_decoder->getElementEndTag()) {
                return false;
            }

            if ($this->_decoder->getElementStartTag(SYNC_GETITEMESTIMATE_FOLDERID)) {
                $collectionid = $this->_decoder->getElementContent();

                if (!$this->_decoder->getElementEndTag()) {
                    return false;
                }
            }

            if (!$this->_decoder->getElementStartTag(SYNC_FILTERTYPE)) {
                return false;
            }
            $filtertype = $this->_decoder->getElementContent();

            if (!$this->_decoder->getElementEndTag()) {
                return false;
            }

            if (!$this->_decoder->getElementStartTag(SYNC_SYNCKEY)) {
                return false;
            }

            $synckey = $this->_decoder->getElementContent();

            if (!$this->_decoder->getElementEndTag()) {
                return false;
            }
            if (!$this->_decoder->getElementEndTag()) {
                return false;
            }

            // compatibility mode - get folderid from the state directory
            if (!isset($collectionid)) {
                $collectionid = $this->_stateMachine->getFolderData($devid, $class);
            }

            $collection = array();
            $collection['synckey'] = $synckey;
            $collection['class'] = $class;
            $collection['filtertype'] = $filtertype;
            $collection['collectionid'] = $collectionid;

            array_push($collections, $collection);
        }

        $this->_encoder->startWBXML();

        $this->_encoder->startTag(SYNC_GETITEMESTIMATE_GETITEMESTIMATE);
        foreach ($collections as $collection) {
            $this->_encoder->startTag(SYNC_GETITEMESTIMATE_RESPONSE);

            $this->_encoder->startTag(SYNC_GETITEMESTIMATE_STATUS);
            $this->_encoder->content(1);
            $this->_encoder->endTag();

            $this->_encoder->startTag(SYNC_GETITEMESTIMATE_FOLDER);

            $this->_encoder->startTag(SYNC_GETITEMESTIMATE_FOLDERTYPE);
            $this->_encoder->content($collection['class']);
            $this->_encoder->endTag();

            $this->_encoder->startTag(SYNC_GETITEMESTIMATE_FOLDERID);
            $this->_encoder->content($collection['collectionid']);
            $this->_encoder->endTag();

            $this->_encoder->startTag(SYNC_GETITEMESTIMATE_ESTIMATE);

            $importer = new Horde_ActiveSync_ContentsCache();

            $syncstate = $this->_stateMachine->loadState($collection['synckey']);

            $exporter = $this->_driver->GetExporter($collection['collectionid']);
            $exporter->Config($importer, $collection['class'], $collection['filtertype'], $syncstate, 0, 0);

            $this->_encoder->content($exporter->GetChangeCount());

            $this->_encoder->endTag();

            $this->_encoder->endTag();

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
    public function handleSendMail($protocolversion)
    {
        // All that happens here is that we receive an rfc822 message on stdin
        // and just forward it to the backend. We provide no output except for
        // an OK http reply
        $rfc822 = $this->readStream();

        return $this->_driver->SendMail($rfc822);
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

        return $this->_driver->SendMail($rfc822, $orig, false, $parent);
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

        return $this->_driver->SendMail($rfc822, false, $orig, $parent);
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

        if ($el[Horde_ActiveSync_Wbxml::EN_TAG] == SYNC_FOLDERHIERARCHY_FOLDERCREATE) {
            $create = true;
        } elseif ($el[Horde_ActiveSync_Wbxml::EN_TAG] == SYNC_FOLDERHIERARCHY_FOLDERUPDATE) {
            $update = true;
        } elseif ($el[Horde_ActiveSync_Wbxml::EN_TAG] == SYNC_FOLDERHIERARCHY_FOLDERDELETE) {
            $delete = true;
        }

        if (!$create && !$update && !$delete) {
            return false;
        }

        // SyncKey
        if (!$this->_decoder->getElementStartTag(SYNC_FOLDERHIERARCHY_SYNCKEY)) {
            return false;
        }
        $synckey = $this->_decoder->getElementContent();
        if (!$this->_decoder->getElementEndTag()) {
            return false;
        }

        // ServerID
        $serverid = false;
        if ($this->_decoder->getElementStartTag(SYNC_FOLDERHIERARCHY_SERVERENTRYID)) {
            $serverid = $this->_decoder->getElementContent();
            if (!$this->_decoder->getElementEndTag()) {
                return false;
            }
        }

        // when creating or updating more information is necessary
        if (!$delete) {
            // Parent
            $parentid = false;
            if ($this->_decoder->getElementStartTag(SYNC_FOLDERHIERARCHY_PARENTID)) {
                $parentid = $this->_decoder->getElementContent();
                if (!$this->_decoder->getElementEndTag()) {
                    return false;
                }
            }

            // Displayname
            if (!$this->_decoder->getElementStartTag(SYNC_FOLDERHIERARCHY_DISPLAYNAME)) {
                return false;
            }
            $displayname = $this->_decoder->getElementContent();
            if (!$this->_decoder->getElementEndTag()) {
                return false;
            }

            // Type
            $type = false;
            if ($this->_decoder->getElementStartTag(SYNC_FOLDERHIERARCHY_TYPE)) {
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

            $this->_encoder->startTag(SYNC_FOLDERHIERARCHY_FOLDERCREATE);


            $this->_encoder->startTag(SYNC_FOLDERHIERARCHY_STATUS);
            $this->_encoder->content(1);
            $this->_encoder->endTag();

            $this->_encoder->startTag(SYNC_FOLDERHIERARCHY_SYNCKEY);
            $this->_encoder->content($newsynckey);
            $this->_encoder->endTag();

            $this->_encoder->startTag(SYNC_FOLDERHIERARCHY_SERVERENTRYID);
            $this->_encoder->content($serverid);
            $this->_encoder->endTag();

            $this->_encoder->endTag();

            $this->_encoder->endTag();
        } elseif ($update) {

            $this->_encoder->startTag(SYNC_FOLDERHIERARCHY_FOLDERUPDATE);

            $this->_encoder->startTag(SYNC_FOLDERHIERARCHY_STATUS);
            $this->_encoder->content(1);
            $this->_encoder->endTag();

            $this->_encoder->startTag(SYNC_FOLDERHIERARCHY_SYNCKEY);
            $this->_encoder->content($newsynckey);
            $this->_encoder->endTag();

            $this->_encoder->endTag();
        } elseif ($delete) {
            $this->_encoder->startTag(SYNC_FOLDERHIERARCHY_FOLDERDELETE);

            $this->_encoder->startTag(SYNC_FOLDERHIERARCHY_STATUS);
            $this->_encoder->content($deletedstat);
            $this->_encoder->endTag();

            $this->_encoder->startTag(SYNC_FOLDERHIERARCHY_SYNCKEY);
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

    public function provisioningRequired()
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
     * @param $cmd
     * @param $devid
     * @param $protocolversion
     * @return unknown_type
     */
    public function handleRequest($cmd, $devId, $version)
    {
        $class = 'Horde_ActiveSync_Request_' . basename($cmd);
        if (class_exists($class)) {
            $request = new $class($this->_driver,
                                  $this->_decoder,
                                  $this->_encoder,
                                  $this->_request,
                                  $version,
                                  $devId,
                                  $this->_provisioning);
            $request->setLogger($this->_logger);
            return $request->handle($this);
        }

        // GetHierarchy is used in v1.0 of the AS protocol, in v2, it is replaced
        // by the FolderSync command

        // @TODO: Leave the following in place until all are refactored...then throw
        // an error if the class does not exist.
        switch($cmd) {
            case 'SendMail':
                $status = $this->handleSendMail($version);
                break;
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
     * Read input from the php input stream
     *
     * @TODO: Get rid of this - the wbxml classes have a php:// stream already
     *        and when we need *just* the stream and not wbxml, we can use
     *        $request->body
     *
     * @return string
     */
    public function readStream()
    {
        $s = "";
        while (1) {
            $data = fread($this->_inputStream, 4096);
            if (strlen($data) == 0) {
                break;
            }
            $s .= $data;
        }

        return $s;
    }

    /**
     * Send the MS_Server-ActiveSync header
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
        if (isset($this->_policykey)) {
            return $this->_policykey;
        }

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