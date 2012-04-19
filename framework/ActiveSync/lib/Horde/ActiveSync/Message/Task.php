<?php
/**
 * Horde_ActiveSync_Message_Task::
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
 * @copyright 2010-2012 Horde LLC (http://www.horde.org)
 * @author    Michael J Rubinsky <mrubinsk@horde.org>
 * @package   ActiveSync
 */
/**
 * Horde_ActiveSync_Message_Task::
 *
 * @license   http://www.horde.org/licenses/gpl GPLv2
 *            NOTE: According to sec. 8 of the GENERAL PUBLIC LICENSE (GPL),
 *            Version 2, the distribution of the Horde_ActiveSync module in or
 *            to the United States of America is excluded from the scope of this
 *            license.
 * @copyright 2010-2012 Horde LLC (http://www.horde.org)
 * @author    Michael J Rubinsky <mrubinsk@horde.org>
 * @package   ActiveSync
 */
class Horde_ActiveSync_Message_Task extends Horde_ActiveSync_Message_Base
{
    /* POOMTASKS */
    const POOMTASKS_BODY           = 'POOMTASKS:Body';
    const POOMTASKS_BODYSIZE       = 'POOMTASKS:BodySize';
    const POOMTASKS_BODYTRUNCATED  = 'POOMTASKS:BodyTruncated';
    const POOMTASKS_CATEGORIES     = 'POOMTASKS:Categories';
    const POOMTASKS_CATEGORY       = 'POOMTASKS:Category';
    const POOMTASKS_COMPLETE       = 'POOMTASKS:Complete';
    const POOMTASKS_DATECOMPLETED  = 'POOMTASKS:DateCompleted';
    const POOMTASKS_DUEDATE        = 'POOMTASKS:DueDate';
    const POOMTASKS_UTCDUEDATE     = 'POOMTASKS:UtcDueDate';
    const POOMTASKS_IMPORTANCE     = 'POOMTASKS:Importance';
    const POOMTASKS_RECURRENCE     = 'POOMTASKS:Recurrence';
    const POOMTASKS_TYPE           = 'POOMTASKS:Type';
    const POOMTASKS_START          = 'POOMTASKS:Start';
    const POOMTASKS_UNTIL          = 'POOMTASKS:Until';
    const POOMTASKS_OCCURRENCES    = 'POOMTASKS:Occurrences';
    const POOMTASKS_INTERVAL       = 'POOMTASKS:Interval';
    const POOMTASKS_DAYOFWEEK      = 'POOMTASKS:DayOfWeek';
    const POOMTASKS_DAYOFMONTH     = 'POOMTASKS:DayOfMonth';
    const POOMTASKS_WEEKOFMONTH    = 'POOMTASKS:WeekOfMonth';
    const POOMTASKS_MONTHOFYEAR    = 'POOMTASKS:MonthOfYear';
    const POOMTASKS_REGENERATE     = 'POOMTASKS:Regenerate';
    const POOMTASKS_DEADOCCUR      = 'POOMTASKS:DeadOccur';
    const POOMTASKS_REMINDERSET    = 'POOMTASKS:ReminderSet';
    const POOMTASKS_REMINDERTIME   = 'POOMTASKS:ReminderTime';
    const POOMTASKS_SENSITIVITY    = 'POOMTASKS:Sensitivity';
    const POOMTASKS_STARTDATE      = 'POOMTASKS:StartDate';
    const POOMTASKS_UTCSTARTDATE   = 'POOMTASKS:UtcStartDate';
    const POOMTASKS_SUBJECT        = 'POOMTASKS:Subject';
    const POOMTASKS_RTF            = 'POOMTASKS:Rtf';
    // EAS 12.0
    const POOMTASKS_ORDINALDATE    = 'POOMTASKS:OrdinalDate';
    const POOMTASKS_SUBORDINALDATE = 'POOMTASKS:SubOrdinalDate';

    const TASK_COMPLETE_TRUE      = 1;
    const TASK_COMPLETE_FALSE     = 0;

