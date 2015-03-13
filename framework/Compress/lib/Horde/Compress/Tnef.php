<?php
/**
 * The Horde_Compress_Tnef class allows MS-TNEF data to be displayed.
 *
 * The TNEF rendering is based on code by:
 *   Graham Norbury <gnorbury@bondcar.com>
 * Original design by:
 *   Thomas Boll <tb@boll.ch>, Mark Simpson <damned@world.std.com>
 *
 * Copyright 2002-2015 Horde LLC (http://www.horde.org/)
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
    const PSETID_MEETING                    = '{6ED8DA90-450B-101B-98DA-00AA003F1305}';
    const PSETID_APPOINTMENT                = '{00062002-0000-0000-C000-000000000046}';
    const PSETID_COMMON                     = '{00062008-0000-0000-C000-000000000046}';
    const PSETID_PUBLIC_STRINGS             = '{00020329-0000-0000-C000-000000000046}';
    const PSETID_NOTE                       = '{0006200E-0000-0000-C000-000000000046}';
    const PSETID_TASK                       = '{00062003-0000-0000-C000-000000000046}';
    const PSETID_MAPI                       = '{00020328-0000-0000-C000-000000000046}';

    const SIGNATURE                         = 0x223e9f78;
    const LVL_MESSAGE                       = 0x01;
    const LVL_ATTACHMENT                    = 0x02;

    // @deprecated Now lives in Horde_Compress_Tnef_Rtf::
    const RTF_COMPRESSED                    = 0x75465a4c;
    const RTF_UNCOMPRESSED                  = 0x414c454d;

    // TNEF specific properties (includes the type).
    const AOWNER                            = 0x60000;
    const ASENTFOR                          = 0x60001;
    const AORIGINALMCLASS                   = 0x70006;
    const ASUBJECT                          = 0x18004;
    const ADATESENT                         = 0x38005;
    const ADATERECEIVED                     = 0x38006;
    const AFROM                             = 0x08000;
    const ASTATUS                           = 0x68007;
    const AMCLASS                           = 0x78008;
    const AMESSAGEID                        = 0x18009;
    const APARENTID                         = 0x1800a;
    const ACONVERSATIONID                   = 0x1800b;
    const ABODY                             = 0x2800c;
    const APRIORITY                         = 0x4800d;
    const ATTACHDATA                        = 0x6800f;
    const AFILENAME                         = 0x18010;
    const ATTACHMETAFILE                    = 0x68011;
    const ATTACHCREATEDATE                  = 0x38012;
    const ADATEMODIFIED                     = 0x38020;
    // idAttachRendData
    const ARENDDATA                         = 0x69002;
    const AMAPIPROPS                        = 0x69003;
    const ARECIPIENTTABLE                   = 0x69004;
    const AMAPIATTRS                        = 0x69005;

    // @deprecated constants to be removed in H6
    const OEMCODEPAGE                       = 0x69007;
    const AVERSION                          = 0x89006;
    const ID_REQUEST_RESP                   = 0x40009;
    const ID_FROM                           = 0x8000;
    const AIDOWNER                          = 0x50008;
    const ID_DATE_START                     = 0x30006;
    const ID_DATE_END                       = 0x30007;

    // All valid MAPI data types.
    // @todo These should all be MAPI_TYPE_*
    const MAPI_TYPE_UNSPECIFIED             = 0x0000;
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

    // Constants for possible value of MAPI_MEETING_REQUEST_TYPE
    const MAPI_MEETING_INITIAL              = 0x00000001;
    const MAPI_MEETING_FULL_UPDATE          = 0x100010000;
    const MAPI_MEETING_INFO                 = 0x00020000;

    // pidTag* properties. These should all be renamed in H6 to include pidTag
    // in the name to make this clear.
    const MAPI_MESSAGE_CLASS                = 0x001A;
    const MAPI_TAG_SUBJECT_PREFIX           = 0x003D;
    const MAPI_CONVERSATION_TOPIC           = 0x0070;

    // pidTagSentRepresentingName
    const MAPI_SENT_REP_NAME                = 0x0042;

    // pidTagSentRepresentingEmail
    const MAPI_SENT_REP_EMAIL_ADDR          = 0x0065;

    // pidTagDisplayTo
    const MAPI_DISPLAY_TO                   = 0x0e04;

    // pidTagSentRepresentingSMTPAddress
    const MAPI_SENT_REP_SMTP_ADDR           = 0x5d02;

    // pidTagInReplyTo
    const MAPI_IN_REPLY_TO_ID               = 0x1042;

    const MAPI_CREATION_TIME                = 0x3007;
    const MAPI_MODIFICATION_TIME            = 0x3008;
    const MAPI_ATTACH_DATA                  = 0x3701;
    const MAPI_ATTACH_EXTENSION             = 0x3703;
    const MAPI_ATTACH_LONG_FILENAME         = 0x3707;
    const MAPI_ATTACH_MIME_TAG              = 0x370E;
    const MAPI_ORIGINAL_CREATORID           = 0x3FF9;
    const MAPI_LAST_MODIFIER_NAME           = 0x3FFA;
    const MAPI_CODEPAGE                     = 0x3FFD;
    const MAPI_SENDER_SMTP                  = 0x5D01;

    // Appointment related.
    // This is pidTagStartDate and contains the value of PidLidAppointmentStartWhole
    const MAPI_START_DATE                   = 0x0060; // pidTag
    const MAPI_END_DATE                     = 0x0061; // pidTag
    const MAPI_APPOINTMENT_SEQUENCE         = 0x8201; // pidLid
    const MAPI_BUSY_STATUS                  = 0x8205; // pidLid
    const MAPI_MEETING_REQUEST_TYPE         = 0x0026; // pidLid
    const MAPI_RESPONSE_REQUESTED           = 0x0063; // pidTag
    const MAPI_APPOINTMENT_LOCATION         = 0x8208; // pidLid
    const MAPI_APPOINTMENT_URL              = 0x8209; // pidLid
    const MAPI_APPOINTMENT_START_WHOLE      = 0x820D; // Full datetime of start (FILETIME format)
    const MAPI_APPOINTMENT_END_WHOLE        = 0x820E; // Full datetime of end (FILETIME format)
    const MAPI_APPOINTMENT_DURATION         = 0x8213; // pidLid - duration in minutes.
    const MAPI_APPOINTMENT_SUBTYPE          = 0x8215; // (Boolean - all day event?)
    const MAPI_APPOINTMENT_RECUR            = 0x8216; // This seems to be a combined property of MAPI_RECURRING, MAPI_RECURRING_TYPE etc...?
    const MAPI_APPOINTMENT_STATE_FLAGS      = 0x8217; // (bitmap for meeting, received, cancelled?)
    const MAPI_RESPONSE_STATUS              = 0x8218;
    const MAPI_RECURRING                    = 0x8223;
    const MAPI_RECURRENCE_TYPE              = 0x8231;
    const MAPI_ALL_ATTENDEES                = 0x8238; // ALL attendees, required/optional and non-sendable.
    const MAPI_TO_ATTENDEES                 = 0x823B; // All "sendable" attendees that are REQUIRED.

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
    const MAPI_ENTRY_UID                    = 0x0003; // pidLidGlobalObjectId, PSETID_MEETING
    const MAPI_ENTRY_CLEANID                = 0x0023; // pidLidCleanGlobalObjectId, PSETID_MEETING
    const MAPI_MEETING_TYPE                 = 0x0026; // pidLidMeetingType, PSETID_MEETING

    const MSG_EDITOR_FORMAT                 = 0x5909;
    const MSG_EDITOR_FORMAT_UNKNOWN         = 0;
    const MSG_EDITOR_FORMAT_PLAIN           = 1;
    const MSG_EDITOR_FORMAT_HTML            = 2;
    const MSG_EDITOR_FORMAT_RTF             = 3;

    const MAPI_NAMED_TYPE_ID                = 0x00;
    const MAPI_NAMED_TYPE_STRING            = 0x01;
    const MAPI_NAMED_TYPE_NONE              = 0xff;

    const MAPI_MV_FLAG                      = 0x1000;

    const IPM_MEETING_REQUEST               = 'IPM.Microsoft Schedule.MtgReq';
    const IPM_MEETING_RESPONSE_POS          = 'IPM.Microsoft Schedule.MtgRespP';
    const IPM_MEETING_RESPONSE_NEG          = 'IPM.Microsoft Schedule.MtgRespN';
    const IPM_MEETING_RESPONSE_TENT         = 'IPM.Microsoft Schedule.MtgRespA';
    const IPM_MEETING_REQUEST_CANCELLED     = 'IPM.Microsoft Schedule.MtgCncl';

    const MAPI_MEETING_RESPONSE_POS         = 'IPM.Schedule.Meeting.Resp.Pos';
    const MAPI_MEETING_RESPONSE_NEG         = 'IPM.Schedule.Meeting.Resp.Neg';
    const MAPI_MEETING_RESPONSE_TENT        = 'IPM.Schedule.Meeting.Resp.Tent';

    const IPM_TASK_REQUEST                  = 'IPM.TaskRequest';

    const IPM_TASK_GUID                     = 0x8519; // pidLidTaskGlobalId, PSETID_Common

    const MAPI_TAG_BODY                     = 0x1000;
    const MAPI_NATIVE_BODY                  = 0x1016;
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
     * @var array of Horde_Compress_Tnef_Object objects.
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

            // Version
            $this->_geti($data, 8); // lvl_message
            $this->_geti($data, 32); // idTnefVersion
            $this->_getx($data, $this->_geti($data, 32));
            $this->_geti($data, 16); //checksum

            // Codepage
            $this->_geti($data, 8);
            $this->_geti($data, 32); // idCodepage
            $this->_getx($data, $this->_geti($data, 32));
            $this->_geti($data, 16); //checksum

            $out = array();
            $this->_msgInfo = new Horde_Compress_Tnef_MessageData($this->_logger);
            while (strlen($data) > 0) {
                switch ($this->_geti($data, 8)) {
                case self::LVL_MESSAGE:
                    $this->_logger->debug('DECODING LVL_MESSAGE property.');
                    $this->_decodeMessageProperty($data);
                    break;

                case self::LVL_ATTACHMENT:
                    $this->_logger->debug('DECODING LVL_ATTACHMENT property.');
                    $this->_decodeAttachment($data);
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
     * Sets the current object being decompressed.
     *
     * @param Horde_Compress_Tnef_Object $object
     */
    public function setCurrentObject(Horde_Compress_Tnef_Object $object)
    {
        $this->_currentObject = $object;
    }

    /**
     * Extract a set of encapsulated MAPI properties. Normally either embedded
     * in an attachment structure, or an idMessageProperty structure.
     *
     * @param string $data             The data string.
     * @param array &$attachment_data  TODO
     */
    protected function _extractMapiAttributes($data)
    {
        // Number of attributes.
        $number = $this->_geti($data, 32);
        $this->_logger->debug(sprintf('TNEF: Extracting %d MAPI attributes.', $number));
        while ((strlen($data) > 0) && $number--) {
            $have_mval = false;
            $num_mval = 1;
            $value = null;
            $attr_type = $this->_geti($data, 16);
            $attr_name = $this->_geti($data, 16);
            $namespace = false;

            // Multivalue attributes.
            if (($attr_type & self::MAPI_MV_FLAG) != 0) {
                $have_mval = true;
                $attr_type = $attr_type & ~self::MAPI_MV_FLAG;
                $this->_logger->debug(sprintf(
                    'TNEF: Multivalue attribute of type: 0x%04X',
                    $attr_type)
                );
            }

            // Named attributes.
            if (($attr_name >= 0x8000) && ($attr_name < 0xFFFE)) {
                $namespace = $this->_toNamespaceGUID($this->_getx($data, 16));

                // The type of named property, an ID or STRING.
                $named_type = $this->_geti($data, 32);
                switch ($named_type) {
                case self::MAPI_NAMED_TYPE_ID:
                    $pid = $attr_name;
                    $attr_name = $this->_geti($data, 32);
                    $msg = sprintf('TNEF: pid: 0x%X type: 0x%X Named Id: %s 0x%04X', $pid, $attr_type, $namespace, $attr_name);
                    $this->_logger->debug($msg);
                    break;

                case self::MAPI_NAMED_TYPE_STRING:
                    // @todo. We haven't needed data from any string named id
                    // yet, but might be able to just assign the name to
                    // $attr_name and pass it down to _currentObject for now.
                    // For H6, look at using some lightweight object to transport
                    // the name/value to the various objects.
                    $attr_name = 0x9999;
                    $id_len = $this->_geti($data, 32);
                    $data_len = $id_len + ((4 - ($id_len % 4)) % 4);
                    $name = Horde_String::substr($this->_getx($data, $data_len), 0, $id_len);
                    $name = trim(Horde_String::convertCharset($name, 'UTF-16LE', 'UTF-8'));
                    $this->_logger->debug(sprintf('TNEF: Named String Id: %s', $name));
                    break;

                case self::MAPI_NAMED_TYPE_NONE:
                    continue 2;
                    break;

                default:
                    $msg = sprintf('TNEF: Unknown NAMED type: pid: 0x%X type: 0x%X Named TYPE: %s 0x%04X', $pid, $attr_type, $namespace, $named_type);
                    $this->_logger->notice($msg);
                    continue 2;
                }
            }

            if ($have_mval) {
                $num_mval = $this->_geti($data, 32);
                $this->_logger->debug(sprintf(
                    'TNEF: Number of multivalues: %s', $num_mval));
            }

            switch ($attr_type) {
            case self::MAPI_NULL:
            case self::MAPI_TYPE_UNSPECIFIED:
                break;

            case self::MAPI_SHORT:
                $value = $this->_geti($data, 16);
                // Padding. See MS-OXTNEF 2.1.3.4
                // Must always pad to a multiple of 4 bytes.
                $this->_geti($data, 16);
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
                $this->_getx($data, 16);
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

                    /* Read and truncate to length. */
                    $value = substr($this->_getx($data, $datalen), 0, $length);
                }

                switch ($attr_type) {
                case self::MAPI_UNICODE_STRING:
                    // MAPI Unicode is UTF-16LE; convert to UTF-8
                    $value = Horde_String::convertCharset(
                        $value,
                        'UTF-16LE',
                        'UTF-8'
                    );
                    break;
                }

                switch ($attr_type) {
                case self::MAPI_STRING:
                case self::MAPI_UNICODE_STRING:
                    // Strings are null-terminated.
                    $value = substr($value, 0, -1);
                    break;
                }

                break;
            default:
                $msg = sprintf(
                    'TNEF: Unknown attribute type, "0x%X"',
                    $attr_type);
                throw new Horde_Compress_Exception($msg);
                $this->_logger->notice($msg);
            }

            // @todo Utility method to make this log more readable.
            $this->_logger->debug(sprintf('TNEF: Attribute: 0x%X Type: 0x%X', $attr_name, $attr_type));
            switch ($attr_name) {
            case self::MAPI_TAG_RTF_COMPRESSED:
                $this->_logger->debug('TNEF: Found compressed RTF text.');
                $rtf =  new Horde_Compress_Tnef_Rtf($this->_logger, $value);
                $this->_files[] = $rtf;
                // Give the currentObject a chance to do something with the RTF
                // body. This is used, e.g., in meeting requests to populate
                // the description field.
                if ($this->_currentObject) {
                    try {
                        $this->_currentObject->setMapiAttribute($attr_type, $attr_name, $rtf->toPlain());
                    } catch (Horde_Compress_Exception $e) {
                        $this->_logger->err(sprintf('TNEF: Unable to set attribute: %s', $e->getMessage()));
                    }
                }
                break;
            case self::MAPI_ATTACH_DATA:
                $this->_logger->debug('TNEF: Found nested MAPI object. Parsing.');
                $this->_getx($value, 16);
                $att = new Horde_Compress_Tnef($this->_logger);
                $att->setCurrentObject($this->_currentObject);
                $att->decompress($value);
                $this->_attachments[] = $att;
                $this->_logger->debug('TNEF: Completed nested attachment parsing.');
                break;
            default:
                try {
                    $this->_msgInfo->setMapiAttribute($attr_type, $attr_name, $value);
                    if ($this->_currentObject) {
                        $this->_currentObject->setMapiAttribute($attr_type, $attr_name, $value, $namespace);
                    }
                } catch (Horde_Compress_Exception $e) {
                    $this->_logger->err(sprintf('TNEF: Unable to set attribute: %s', $e->getMessage()));
                }
            }
        }
    }

    /**
     * Decodes all LVL_ATTACHMENT data. Attachment data MUST be at the end of
     * TNEF stream. First LVL_ATTACHMENT MUST be ARENDDATA (attAttachRendData).
     *
     * From MS-OXTNEF:
     * ; An attachment is determined/delimited by attAttachRendData, followed by
     * ; other encoded attributes, if any, and ending with attAttachment
     * ; if there are any encoded properties.
     * AttachData = AttachRendData [*AttachAttribute] [AttachProps]
     * AttachRendData = attrLevelAttachment idAttachRendData Length Data Checksum
     * AttachAttribute = attrLevelAttachment idAttachAttr Length Data Checksum
     * AttachProps = attrLevelAttachment idAttachment Length Data Checksum
     *
     * @param  [type] &$data [description]
     * @return [type]        [description]
     */
    protected function _decodeAttachment(&$data)
    {
        $attribute = $this->_geti($data, 32);
        $size = $this->_geti($data, 32);
        $value = $this->_getx($data, $size);
        $this->_geti($data, 16);
        switch ($attribute) {
        case self::ARENDDATA:
            // This delimits the attachment structure. I.e., every attachment
            // MUST begin with idAttachRendData.
            $this->_logger->debug('Creating new attachment.');
            if (!$this->_currentObject instanceof Horde_Compress_Tnef_VTodo) {
                $this->_currentObject = new Horde_Compress_Tnef_File($this->_logger);
                $this->_files[] = $this->_currentObject;
            }
            break;
        case self::AFILENAME:
             // Strip path.
            $value = preg_replace('/.*[\/](.*)$/', '\1', $value);
            $value = str_replace("\0", '', $value);
            $this->_currentObject->setTnefAttribute($attribute, $value, $size);
            break;
        case self::ATTACHDATA:
             // The attachment itself.
            $this->_currentObject->setTnefAttribute($attribute, $value, $size);
            break;
        case self::AMAPIATTRS:
            // idAttachment (Attachment properties)
            $this->_extractMapiAttributes($value);
            break;
        default:
            if (!empty($this->_currentObject)) {
                $this->_currentObject->setTnefAttribute($attribute, $value, $size);
            }
        }
    }

    /**
     * Decodes TNEF attributes.
     *
     * @param  [type] &$data [description]
     * @return [type]        [description]
     */
    protected function _decodeMessageProperty(&$data)
    {
        // This contains the type AND the attribute name. We should only check
        // against the name since this is very confusing  (everything else is
        // checked against just name). Can't change until Horde 6 though since
        // the constants would have to change. Also, the type identifiers are
        // different between MAPI and TNEF. Of course...
        // $type = $this->_geti($data, 16);
        // $attribute = $this->_geti($data, 16);
        $attribute = $this->_geti($data, 32);
        $this->_logger->debug(sprintf('TNEF: Message property 0x%X found.', $attribute));
        $value = false;
        switch ($attribute) {
        case self::AMCLASS:
            // Start of a new message.
            $message_class = trim($this->_decodeAttribute($data));
            $this->_logger->debug(sprintf('TNEF: Message class: %s', $message_class));
            switch ($message_class) {
            case self::IPM_MEETING_REQUEST :
                $this->_currentObject = new Horde_Compress_Tnef_Icalendar($this->_logger);
                $this->_currentObject->setMethod('REQUEST', $message_class);
                $this->_files[] = $this->_currentObject;
                break;
            case self::IPM_MEETING_RESPONSE_TENT:
            case self::IPM_MEETING_RESPONSE_NEG:
            case self::IPM_MEETING_RESPONSE_POS:
                $this->_currentObject = new Horde_Compress_Tnef_Icalendar($this->_logger);
                $this->_currentObject->setMethod('REPLY', $message_class);
                $this->_files[] = $this->_currentObject;
                break;
            case self::IPM_MEETING_REQUEST_CANCELLED:
                $this->_currentObject = new Horde_Compress_Tnef_Icalendar($this->_logger);
                $this->_currentObject->setMethod('CANCEL', $message_class);
                $this->_files[] = $this->_currentObject;
                break;
            case self::IPM_TASK_REQUEST:
                $this->_currentObject = new Horde_Compress_Tnef_VTodo($this->_logger, null, array('parent' => &$this));
                $this->_files[] = $this->_currentObject;
                break;
            default:
                $this->_logger->debug(sprintf('Unknown message class: %s', $message_class));
            }
            break;
        case self::AMAPIPROPS:
            $this->_logger->debug('TNEF: Extracting encapsulated message properties (idMsgProps)');
            $properties = $this->_decodeAttribute($data);
            $this->_extractMapiAttributes($properties);
            break;
        case self::APRIORITY:
        case self::AOWNER:
        case self::ARECIPIENTTABLE:
        case self::ABODY:
        case self::ASTATUS:
        case self::ACONVERSATIONID:
        case self::APARENTID:
        case self::AMESSAGEID:
        case self::ASUBJECT:
        case self::AORIGINALMCLASS:
            $value = $this->_decodeAttribute($data);
            break;
        case self::ADATERECEIVED:
        case self::ADATESENT:
        case self::ADATEMODIFIED:
        case self::ID_DATE_END:
            try {
                $value = new Horde_Date(Horde_Mapi::filetimeToUnixtime($this->_decodeAttribute($data)), 'UTC');
            } catch (Horde_Mapi_Exception $e) {
                throw new Horde_Compress_Exception($e);
            } catch (Horde_Date_Exception $e) {
                throw new Horde_Compress_Exception($e);
            }
            break;
        case self::AFROM:
        case self::ASENTFOR:
            $msgObj = $this->_decodeAttribute($data);
            $display_name = $this->_getx($msgObj, $this->_geti($msgObj, 16));
            $email = $this->_getx($msgObj, $this->_geti($msgObj, 16));
            $value = $email; // @todo - Do we need to pass display name too?
            break;
        default:
            $size = $this->_geti($data, 32);
            $value = $this->_getx($data, $size);
            $this->_geti($data, 16); // Checksum.
        }
        if ($value && $this->_currentObject) {
            $this->_currentObject->setTnefAttribute($attribute, $value, (empty($size) ? strlen($value) : $size));
        }
    }

    /**
     * Decode a single attribute.
     *
     * @param string &$data  The data string.
     */
    protected function _decodeAttribute(&$data)
    {
        $size = $this->_geti($data, 32);
        $value = $this->_getx($data, $size);
        $this->_geti($data, 16); // Checksum.

        return $value;
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

    protected function _toNamespaceGUID($value)
    {
        $guid = unpack("VV/v2v/n4n", $value);
        return sprintf("{%08X-%04X-%04X-%04X-%04X%04X%04X}",$guid['V'], $guid['v1'], $guid['v2'],$guid['n1'],$guid['n2'],$guid['n3'],$guid['n4']);
    }

}
