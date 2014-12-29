<?php
/**
 * The Horde_Compress_Tnef class allows MS-TNEF data to be displayed.
 *
 * The TNEF rendering is based on code by:
 *   Graham Norbury <gnorbury@bondcar.com>
 * Original design by:
 *   Thomas Boll <tb@boll.ch>, Mark Simpson <damned@world.std.com>
 *
 * Copyright 2002-2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @author   Michael Slusarz <slusarz@horde.org>
 * @author   Michael J Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Compress
 */
class Horde_Compress_Tnef extends Horde_Compress_Base
{
    const SIGNATURE                         = 0x223e9f78;
    const LVL_MESSAGE                       = 0x01;
    const LVL_ATTACHMENT                    = 0x02;

    // @deprecated Now lives in Horde_Compress_Tnef_Rtf::
    const RTF_COMPRESSED                    = 0x75465a4c;
    const RTF_UNCOMPRESSED                  = 0x414c454d;

    const ASUBJECT                          = 0x88004;
    const ADATESENT                         = 0x38005;
    const ADATERECEIVED                     = 0x38006;

    const AMCLASS                           = 0x78008;
    const ATTACHDATA                        = 0x6800f;
    const AFILENAME                         = 0x18010;
    const ATTACHMETAFILE                    = 0x68011;
    const ATTACHCREATEDATE                  = 0x38012;

    const ARENDDATA                         = 0x69002;
    const AMAPIPROPS                        = 0x69003;
    const AMAPIATTRS                        = 0x69005;
    const OEMCODEPAGE                       = 0x69007;

    const AVERSION                          = 0x89006;

    const ID_REQUEST_RESP                   = 0x40009;
    const ID_FROM                           = 0x8000;
    const ID_DATE_START                     = 0x30006;
    const ID_DATE_END                       = 0x30007;
    const AIDOWNER                          = 0x50008;

    const MAPI_NULL                         = 0x0001;
    const MAPI_SHORT                        = 0x0002;
    const MAPI_INT                          = 0x0003;
    const MAPI_FLOAT                        = 0x0004;
    const MAPI_DOUBLE                       = 0x0005;
    const MAPI_CURRENCY                     = 0x0006;
    const MAPI_APPTIME                      = 0x0007;
    const MAPI_ERROR                        = 0x000a;
    const MAPI_BOOLEAN                      = 0x000b;
    const MAPI_OBJECT                       = 0x000d;
    const MAPI_INT8BYTE                     = 0x0014;
    const MAPI_STRING                       = 0x001e;
    const MAPI_UNICODE_STRING               = 0x001f;
    const MAPI_SYSTIME                      = 0x0040;
    const MAPI_CLSID                        = 0x0048;
    const MAPI_BINARY                       = 0x0102;

    // @todo Horde 6 - move constants to the appropriate Tnef subclass.
    // MAPI START and END should always be also set in ID_DATE_START and
    // ID_DATE_END so no need to translate them?
    const MAPI_MEETING_REQUEST_TYPE         = 0x0026;
    const MAPI_MEETING_INITIAL              = 0x00000001;
    const MAPI_MEETING_FULL_UPDATE          = 0x00010000;
    const MAPI_MEETING_INFO                 = 0x00020000;


    const MAPI_SENT_REP_NAME                = 0x0042;
    const MAPI_START_DATE                   = 0x0060;
    const MAPI_END_DATE                     = 0x0061;
    const MAPI_SENT_REP_EMAIL_ADDR          = 0x0065;
    const MAPI_COMPRESSED                   = 0x1009;
    const MAPI_IN_REPLY_TO_ID               = 0x1042;

    const MAPI_MESSAGE_CLASS                = 0x001A;
    const MAPI_TAG_SUBJECT_PREFIX           = 0x003D;
    const MAPI_CONVERSATION_TOPIC           = 0x0070;

