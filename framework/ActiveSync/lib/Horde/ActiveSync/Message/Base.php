<?php
/**
 * Horde_ActiveSync_Message_Base::
 *
 * Portions of this class were ported from the Z-Push project:
 *   File      :   wbxml.php
 *   Project   :   Z-Push
 *   Descr     :   WBXML mapping file
 *
 *   Created   :   01.10.2007
 *
 *   © Zarafa Deutschland GmbH, www.zarafaserver.de
 *   This file is distributed under GPL-2.0.
 *   Consult COPYING file for details
 *
 * @license   http://www.horde.org/licenses/gpl GPLv2
 *            NOTE: According to sec. 8 of the GENERAL PUBLIC LICENSE (GPL),
 *            Version 2, the distribution of the Horde_ActiveSync module in or
 *            to the United States of America is excluded from the scope of this
 *            license.
 * @copyright 2009-2017 Horde LLC (http://www.horde.org)
 * @author    Michael J Rubinsky <mrubinsk@horde.org>
 * @package   ActiveSync
 */
/**
 * Horde_ActiveSync_Message_Base:: Base class for all ActiveSync message
 * objects.
 *
 * @license   http://www.horde.org/licenses/gpl GPLv2
 *            NOTE: According to sec. 8 of the GENERAL PUBLIC LICENSE (GPL),
 *            Version 2, the distribution of the Horde_ActiveSync module in or
 *            to the United States of America is excluded from the scope of this
 *            license.
 * @copyright 2009-2017 Horde LLC (http://www.horde.org)
 * @author    Michael J Rubinsky <mrubinsk@horde.org>
 * @package   ActiveSync
 */
class Horde_ActiveSync_Message_Base
{
    /* Attribute Keys */
    const KEY_ATTRIBUTE         = 1;
    const KEY_VALUES            = 2;
    const KEY_TYPE              = 3;
    const KEY_PROPERTY          = 4;

    /* Types */
    const TYPE_DATE             = 1;
    const TYPE_HEX              = 2;
    const TYPE_DATE_DASHES      = 3;
    const TYPE_MAPI_STREAM      = 4;
    const TYPE_MAPI_GOID        = 5;
    const TYPE_DATE_LOCAL       = 6;
    const PROPERTY_NO_CONTAINER = 7;

    /**
     * Holds the mapping for object properties
     *
     * @var array
     */
    protected $_mapping;

    /**
     * Holds property values
     *
     * @var array
     */
    protected $_properties = array();

    /**
     * Message flags
     *
     * @var Horde_ActiveSync::FLAG_* constant
     */
    public $flags = false;

    /**
     * Request type. One of: Horde_ActiveSync::SYNC_ADD, SYNC_MODIFY,
     *    SYNC_REMOVE, or SYNC_FETCH. Used internally for enforcing various
     *    protocol rules depending on request. @since 2.31.0
     *
     * @var  string
     */
    public $commandType;

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

    /**
     * Existence cache, used for working with ghosted properties.
     *
     * @var array
     */
    protected $_exists = array();

    /**
     * The version of EAS we are to support.
     *
     * @var float
     */
    protected $_version = Horde_ActiveSync::VERSION_TWOFIVE;

    /**
     * The device object
     *
     * @var Horde_ActiveSync_Device
     * @since 2.9.2
     */
    protected $_device;

    /**
     * Cache of current stream filters.
     *
     * @var array
     */
    protected $_streamFilters = array();

    /**
     * Const'r
     *
     * @param array $options  Configuration options for the message:
     *   - logger: (Horde_Log_Logger)  A logger instance
     *             DEFAULT: none (No logging).
     *   - protocolversion: (float)  The version of EAS to support.
     *              DEFAULT: Horde_ActiveSync::VERSION_TWOFIVE (2.5)
     *   - device: (Horde_ActiveSync_Device)  The device object. @since 2.9.2
     *
     * @return Horde_ActiveSync_Message_Base
     */
    public function __construct(array $options = array())
    {
        if (!empty($options['logger'])) {
            $this->_logger = Horde_ActiveSync::_wrapLogger($options['logger']);
        } else {
            $this->_logger =  new Horde_ActiveSync_Log_Logger(new Horde_Log_Handler_Null());
        }
        if (!empty($options['protocolversion'])) {
            $this->_version = $options['protocolversion'];
        }
        if (!empty($options['device'])) {
            $this->_device = $options['device'];
        }
    }

