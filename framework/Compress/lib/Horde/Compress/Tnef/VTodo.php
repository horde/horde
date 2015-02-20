<?php
/**
 * Object to parse and represent vTODO data encapsulated in a TNEF file.
 *
 * Copyright 2002-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Michael J Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Compress
 */
/**
 * Copyright 2002-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Michael J Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Compress
 */
class Horde_Compress_Tnef_VTodo extends Horde_Compress_Tnef_Object
{
    const MAPI_TASK_STATUS          = 0x8101;
    const MAPI_TASK_PERCENTCOMPLETE = 0x8102;

    // These are in user's local timezone and MUST have a time component of
    // 12:00 midnight.
    const MAPI_TASK_STARTDATE       = 0x8104;
    const MAPI_TASK_DUEDATE         = 0x8105;

    // The following is used as a placeholder value when a task has no start
    // or due date. Some clients set the property empty, some set it to this
    // value.
    const MAPI_TASK_NODUEDATE       = 0x5AE980E0;

    // The following properties are UTC equivalant values of MAPI_TASk_STARTDATE
    // and MAPI_TASK_DUEDATE
    const MAPI_TASK_COMMONEND       = 0x8517;
    const MAPI_TASK_COMMONSTART     = 0x8516;

    const MAPI_TASK_ACCEPTED        = 0x8108;
    const MAPI_TASK_DATECOMPLETED   = 0x810F;
    const MAPI_TASK_STATE           = 0x8113;
    const MAPI_TASK_ASSIGNERS       = 0x8117;

    // If non-zero, updates are requested.
    const MAPI_TASK_UPDATES         = 0x811B;
    const MAPI_TASK_OWNER           = 0x811F;
    const MAPI_TASK_ASSIGNER        = 0x8121;
    const MAPI_TASK_LASTUSER        = 0x8122;
    const MAPI_TASK_OWNERSHIP       = 0x8129;

    /**
     * MAPI_TASK_STATUS constants
     */
    const STATUS_NOT_STARTED        = 0x00000000;
    const STATUS_IN_PROGRESS        = 0x00000001;
    const STATUS_COMPLETE           = 0x00000002;
    const STATUS_WAIT               = 0x00000003;
    const STATUS_DEFERRED           = 0x00000004;

    /**
     * MAPI_TASK_STATE constants
     */
    const STATE_TASK_NOT_FOUND     = 0x00000000;
    const STATE_NOT_ASSIGNED       = 0x00000001;
    const STATE_ASSIGNEE_COPY      = 0x00000002;
    const STATE_ASSIGNERS_COPY     = 0x00000003;
    const STATE_ASSIGNERS_REJECTED = 0x00000004;

    /**
     * MAPI_TASK_OWNERSHIP
     */
    const OWNERSHIP_NONE           = 0x00000000;
    const OWNERSHIP_ASSIGNERS_COPY = 0x00000001;
    const OWNERSHIP_ASSIGNEES_COPY = 0x00000002;

    /**
     * MAPI_MESSAGE_CLASS
     */
    const CLASS_REQUEST            = 'IPM.TaskRequest';
    const CLASS_ACCEPT             = 'IPM.TaskRequest.Accept';
    const CLASS_DECLINE            = 'IPM.TaskRequest.Decline';
    const CLASS_UPDATE             = 'IPM.TaskRequest.Update';

    const TASK_STATUS_ACTION       = 'NEEDS-ACTION';
    const TASK_STATUS_IN_PROGRESS  = 'IN-PROGRESS';
    const TASK_STATUS_COMPLETED    = 'COMPLETED';

    /**
     * Due date (timestamp).
     *
     * @var integer.
     */
    protected $_due;

    /**
     * UID
     *
     * @var string
     */
    protected $_guid;

    /**
     * @var integer
     */
    protected $_msgformat;

    /**
     * Percentage of task that is completed.
     *
     * @var integer
     */
    protected $_percentComplete;

    /**
     * Plain body
     *
     * @var string
     */
    protected $_bodyPlain;

    /**
     * HTML body.
     *
     * @var string
     */
    protected $_bodyHtml;

    /**
     * Compressed RTF body.
     *
     * @var string
     */
    protected $_rtfCompressed;

    /**
     * If true, assignee is requested to send updates.
     *
     * @var boolean
     */
    protected $_updates = false;

    /**
     * The MAPI_TASK_STATE value. Used to help determine METHOD.
     *
     * @var integer.
     */
    protected $_state;

    /**
     * The MAPI_TASK_OWNERSHIP value.
     *
     * @var integer
     */
    protected $_ownership;