    const IMPORTANCE_LOW          = 0;
    const IMPORTANCE_NORMAL       = 1;
    const IMPORTANCE_HIGH         = 2;

    const REMINDER_SET_FALSE      = 0;
    const REMINDER_SET_TRUE       = 1;

    protected $_mapping = array (
        self::POOMTASKS_BODY          => array (self::KEY_ATTRIBUTE => 'body'),
        self::POOMTASKS_COMPLETE      => array (self::KEY_ATTRIBUTE => 'complete'),
        self::POOMTASKS_DATECOMPLETED => array (self::KEY_ATTRIBUTE => 'datecompleted', self::KEY_TYPE => self::TYPE_DATE_DASHES),
        self::POOMTASKS_DUEDATE       => array (self::KEY_ATTRIBUTE => 'duedate', self::KEY_TYPE => self::TYPE_DATE_DASHES),
        self::POOMTASKS_UTCDUEDATE    => array (self::KEY_ATTRIBUTE => 'utcduedate', self::KEY_TYPE => self::TYPE_DATE_DASHES),
        self::POOMTASKS_IMPORTANCE    => array (self::KEY_ATTRIBUTE => 'importance'),
        self::POOMTASKS_RECURRENCE    => array (self::KEY_ATTRIBUTE => 'recurrence', self::KEY_TYPE => 'Horde_ActiveSync_Message_TaskRecurrence'),
        self::POOMTASKS_REGENERATE    => array (self::KEY_ATTRIBUTE => 'regenerate'),
        self::POOMTASKS_DEADOCCUR     => array (self::KEY_ATTRIBUTE => 'deadoccur'),
        self::POOMTASKS_REMINDERSET   => array (self::KEY_ATTRIBUTE => 'reminderset'),
        self::POOMTASKS_REMINDERTIME  => array (self::KEY_ATTRIBUTE => 'remindertime', self::KEY_TYPE => self::TYPE_DATE_DASHES),
        self::POOMTASKS_SENSITIVITY   => array (self::KEY_ATTRIBUTE => 'sensitiviy'),
        self::POOMTASKS_STARTDATE     => array (self::KEY_ATTRIBUTE => 'startdate', self::KEY_TYPE => self::TYPE_DATE_DASHES),
        self::POOMTASKS_UTCSTARTDATE  => array (self::KEY_ATTRIBUTE => 'utcstartdate', self::KEY_TYPE => self::TYPE_DATE_DASHES),
        self::POOMTASKS_SUBJECT       => array (self::KEY_ATTRIBUTE => 'subject'),
        self::POOMTASKS_RTF           => array (self::KEY_ATTRIBUTE => 'rtf'),
        self::POOMTASKS_CATEGORIES    => array (self::KEY_ATTRIBUTE => 'categories', self::KEY_VALUES => self::POOMTASKS_CATEGORY),
    );

    protected $_properties = array(
        'body'          => false,
        'bodytruncated' => 0,
        'categories'    => array(),
        'complete'      => false,
        'datecompleted' => false,
        'deadoccur'     => false,
        'duedate'       => false,
        'importance'    => false,
        'recurrence'    => false,
        'regenerate'    => false,
        'reminderset'   => false,
        'remindertime'  => false,
        'rtf'           => false,
        'sensitiviy'    => false,
        'startdate'     => false,
        'subject'       => false,
        'utcduedate'    => false,
        'utcstartdate'  => false,
    );

    /**
     * Sets the task subject
     *
     * @param string $subject
     */
    public function setSubject($subject)
    {
        $this->_properties['subject'] = $subject;
    }

    /**
     * Get the task subject/title
     *
     * @return string  The task subject
     */
    public function getSubject()
    {
        return $this->_getAttribute('subject');
    }

    /**
     * Returns the body of the task.
     *
     * @return string  The descriptive body.
     */
    public function getBody()
    {
        return $this->_getAttribute('body');
    }

    /**
     * Set the task body element.
     *
     * @param string $body  The task body
     */
    public function setBody($body)
    {
        $this->_properties['body'] = $body;
    }

