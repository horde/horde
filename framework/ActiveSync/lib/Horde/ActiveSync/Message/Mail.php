<?php
/**
 * Horde_ActiveSync_Message_Mail::
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
 * @copyright 2011-2013 Horde LLC (http://www.horde.org)
 * @author    Michael J Rubinsky <mrubinsk@horde.org>
 * @package   ActiveSync
 */
/**
 * Horde_ActiveSync_Message_Mail::
 *
 * @license   http://www.horde.org/licenses/gpl GPLv2
 *            NOTE: According to sec. 8 of the GENERAL PUBLIC LICENSE (GPL),
 *            Version 2, the distribution of the Horde_ActiveSync module in or
 *            to the United States of America is excluded from the scope of this
 *            license.
 * @copyright 2011-2013 Horde LLC (http://www.horde.org)
 * @author    Michael J Rubinsky <mrubinsk@horde.org>
 * @package   ActiveSync
 *
 * @property string     to
 * @property string     cc
 * @property string     from
 * @property string     subject
 * @property string     threadtopic
 * @property Horde_Date datereceived
 * @property string     displayto
 * @property integer    importance
 * @property integer    mimetruncated
 * @property string     mimedata
 * @property integer    mimesize
 * @property integer    messageclass
 * @property Horde_ActiveSync_Message_MeetingRequest  meetingrequest
 * @property string     reply_to
 * @property integer    read
 * @property cpid       integer  The codepage id.
 * @property Horde_ActiveSync_Message_Attachments attachments (EAS 2.5 only).
 * @property integer    bodytruncated (EAS 2.5 only)
 * @property integer    bodysize (EAS 2.5 only)
 * @property mixed stream|string  body (EAS 2.5 only)
 * @property integer    airsyncbasenativebodytype (EAS > 2.5 only).
 * @property Horde_ActiveSync_Message_AirSyncBaseBody airsyncbasebody (EAS > 2.5 only).
 * @property Horde_ActiveSync_Message_AirSyncBaseAttachments airsyncbaseattachments (EAS > 2.5 only).
 * @property integer contentclass (EAS > 2.5 only).
 * @property Horde_ActiveSync_Message_Flag flag (EAS > 2.5 only).
 *
 * // Internal properties. Not streamed to device.
 * @property string messageid
 * @property boolean answered
 * @property boolean forwarded
 */
class Horde_ActiveSync_Message_Mail extends Horde_ActiveSync_Message_Base
{
    const POOMMAIL_ATTACHMENT        = 'POOMMAIL:Attachment';
    const POOMMAIL_ATTACHMENTS       = 'POOMMAIL:Attachments';
    const POOMMAIL_BODY              = 'POOMMAIL:Body';
    const POOMMAIL_BODYSIZE          = 'POOMMAIL:BodySize';
    const POOMMAIL_BODYTRUNCATED     = 'POOMMAIL:BodyTruncated';
    const POOMMAIL_DATERECEIVED      = 'POOMMAIL:DateReceived';
    const POOMMAIL_DISPLAYTO         = 'POOMMAIL:DisplayTo';
    const POOMMAIL_IMPORTANCE        = 'POOMMAIL:Importance';
    const POOMMAIL_MESSAGECLASS      = 'POOMMAIL:MessageClass';
    const POOMMAIL_SUBJECT           = 'POOMMAIL:Subject';
    const POOMMAIL_READ              = 'POOMMAIL:Read';
    const POOMMAIL_TO                = 'POOMMAIL:To';
    const POOMMAIL_CC                = 'POOMMAIL:Cc';
    const POOMMAIL_FROM              = 'POOMMAIL:From';
    const POOMMAIL_REPLY_TO          = 'POOMMAIL:Reply-To';
    const POOMMAIL_ALLDAYEVENT       = 'POOMMAIL:AllDayEvent';
    const POOMMAIL_CATEGORIES        = 'POOMMAIL:Categories';
    const POOMMAIL_CATEGORY          = 'POOMMAIL:Category';
    const POOMMAIL_DTSTAMP           = 'POOMMAIL:DtStamp';
    const POOMMAIL_ENDTIME           = 'POOMMAIL:EndTime';
    const POOMMAIL_INSTANCETYPE      = 'POOMMAIL:InstanceType';
    const POOMMAIL_BUSYSTATUS        = 'POOMMAIL:BusyStatus';
    const POOMMAIL_LOCATION          = 'POOMMAIL:Location';
    const POOMMAIL_MEETINGREQUEST    = 'POOMMAIL:MeetingRequest';
    const POOMMAIL_ORGANIZER         = 'POOMMAIL:Organizer';
    const POOMMAIL_RECURRENCEID      = 'POOMMAIL:RecurrenceId';
    const POOMMAIL_REMINDER          = 'POOMMAIL:Reminder';
    const POOMMAIL_RESPONSEREQUESTED = 'POOMMAIL:ResponseRequested';
    const POOMMAIL_RECURRENCES       = 'POOMMAIL:Recurrences';
    const POOMMAIL_RECURRENCE        = 'POOMMAIL:Recurrence';
    const POOMMAIL_TYPE              = 'POOMMAIL:Type';
    const POOMMAIL_UNTIL             = 'POOMMAIL:Until';
    const POOMMAIL_OCCURRENCES       = 'POOMMAIL:Occurrences';
    const POOMMAIL_INTERVAL          = 'POOMMAIL:Interval';
    const POOMMAIL_DAYOFWEEK         = 'POOMMAIL:DayOfWeek';
    const POOMMAIL_DAYOFMONTH        = 'POOMMAIL:DayOfMonth';
    const POOMMAIL_WEEKOFMONTH       = 'POOMMAIL:WeekOfMonth';
    const POOMMAIL_MONTHOFYEAR       = 'POOMMAIL:MonthOfYear';
    const POOMMAIL_STARTTIME         = 'POOMMAIL:StartTime';
    const POOMMAIL_SENSITIVITY       = 'POOMMAIL:Sensitivity';
    const POOMMAIL_TIMEZONE          = 'POOMMAIL:TimeZone';
    const POOMMAIL_GLOBALOBJID       = 'POOMMAIL:GlobalObjId';
    const POOMMAIL_THREADTOPIC       = 'POOMMAIL:ThreadTopic';
    const POOMMAIL_MIMEDATA          = 'POOMMAIL:MIMEData';
    const POOMMAIL_MIMETRUNCATED     = 'POOMMAIL:MIMETruncated';
    const POOMMAIL_MIMESIZE          = 'POOMMAIL:MIMESize';
    const POOMMAIL_INTERNETCPID      = 'POOMMAIL:InternetCPID';