    /**
     * The METHOD to use in the generated vTodo component. Default
     * to REQUEST since TNEF files are generally not used for PUBLISH.
     *
     * @var string
     */
    protected $_method = 'REQUEST';

    /**
     * The MAPI_MESSAGE_CLASS
     *
     * @var string
     */
    protected $_messageClass;

    /**
     * The current owner of the task. Note, this is the CURRENT owner,
     * so for the initial REQUEST, this will be empty. MS doesn't consider
     * the task creator the owner in this context.
     *
     * @var string
     */
    protected $_owner;

    /**
     * Last user to modify the request.
     *
     * @var string
     */
    protected $_lastUser;

    /**
     * Start time of task.
     *
     * @var integer
     */
    protected $_start;

    /**
     * Status of task.
     *
     * @var string
     */
    protected $_status;

    /**
     * The MIME type of this object's content.
     *
     * @var string
     */
    public $type = 'text/calendar';

    /**
     * Timestamp when task was completed.
     *
     * @var integer
     */
    protected $_completed;

    public function __get($property)
    {
        if ($property == 'content') {
            return $this->_tovTodo();
        }

        throw new InvalidArgumentException('Invalid property access.');
    }

    /**
     * Allow this object to set any TNEF attributes it needs to know about,
     * ignore any it doesn't care about.
     *
     * @param integer $attribute  The attribute descriptor.
     * @param mixed $value        The value from the MAPI stream.
     * @param integer $size       The byte length of the data, as reported by
     *                            the MAPI data.
     */
    public function setTnefAttribute($attribute, $value, $size)
    {
        switch ($attribute) {
        case Horde_Compress_Tnef::ABODY;
            $this->_bodyPlain = trim($value);
            break;
        }
    }

    /**
     * Allow this object to set any MAPI attributes it needs to know about,
     * ignore any it doesn't care about.
     *
     * @param integer $type  The attribute type descriptor.
     * @param integer $name  The attribute name descriptor.
     */
    public function setMapiAttribute($type, $name, $value, $ns = null)
    {
        if ($ns == Horde_Compress_Tnef::PSETID_COMMON) {
            switch ($name) {
            case Horde_Compress_Tnef::IPM_TASK_GUID:
                // Almost positive this is wrong :(
                $this->_guid = Horde_Mapi::getUidFromGoid(bin2hex($value));
                break;
            case Horde_Compress_Tnef::MSG_EDITOR_FORMAT:
                // Map this?
                $this->_msgformat = $value;
                break;
            case Horde_Compress_Tnef::MAPI_TAG_BODY:
                // plaintext. Most likely set via the attBody TNEF attribute,
                // and not by the MAPI property.
                if (empty($this->_bodyPlain)) {
                    $this->_bodyPlain = $value;
                }
                break;
            case Horde_Compress_Tnef::MAPI_TAG_HTML:
                // html
                $this->_bodyHtml = $value;
                break;
            case self::MAPI_TASK_COMMONSTART:
                $this->_start = new Horde_Date(Horde_Mapi::filetimeToUnixtime($value));
                $this->_start = $this->_start->timestamp();
                break;
            case self::MAPI_TASK_COMMONEND:
                $this->_due = new Horde_Date(Horde_Mapi::filetimeToUnixtime($value));
                $this->_due = $this->_due->timestamp();
                break;
            }
        } elseif ($ns == Horde_Compress_Tnef::PSETID_TASK) {
            switch ($name) {
            case self::MAPI_TASK_OWNER:
                // This is the OWNER, not to be confused with the ORGANIZER.
                // I.e., this is the person the task has been assigned to.
                // The ORGANIZER is the person who created the task and has
                // assigned it. I.e., the person that any task updates are
                // sent back to by the owner.
                $this->_owner = str_replace(array('(', ')'), array('<', '>'), $value);
                break;
            case self::MAPI_TASK_DUEDATE:
                // Favor COMMONEND
                if (empty($this->_due)) {
                    $this->_due = Horde_Mapi::filetimeToUnixtime($value);
                }
                break;
            case self::MAPI_TASK_STARTDATE:
                if (empty($this->_start)) {
                    $this->_start = Horde_Mapi::filetimeToUnixtime($value);
                }
                break;
            case self::MAPI_TASK_DATECOMPLETED:
                $this->_completed = Horde_Mapi::filetimeToUnixtime($value);
                break;
            case self::MAPI_TASK_PERCENTCOMPLETE:
                $value = unpack('d', $value);
                $this->_percentComplete = $value[1] * 100;
                break;
            case self::MAPI_TASK_STATUS:
                switch ($value) {
                case self::STATUS_NOT_STARTED:
                case self::STATUS_WAIT:
                case self::STATUS_DEFERRED:
                    $this->_percentComplete = 0;
                    $this->_status = self::TASK_STATUS_ACTION;
                    break;
                case self::STATUS_IN_PROGRESS:
                    $this->_status = self::TASK_STATUS_IN_PROGRESS;
                    break;
                case self::STATUS_COMPLETE:
                    $this->_status = self::TASK_STATUS_COMPLETED;
                    $this->_percentComplete = 1;
                    break;
                }
                break;
            case self::MAPI_TASK_UPDATES:
                if (!empty($value)) {
                    $this->_updates = true;
                }
                break;
            case self::MAPI_TASK_OWNERSHIP:
                $this->_ownership = $value;
                break;
            case self::MAPI_TASK_STATE:
                $this->_state = $value;
                break;
            case Horde_Compress_Tnef::MAPI_LAST_MODIFIER_NAME:
                $this->_lastUser = $value;
                break;
            // case self::MAPI_TASK_ASSIGNER:
            //     // *sigh* This isn't set by Outlook/Exchange until AFTER the
            //     // assignee receives the request. I.e., this is blank on the initial
            //     // REQUEST so not a valid way to obtain the task creator.
            //     //$this->_organizer = $value;
            //     break;
            // case self::MAPI_TASK_LASTUSER:
            //     // From MS-OXOTASK 2.2.2.2.25:
            //     // Before client sends a REQUEST, it is set to the assigner.
            //     // Before client sends an ACCEPT, it is set to the assignee.
            //     // Before client sneds REJECT, it is set to the assigner, not assignee.
            //     // Unfortunately, it is only the display name, not the email!
            //     //$this->_lastUser = $value;
                break;
            }
        } else {
            // pidTag?
            switch ($name) {
            case Horde_Compress_Tnef::MAPI_SENT_REP_EMAIL_ADDR:
                $this->_organizer = $value;
            }
        }
    }