    public function __destruct()
    {
        foreach ($this->_streamFilters as $filter) {
            stream_filter_remove($filter);
        }
    }

    /**
     * Return the EAS version this object supports.
     *
     * @return float  A Horde_ActiveSync::VERSION_* constant.
     */
    public function getProtocolVersion()
    {
        return $this->_version;
    }

    /**
     * Check the existence of a property in this message.
     *
     * @param string $property  The property name
     *
     * @return boolean
     */
    public function propertyExists($property)
    {
        return array_key_exists($property, $this->_properties);
    }

    /**
     * Accessor
     *
     * @param string $property  Property to get.
     *
     * @return mixed  The value of the requested property.
     * @todo: Return boolean false if not set. Not BC to change it.
     */
    public function &__get($property)
    {
        if ($this->_properties[$property] !== false) {
            return $this->_properties[$property];
        } else {
            $string = '';
            return $string;
        }
    }

    /**
     * Setter
     *
     * @param string $property  The property to set.
     * @param mixed  $value     The value to set it to.
     *
     * @throws InvalidArgumentException
     */
    public function __set($property, $value)
    {
        if (!array_key_exists($property, $this->_properties)) {
            $this->_logger->err('Unknown property: ' . $property);
            throw new InvalidArgumentException(get_class($this) . ' Unknown property: ' . $property);
        }
        $this->_properties[$property] = $value;
        $this->_exists[$property] = true;
    }

    /**
     * Give concrete classes the chance to enforce rules.
     *
     * @return boolean  True on success, otherwise false.
     * @since  2.31.0
     */
    protected function _validateDecodedValues()
    {
        return true;
    }

    /**
     * Give concrete classes the chance to enforce rules before encoding
     * messages to send to the client.
     *
     * @return boolean  True if values were valid (or could be made valid).
     *     False if values are unable to be validated.
     * @since  2.31.0
     */
    protected function _preEncodeValidation()
    {
        return true;
    }

    /**
     * Magic caller method.
     *
     * @param  mixed $method  The method to call.
     * @param  array $arg     Method arguments.
     *
     * @return mixed
     */
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

    /**
     * Magic method.
     *
     * @param string $property  The property name to check.
     *
     * @return boolean.
     */
    public function __isset($property)
    {
        return isset($this->_properties[$property]);
    }