    const MAPI_ATTACH_EXTENSION             = 0x3703;
    const MAPI_CREATION_TIME                = 0x3007;
    const MAPI_MODIFICATION_TIME            = 0x3008;
    const MAPI_ATTACH_DATA                  = 0x3701;
    const MAPI_ATTACH_LONG_FILENAME         = 0x3707;
    const MAPI_ATTACH_MIME_TAG              = 0x370E;

    const MAPI_ORIGINAL_CREATORID           = 0x3FF9;
    const MAPI_LAST_MODIFIER_NAME           = 0x3FFA;
    const MAPI_CODEPAGE                     = 0x3FFD;

    const MAPI_TASK_STARTDATE               = 0x8104;
    const MAPI_TASK_DUEDATE                 = 0x8105;

    const MAPI_APPOINTMENT_SEQUENCE         = 0x8201;

    // Do we need this?
    const MAPI_BUSY_STATUS                  = 0x8205;

    const MAPI_APPOINTMENT_LOCATION         = 0x8208;
    const MAPI_APPOINTMENT_URL              = 0x8209;
    const MAPI_APPOINTMENT_START_WHOLE      = 0x820D; // Full datetime of start (FILETIME format)
    const MAPI_APPOINTMENT_END_WHOLE        = 0x820E; // Full datetime of end (FILETIME format)
    const MAPI_APPOINTMENT_DURATION         = 0x8213; // duration in minutes.
    const MAPI_APPOINTMENT_SUBTYPE          = 0x8215; // (Boolean - all day event?)
    const MAPI_APPOINTMENT_RECUR            = 0x8216; // This seems to be a combined property of MAPI_RECURRING, MAPI_RECURRING_TYPE etc...?
    const MAPI_APPOINTMENT_STATE_FLAGS      = 0x8217; // (bitmap for meeting, received, cancelled?)
    const MAPI_RESPONSE_STATUS              = 0x8218;
    const MAPI_RECURRING                    = 0x8223;
    const MAPI_RECURRENCE_TYPE              = 0x8231;

    // tz. Not sure when to use STRUCT vs DEFINITION_RECUR. Possible ok to always use STRUCT?
    const MAPI_TIMEZONE_STRUCT              = 0x8233; // Timezone for recurring mtg?
    const MAPI_TIMEZONE_DESCRIPTION         = 0x8234; // Description for tz_struct?
    const MAPI_START_CLIP_START             = 0x8235; // Start datetime in UTC
    const MAPI_START_CLIP_END               = 0x8236; // End datetime in UTC
    const MAPI_CONFERENCING_TYPE            = 0x8241;
    const MAPI_ORGANIZER_ALIAS              = 0x8243; // Supposed to be organizer email, but it seems to be empty?
    const MAPI_APPOINTMENT_COUNTER_PROPOSAL = 0x8257; // Boolean
    const MAPI_TIMEZONE_START               = 0x825E; // Timezone of start_whole
    const MAPI_TIMEZONE_END                 = 0x825F; // Timezone of end_whole
    const MAPI_TIMEZONE_DEFINITION_RECUR    = 0x8260; // Timezone for use in converting meeting date/time in recurring meeting???
    const MAPI_REMINDER_DELTA               = 0x8501; // Minutes between start of mtg and overdue.
    const MAPI_SIGNAL_TIME                  = 0x8502; // Initial alarm time.
    const MAPI_REMINDER_SIGNAL_TIME         = 0x8560; // Time that item becomes overdue.
    const MAPI_ENTRY_UID                    = 0x0003; // GOID??
    const MAPI_MEETING_TYPE                 = 0x0026;

    const MSG_EDITOR_FORMAT                 = 0x5909;
    const MSG_EDITOR_FORMAT_UNKNOWN         = 0;
    const MSG_EDITOR_FORMAT_PLAIN           = 1;
    const MSG_EDITOR_FORMAT_HTML            = 2;
    const MSG_EDITOR_FORMAT_RTF             = 3;

    const MAPI_NAMED_TYPE_ID                = 0x0000;
    const MAPI_NAMED_TYPE_STRING            = 0x0001;
    const MAPI_MV_FLAG                      = 0x1000;