    /**
     * Output the data for this object in an array.
     *
     * @return array
     *   - type: (string)    The MIME type of the content.
     *   - subtype: (string) The MIME subtype.
     *   - name: (string)    The filename.
     *   - stream: (string)  The file data.
     */
    public function toArray()
    {
        return $this->_tovTodo();
    }

    protected function _tovTodo()
    {
        $iCal = new Horde_Icalendar();
        $iCal->setAttribute('METHOD', $this->_method);
        $vtodo = Horde_Icalendar::newComponent('vtodo', $iCal);

        $vtodo->setAttribute('UID', $this->_guid);

        // For REQUESTS, we MUST have the ORGANIZER and an ATTENDEE.
        if ($this->_state == self::STATE_ASSIGNERS_COPY || $this->_ownership == self::OWNERSHIP_ASSIGNERS_COPY) {
            $vtodo->setAttribute('ORGANIZER', 'mailto: ' . $this->_organizer);
            $list = new Horde_Mail_Rfc822_List($this->_owner);
            foreach ($list as $email) {
                $vtodo->setAttribute('ATTENDEE', $email, array('ROLE' => 'REQ-PARTICIPANT'));
            }
        }
        if ($this->_due) {
            $vtodo->setAttribute('DUE', $this->_due);
        }
        if ($this->_start) {
            $vtodo->setAttribute('DTSTART', $this->_start);
        }
        if ($this->_completed) {
            $vtodo->setAttribute('COMPLETED', $this->_completed);
        }

        if (isset($this->_percentComplete)) {
            $vtodo->setAttribute('PERCENT-COMPLETE', $this->_percentComplete);
        }

        // Summary is stored in the message data.
        $msg = $this->_options['parent']->getMsgInfo();
        if ($msg->subject) {
            $vtodo->setAttribute('SUMMARY', $msg->subject);
        }

        // Figure out the body.
        if ($this->_bodyPlain) {
            $vtodo->setAttribute('DESCRIPTION', $this->_bodyPlain);
        } elseif ($this->_bodyHtml) {
            $vtodo->setAttribute('DESCRIPTION', Horde_Text_Filter::filter($this->_bodyHtml, 'html2text'));
        }

        $iCal->addComponent($vtodo);

        return array(
            'type'    => 'text',
            'subtype' => 'calendar',
            'name'    => $msg->subject ? $msg->subject . '.vtodo': 'Untitled.vtodo',
            'stream'  => $iCal->exportvCalendar()
        );
    }

}