    /**
     * Set the task completion flag
     *
     * @param integer $flag  TASK_COMPLETE constant
     */
    public function setComplete($flag)
    {
        $this->_properties['complete'] = $flag;
    }

    /**
     * Get the completion flag
     *
     * @return integer  A TASK_COMPLETE constant
     */
    public function getComplete()
    {
        return $this->_getAttribute('complete');
    }

    /**
     * Set the date the task was completed.
     *
     * @param Horde_Date $date  The date in local tz.
     */
    public function setDateCompleted(Horde_Date $date)
    {
        $this->_properties['datecompleted'] = $date;
    }

    /**
     * Get the date completed.
     *
     * @return Horde_Date  The date in the local tz.
     */
    public function getDateCompleted()
    {
        return $this->_getAttribute('datecompleted');
    }

    /**
     * Set the due date. Note that even though the property is called UTCDueDate
     * we still pass a Horde_Date in the user's timezone since the dates are
     * transformed to UTC during encoding. Yay consistency...
     *
     * @param Horde_Date $date  The date.
     */
    public function setDueDate(Horde_Date $date)
    {
        $this->_properties['utcduedate'] = $date;
    }

    /**
     * Get the task due date.
     *
     * @return Horde_Date  Date in local tz
     */
    public function getDueDate()
    {
        return $this->_getAttribute('utcduedate');
    }

    /**
     * Set the importance
     *
     * @param integer $importance  A IMPORTANCE_* flag
     */
    public function setImportance($importance)
    {
        if (is_null($importance)) {
            $importance = self::IMPORTANCE_NORMAL;
        }

        $this->_properties['importance'] = $importance;
    }

    /**
     * Get the task importance level
     *
     * @return integer  A IMPORTANCE_* constant
     */
    public function getImportance()
    {
        return $this->_getAttribute('importance', self::IMPORTANCE_NORMAL);
    }

    /**
     * Set the reminder datetime
     *
     * @param Horde_Date $datetime  The time to trigger the alarm in local tz.
     */
    public function setReminder(Horde_Date $datetime)
    {
        $this->_properties['remindertime'] = $datetime;
        $this->_properties['reminderset'] = self::REMINDER_SET_TRUE;
    }

    /**
     * Get the reminder time.
     *
     * @return Horde_Date  in local tz
     */
    public function getReminder()
    {
        if (!$this->_getAttribute('reminderset')) {
            return false;
        }

        return $this->_getAttribute('remindertime');
    }

    /**
     * Set the task start datetime
     *
     * @param Horde_Date $date  Date in local tz
     */
    public function setStartDate(Horde_Date $date)
    {
        $this->_properties['utcstartdate'] = $date;
    }

    /**
     * Get the task start datetime
     *
     * @return Horde_Date
     */
    public function getStartDate()
    {
        return $this->_getAttribute('utcstartdate');
    }

    /**
     * Return this object's folder class
     *
     * @return string
     */
    public function getClass()
    {
        return 'Tasks';
    }