    const IPM_MEETING_REQUEST               = 'IPM.Microsoft Schedule.MtgReq';
    const IPM_MEETING_RESPONSE_POS          = 'IPM.Microsoft Schedule.MtgRespP';
    const IPM_MEETING_RESPONSE_NEG          = 'IPM.Microsoft Schedule.MtgRespN';
    const IPM_MEETING_RESPONSE_TENT         = 'IPM.Microsoft Schedule.MtgRespA';
    const IPM_MEETING_REQUEST_CANCELLED     = 'IPM.Microsoft Schedule.MtgCncl';
    const IPM_TASK_REQUEST                  = 'IPM.TaskRequest';

    const IPM_TASK_GUID                     = 0x00008519;

    const MAPI_TAG_SYNC_BODY                = 0x1008;
    const MAPI_TAG_HTML                     = 0x1013;
    const MAPI_TAG_RTF_COMPRESSED           = 0x1009;

    const RECUR_DAILY                       = 0x200A;
    const RECUR_WEEKLY                      = 0x200B;
    const RECUR_MONTHLY                     = 0x200C;
    const RECUR_YEARLY                      = 0x200D;

    const PATTERN_DAY                       = 0x0000;
    const PATTERN_WEEK                      = 0x0001;
    const PATTERN_MONTH                     = 0x0002;
    const PATTERN_MONTH_END                 = 0x0004;
    const PATTERN_MONTH_NTH                 = 0x0003;

    const RECUR_END_DATE                    = 0x00002021;
    const RECUR_END_N                       = 0x00002022;


    /**
     */
    public $canDecompress = true;

    /**
     * Collection of files contained in the TNEF data.
     *
     * @var array of Horde_Compress_Tnef_Object classes.
     */
    protected $_files = array();

    /**
     * Collection of embedded TNEF attachments within the outer TNEF file.
     *
     * @var array of Horde_Compress_Tnef objects.
     */
    protected $_attachments = array();

    /**
     *
     * @var Horde_Compress_Tnef_MessageData
     */
    protected $_msgInfo;

    /**
     * The TNEF object currently being decoded.
     *
     * @var Horde_Compress_Tnef_Object
     */
    protected $_currentObject;

    /**
     * Decompress the TNEF data. For BC reasons we can only return a numerically
     * indexed array of object data. For more detailed information, use
     * self::getFiles(), self::getAttachements(), and self::getMsgInfo().
     *
     * @todo Refactor return data for Horde 6.
     * @return array  The decompressed data.
     * @throws Horde_Compress_Exception
     */
    public function decompress($data, array $params = array())
    {
        if ($this->_geti($data, 32) == self::SIGNATURE) {
            $this->_logger->debug(sprintf(
                'TNEF: Signature: 0x%08X Key: 0x%04X',
                self::SIGNATURE,
                $this->_geti($data, 16))
            );
            $out = array();
            $this->_msgInfo = new Horde_Compress_Tnef_MessageData($this->_logger);
            while (strlen($data) > 0) {
                switch ($this->_geti($data, 8)) {
                case self::LVL_MESSAGE:
                    $this->_decodeAttribute($data);
                    break;

                case self::LVL_ATTACHMENT:
                    $this->_decodeAttribute($data);
                    break;
                }
            }
        }

        // Add the files. @todo the embedded attachments.
        foreach ($this->_files as $object) {
            $out[] = $object->toArray();
        }

        return $out;
    }

    /**
     * Return the collection of files in the TNEF data.
     *
     * @return array  @see self::$_files
     */
    public function getFiles()
    {
        return $this->_files;
    }

    /**
     * Return the collection of embedded attachments.
     *
     * @return array @see self::$_attachments
     */
    public function getAttachments()
    {
        return $this->_attachments;
    }

    /**
     * Return the message information data.
     *
     * @return array @see self::$_msgInfo
     */
    public function getMsgInfo()
    {
        return $this->_msgInfo;
    }

