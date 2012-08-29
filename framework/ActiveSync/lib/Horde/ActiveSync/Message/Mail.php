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
 * @copyright 2011-2012 Horde LLC (http://www.horde.org)
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
 * @copyright 2011-2012 Horde LLC (http://www.horde.org)
 * @author    Michael J Rubinsky <mrubinsk@horde.org>
 * @package   ActiveSync
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
    );

    /* Mail message types */
    const CLASS_NOTE            = 'IPM.Note';
    const CLASS_MEETING_REQUEST = 'IPM.Schedule.Meeting.Request';
    const CLASS_MEETING_NOTICE  = 'IPM.Notification.Meeting';

    /* Flags */
    const FLAG_READ_UNSEEN = 0;
    const FLAG_READ_SEEN   = 1;

    public $read = false;
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
    );

    /**
     * Const'r
     *
     * @param array $options  Configuration options for the message:
     *   - logger: (Horde_Log_Logger)  A logger instance
     *             DEFAULT: none (No logging).
     *   - version: (float)  The version of EAS to support.
     *              DEFAULT: Horde_ActiveSync::VERSION_TWOFIVE (2.5)
     *
     * @return Horde_ActiveSync_Message_Base
     */
    public function __construct(array $options = array())
    {
        parent::__construct($options);
        if ($this->_version < 12.0) {
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
        if ($this->_version >= 12.0) {
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

}