    // EAS 12.0
    const POOMMAIL_CONTENTCLASS      = 'POOMMAIL:ContentClass';
    const POOMMAIL_FLAG              = 'POOMMAIL:Flag';

    // EAS 14.0
    const POOMMAIL_COMPLETETIME            = 'POOMMAIL:CompleteTime';
    const POOMMAIL_DISALLOWNEWTIMEPROPOSAL = 'POOMMAIL:DisallowNewTimeProposal';

    // EAS 14 POOMMAIL2
    const POOMMAIL2_UMCALLERID            = 'POOMMAIL2:UmCallerId';
    const POOMMAIL2_UMUSERNOTES           = 'POOMMAIL2:UmUserNotes';
    const POOMMAIL2_UMATTDURATION         = 'POOMMAIL2:UmAttDuration';
    const POOMMAIL2_UMATTORDER            = 'POOMMAIL2:UmAttOrder';
    const POOMMAIL2_CONVERSATIONID        = 'POOMMAIL2:ConversationId';
    const POOMMAIL2_CONVERSATIONINDEX     = 'POOMMAIL2:ConversationIndex';
    const POOMMAIL2_LASTVERBEXECUTED      = 'POOMMAIL2:LastVerbExecuted';
    const POOMMAIL2_LASTVERBEXECUTIONTIME = 'POOMMAIL2:LastVerbExecutionTime';
    const POOMMAIL2_RECEIVEDASBCC         = 'POOMMAIL2:ReceivedAsBcc';
    const POOMMAIL2_SENDER                = 'POOMMAIL2:Sender';
    const POOMMAIL2_CALENDARTYPE          = 'POOMMAIL2:CalendarType';
    const POOMMAIL2_ISLEAPMONTH           = 'POOMMAIL2:IsLeapMonth';
    const POOMMAIL2_ACCOUNTID             = 'POOMMAIL2:AccountId';
    const POOMMAIL2_FIRSTDAYOFWEEK        = 'POOMMAIL2:FirstDayOfWeek';
    const POOMMAIL2_MEETINGMESSAGETYPE    = 'POOMMAIL2:MeetingMessageType';

    /* Mail message types */
    const CLASS_NOTE            = 'IPM.Note';
    const CLASS_MEETING_REQUEST = 'IPM.Schedule.Meeting.Request';
    const CLASS_MEETING_NOTICE  = 'IPM.Notification.Meeting';

    /* Flags */
    const FLAG_READ_UNSEEN   = 0;
    const FLAG_READ_SEEN     = 1;

    /* UTF-8 codepage id. */
    const INTERNET_CPID_UTF8 = 65001;

