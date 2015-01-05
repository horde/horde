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
    const MAPI_TASK_OWNER           = 0x801B;
    const MAPI_TASK_STATUS          = 0x8101;
    const MAPI_TASK_PERCENTCOMPLETE = 0x8102;
    const MAPI_TASK_STARTDATE       = 0x8104;
    const MAPI_TASK_DUEDATE         = 0x8105;
    const MAPI_TASK_DATECOMPLETED   = 0x814A;

    const MAPI_TASK_COMMONEND       = 0x8517;
    const MAPI_TASK_COMMONSTART     = 0x81BD;

    const STATUS_NOT_STARTED        = 0x00000000;
    const STATUS_IN_PROGRESS        = 0x00000001;
    const STATUS_COMPLETE           = 0x00000002;
    const STATUS_WAIT               = 0x00000003;
    const STATUS_DEFERRED           = 0x00000004;

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
     * The MIME type of this object's content.
     *
     * @var string
     */
    public $type = 'text/vTodo';

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
     * Allow this object to set any MAPI attributes it needs to know about,
     * ignore any it doesn't care about.
     *
     * @param integer $type  The attribute type descriptor.
     * @param integer $name  The attribute name descriptor.
     */
    public function setMapiAttribute($type, $name, $value)
    {
        switch ($name) {
        case self::MAPI_TASK_OWNER:
            $this->owner = $value;
            break;
        case Horde_Compress_Tnef::IPM_TASK_GUID:
            // Almost positive this is wrong :(
            $this->_guid = Horde_Mapi::getUidFromGoid(bin2hex($value));
            break;
        case Horde_Compress_Tnef::MSG_EDITOR_FORMAT:
            // Map this?
            $this->_msgformat = $value;
            break;
        case Horde_Compress_Tnef::MAPI_TAG_SYNC_BODY:
            //rtfsyncbody
            break;
        case Horde_Compress_Tnef::MAPI_TAG_HTML:
            //htmlbody
            break;
        case self::MAPI_TASK_DUEDATE:
            // Favor COMMONEND
            if (empty($this->_due)) {
                $this->_due = Horde_Mapi::filetimeToUnixtime($value);
            }
            break;
        case self::MAPI_TASK_COMMONEND:
            $this->_due = new Horde_Date(Horde_Mapi::filetimeToUnixtime($value));
            $this->_due = $this->_due->timestamp();
        case self::MAPI_TASK_STARTDATE:
            if (empty($this->start)) {
                $this->start = Horde_Mapi::filetimeToUnixtime($value);
            }
            break;
        case self::MAPI_TASK_COMMONSTART:
            $this->start = new Horde_Date(Horde_Mapi::filetimeToUnixtime($value));
            $this->start = $this->start->timestamp();
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
            case self::STATUS_DEFERRED: // ??
                $this->_percentComplete = 0;
                $this->status = 'NEEDS-ACTION';
                break;
            case self::STATUS_IN_PROGRESS:
                $this->status = 'IN-PROGRESS';
                break;
            case self::STATUS_COMPLETE:
                $this->status = 'COMPLETED';
                $this->_percentComplete = 1;
                break;
            // Body properties. I still can't figure this out.
            // They don't actually seem to be set here, even though there
            // is an explicit property for them, but rather in the enclosing
            // TNEF file. Maybe this depends on the settings/version of the
            // Outlook client that is creating the Task? For now, we will
            // have to do our best to get something to place in the body,
            // regardless of where it comes from.
            case Horde_Compress_Tnef::MAPI_TAG_BODY:
                // plaintext?
                $this->_bodyPlain = $value;
                break;
            case Horde_Compress_Tnef::MAPI_TAG_HTML:
                // html
                $this->_bodyHtml = $value;
                break;
            case Horde_Compress_Tnef::MAPI_TAG_RTF_COMPRESSED:
                $this->_rtfCompressed = $value;
                break;
            case Horde_Compress_Tnef::MAPI_TAG_SYNC_BODY:
                $this->_inSync = $value;
                break;
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
        $vtodo = Horde_Icalendar::newComponent('vtodo', $iCal);

        $vtodo->setAttribute('UID', $this->_guid);

        if ($this->_due) {
            $vtodo->setAttribute('DUE', $this->_due);
        }
        if ($this->start) {
            $vtodo->setAttribute('DTSTART', $this->start);
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
            $vtodo->setAttribute('DESCRIPTION', $this->bodyPlain);
        } elseif ($this->_bodyHtml) {
            $vtodo->setAttribute(Horde_Text_Filter::filter($this->bodyHtml, 'html2text'));
        } elseif ($this->_rtfCompressed) {
            // @todo Decompress and parse using Horde_Mime_Viewer_Rtf?
        } else {
            $files = $this->_options['parent']->getFiles();
            foreach ($files as $file) {
                if ($file instanceof Horde_Compress_Tnef_Rtf) {
                    $vtodo->setAttribute('DESCRIPTION', $file->toPlain());
                }
            }
        }
        $iCal->addComponent($vtodo);

        return array(
            'type'    => 'text',
            'subtype' => 'vTodo',
            'name'    => 'Untitled.vtodo',
            'stream'  => $iCal->exportvCalendar()
        );
    }

}