    /**
     * Set the list of non-ghosted fields for this message.
     *
     * @param array $fields  The array of fields, keyed by the fully qualified
     *                       property name i.e., POOMCONTACTS:Anniversary. To
     *                       signify an empty SUPPORTED container $fields should
     *                       contain a single element equal to
     *                       Horde_ActiveSync::ALL_GHOSTED.
     */
    public function setSupported(array $fields)
    {
        $this->_supported = array();
        if (current($fields) == Horde_ActiveSync::ALL_GHOSTED) {
            $this->_supported = $fields;
            return;
        }

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
     * A property is ghosted if it is NOT listed in the SUPPORTED list sent
     * by the client AND is NOT present in the request data.
     *
     * @param string $property  The property to check
     *
     * @return boolean
     */
    public function isGhosted($property)
    {
        // MS-ASCMD 2.2.3.168:
        // An empty SUPPORTED container indicates that ALL elements able to be
        // ghosted ARE ghosted. A *missing* SUPPORTED tag indicates that NO
        // fields are ghosted - any ghostable properties are always considered
        // NOT ghosted.
        if (empty($this->_supported)) {
            return false;
        }
        if (current($this->_supported) == Horde_ActiveSync::ALL_GHOSTED &&
            empty($this->_exists[$property])) {
            return true;
        }

        return array_search($property, $this->_supported) === false &&
               empty($this->_exists[$property]);
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
     */
    public function decodeStream(Horde_ActiveSync_Wbxml_Decoder &$decoder)
    {
        while (1) {
            $entity = $decoder->getElement();

            if ($entity[Horde_ActiveSync_Wbxml::EN_TYPE] == Horde_ActiveSync_Wbxml::EN_TYPE_STARTTAG) {
                if (!($entity[Horde_ActiveSync_Wbxml::EN_FLAGS] & Horde_ActiveSync_Wbxml::EN_FLAGS_CONTENT)) {
                    $map = $this->_mapping[$entity[Horde_ActiveSync_Wbxml::EN_TAG]];
                    if (!isset($map[self::KEY_TYPE])) {
                        $this->{$map[self::KEY_ATTRIBUTE]} = '';
                    } elseif ($map[self::KEY_TYPE] == self::TYPE_DATE || $map[self::KEY_TYPE] == self::TYPE_DATE_DASHES ) {
                        $this->{$map[self::KEY_ATTRIBUTE]} = '';
                    }
                    continue;
                }

                // Found start tag
                if (!isset($this->_mapping[$entity[Horde_ActiveSync_Wbxml::EN_TAG]])) {
                    $this->_logger->err(sprintf(
                        'Tag %s unexpected in type XML type %s.',
                         $entity[Horde_ActiveSync_Wbxml::EN_TAG],
                         get_class($this))
                    );
                    throw new Horde_ActiveSync_Exception('Unexpected tag');
                } else {
                    $map = $this->_mapping[$entity[Horde_ActiveSync_Wbxml::EN_TAG]];
                    if (isset($map[self::KEY_VALUES])) {
                        // Handle arrays of attribute values
                        while (1) {
                            // If we can have multiple types of objects in this
                            // container, or we are parsing a NO_CONTAINER,
                            // check that we are not at the end tag of the
                            // or we have a valid start tag for the NO_CONTAINER
                            // object. If not, break out of loop.
                            if (is_array($map[self::KEY_VALUES])) {
                                $token = $decoder->peek();
                                if ($token[Horde_ActiveSync_Wbxml_Decoder::EN_TYPE] == Horde_ActiveSync_Wbxml_Decoder::EN_TYPE_ENDTAG) {
                                    break;
                                }
                            } elseif (!(isset($map[self::KEY_PROPERTY]) && $map[self::KEY_PROPERTY] == self::PROPERTY_NO_CONTAINER) &&
                                      !$decoder->getElementStartTag($map[self::KEY_VALUES])) {
                                break;
                            }

                            // We know we have some valid value, parse out what
                            // it is. Either an array of (possibly varied)
                            // objects, a single object, or simple value.
                            if (is_array($map[self::KEY_VALUES])) {
                                $token = $decoder->getToken();
                                if (($idx = array_search($token[Horde_ActiveSync_Wbxml_Decoder::EN_TAG], $map[self::KEY_VALUES])) !== false) {
                                    $class = $map[self::KEY_TYPE][$idx];
                                    $decoded = new $class(array(
                                        'protocolversion' => $this->_version,
                                        'logger' => $this->_logger)
                                    );
                                    $decoded->commandType = $this->commandType;
                                    $decoded->decodeStream($decoder);
                                } else {
                                    throw new Horde_ActiveSync_Exception('Error in message map configuration');
                                }
                            } elseif (isset($map[self::KEY_TYPE])) {
                                $class = $map[self::KEY_TYPE];
                                $decoded = new $class(array(
                                    'protocolversion' => $this->_version,
                                    'logger' => $this->_logger)
                                );
                                $decoded->commandType = $this->commandType;
                                $decoded->decodeStream($decoder);
                            } else {
                                $decoded = $decoder->getElementContent();
                            }

                            // Assign the parsed value to the mapped attribute.
                            if (!isset($this->{$map[self::KEY_ATTRIBUTE]})) {
                                $this->{$map[self::KEY_ATTRIBUTE]} = array($decoded);
                            } else {
                                $this->{$map[self::KEY_ATTRIBUTE]}[] = $decoded;
                            }

                            // Get the end tag of this attribute node.
                            if (!$decoder->getElementEndTag()) {
                                throw new Horde_ActiveSync_Exception('Missing expected wbxml end tag');
                            }

                            // For NO_CONTAINER attributes, need some magic to
                            // make sure we break out properly.
                            if (isset($map[self::KEY_PROPERTY]) && $map[self::KEY_PROPERTY] == self::PROPERTY_NO_CONTAINER) {
                                $e = $decoder->peek();
                                // Go back to the initial while if another block
                                // of a non-container element is found.
                                if ($e[Horde_ActiveSync_Wbxml::EN_TYPE] == Horde_ActiveSync_Wbxml::EN_TYPE_STARTTAG) {
                                    continue 2;
                                }
                                // Break on end tag because no other container
                                // elements block end is reached.
                                if ($e[Horde_ActiveSync_Wbxml::EN_TYPE] == Horde_ActiveSync_Wbxml::EN_TYPE_ENDTAG || empty($e)) {
                                    break;
                                }
                            }
                        }

                        // Do not get container end tag for an array without a container
                        if (!(isset($map[self::KEY_PROPERTY]) && $map[self::KEY_PROPERTY] == self::PROPERTY_NO_CONTAINER) &&
                            !$decoder->getElementEndTag()) {
                            return false;
                        }
                    } else {
                        // Handle a simple attribute value
                        if (isset($map[self::KEY_TYPE])) {
                            if (in_array($map[self::KEY_TYPE], array(self::TYPE_DATE, self::TYPE_DATE_DASHES, self::TYPE_DATE_LOCAL))) {
                                $decoded = $this->_parseDate($decoder->getElementContent());
                            } elseif ($map[self::KEY_TYPE] == self::TYPE_HEX) {
                                $decoded = self::_hex2bin($decoder->getElementContent());
                            } else {
                                // Complex type, decode recursively
                                $class = $map[self::KEY_TYPE];
                                $subdecoder = new $class(array(
                                    'protocolversion' => $this->_version,
                                    'logger' => $this->_logger)
                                );
                                $subdecoder->commandType = $this->commandType;
                                $subdecoder->decodeStream($decoder);
                                $decoded = $subdecoder;
                            }
                        } else {
                            // Simple type, just get content
                            $decoded = $decoder->getElementContent();
                            if ($decoded === false) {
                                $decoded = '';
                                $this->_logger->notice(sprintf(
                                    'Unable to get expected content for %s: Setting to an empty string.',
                                    $entity[Horde_ActiveSync_Wbxml::EN_TAG])
                                );
                            }
                        }
                        if (!$decoder->getElementEndTag()) {
                            $this->_logger->err(sprintf(
                                'Unable to get end tag for %s.',
                                $entity[Horde_ActiveSync_Wbxml::EN_TAG])
                            );
                            throw new Horde_ActiveSync_Exception('Missing expected wbxml end tag');
                        }
                        $this->{$map[self::KEY_ATTRIBUTE]} = $decoded;
                    }
                }
            } elseif ($entity[Horde_ActiveSync_Wbxml::EN_TYPE] == Horde_ActiveSync_Wbxml::EN_TYPE_ENDTAG) {
                $decoder->_ungetElement($entity);
                break;
            } else {
                $this->_logger->err('Unexpected content in type');
                break;
            }
        }
        if (!$this->_validateDecodedValues()) {
            throw new Horde_ActiveSync_Exception(sprintf(
                'Invalid values detected in %s.',
                get_class($this))
            );
        }
    }

    /**
     * Encodes this object (and any sub-objects) as wbxml to the output stream.
     * Output is ordered according to $_mapping
     *
     * @param Horde_ActiveSync_Wbxml_Encoder $encoder  The wbxml stream encoder
     * @throws  Horde_ActiveSync_Exception
     */
    public function encodeStream(Horde_ActiveSync_Wbxml_Encoder &$encoder)
    {
        if (!$this->_preEncodeValidation()) {
            $this->_logger->err(sprintf(
                'Pre-encoding validation failed for %s item',
                get_class($this))
            );
            throw new Horde_ActiveSync_Exception(sprintf(
                'Pre-encoding validation failded for %s item',
                get_class($this))
            );
        }

        foreach ($this->_mapping as $tag => $map) {
            if (isset($this->{$map[self::KEY_ATTRIBUTE]})) {
                // Variable is available
                if (is_object($this->{$map[self::KEY_ATTRIBUTE]}) &&
                    !($this->{$map[self::KEY_ATTRIBUTE]} instanceof Horde_Date)) {
                    // Objects can do their own encoding
                    $encoder->startTag($tag);
                    $this->{$map[self::KEY_ATTRIBUTE]}->encodeStream($encoder);
                    $encoder->endTag();
                } elseif (isset($map[self::KEY_VALUES]) &&
                          is_array($this->{$map[self::KEY_ATTRIBUTE]})) {
                    // Array of objects. Note that some array values must be
                    // send as an empty tag if they contain no elements.
                    if (count($this->{$map[self::KEY_ATTRIBUTE]})) {
                        if (!isset($map[self::KEY_PROPERTY]) ||
                            $map[self::KEY_PROPERTY] != self::PROPERTY_NO_CONTAINER) {
                            $encoder->startTag($tag);
                        }
                        foreach ($this->{$map[self::KEY_ATTRIBUTE]} as $element) {
                            if (is_object($element)) {
                                // Hanlde multi-typed array containers.
                                if (is_array($map[self::KEY_VALUES])) {
                                    $idx = array_search(get_class($element), $map[self::KEY_TYPE]);
                                    $tag = $map[self::KEY_VALUES][$idx];
                                } else {
                                    $tag = $map[self::KEY_VALUES];
                                }
                                // Outputs object container (eg Attachment)
                                $encoder->startTag($tag);
                                $element->encodeStream($encoder);
                                $encoder->endTag();
                            } else {
                                // Do not ever output empty items here
                                if(strlen($element) > 0) {
                                    $encoder->startTag($map[self::KEY_VALUES]);
                                    $encoder->content($element);
                                    $encoder->endTag();
                                }
                            }
                        }
                        if (!isset($map[self::KEY_PROPERTY]) || $map[self::KEY_PROPERTY] != self::PROPERTY_NO_CONTAINER) {
                            $encoder->endTag();
                        }
                    } elseif ($this->_checkSendEmpty($tag)) {
                        $encoder->startTag($tag, null, true);
                    }
                } else {
                    // Simple type
                    if (!is_resource($this->{$map[self::KEY_ATTRIBUTE]}) &&
                        strlen($this->{$map[self::KEY_ATTRIBUTE]}) == 0) {
                          // Do not output empty items except for the following:
                          if ($this->_checkSendEmpty($tag)) {
                              $encoder->startTag($tag, $this->{$map[self::KEY_ATTRIBUTE]}, true);
                          }
                          continue;
                    } elseif ($encoder->multipart &&
                              in_array($tag, array(
                                Horde_ActiveSync::SYNC_DATA,
                                Horde_ActiveSync::AIRSYNCBASE_DATA,
                                Horde_ActiveSync_Request_ItemOperations::ITEMOPERATIONS_DATA)
                              )) {
                        $this->_logger->meta('HANDLING MULTIPART OUTPUT');
                        $encoder->addPart($this->{$map[self::KEY_ATTRIBUTE]});
                        $encoder->startTag(Horde_ActiveSync_Request_ItemOperations::ITEMOPERATIONS_PART);
                        $encoder->content((string)(count($encoder->getParts()) - 1));
                        $encoder->endTag();
                        continue;
                    }

                    $encoder->startTag($tag);
                    if (isset($map[self::KEY_TYPE]) &&
                        (in_array($map[self::KEY_TYPE], array(self::TYPE_DATE, self::TYPE_DATE_DASHES, self::TYPE_DATE_LOCAL)))) {
                        if (!empty($this->{$map[self::KEY_ATTRIBUTE]})) { // don't output 1-1-1970
                            $encoder->content($this->_formatDate($this->{$map[self::KEY_ATTRIBUTE]}, $map[self::KEY_TYPE]));
                        }
                    } elseif (isset($map[self::KEY_TYPE]) && $map[self::KEY_TYPE] == self::TYPE_HEX) {
                        $encoder->content(Horde_String::upper(bin2hex($this->{$map[self::KEY_ATTRIBUTE]})));
                    } elseif (isset($map[self::KEY_TYPE]) && $map[self::KEY_TYPE] == self::TYPE_MAPI_STREAM) {
                        $encoder->content($this->{$map[self::KEY_ATTRIBUTE]});
                    } else {
                        $encoder->content(
                            $this->_checkEncoding($this->{$map[self::KEY_ATTRIBUTE]}, $tag));
                    }
                    $encoder->endTag();
                }
            }
        }
    }

    /**
     * Checks if the data needs to be encoded like e.g., when outputing binary
     * data in-line during ITEMOPERATIONS requests. Concrete classes should
     * override this if needed.
     *
     * @param mixed  $data  The data to check. A string or stream resource.
     * @param string $tag   The tag we are outputing.
     *
     * @return mixed  The encoded data. A string or stream resource with
     *                a filter attached.
     */
    protected function _checkEncoding($data, $tag)
    {
        if (is_resource($data)) {
            stream_filter_register('horde_null', 'Horde_Stream_Filter_Null');
            stream_filter_register('horde_eol', 'Horde_Stream_Filter_Eol');
            $this->_streamFilters[] = stream_filter_prepend($data, 'horde_null', STREAM_FILTER_READ);
            $this->_streamFilters[] = stream_filter_prepend($data, 'horde_eol', STREAM_FILTER_READ);

        }
        return $data;
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
     * Helper method to allow default values for unset properties.
     *
     * @param string $name     The property name
     * @param stting $default  The default value to return if $property is empty
     *
     * @return mixed
     */
    protected function _getAttribute($name, $default = null)
    {
        if ((!is_array($this->_properties[$name]) && $this->_properties[$name] !== false) ||
            is_array($this->_properties[$name])) {
            return $this->_properties[$name];
        } else {
            return $default;
        }
    }

    /**
     * Returns whether or not this message actually contains any data to
     * send.
     *
     * @return boolean  True if message is empty, otherwise false.
     * @since  2.34.0
     */
    public function isEmpty()
    {
        foreach ($this->_properties as $value) {
            if (!empty($value)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Oh yeah. This is beautiful. Exchange outputs date fields differently in
     * calendar items and emails. We could just always send one or the other,
     * but unfortunately nokia's 'Mail for exchange' depends on this quirk.
     * So we have to send a different date type depending on where it's used.
     * Used when encoding a date value to send to the client.
     *
     * @param Horde_Date $dt  The Horde_Date object to format
     *                        (should normally be in local tz).
     * @param integer $type   The type to format as: One of
     *     TYPE_DATE or TYPE_DATE_DASHES, TYPE_DATE_LOCAL
     *
     * @return string  The formatted date
     * @throws  InvalidArgumentException
     */
    protected function _formatDate(Horde_Date $dt, $type)
    {
        switch ($type) {
        case self::TYPE_DATE:
            return $dt->setTimezone('UTC')->format('Ymd\THis\Z');
        case self::TYPE_DATE_DASHES:
            return $dt->setTimezone('UTC')->format('Y-m-d\TH:i:s\.000\Z');
        case self::TYPE_DATE_LOCAL:
            return $dt->format('Y-m-d\TH:i:s\.000\Z');
        default:
            throw new InvalidArgumentException('Unidentified DATE_TYPE');
        }
    }

    /**
     * Get a Horde_Date from a timestamp, ensuring it's in the correct format.
     * Used when decoding an incoming date value from the client.
     *
     * @param string $ts  The timestamp
     *
     * @return Horde_Date|boolean  The Horde_Date or false if unable to decode.
     */
    protected function _parseDate($ts)
    {
        if (preg_match('/(\d{4})\D*(\d{2})\D*(\d{2})(T(\d{2})\D*(\d{2})\D*(\d{2})(.\d+)?Z)?$/', $ts)) {
            try {
                return new Horde_Date($ts);
            } catch (Horde_Date_Exception $e) {
            }
        }

        return false;
    }

    /**
     * Function which converts a hex entryid to a binary entryid.
     *
     * @param string $data  The hexadecimal string
     *
     * @return string  The binary data
     */
    private static function _hex2bin($data)
    {
        $len = strlen($data);
        $newdata = '';
        for ($i = 0; $i < $len; $i += 2) {
            $newdata .= pack('C', hexdec(substr($data, $i, 2)));
        }

        return $newdata;
    }

}