    /* Importance */
    const IMPORTANCE_LOW     = 0;
    const IMPORTANCE_NORM    = 1;
    const IMPORTANCE_HIGH    = 2;

    /* Verbs */
    const VERB_NONE          = 0;
    const VERB_REPLY_SENDER  = 1;
    const VERB_REPLY_ALL     = 2;
    const VERB_FORWARD       = 3;

    /**
     * Property mappings
     *
     * @var array
     */
    protected $_mapping = array(
        self::POOMMAIL_TO             => array(self::KEY_ATTRIBUTE => 'to'),
        self::POOMMAIL_CC             => array(self::KEY_ATTRIBUTE => 'cc'),
        self::POOMMAIL_FROM           => array(self::KEY_ATTRIBUTE => 'from'),
        self::POOMMAIL_SUBJECT        => array(self::KEY_ATTRIBUTE => 'subject'),
        self::POOMMAIL_THREADTOPIC    => array(self::KEY_ATTRIBUTE => 'threadtopic'),
        self::POOMMAIL_DATERECEIVED   => array(self::KEY_ATTRIBUTE => 'datereceived', self::KEY_TYPE => self::TYPE_DATE_DASHES),
        self::POOMMAIL_DISPLAYTO      => array(self::KEY_ATTRIBUTE => 'displayto'),
        self::POOMMAIL_IMPORTANCE     => array(self::KEY_ATTRIBUTE => 'importance'),
        self::POOMMAIL_READ           => array(self::KEY_ATTRIBUTE => 'read'),
        self::POOMMAIL_MIMETRUNCATED  => array(self::KEY_ATTRIBUTE => 'mimetruncated' ),
        self::POOMMAIL_MIMEDATA       => array(self::KEY_ATTRIBUTE => 'mimedata', self::KEY_TYPE => 'KEY_TYPE_MAPI_STREAM'),
        self::POOMMAIL_MIMESIZE       => array(self::KEY_ATTRIBUTE => 'mimesize' ),
        self::POOMMAIL_MESSAGECLASS   => array(self::KEY_ATTRIBUTE => 'messageclass'),
        self::POOMMAIL_MEETINGREQUEST => array(self::KEY_ATTRIBUTE => 'meetingrequest', self::KEY_TYPE => 'Horde_ActiveSync_Message_MeetingRequest'),
        self::POOMMAIL_REPLY_TO       => array(self::KEY_ATTRIBUTE => 'reply_to'),
        self::POOMMAIL_INTERNETCPID   => array(self::KEY_ATTRIBUTE => 'cpid'),
    );

    /**
     * Property values.
     *
     * @var array
     */
    protected $_properties = array(
        'to'             => false,
        'cc'             => false,
        'from'           => false,
        'subject'        => false,
        'threadtopic'    => false,
        'datereceived'   => false,
        'displayto'      => false,
        'importance'     => false,
        'mimetruncated'  => false,
        'mimedata'       => false,
        'mimesize'       => false,
        'messageclass'   => false,
        'meetingrequest' => false,
        'reply_to'       => false,
        'read'           => false,
        'cpid'           => false,
    );

    /**
     * The Message-ID. Not streamed to device, needed to determine the reply/
     * forward state.
     *
     * @var string
     * @since 2.4.0
     */
    public $messageid;