    /**
     * TODO
     *
     * @param string $data             The data string.
     * @param array &$attachment_data  TODO
     */
    protected function _extractMapiAttributes($data)
    {
        // Number of attributes.
        $number = $this->_geti($data, 32);

        while ((strlen($data) > 0) && $number--) {
            $have_mval = false;
            $num_mval = 1;
            $value = null;
            $attr_type = $this->_geti($data, 16);
            $attr_name = $this->_geti($data, 16);

            // Multivalue attributes.
            if (($attr_type & self::MAPI_MV_FLAG) != 0) {
                $this->_logger->debug('TNEF: Multivalue attribute!');
                $have_mval = true;
                $attr_type = $attr_type & ~self::MAPI_MV_FLAG;
            }

            // Named attributes.
            if (($attr_name >= 0x8000) && ($attr_name < 0xFFFE)) {
                // GUID?
                $this->_getx($data, 16);
                $named_type = $this->_geti($data, 32);

                switch ($named_type) {
                case self::MAPI_NAMED_TYPE_ID:
                    $attr_name =$this->_geti($data, 32);
                    $this->_logger->debug(sprintf(
                        'TNEF: Named Id: 0x%04X', $attr_name)
                    );
                    break;

                case self::MAPI_NAMED_TYPE_STRING:
                    $attr_name = 0x9999;
                    $id_len = $this->_geti($data, 32);
                    $data_len = $id_len + ((4 - ($id_len % 4)) % 4);
                    $this->_logger->debug(sprintf(
                        'TNEF: Named Id: %s', substr($this->_getx($data, $data_len)))
                    );
                    break;

                default:
                    $this->_logger->notice(sprintf(
                        'TNEF: Unknown Named Type: 0x%04X.', $named_type));
                }
            }

            if ($have_mval) {
                $num_mval = $this->_geti($data, 32);
                $this->_logger->debug(sprintf(
                    'TNEF: Number of multivalues: %s', $num_mval));
            }

            switch ($attr_type) {
            case self::MAPI_NULL:
                break;

            case self::MAPI_SHORT:
                $value = $this->_geti($data, 16);
                break;

            case self::MAPI_INT:
            case self::MAPI_BOOLEAN:
                for ($i = 0; $i < $num_mval; $i++) {
                    $value = $this->_geti($data, 32);
                }
                break;

            case self::MAPI_FLOAT:
            case self::MAPI_ERROR:
                $value = $this->_getx($data, 4);
                break;

            case self::MAPI_DOUBLE:
            case self::MAPI_APPTIME:
            case self::MAPI_CURRENCY:
            case self::MAPI_INT8BYTE:
            case self::MAPI_SYSTIME:
                $value = $this->_getx($data, 8);
                break;

            case self::MAPI_CLSID:
                $this->_logger->debug('TNEF: CLSID??');
                break;

            case self::MAPI_STRING:
            case self::MAPI_UNICODE_STRING:
            case self::MAPI_BINARY:
            case self::MAPI_OBJECT:
                $num_vals = ($have_mval) ? $num_mval : $this->_geti($data, 32);
                for ($i = 0; $i < $num_vals; $i++) {
                    $length = $this->_geti($data, 32);
                    /* Pad to next 4 byte boundary. */
                    $datalen = $length + ((4 - ($length % 4)) % 4);
                    if ($attr_type == self::MAPI_STRING) {
                        --$length;
                    }

                    /* Read and truncate to length. */
                    $value = substr($this->_getx($data, $datalen), 0, $length);
                }
                break;
            default:
                $this->_logger->notice('TNEF: Unknown attribute type!');
            }

            // @todo Utility method to make this log more readable.
            $this->_logger->debug(sprintf('TNEF: Attribute: 0x%X Type:', $attr_name, $attr_type));
            switch ($attr_name) {
            case self::MAPI_ATTACH_DATA:
                $this->_logger->debug('TNEF: Found nested attachment. Parsing.');
                // ?
                $this->_getx($value, 16);

                $att = &new Horde_Compress_Tnef($this->_logger);
                $att->decompress($value);
                $this->attachments[] = $att;
                $this->_logger->debug('TNEF: Completed nested attachment parsing.');
                break;

            // case self::MAPI_TAG_RTF_COMPRESSED:
            //     $this->_logger->debug('TNEF: Found compressed RTF text.');
            //     $this->_files[] = &new Horde_Compress_Tnef_FileRTF($this->_logger, $value);
            //     break;

            default:
                $this->_msgInfo->setMapiAttribute($attr_type, $attr_name, $value);
                if ($this->_currentObject) {
                    $this->_currentObject->setMapiAttribute($attr_type, $attr_name, $value);
                }
            }
        }
    }

