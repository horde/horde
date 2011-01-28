<?php
/**
 * Horde_ActiveSync_Message_* classes represent a single ActiveSync message
 * such as a Contact or Appointment. Encoding/Decoding logic taken from the
 * Z-Push library. Original file header and copyright notice appear below.
 *
 * @copyright 2010-2011 The Horde Project (http://www.horde.org)
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Horde_ActiveSync
 */
/***********************************************
* File      :   streamer.php
* Project   :   Z-Push
* Descr     :   This file handles streaming of
*                WBXML objects. It must be
*                subclassed so the internals of
*                the object can be specified via
*                $mapping. Basically we set/read
*                the object variables of the
*                subclass according to the mappings
*
*
* Created   :   01.10.2007
*
* � Zarafa Deutschland GmbH, www.zarafaserver.de
* This file is distributed under GPL v2.
* Consult LICENSE file for details
************************************************/
class Horde_ActiveSync_Message_Base
{

    /* Attribute Keys */
    const KEY_ATTRIBUTE = 1;
    const KEY_VALUES = 2;
    const KEY_TYPE = 3;

    /* Types */
    const TYPE_DATE = 1;
    const TYPE_HEX = 2;
    const TYPE_DATE_DASHES = 3;
    const TYPE_MAPI_STREAM = 4;

    /**
     * Holds the mapping for object properties
     *
     * @var array
     */
    protected $_mapping;

    /**
     * Holds property values
     *
     * @array
     */
    protected $_properties = array();

    /**
     * Message flags
     * //FIXME: use accessor methods, make this protected
     * @var Horde_ActiveSync_FLAG_* constant
     */
    public $flags = false;

    /**
     * Logger
     *
     * @var Horde_Log_Logger
     */
    protected $_logger;

    /**
     * An array describing the non-ghosted elements this message supports.
     *
     * @var array
     */
    protected $_supported = array();
    protected $_exists = array();

    /**
     * Const'r
     *
     * @param array $mapping  A mapping array from constants -> property names
     * @param array $options  Any addition options the message may require
     *
     * @return Horde_ActiveSync_Message_Base
     */
    public function __construct($options)
    {
        if (!empty($options['logger'])) {
            $this->_logger = $options['logger'];
        } else {
            $this->_logger = new Horde_Support_Stub();
        }
    }

    public function __get($property)
    {
        if (!array_key_exists($property, $this->_properties)) {
            $this->_logger->err('Unknown property: ' . $property);
            throw new InvalidArgumentException('Unknown property: ' . $property);
        }

        if (!empty($this->_properties[$property])) {
            return $this->_properties[$property];
        } else {
            return '';
        }
    }

    public function __set($property, $value)
    {
        if (!array_key_exists($property, $this->_properties)) {
            $this->_logger->err('Unknown property: ' . $property);
            throw new InvalidArgumentException('Unknown property: ' . $property);
        }
        $this->_properties[$property] = $value;
        $this->_exists[$property] = true;
    }

    public function __call($method, $arg)
    {
        /* Support calling set{Property}() */
        if (strpos($method, 'set') === 0) {
            $property = Horde_String::lower(substr($method, 3));
            $this->_properties[$property] = $arg;
        } elseif (strpos($method, 'get') === 0) {
            return $this->_getAttribute(Horde_String::lower(substr($method, 3)));
        }

        throw new BadMethodCallException('Unknown method: ' . $method . ' in class: ' . __CLASS__);
    }

    public function __isset($property)
    {
        return isset($this->_properties[$property]);
    }

    /**
     * Set the list of non-ghosted fields for this message.
     *
     * @param array $fields  The array of fields.
     */
    public function setSupported($fields)
    {
        $this->_supported = array();
        foreach ($fields as $field) {
            $this->_supported[] = $this->_mapping[$field][self::KEY_ATTRIBUTE];
        }
    }

    /**
     * Get the list of non-ghosted properties for this message.
     *
     * @return array  The array of non-ghosted properties
     */
    public function getSupported()
    {
        return $this->_supported;
    }