    /**
     * Const'r
     *
     * @param array $options  Configuration options for the message:
     *   - logger: (Horde_Log_Logger)  A logger instance
     *             DEFAULT: none (No logging).
     *   - protocolversion: (float)  The version of EAS to support.
     *              DEFAULT: Horde_ActiveSync::VERSION_TWOFIVE (2.5)
     *
     * @return Horde_ActiveSync_Message_Base
     */
    public function __construct(array $options = array())
    {
        parent::__construct($options);
        if ($this->_version == Horde_ActiveSync::VERSION_TWOFIVE) {
            $this->_mapping += array(
                self::POOMMAIL_ATTACHMENTS    => array(self::KEY_ATTRIBUTE => 'attachments', self::KEY_TYPE => 'Horde_ActiveSync_Message_Attachment', self::KEY_VALUES => self::POOMMAIL_ATTACHMENT),
                self::POOMMAIL_BODYTRUNCATED  => array(self::KEY_ATTRIBUTE => 'bodytruncated'),
                self::POOMMAIL_BODYSIZE       => array(self::KEY_ATTRIBUTE => 'bodysize'),
                self::POOMMAIL_BODY           => array(self::KEY_ATTRIBUTE => 'body'),
            );

            $this->_properties += array(
                'attachments'    => false,
                'bodytruncated'  => false,
                'bodysize'       => false,
                'body'           => false,
            );
        }
        if ($this->_version >= Horde_ActiveSync::VERSION_TWELVE) {
            $this->_mapping += array(
                Horde_ActiveSync::AIRSYNCBASE_NATIVEBODYTYPE => array(self::KEY_ATTRIBUTE => 'airsyncbasenativebodytype'),
                Horde_ActiveSync::AIRSYNCBASE_BODY           => array(self::KEY_ATTRIBUTE => 'airsyncbasebody', self::KEY_TYPE=> 'Horde_ActiveSync_Message_AirSyncBaseBody'),
                Horde_ActiveSync::AIRSYNCBASE_ATTACHMENTS    => array(self::KEY_ATTRIBUTE => 'airsyncbaseattachments', self::KEY_TYPE => 'Horde_ActiveSync_Message_AirSyncBaseAttachment', self::KEY_VALUES => Horde_ActiveSync::AIRSYNCBASE_ATTACHMENT ),
                self::POOMMAIL_FLAG                          => array(self::KEY_ATTRIBUTE => 'flag', self::KEY_TYPE => 'Horde_ActiveSync_Message_Flag'),
                self::POOMMAIL_CONTENTCLASS                  => array(self::KEY_ATTRIBUTE => 'contentclass'),
            );

            $this->_properties += array(
                'airsyncbasenativebodytype' => false,
                'airsyncbasebody'           => false,
                'airsyncbaseattachments'    => false,
                'contentclass'              => false,
                'flag'                      => false,
            );

            if ($this->_version >= Horde_ActiveSync::VERSION_FOURTEEN) {
                $this->_mapping += array(
                    self::POOMMAIL2_UMCALLERID            => array(self::KEY_ATTRIBUTE => 'umcallerid'),
                    self::POOMMAIL2_UMUSERNOTES           => array(self::KEY_ATTRIBUTE => 'umusernotes'),
                    self::POOMMAIL2_UMATTDURATION         => array(self::KEY_ATTRIBUTE => 'umattduration'),
                    self::POOMMAIL2_UMATTORDER            => array(self::KEY_ATTRIBUTE => 'umattorder'),
                    self::POOMMAIL2_CONVERSATIONID        => array(self::KEY_ATTRIBUTE => 'conversationid'),
                    self::POOMMAIL2_CONVERSATIONINDEX     => array(self::KEY_ATTRIBUTE => 'conversationindex'),
                    self::POOMMAIL2_LASTVERBEXECUTED      => array(self::KEY_ATTRIBUTE => 'lastverbexecuted'),
                    self::POOMMAIL2_LASTVERBEXECUTIONTIME => array(self::KEY_ATTRIBUTE => 'lastverbexecutiontime', self::KEY_TYPE => self::TYPE_DATE_DASHES),
                    self::POOMMAIL2_RECEIVEDASBCC         => array(self::KEY_ATTRIBUTE => 'receivedasbcc'),
                    self::POOMMAIL2_SENDER                => array(self::KEY_ATTRIBUTE => 'sender'),
                    self::POOMMAIL2_CALENDARTYPE          => array(self::KEY_ATTRIBUTE => 'calendartype'),
                    self::POOMMAIL2_ISLEAPMONTH           => array(self::KEY_ATTRIBUTE => 'isleapmonth'),
                    self::POOMMAIL2_ACCOUNTID             => array(self::KEY_ATTRIBUTE => 'accountid'),
                    self::POOMMAIL2_FIRSTDAYOFWEEK        => array(self::KEY_ATTRIBUTE => 'firstdayofweek'),
                    self::POOMMAIL2_MEETINGMESSAGETYPE    => array(self::KEY_ATTRIBUTE => 'meetingmessagetype')
                );

                $this->_properties += array(
                   'umcallerid'            => false,
                   'umusernotes'           => false,
                   'umattduration'         => false,
                   'umattorder'            => false,
                   'conversationid'        => false,
                   'conversationindex'     => false,
                   'lastverbexecuted'      => false,
                   'lastverbexecutiontime' => false,
                   'receivedasbcc'         => false,
                   'sender'                => false,
                   'calendartype'          => false,
                   'isleapmonth'           => false,
                   'accountid'             => false,
                   'firstdayofweek'        => false,
                   'meetingmessagetype'    => false
                );
            }
        }
    }

    /**
     * Return the class type for this object.
     *
     * @return string
     */
    public function getClass()
    {
        return 'Email';
    }

    /**
     * Checks to see if we should send an empty value.
     *
     * @param string $tag  The tag name
     *
     * @return boolean
     */
    protected function _checkSendEmpty($tag)
    {
        switch ($tag) {
        case self::POOMMAIL_FLAG:
            return true;
        }

        return false;
    }

}
