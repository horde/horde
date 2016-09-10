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
 * @copyright 2011-2016 Horde LLC (http://www.horde.org)
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
 * @copyright 2011-2016 Horde LLC (http://www.horde.org)
 * @author    Michael J Rubinsky <mrubinsk@horde.org>
 * @package   ActiveSync
 *
 * @property string         $to
 * @property string         $cc
 * @property string         $from
 * @property string         $subject
 * @property string         $threadtopic
 * @property Horde_Date     $datereceived
 * @property string         $displayto
 * @property integer        $importance
 * @property integer        $mimetruncated
 * @property string         $mimedata
 * @property integer        $mimesize
 * @property integer        $messageclass
 * @property Horde_ActiveSync_Message_MeetingRequest
 *                          $meetingrequest
 * @property string         $reply_to
 * @property integer        $read
 * @property cpid           $integer  The codepage id.
 * @property Horde_ActiveSync_Message_Attachments
 *                          $attachments (EAS 2.5 only).
 * @property integer        $bodytruncated (EAS 2.5 only)
 * @property integer        $bodysize (EAS 2.5 only)
 * @property stream|string  $body (EAS 2.5 only)
 * @property integer        $airsyncbasenativebodytype (EAS > 2.5 only).
 * @property Horde_ActiveSync_Message_AirSyncBaseBody
 *                          $airsyncbasebody (EAS > 2.5 only).
 * @property Horde_ActiveSync_Message_AirSyncBaseAttachments
 *                          $airsyncbaseattachments (EAS > 2.5 only).
 * @property integer        $contentclass (EAS > 2.5 only).
 * @property Horde_ActiveSync_Message_Flag
 *                          $flag (EAS > 2.5 only).
 * @property boolean        $isdraft (EAS 16.0 only).
 * @property string         $bcc  The bcc recipients (EAS 16.0 only).
 * @property boolean        $send (EAS 16.0 only).
 *
 * // Internal properties. Not streamed to device.
 * @property string         $messageid @since 2.4.0
 * @property boolean        $answered @since 2.4.0
 * @property boolean        $forwarded @since 2.4.0
 */
class Horde_ActiveSync_Message_Mail extends Horde_ActiveSync_Message_Base
{
    const POOMMAIL_ATTACHMENT              = 'POOMMAIL:Attachment';
    const POOMMAIL_ATTACHMENTS             = 'POOMMAIL:Attachments';
    const POOMMAIL_BODY                    = 'POOMMAIL:Body';
    const POOMMAIL_BODYSIZE                = 'POOMMAIL:BodySize';
    const POOMMAIL_BODYTRUNCATED           = 'POOMMAIL:BodyTruncated';
    const POOMMAIL_DATERECEIVED            = 'POOMMAIL:DateReceived';
    const POOMMAIL_DISPLAYTO               = 'POOMMAIL:DisplayTo';
    const POOMMAIL_IMPORTANCE              = 'POOMMAIL:Importance';
    const POOMMAIL_MESSAGECLASS            = 'POOMMAIL:MessageClass';
    const POOMMAIL_SUBJECT                 = 'POOMMAIL:Subject';
    const POOMMAIL_READ                    = 'POOMMAIL:Read';
    const POOMMAIL_TO                      = 'POOMMAIL:To';
    const POOMMAIL_CC                      = 'POOMMAIL:Cc';
    const POOMMAIL_FROM                    = 'POOMMAIL:From';
    const POOMMAIL_REPLY_TO                = 'POOMMAIL:Reply-To';
    const POOMMAIL_ALLDAYEVENT             = 'POOMMAIL:AllDayEvent';
    const POOMMAIL_CATEGORIES              = 'POOMMAIL:Categories';
    const POOMMAIL_CATEGORY                = 'POOMMAIL:Category';
    const POOMMAIL_DTSTAMP                 = 'POOMMAIL:DtStamp';
    const POOMMAIL_ENDTIME                 = 'POOMMAIL:EndTime';
    const POOMMAIL_INSTANCETYPE            = 'POOMMAIL:InstanceType';
    const POOMMAIL_BUSYSTATUS              = 'POOMMAIL:BusyStatus';
    const POOMMAIL_LOCATION                = 'POOMMAIL:Location';
    const POOMMAIL_MEETINGREQUEST          = 'POOMMAIL:MeetingRequest';
    const POOMMAIL_ORGANIZER               = 'POOMMAIL:Organizer';
    const POOMMAIL_RECURRENCEID            = 'POOMMAIL:RecurrenceId';
    const POOMMAIL_REMINDER                = 'POOMMAIL:Reminder';
    const POOMMAIL_RESPONSEREQUESTED       = 'POOMMAIL:ResponseRequested';
    const POOMMAIL_RECURRENCES             = 'POOMMAIL:Recurrences';
    const POOMMAIL_RECURRENCE              = 'POOMMAIL:Recurrence';
    const POOMMAIL_TYPE                    = 'POOMMAIL:Type';
    const POOMMAIL_UNTIL                   = 'POOMMAIL:Until';
    const POOMMAIL_OCCURRENCES             = 'POOMMAIL:Occurrences';
    const POOMMAIL_INTERVAL                = 'POOMMAIL:Interval';
    const POOMMAIL_DAYOFWEEK               = 'POOMMAIL:DayOfWeek';
    const POOMMAIL_DAYOFMONTH              = 'POOMMAIL:DayOfMonth';
    const POOMMAIL_WEEKOFMONTH             = 'POOMMAIL:WeekOfMonth';
    const POOMMAIL_MONTHOFYEAR             = 'POOMMAIL:MonthOfYear';
    const POOMMAIL_STARTTIME               = 'POOMMAIL:StartTime';
    const POOMMAIL_SENSITIVITY             = 'POOMMAIL:Sensitivity';
    const POOMMAIL_TIMEZONE                = 'POOMMAIL:TimeZone';
    const POOMMAIL_GLOBALOBJID             = 'POOMMAIL:GlobalObjId';
    const POOMMAIL_THREADTOPIC             = 'POOMMAIL:ThreadTopic';
    const POOMMAIL_MIMEDATA                = 'POOMMAIL:MIMEData';
    const POOMMAIL_MIMETRUNCATED           = 'POOMMAIL:MIMETruncated';
    const POOMMAIL_MIMESIZE                = 'POOMMAIL:MIMESize';
    const POOMMAIL_INTERNETCPID            = 'POOMMAIL:InternetCPID';

    // EAS 12.0
    const POOMMAIL_CONTENTCLASS            = 'POOMMAIL:ContentClass';
    const POOMMAIL_FLAG                    = 'POOMMAIL:Flag';

    // EAS 14.0
    const POOMMAIL_COMPLETETIME            = 'POOMMAIL:CompleteTime';
    const POOMMAIL_DISALLOWNEWTIMEPROPOSAL = 'POOMMAIL:DisallowNewTimeProposal';

    // EAS 14 POOMMAIL2
    const POOMMAIL2_UMCALLERID             = 'POOMMAIL2:UmCallerId';
    const POOMMAIL2_UMUSERNOTES            = 'POOMMAIL2:UmUserNotes';
    const POOMMAIL2_UMATTDURATION          = 'POOMMAIL2:UmAttDuration';
    const POOMMAIL2_UMATTORDER             = 'POOMMAIL2:UmAttOrder';
    const POOMMAIL2_CONVERSATIONID         = 'POOMMAIL2:ConversationId';
    const POOMMAIL2_CONVERSATIONINDEX      = 'POOMMAIL2:ConversationIndex';
    const POOMMAIL2_LASTVERBEXECUTED       = 'POOMMAIL2:LastVerbExecuted';
    const POOMMAIL2_LASTVERBEXECUTIONTIME  = 'POOMMAIL2:LastVerbExecutionTime';
    const POOMMAIL2_RECEIVEDASBCC          = 'POOMMAIL2:ReceivedAsBcc';
    const POOMMAIL2_SENDER                 = 'POOMMAIL2:Sender';
    const POOMMAIL2_CALENDARTYPE           = 'POOMMAIL2:CalendarType';
    const POOMMAIL2_ISLEAPMONTH            = 'POOMMAIL2:IsLeapMonth';
    const POOMMAIL2_ACCOUNTID              = 'POOMMAIL2:AccountId';
    const POOMMAIL2_FIRSTDAYOFWEEK         = 'POOMMAIL2:FirstDayOfWeek';

    // EAS 14.1
    const POOMMAIL2_MEETINGMESSAGETYPE     = 'POOMMAIL2:MeetingMessageType';

    // EAS 16.0
    const POOMMAIL2_ISDRAFT                = 'POOMMAIL2:IsDraft';
    const POOMMAIL2_BCC                    = 'POOMMAIL2:Bcc';
    const POOMMAIL2_SEND                   = 'POOMMAIL2:Send';

    /* Mail message types */
    const CLASS_NOTE                       = 'IPM.Note';
    const CLASS_MEETING_REQUEST            = 'IPM.Schedule.Meeting.Request';
    const CLASS_MEETING_NOTICE             = 'IPM.Notification.Meeting';

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
        self::POOMMAIL_REPLY_TO       => array(self::KEY_ATTRIBUTE => 'reply_to'),
        self::POOMMAIL_DATERECEIVED   => array(self::KEY_ATTRIBUTE => 'datereceived', self::KEY_TYPE => self::TYPE_DATE_DASHES),
        self::POOMMAIL_DISPLAYTO      => array(self::KEY_ATTRIBUTE => 'displayto'),
        self::POOMMAIL_THREADTOPIC    => array(self::KEY_ATTRIBUTE => 'threadtopic'),
        self::POOMMAIL_IMPORTANCE     => array(self::KEY_ATTRIBUTE => 'importance'),
        self::POOMMAIL_READ           => array(self::KEY_ATTRIBUTE => 'read'),
        self::POOMMAIL_MIMETRUNCATED  => array(self::KEY_ATTRIBUTE => 'mimetruncated' ),
        // Not used.
        self::POOMMAIL_MIMEDATA       => array(self::KEY_ATTRIBUTE => 'mimedata', self::KEY_TYPE => 'KEY_TYPE_MAPI_STREAM'),
        self::POOMMAIL_MIMESIZE       => array(self::KEY_ATTRIBUTE => 'mimesize' ),

        self::POOMMAIL_MESSAGECLASS   => array(self::KEY_ATTRIBUTE => 'messageclass'),
        self::POOMMAIL_MEETINGREQUEST => array(self::KEY_ATTRIBUTE => 'meetingrequest', self::KEY_TYPE => 'Horde_ActiveSync_Message_MeetingRequest'),
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
     * Const'r
     *
     * @see Horde_ActiveSync_Message_Base::__construct()
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
                Horde_ActiveSync::AIRSYNCBASE_ATTACHMENTS    => array(
                    self::KEY_ATTRIBUTE => 'airsyncbaseattachments',
                    self::KEY_TYPE => array('Horde_ActiveSync_Message_AirSyncBaseAttachment', 'Horde_ActiveSync_Message_AirSyncBaseAdd', 'Horde_ActiveSync_Message_AirSyncBaseDelete'),
                    self::KEY_VALUES => array(Horde_ActiveSync::AIRSYNCBASE_ATTACHMENT, Horde_ActiveSync::AIRSYNCBASE_ADD, Horde_ActiveSync::AIRSYNCBASE_DELETE),
                ),
                self::POOMMAIL_FLAG                          => array(self::KEY_ATTRIBUTE => 'flag', self::KEY_TYPE => 'Horde_ActiveSync_Message_Flag'),
                self::POOMMAIL_CONTENTCLASS                  => array(self::KEY_ATTRIBUTE => 'contentclass'),
            );

            $this->_properties += array(
                'airsyncbasenativebodytype' => false,
                'airsyncbasebody'           => false,
                'airsyncbaseattachments'    => array(),
                'contentclass'              => false,
                'flag'                      => false,
            );

            // Removed in 16.0
            if ($this->_version <= Horde_ActiveSync::VERSION_FOURTEENONE) {
                $this->_mapping += array(
                    self::POOMMAIL_LOCATION => array(self::KEY_ATTRIBUTE => 'location'),
                    self::POOMMAIL_GLOBALOBJID => array(self::KEY_ATTRIBUTE => 'globalobjid')
                );
                $this->_properties += array(
                    'location' => false,
                    'globalobjid' => false,
                );
            }

            if ($this->_version >= Horde_ActiveSync::VERSION_FOURTEEN) {
                $this->_mapping += array(
                    self::POOMMAIL_CATEGORIES             => array(self::KEY_ATTRIBUTE => 'categories', self::KEY_VALUES => self::POOMMAIL_CATEGORY),
                    self::POOMMAIL_CATEGORY               => array(self::KEY_ATTRIBUTE => 'category'),
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
                    self::POOMMAIL2_FIRSTDAYOFWEEK        => array(self::KEY_ATTRIBUTE => 'firstdayofweek')
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
                   'categories'            => array(),

                   // Internal use
                   'messageid'             => false,
                   'answered'              => false,
                   'forwarded'             => false,
                );
            }

            if ($this->_version > Horde_ActiveSync::VERSION_FOURTEEN) {
                $this->_mapping += array(
                    Horde_ActiveSync::AIRSYNCBASE_BODYPART => array(self::KEY_ATTRIBUTE => 'airsyncbasebodypart', self::KEY_TYPE => 'Horde_ActiveSync_Message_AirSyncBaseBodypart')
                );
                $this->_properties += array(
                    'airsyncbasebodypart' => false
                );
            }

            if ($this->_version >= Horde_ActiveSync::VERSION_SIXTEEN) {
                $this->_mapping += array(
                    self::POOMMAIL2_ISDRAFT                => array(self::KEY_ATTRIBUTE => 'isdraft'),
                    self::POOMMAIL2_BCC                    => array(self::KEY_ATTRIBUTE => 'bcc'),
                    self::POOMMAIL2_SEND                   => array(self::KEY_ATTRIBUTE => 'send'),
                    Horde_ActiveSync::AIRSYNCBASE_LOCATION => array(self::KEY_ATTRIBUTE => 'location',
                    Horde_ActiveSync_Message_Appointment::POOMCAL_UID => array(self::KEY_ATTRIBUTE => 'uid')),
                );

                $this->_properties += array(
                    'isdraft'  => false,
                    'bcc'      => false,
                    'send'     => false,
                    'location' => false,
                    'uid'      => false,
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
        case self::POOMMAIL_CATEGORIES:
            return true;
        }

        return false;
    }

}