    /**
     * Set recurrence information for this task
     *
     * @param Horde_Date_Recurrence $recurrence
     */
    public function setRecurrence(Horde_Date_Recurrence $recurrence)
    {
        $r = new Horde_ActiveSync_Message_TaskRecurrence();

        // Map the type fields
        switch ($recurrence->recurType) {
        case Horde_Date_Recurrence::RECUR_DAILY:
            $r->type = Horde_ActiveSync_Message_Recurrence::TYPE_DAILY;
            break;
        case Horde_Date_Recurrence::RECUR_WEEKLY;
            $r->type = Horde_ActiveSync_Message_Recurrence::TYPE_WEEKLY;
            $r->dayofweek = $recurrence->getRecurOnDays();
            break;
        case Horde_Date_Recurrence::RECUR_MONTHLY_DATE:
            $r->type = Horde_ActiveSync_Message_Recurrence::TYPE_MONTHLY;
            break;
        case Horde_Date_Recurrence::RECUR_MONTHLY_WEEKDAY;
            $r->type = Horde_ActiveSync_Message_Recurrence::TYPE_MONTHLY_NTH;
            $r->weekofmonth = ceil($recurrence->start->mday / 7);
            $r->dayofweek = $this->_dayOfWeekMap[$recurrence->start->dayOfWeek()];
            break;
        case Horde_Date_Recurrence::RECUR_YEARLY_DATE:
            $r->type = Horde_ActiveSync_Message_Recurrence::TYPE_YEARLY;
            break;
        case Horde_Date_Recurrence::RECUR_YEARLY_WEEKDAY:
            $r->type = Horde_ActiveSync_Message_Recurrence::TYPE_YEARLYNTH;
            $r->dayofweek = $this->_dayOfWeekMap[$recurrence->start->dayOfWeek()];
            $r->weekofmonth = ceil($recurrence->start->mday / 7);
            $r->monthofyear = $recurrence->start->month;
            break;
        }
        if (!empty($recurrence->recurInterval)) {
            $r->interval = $recurrence->recurInterval;
        }

        // AS messages can only have one or the other (or none), not both
        if ($recurrence->hasRecurCount()) {
            $r->occurrences = $recurrence->getRecurCount();
        } elseif ($recurrence->hasRecurEnd()) {
            $r->until = $recurrence->getRecurEnd();
        }

        // Not sure when a recurring task would ever not regenerate.... but the
        // protocol requires this.
        $r->regenerate = true;

        $this->_properties['recurrence'] = $r;
    }

    /**
     * Obtain a recurrence object. Note this returns a Horde_Date_Recurrence
     * object, not Horde_ActiveSync_Message_Recurrence.
     *
     * @return Horde_Date_Recurrence
     */
    public function getRecurrence()
    {
        if (!$recurrence = $this->_getAttribute('recurrence')) {
            return false;
        }

        $d = clone($this->getDueDate());
        //  $d->setTimezone($this->getTimezone());

        $rrule = new Horde_Date_Recurrence($d);

        /* Map MS AS type field to Horde_Date_Recurrence types */
        switch ($recurrence->type) {
        case Horde_ActiveSync_Message_Recurrence::TYPE_DAILY:
            $rrule->setRecurType(Horde_Date_Recurrence::RECUR_DAILY);
             break;
        case Horde_ActiveSync_Message_Recurrence::TYPE_WEEKLY:
            $rrule->setRecurType(Horde_Date_Recurrence::RECUR_WEEKLY);
            $rrule->setRecurOnDay($recurrence->dayofweek);
            break;
        case Horde_ActiveSync_Message_Recurrence::TYPE_MONTHLY:
            $rrule->setRecurType(Horde_Date_Recurrence::RECUR_MONTHLY_DATE);
            break;
        case Horde_ActiveSync_Message_Recurrence::TYPE_MONTHLY_NTH:
            $rrule->setRecurType(Horde_Date_Recurrence::RECUR_MONTHLY_WEEKDAY);
            $rrule->setRecurOnDay($recurrence->dayofweek);
            break;
        case Horde_ActiveSync_Message_Recurrence::TYPE_YEARLY:
            $rrule->setRecurType(Horde_Date_Recurrence::RECUR_YEARLY_DATE);
            break;
        case Horde_ActiveSync_Message_Recurrence::TYPE_YEARLYNTH:
            $rrule->setRecurType(Horde_Date_Recurrence::RECUR_YEARLY_WEEKDAY);
            $rrule->setRecurOnDay($recurrence->dayofweek);
            break;
        }

        if ($rcnt = $recurrence->occurrences) {
            $rrule->setRecurCount($rcnt);
        }
        if ($runtil = $recurrence->until) {
            $rrule->setRecurEnd(new Horde_Date($runtil));
        }
        if ($interval = $recurrence->interval) {
            $rrule->setRecurInterval($interval);
        }

        return $rrule;
    }

    protected function _checkSendEmpty($tag)
    {
        if ($tag == self::POOMTASKS_BODYTRUNCATED && $this->bodysize > 0) {
            return true;
        }

        return false;
    }

}