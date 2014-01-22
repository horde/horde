<?php
/**
 * The Horde_Compress_Tnef class allows MS-TNEF data to be displayed.
 *
 * The TNEF rendering is based on code by:
 *   Graham Norbury <gnorbury@bondcar.com>
 * Original design by:
 *   Thomas Boll <tb@boll.ch>, Mark Simpson <damned@world.std.com>
 *
 * Copyright 2002-2013 Horde LLC (http://www.horde.org/)
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

    const RTF_COMPRESSED                    = 0x75465a4c;
    const RTF_UNCOMPRESSED                  = 0x414c454d;

    const ASUBJECT                          = 0x88004;
    const AMCLASS                           = 0x78008;
    const ATTACHDATA                        = 0x6800f;
    const AFILENAME                         = 0x18010;
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

    // MAPI START and END should always be also set in ID_DATE_START and
    // ID_DATE_END so no need to translate them?
    const MAPI_START_DATE                   = 0x0060;
    const MAPI_END_DATE                     = 0x0061;
    const MAPI_COMPRESSED                   = 0x1009;

    const MAPI_MESSAGE_CLASS                = 0x001A;
    const MAPI_CONVERSATION_TOPIC           = 0x0070;

    const MAPI_ATTACH_DATA                  = 0x3701;
    const MAPI_ATTACH_LONG_FILENAME         = 0x3707;
    const MAPI_ATTACH_MIME_TAG              = 0x370E;

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
    const MAPI_ENTRY_UID                    = 0x0023; // GOID??
    const MAPI_LAST_MODIFIER_NAME           = 0x3FFA;
    const MAPI_MEETING_TYPE                 = 0x0026;

    const MAPI_NAMED_TYPE_ID                = 0x0000;
    const MAPI_NAMED_TYPE_STRING            = 0x0001;
    const MAPI_MV_FLAG                      = 0x1000;

    const IPM_MEETING_REQUEST           = 'IPM.Microsoft Schedule.MtgReq';
    const IPM_MEETING_RESPONSE_POS      = 'IPM.Microsoft Schedule.MtgRespP';
    const IPM_MEETING_RESPONSE_NEG      = 'IPM.Microsoft Schedule.MtgRespN';
    const IPM_MEETING_RESPONSE_TENT     = 'IPM.Microsoft Schedule.MtgRespA';
    const IPM_MEETING_REQUEST_CANCELLED = 'IPM.Microsoft Schedule.MtgCncl';

    const RECUR_DAILY = 0x200A;
    const RECUR_WEEKLY = 0x200B;
    const RECUR_MONTHLY = 0x200C;
    const RECUR_YEARLY = 0x200D;

    const PATTERN_DAY  = 0x0000;
    const PATTERN_WEEK = 0x0001;
    const PATTERN_MONTH = 0x0002;
    const PATTERN_MONTH_END = 0x0004;
    const PATTERN_MONTH_NTH = 0x0003;

    const RECUR_END_DATE = 0x00002021;
    const RECUR_END_N = 0x00002022;


    /**
     */
    public $canDecompress = true;

    /**
     * Temporary cache of data needed to build an iTip from the encoded
     * MAPI appointment properties.
     *
     * @var array
     */
    protected $_iTip = array();

    protected $_conversation_topic;
    protected $_lastModifier;

    /**
     * @return array  The decompressed data.
     * @throws Horde_Compress_Exception
     */
    public function decompress($data, array $params = array())
    {
        $out = array();
        $message = array();

        if ($this->_geti($data, 32) == self::SIGNATURE) {
            // LegacyKey value - not used.
            $this->_geti($data, 16);

            while (strlen($data) > 0) {
                switch ($this->_geti($data, 8)) {
                case self::LVL_MESSAGE:
                    $this->_decodeMessageProperty($data);
                    break;

                case self::LVL_ATTACHMENT:
                    $this->_decodeAttachment($data, $out);
                    break;
                }
            }
            $this->_checkiTip($out);
        }

        return array_reverse($out);
    }

    /**
     * Pop specified number of bytes from the buffer.
     *
     * @param string &$data  The data string.
     * @param integer $bytes  How many bytes to retrieve.
     *
     * @return TODO
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

    /**
     * TODO
     *
     * @param string &$data      The data string.
     * @param string $attribute  The attribute to decode
     *
     * @return @todo
     */
    protected function _decodeAttribute(&$data, $attribute)
    {
        $fetched = $this->_getx($data, $this->_geti($data, 32));
        // checksum
        $this->_geti($data, 16);
        return $fetched;
    }

    /**
     * TODO
     *
     * @param string $data             The data string.
     * @param array &$attachment_data  TODO
     */
    protected function _extractMapiAttributes($data, &$attachment_data)
    {
        /* Number of attributes. */
        $number = $this->_geti($data, 32);

        while ((strlen($data) > 0) && $number--) {
            $have_mval = false;
            $num_mval = 1;
            $named_id = $value = null;
            $attr_type = $this->_geti($data, 16);
            $attr_name = $this->_geti($data, 16);

            if (($attr_type & self::MAPI_MV_FLAG) != 0) {
                $have_mval = true;
                $attr_type = $attr_type & ~self::MAPI_MV_FLAG;
            }

            if (($attr_name >= 0x8000) && ($attr_name < 0xFFFE)) {
                $this->_getx($data, 16);
                $named_type = $this->_geti($data, 32);

                switch ($named_type) {
                case self::MAPI_NAMED_TYPE_ID:
                    $named_id = $this->_geti($data, 32);
                    $attr_name = $named_id;
                    break;

                case self::MAPI_NAMED_TYPE_STRING:
                    $attr_name = 0x9999;
                    $idlen = $this->_geti($data, 32);
                    $datalen = $idlen + ((4 - ($idlen % 4)) % 4);
                    $named_id = substr($this->_getx($data, $datalen), 0, $idlen);
                    break;
                }
            }

            if ($have_mval) {
                $num_mval = $this->_geti($data, 32);
            }

            switch ($attr_type) {
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
            }

            /* Store any interesting attributes. */
            switch ($attr_name) {
            case self::MAPI_ATTACH_LONG_FILENAME:
                /* Used in preference to AFILENAME value. */
                $attachment_data[0]['name'] = preg_replace('/.*[\/](.*)$/', '\1', $value);
                $attachment_data[0]['name'] = str_replace("\0", '', $attachment_data[0]['name']);
                break;

            case self::MAPI_ATTACH_MIME_TAG:
                /* Is this ever set, and what is format? */
                $attachment_data[0]['type'] = preg_replace('/^(.*)\/.*/', '\1', $value);
                $attachment_data[0]['type'] = str_replace("\0", '', $attachment_data[0]['type']);
                $attachment_data[0]['subtype'] = preg_replace('/.*\/(.*)$/', '\1', $value);
                $attachment_data[0]['subtype'] = str_replace("\0", '', $attachment_data[0]['subtype']);
                break;

            // MAPI properties for meeting requests/responses.
            case self::MAPI_CONVERSATION_TOPIC:
                $this->_conversation_topic = $value;
                break;
            case self::MAPI_APPOINTMENT_LOCATION:
                $attachment_data[0]['location'] = $value;
                break;
            case self::MAPI_APPOINTMENT_URL:
                $attachment_data[0]['url'] = $value;
                break;
            case self::MAPI_APPOINTMENT_START_WHOLE:
                try {
                    $attachment_data[0]['start_utc'] = new Horde_Date(Horde_Mapi::filetimeToUnixtime($value), 'UTC');
                } catch (Horde_Mapi_Exception $e) {
                    throw new Horde_Compress_Exception($e);
                }
                break;
            case self::MAPI_APPOINTMENT_END_WHOLE:
                try {
                    $attachment_data[0]['end_utc'] = new Horde_Date(Horde_Mapi::filetimeToUnixtime($value), 'UTC');
                } catch (Horde_Mapi_Exception $e) {
                    throw new Horde_Compress_Exception($e);
                }
                break;
            case self::MAPI_APPOINTMENT_DURATION:
                $attachment_data[0]['duration'] = $value;
                break;
            case self::MAPI_APPOINTMENT_SUBTYPE:
                $attachment_data[0]['allday'] = $value;
                break;
            case self::MAPI_ORGANIZER_ALIAS:
                $attachment_data[0]['organizer'] = $value;
                break;
            case self::MAPI_LAST_MODIFIER_NAME:
                $this->_lastModifier = $value;
                break;
            case self::MAPI_ENTRY_UID:
                $attachment_data[0]['uid'] = Horde_Mapi::getUidFromGoid(bin2hex($value));
                break;
            case self::MAPI_APPOINTMENT_RECUR:
                if (empty($attachment_data[0]['recurrence'])) {
                    $attachment_data[0]['recurrence'] = array();
                }
                $attachment_data[0]['recurrence']['recur'] = $this->_parseRecurrence($value);
                break;
            case self::MAPI_RECURRING:
                if (empty($attachment_data[0]['recurrence'])) {
                    $attachment_data[0]['recurrence'] = array();
                }
                break;
            case self::MAPI_RECURRENCE_TYPE:
                $attachment_data[0]['recurrence']['type'] = $value;
                break;
            }
        }
    }

    /**
     * TODO
     *
     * @param string &$data  The data string.
     */
    protected function _decodeMessageProperty(&$data)
    {
        $attribute = $this->_geti($data, 32);
        switch ($attribute) {
        case self::AMCLASS:
            // Start of a message.
            $message_class = trim($this->_decodeAttribute($data, $attribute));

            // For now, we only care about the parts that can be represented
            // as attachments.
            switch ($message_class) {
            case self::IPM_MEETING_REQUEST:
                $this->_iTip[0]['method'] = 'REQUEST';
                break;
            case self::IPM_MEETING_REQUEST_CANCELLED:
                $this->_iTip[0]['method'] = 'CANCEL';
                break;
            }
            break;

        // @TODO not sure if/when we would need these vs the other date props.
        // case self::ID_DATE_START:
        // case self::ID_DATE_END:
        //     $date_data = $this->_decodeAttribute($data, $attribute);
        //     $date = new Horde_Date();
        //     $date->year = $this->_geti($date_data, 16);
        //     $date->month = $this->_geti($date_data, 16);
        //     $date->mday = $this->_geti($date_data, 16);
        //     $date->hour = $this->_geti($date_data, 16);
        //     $date->min = $this->_geti($date_data, 16);
        //     $date->sec = $this->_geti($date_data, 16);
        //     break;

        case self::AIDOWNER:
            $aid = $this->_decodeAttribute($data, $attribute);
            $this->_iTip[0]['aid'] = $this->_geti($aid, 32);
            break;

        case self::ID_REQUEST_RESP:
            $response = $this->_decodeAttribute($data, $attribute);
            // This is a boolean value in the low-order bits and null byte in
            // the high order bits.
            $this->_iTip[0]['RequestResponse'] = $this->_geti($response, 16);
            break;

        case self::AMAPIPROPS:
            $properties = $this->_decodeAttribute($data, $attribute);
            $this->_extractMapiAttributes($properties, $this->_iTip);
            break;

        default:
            $this->_decodeAttribute($data, $attribute);
        }
    }

    /**
     * TODO
     *
     * @param string &$data            The data string.
     * @param array &$attachment_data  TODO
     */
    protected function _decodeAttachment(&$data, &$attachment_data)
    {
        $attribute = $this->_geti($data, 32);

        switch ($attribute) {
        case self::ARENDDATA:
            /* Marks start of new attachment. */
            $this->_getx($data, $this->_geti($data, 32));

            /* Checksum */
            $this->_geti($data, 16);

            /* Add a new default data block to hold details of this
               attachment. Reverse order is easier to handle later! */
            array_unshift($attachment_data, array('type'    => 'application',
                                                  'subtype' => 'octet-stream',
                                                  'name'    => 'unknown',
                                                  'stream'  => ''));
            break;

        case self::AFILENAME:
            /* Strip path. */
            $attachment_data[0]['name'] = preg_replace('/.*[\/](.*)$/', '\1', $this->_getx($data, $this->_geti($data, 32)));
            $attachment_data[0]['name'] = str_replace("\0", '', $attachment_data[0]['name']);

            /* Checksum */
            $this->_geti($data, 16);
            break;

        case self::ATTACHDATA:
            /* The attachment itself. */
            $length = $this->_geti($data, 32);
            $attachment_data[0]['size'] = $length;
            $attachment_data[0]['stream'] = $this->_getx($data, $length);

            /* Checksum */
            $this->_geti($data, 16);
            break;

        case self::AMAPIATTRS:
            $length = $this->_geti($data, 32);
            $value = $this->_getx($data, $length);

            /* Checksum */
            $this->_geti($data, 16);
            $this->_extractMapiAttributes($value, $attachment_data);
            break;

        default:
            $this->_decodeAttribute($data, $attribute);
        }
    }

    /**
     * Generate an iTip from embedded TNEF MEETING data.
     *
     */
    protected function _checkiTip(&$out)
    {
        if (!empty($this->_iTip[0])) {
            $iCal = new Horde_Icalendar();
            // @todo, UPDATE?
            $iCal->setAttribute('METHOD', $this->_iTip[0]['method']);

            $vEvent = Horde_Icalendar::newComponent('vevent', $iCal);

            // @todo For now, just bail out if we don't have end_utc. Need to
            // implement more of the time related properties??
            if (empty($this->_iTip[0]['end_utc'])) {
                return;
            }
            $end = clone $this->_iTip[0]['end_utc'];
            $end->sec++;
            if ($this->_iTip[0]['allday']) {
                $vEvent->setAttribute('DTSTART', $this->_iTip[0]['start_utc'], array('VALUE' => 'DATE'));
                $vEvent->setAttribute('DTEND', $end, array('VALUE' => 'DATE'));
            } else {
                $vEvent->setAttribute('DTSTART', $this->_iTip[0]['start_utc']);
                $vEvent->setAttribute('DTEND', $end);
            }
            $vEvent->setAttribute('DTSTAMP', $_SERVER['REQUEST_TIME']);
            $vEvent->setAttribute('UID', $this->_iTip[0]['uid']);
            //$vEvent->setAttribute('CREATED', $created);
            //$vEvent->setAttribute('LAST-MODIFIED', $modified);

            $vEvent->setAttribute('SUMMARY', $this->_conversation_topic);

            if (empty($this->_iTip[0]['organizer']) && !empty($this->_lastModifier)) {
                $email = $this->_lastModifier;
            } else if (!empty($this->_iTip[0]['organizer'])) {
                $email = $this->_iTip[0]['organizer'];
            }
            if (!empty($email)) {
                $vEvent->setAttribute('ORGANIZER', 'mailto:' . $email);
            }
            if (!empty($this->_iTip[0]['url'])) {
                $vEvent->setAttribute('URL', $this->_iTip[0]['url']);
            }
            if (!empty($this->_iTip[0]['recurrence']['recur'])) {
                $rrule = $this->_iTip[0]['recurrence']['recur']->toRRule20($iCal);
                $vEvent->setAttribute('RRULE', $rrule);
            }
            $iCal->addComponent($vEvent);

            array_unshift($out, array(
                'type'    => 'text',
                'subtype' => 'calendar',
                'name'    => $this->_conversation_topic,
                'stream'  => $iCal->exportvCalendar()));
        }
    }

    protected function _parseRecurrence($value)
    {
        // both are 0x3004 (version strings);
        $this->_geti($value, 16);
        $this->_geti($value, 16);

        $freq = $this->_geti($value, 16);
        $pattern = $this->_geti($value, 16);
        $calendarType = $this->_geti($value, 16);
        $firstDt = $this->_geti($value, 32);
        $period = $this->_geti($value, 32);
        // Only used for tasks, otherwise value must be zero.
        $flag = $this->_geti($value, 32);

        // TypeSpecific field
        switch ($pattern) {
        case self::PATTERN_DAY:
            // Nothing here to see, move along.
            break;
        case self::PATTERN_WEEK:
            // Bits: 0/unused, 1/Saturday, 2/Friday, 3/Thursday, 4/Wednesday,
            // 5/Tuesday, 6/Monday, 7/Sunday.
            $day = $this->_geti($value, 8);
            $this->_geti($value, 24);
            break;
        case self::PATTERN_MONTH:
        case self::PATTERN_MONTH_END:
            // Day of month on which the recurrence falls.
            $day = $this->_geti($value, 32);
            break;
        case self::PATTERN_MONTH_NTH:
            // Bits: 0/unused, 1/Saturday, 2/Friday, 3/Thursday, 4/Wednesday,
            // 5/Tuesday, 6/Monday, 7/Sunday.
            // For Nth Weekday of month
            $day = $this->_geti($value, 8);
            $this->_geti($value, 24);
            $n = $this->_geti($value, 32);
            break;
        }
        $end = $this->_geti($value, 32);
        $count = $this->_geti($value, 32);
        $fdow = $this->_geti($value, 32);
        $deletedCount = $this->_geti($value, 32);
        for ($i = 0; $i < $deletedCount; $i++) {
            $deleted[] = $this->_geti($value, 32);
        }
        $modifiedCount = $this->_geti($value, 32);
        for ($i = 0; $i < $modifiedCount; $i++) {
            $modified[] = $this->_geti($value, 32);
        }

        // What Timezone are these in?
        try {
            $startDate = new Horde_Date(Horde_Mapi::filetimeToUnixtime($this->_geti($value, 32)));
            $endDate = new Horde_Date(Horde_Mapi::filetimeToUnixtime($this->_geti($value, 32)));
        } catch (Horde_Mapi_Exception $e) {
            throw new Horde_Compress_Exception($e);
        }

        $rrule = new Horde_Date_Recurrence($startDate);
        switch ($pattern) {
        case self::PATTERN_DAY:
            $rrule->setRecurType(Horde_Date_Recurrence::RECUR_DAILY);
            break;
        case self::PATTERN_WEEK:
            $rrule->setRecurType(Horde_Date_Recurrence::RECUR_WEEKLY);
            break;
        case self::PATTERN_MONTH:
        case self::PATTERN_MONTH_END:
            $rrule->setRecurType(Horde_Date_Recurrence::RECUR_MONTHLY_DATE);
            break;
        case self::PATTERN_MONTH_NTH:
            $rrule->setRecurType(Horde_Date_Recurrence::RECUR_MONTHLY_WEEKDAY);
            break;
        default:
            if ($freq == self::RECUR_YEARLY) {
                $rrule->setRecurType(Horde_Date_Recurrence::RECUR_YEARLY);
            }
        }

        switch ($end) {
        case self::RECUR_END_N:
            $rrule->setRecurCount($count);
            break;
        case self::RECUR_END_DATE:
            $rrule->setRecurEnd($endDate);
            break;
        }

        return $rrule;
    }

}