    /**
     * Determines if the property specified has been ghosted by the client.
     * A ghosted property is 1) NOT listed in the supported list and 2) NOT
     * present in the current message. If it's IN the supported list and NOT
     * in the current message, then it IS ghosted and the server should keep
     * the field's current value when performing any change action due to this
     * message.
     *
     * @param string $property  The property to check
     * @return boolean
     */
    public function isGhosted($property)
    {
        if (array_search($property, $this->_supported) === false) {
            return false;
        } elseif (empty($this->_exists[$property])) {
            return true;
        }

        return false;
    }

    /**
     * Recursively decodes the WBXML from input stream. This means that if this
     * message contains complex types (like Appointment.Recuurence for example)
     * the sub-objects are auto-instantiated and decoded as well. Places the
     * decoded objects in the local properties array.
     *
     * @param Horde_ActiveSync_Wbxml_Decoder  The stream decoder
     *
     * @throws Horde_ActiveSync_Exception
     * @return void
     */
    public function decodeStream(Horde_ActiveSync_Wbxml_Decoder &$decoder)
    {
        while (1) {
            $entity = $decoder->getElement();

            if ($entity[Horde_ActiveSync_Wbxml::EN_TYPE] == Horde_ActiveSync_Wbxml::EN_TYPE_STARTTAG) {
                if (! ($entity[Horde_ActiveSync_Wbxml::EN_FLAGS] & Horde_ActiveSync_Wbxml::EN_FLAGS_CONTENT)) {
                    $map = $this->_mapping[$entity[Horde_ActiveSync_Wbxml::EN_TAG]];
                    if (!isset($map[self::KEY_TYPE])) {
                        $this->$map[self::KEY_ATTRIBUTE] = '';
                    } elseif ($map[self::KEY_TYPE] == self::TYPE_DATE || $map[self::KEY_TYPE] == self::TYPE_DATE_DASHES ) {
                        $this->$map[self::KEY_ATTRIBUTE] = '';
                    }
                    continue;
                }

                /* Found start tag */
                if (!isset($this->_mapping[$entity[Horde_ActiveSync_Wbxml::EN_TAG]])) {
                    $this->_logDebug('Tag ' . $entity[Horde_ActiveSync_Wbxml::EN_TAG] . ' unexpected in type XML type ' . get_class($this));
                    throw new Horde_ActiveSync_Exception('Unexpected tag');
                } else {
                    $map = $this->_mapping[$entity[Horde_ActiveSync_Wbxml::EN_TAG]];

                    /* Handle arrays of attribute values */
                    if (isset($map[self::KEY_VALUES])) {
                        while (1) {
                            if (!$decoder->getElementStartTag($map[self::KEY_VALUES])) {
                                break;
                            }
                            if (isset($map[self::KEY_TYPE])) {
                                $decoded = new $map[self::KEY_TYPE];
                                $decoded->decodeStream($decoder);
                            } else {
                                $decoded = $decoder->getElementContent();
                            }

                            if (!isset($this->$map[self::KEY_ATTRIBUTE])) {
                                $this->$map[self::KEY_ATTRIBUTE] = array($decoded);
                            } else {
                                array_push($this->$map[self::KEY_ATTRIBUTE], $decoded);
                            }

                            if (!$decoder->getElementEndTag()) {
                                throw new Horde_ActiveSync_Exception('Missing expected wbxml end tag');
                            }
                        }

                        if (!$decoder->getElementEndTag()) {
                            return false;
                        }
                    } else {
                        /* Handle a simple attribute value */
                        if (isset($map[self::KEY_TYPE])) {
                            /* Complex type, decode recursively */
                            if ($map[self::KEY_TYPE] == self::TYPE_DATE || $map[self::KEY_TYPE] == self::TYPE_DATE_DASHES) {
                                $decoded = self::_parseDate($decoder->getElementContent());
                                if (!$decoder->getElementEndTag()) {
                                    throw new Horde_ActiveSync_Exception('Missing expected wbxml end tag');
                                }
                            } elseif ($map[self::KEY_TYPE] == self::TYPE_HEX) {
                                $decoded = self::hex2bin($decoder->getElementContent());
                                if (!$decoder->getElementEndTag()) {
                                   throw new Horde_ActiveSync_Exception('Missing expected wbxml end tag');
                                }
                            } else {
                                $subdecoder = new $map[self::KEY_TYPE]();
                                if ($subdecoder->decodeStream($decoder) === false) {
                                    throw new Horde_ActiveSync_Exception('Missing expected wbxml end tag');
                                }

                                $decoded = $subdecoder;
                                if (!$decoder->getElementEndTag()) {
                                    $this->_logError('No end tag for ' . $entity[Horde_ActiveSync_Wbxml::EN_TAG]);
                                    throw new Horde_ActiveSync_Exception('Missing expected wbxml end tag');
                                }
                            }
                        } else {
                            /* Simple type, just get content */
                            $decoded = $decoder->getElementContent();
                            if ($decoded === false) {
                                $this->_logError('Unable to get content for ' . $entity[Horde_ActiveSync_Wbxml::EN_TAG]);
                                //throw new Horde_ActiveSync_Exception('Unknown parsing error.');
                            }

                            if (!$decoder->getElementEndTag()) {
                                $this->_logError('Unable to get end tag for ' . $entity[Horde_ActiveSync_Wbxml::EN_TAG]);
                                throw new Horde_ActiveSync_Exception('Missing expected wbxml end tag');
                            }
                        }
                        /* $decoded now contains data object (or string) */
                        $this->$map[self::KEY_ATTRIBUTE] = $decoded;
                    }
                }
            } elseif ($entity[Horde_ActiveSync_Wbxml::EN_TYPE] == Horde_ActiveSync_Wbxml::EN_TYPE_ENDTAG) {
                $decoder->_ungetElement($entity);
                break;
            } else {
                $this->_logError('Unexpected content in type');
                break;
            }
        }
    }