    /**
     * TODO
     *
     * @param string &$data  The data string.
     */
    protected function _decodeAttribute(&$data)
    {
        $attribute = $this->_geti($data, 32);
        $length = $this->_geti($data, 32);
        $value = $this->_getx($data, $length);
        $this->_geti($data, 16);

        $this->_logger->debug(sprintf('TNEF: Decoding message attribute: 0x%X', $attribute));

        switch ($attribute) {
        case self::ARENDDATA:
            $this->_logger->debug('Creating new attachment.');
            $this->_currentObject = &new Horde_Compress_Tnef_File($this->_logger);
            $this->_files[] = &$this->_currentObject;
            break;

        case self::AMCLASS:
            // Start of a new message.
            $message_class = trim($value);
            $this->_logger->debug(sprintf('TNEF: Message class: %s', $message_class));
            switch ($message_class) {
            case self::IPM_MEETING_REQUEST:
                $this->_currentObject = new Horde_Compress_Tnef_Icalendar($this->_logger);
                $this->_currentObject->method = 'REQUEST';
                $this->_files[] = $this->_currentObject;
                break;
            case self::IPM_MEETING_REQUEST_CANCELLED:
                $this->_currentObject = new Horde_Compress_Tnef_Icalendar($this->_logger);
                $this->_currentObject->method = 'CANCEL';
                $this->_files[] = &$this->_currentObject;
                break;
            // case self::IPM_TASK_REQUEST:
            //     $this->_currentObject = &new Horde_Compress_Tnef_vTodo($this->_logger);
            //     $this->_files[] = &$this->_currentObject;
            //     break;
            }
            break;

        case self::AMAPIATTRS:
            $this->_logger->debug('TNEF: Extracting MAPI attributes.');
            $this->_extractMapiAttributes($value);
            break;

        case self::AMAPIPROPS:
            $this->_logger->debug('TNEF: Extracting MAPI properties.');
            $this->_extractMapiAttributes($value);
            break;

        default:
            $this->_msgInfo->setTnefAttribute($attribute, $value, $length);
            if ($this->_currentObject) {
                $this->_currentObject->setTnefAttribute($attribute, $value, $length);
            }
        }
    }

    /**
     * Pop specified number of bytes from the buffer.
     *
     * @param string &$data  The data string.
     * @param integer $bytes  How many bytes to retrieve.
     *
     * @return @todo these also need to exist in the objects. Need to
     *         refactor this away by adding a data/stream object
     *         with getx/geti methods with the data hanled internally.
     */
    protected function _getx(&$data, $bytes)
    {
        $value = null;

        if (strlen($data) >= $bytes) {
            $value = substr($data, 0, $bytes);
            $data = substr_replace($data, '', 0, $bytes);
        }

        return $value;
    }

    /**
     * Pop specified number of bits from the buffer
     *
     * @param string &$data  The data string.
     * @param integer $bits  How many bits to retrieve.
     *
     * @return TODO
     */
    protected function _geti(&$data, $bits)
    {
        $bytes = $bits / 8;
        $value = null;

        if (strlen($data) >= $bytes) {
            $value = ord($data[0]);
            if ($bytes >= 2) {
                $value += (ord($data[1]) << 8);
            }
            if ($bytes >= 4) {
                $value += (ord($data[2]) << 16) + (ord($data[3]) << 24);
            }
            $data = substr_replace($data, '', 0, $bytes);
        }

        return $value;
    }

}
