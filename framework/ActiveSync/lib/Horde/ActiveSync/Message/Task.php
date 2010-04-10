<?php
/**
 * Horde_ActiveSync_Message_Task class represents a single ActiveSync Task.
 *
 * @copyright 2010 The Horde Project (http://www.horde.org)
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Horde_ActiveSync
 */
class Horde_ActiveSync_Message_Task extends Horde_ActiveSync_Message_Base
{
    public $categories = array();

    /* POOMTASKS */
    const POOMTASKS_BODY = 'POOMTASKS:Body';
    const POOMTASKS_BODYSIZE = 'POOMTASKS:BodySize';
    const POOMTASKS_BODYTRUNCATED = 'POOMTASKS:BodyTruncated';
    const POOMTASKS_CATEGORIES = 'POOMTASKS:Categories';
    const POOMTASKS_CATEGORY = 'POOMTASKS:Category';
    const POOMTASKS_COMPLETE = 'POOMTASKS:Complete';
    const POOMTASKS_DATECOMPLETED = 'POOMTASKS:DateCompleted';
    const POOMTASKS_DUEDATE = 'POOMTASKS:DueDate';
    const POOMTASKS_UTCDUEDATE = 'POOMTASKS:UtcDueDate';
    const POOMTASKS_IMPORTANCE = 'POOMTASKS:Importance';
    const POOMTASKS_RECURRENCE = 'POOMTASKS:Recurrence';
    const POOMTASKS_TYPE = 'POOMTASKS:Type';
    const POOMTASKS_START = 'POOMTASKS:Start';
    const POOMTASKS_UNTIL = 'POOMTASKS:Until';
    const POOMTASKS_OCCURRENCES = 'POOMTASKS:Occurrences';
    const POOMTASKS_INTERVAL = 'POOMTASKS:Interval';
    const POOMTASKS_DAYOFWEEK = 'POOMTASKS:DayOfWeek';
    const POOMTASKS_DAYOFMONTH = 'POOMTASKS:DayOfMonth';
    const POOMTASKS_WEEKOFMONTH = 'POOMTASKS:WeekOfMonth';
    const POOMTASKS_MONTHOFYEAR = 'POOMTASKS:MonthOfYear';
    const POOMTASKS_REGENERATE = 'POOMTASKS:Regenerate';
    const POOMTASKS_DEADOCCUR = 'POOMTASKS:DeadOccur';
    const POOMTASKS_REMINDERSET = 'POOMTASKS:ReminderSet';
    const POOMTASKS_REMINDERTIME = 'POOMTASKS:ReminderTime';
    const POOMTASKS_SENSITIVITY = 'POOMTASKS:Sensitivity';
    const POOMTASKS_STARTDATE = 'POOMTASKS:StartDate';
    const POOMTASKS_UTCSTARTDATE = 'POOMTASKS:UtcStartDate';
    const POOMTASKS_SUBJECT = 'POOMTASKS:Subject';
    const POOMTASKS_RTF = 'POOMTASKS:Rtf';

    /**
     * Const'r
     *
     * @param array $params
     */
    function __construct($params = array()) {
        $mapping = array (
            self::POOMTASKS_BODY => array (self::KEY_ATTRIBUTE => 'body'),
            self::POOMTASKS_COMPLETE => array (self::KEY_ATTRIBUTE => 'complete'),
            self::POOMTASKS_DATECOMPLETED => array (self::KEY_ATTRIBUTE => 'datecompleted', self::KEY_TYPE => self::TYPE_DATE_DASHES),
            self::POOMTASKS_DUEDATE => array (self::KEY_ATTRIBUTE => 'duedate', self::KEY_TYPE => self::TYPE_DATE_DASHES),
            self::POOMTASKS_UTCDUEDATE => array (self::KEY_ATTRIBUTE => 'utcduedate', self::KEY_TYPE => self::TYPE_DATE_DASHES),
            self::POOMTASKS_IMPORTANCE => array (self::KEY_ATTRIBUTE => 'importance'),
            self::POOMTASKS_RECURRENCE => array (self::KEY_ATTRIBUTE => 'recurrence', self::KEY_TYPE => 'SyncTaskRecurrence'),
            self::POOMTASKS_REGENERATE => array (self::KEY_ATTRIBUTE => 'regenerate'),
            self::POOMTASKS_DEADOCCUR => array (self::KEY_ATTRIBUTE => 'deadoccur'),
            self::POOMTASKS_REMINDERSET => array (self::KEY_ATTRIBUTE => 'reminderset'),
            self::POOMTASKS_REMINDERTIME => array (self::KEY_ATTRIBUTE => 'remindertime', self::KEY_TYPE => self::TYPE_DATE_DASHES),
            self::POOMTASKS_SENSITIVITY => array (self::KEY_ATTRIBUTE => 'sensitiviy'),
            self::POOMTASKS_STARTDATE => array (self::KEY_ATTRIBUTE => 'startdate', self::KEY_TYPE => self::TYPE_DATE_DASHES),
            self::POOMTASKS_UTCSTARTDATE => array (self::KEY_ATTRIBUTE => 'utcstartdate', self::KEY_TYPE => self::TYPE_DATE_DASHES),
            self::POOMTASKS_SUBJECT => array (self::KEY_ATTRIBUTE => 'subject'),
            self::POOMTASKS_RTF => array (self::KEY_ATTRIBUTE => 'rtf'),
            self::POOMTASKS_CATEGORIES => array (self::KEY_ATTRIBUTE => 'categories', self::KEY_VALUES => self::POOMTASKS_CATEGORY),
        );

        parent::__construct($mapping, $params);
    }
    
}