    /**
     * Encodes this object (and any sub-objects) as wbxml to the output stream.
     * Output is ordered according to $_mapping
     *
     * @param Horde_ActiveSync_Wbxml_Encoder $encoder  The wbxml stream encoder
     *
     * @return void
     */
    public function encodeStream(Horde_ActiveSync_Wbxml_Encoder &$encoder)
    {
        foreach ($this->_mapping as $tag => $map) {
            if (isset($this->$map[self::KEY_ATTRIBUTE])) {
                /* Variable is available */
                if (is_object($this->$map[self::KEY_ATTRIBUTE]) && !($this->$map[self::KEY_ATTRIBUTE] instanceof Horde_Date)) {
                    /* Subobjects can do their own encoding */
                    $encoder->startTag($tag);
                    $this->$map[self::KEY_ATTRIBUTE]->encodeStream($encoder);
                    $encoder->endTag();
                } elseif (isset($map[self::KEY_VALUES]) && is_array($this->$map[self::KEY_ATTRIBUTE])) {
                    /* Array of objects */
                    $encoder->startTag($tag); // Outputs array container (eg Attachments)
                    foreach ($this->$map[self::KEY_ATTRIBUTE] as $element) {
                        if (is_object($element)) {
                            // Outputs object container (eg Attachment)
                            $encoder->startTag($map[self::KEY_VALUES]);
                            $element->encodeStream($encoder);
                            $encoder->endTag();
                        } else {
                            if(strlen($element) == 0) {
                                  // Do not output empty items. Not sure if we
                                  // should output an empty tag with
                                  // $encoder->startTag($map[self::KEY_VALUES], false, true);
                            } else {
                                $encoder->startTag($map[self::KEY_VALUES]);
                                $encoder->content($element);
                                $encoder->endTag();
                            }
                        }
                    }
                    $encoder->endTag();
                } else {
                    /* Simple type */
                    if (strlen($this->$map[self::KEY_ATTRIBUTE]) == 0) {
                          // Do not output empty items except for the following:
                          if ($this->_checkSendEmpty($tag)) {
                              $encoder->startTag($tag, false, true);
                          } else {
                            continue;
                          }
                    } else {
                        $encoder->startTag($tag);
                    }
                    if (isset($map[self::KEY_TYPE]) && ($map[self::KEY_TYPE] == self::TYPE_DATE || $map[self::KEY_TYPE] == self::TYPE_DATE_DASHES)) {
                        if (!empty($this->$map[self::KEY_ATTRIBUTE])) { // don't output 1-1-1970
                            $encoder->content(self::_formatDate($this->$map[self::KEY_ATTRIBUTE], $map[self::KEY_TYPE]));
                        }
                    } elseif (isset($map[self::KEY_TYPE]) && $map[self::KEY_TYPE] == self::TYPE_HEX) {
                        $encoder->content(Horde_String::upper(bin2hex($this->$map[self::KEY_ATTRIBUTE])));
                    } elseif (isset($map[self::KEY_TYPE]) && $map[self::KEY_TYPE] == self::TYPE_MAPI_STREAM) {
                        $encoder->content($this->$map[self::KEY_ATTRIBUTE]);
                    } else {
                        $encoder->content($this->$map[self::KEY_ATTRIBUTE]);
                    }
                    $encoder->endTag();
                }
            }
        }
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
        return false;
    }

    /**
     *
     * @param $message
     * @return unknown_type
     */
    protected function _logDebug($message)
    {
        if (!empty($this->_logger)) {
            $this->_logger->debug($message);
        }
    }

    /**
     *
     * @param $message
     * @return unknown_type
     */
    protected function _logError($message)
    {
        if (!empty($this->_logger)) {
            $this->_logger->err($message);
        }
    }

    /**
     * Helper method to allow default values for unset properties.
     *
     * @param string $name  The property name
     * @param stting $default The default value to return if $property is empty
     *
     * @return mixed
     */
    protected function _getAttribute($name, $default = null)
    {
        if (!empty($this->_properties[$name])) {
            return $this->_properties[$name];
        } else {
            return $default;
        }
    }

    /**
     * Oh yeah. This is beautiful. Exchange outputs date fields differently in
     * calendar items and emails. We could just always send one or the other,
     * but unfortunately nokia's 'Mail for exchange' depends on this quirk.
     * So we have to send a different date type depending on where it's used.
     *
     * @param Horde_Date $dt  The datetime to format (assumed to be in local tz)
     * @param Constant $type  The type to format as (TYPE_DATE or TYPE_DATE_DASHES)
     *
     * @return string  The formatted date
     */
    static protected function _formatDate($dt, $type)
    {
        if ($type == Horde_ActiveSync_Message_Base::TYPE_DATE) {
            return $dt->setTimezone('UTC')->format('Ymd\THis\Z');
        } elseif ($type == Horde_ActiveSync_Message_Base::TYPE_DATE_DASHES) {
            return $dt->setTimezone('UTC')->format('Y-m-d\TH:i:s\.000\Z');
        }
    }

    /**
     * Get a Horde_Date from a timestamp, ensuring it's in the correct format.
     *
     * @param string $ts
     *
     * @return Horde_Date
     */
    static protected function _parseDate($ts)
    {
        if (preg_match("/(\d{4})[^0-9]*(\d{2})[^0-9]*(\d{2})T(\d{2})[^0-9]*(\d{2})[^0-9]*(\d{2})(.\d+)?Z/", $ts, $matches)) {
            return new Horde_Date($ts);
        }

        throw new Horde_ActiveSync_Exception('Invalid date format');
    }

    /**
     * Function which converts a hex entryid to a binary entryid.
     * @param string @data the hexadecimal string
     */
    static private function hex2bin($data)
    {
        $len = strlen($data);
        $newdata = "";

        for($i = 0;$i < $len;$i += 2)
        {
            $newdata .= pack("C", hexdec(substr($data, $i, 2)));
        }
        return $newdata;
    }

}