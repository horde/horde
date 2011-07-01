<?php
/**
 * Implementation of the Kolab XML format.
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_Format
 * @author   Thomas Jarosch <thomas.jarosch@intra2net.com>
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Kolab_Format
 */

/**
 * Kolab XML to array hash converter.
 *
 * For implementing a new format type you will have to inherit this
 * class and provide a _load/_save function.
 *
 * Copyright 2007-2009 Klarälvdalens Datakonsult AB
 * Copyright 2010-2011 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you did not
 * receive this file, see
 * http://www.horde.org/licenses/lgpl21.
 *
 * @category Kolab
 * @package  Kolab_Format
 * @author   Thomas Jarosch <thomas.jarosch@intra2net.com>
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Kolab_Format
 */
class Horde_Kolab_Format_Xml implements Horde_Kolab_Format
{
    /**
     * Defines a XML value that should get a default value if missing
     */
    const PRODUCT_ID = __CLASS__;

    /**
     * Defines a XML value that should get a default value if missing
     */
    const VALUE_DEFAULT = 0;

    /**
     * Defines a XML value that may be missing
     */
    const VALUE_MAYBE_MISSING = 1;

    /**
     * Defines a XML value that may not be missing
     */
    const VALUE_NOT_EMPTY = 2;

    /**
     * Defines a XML value that will be calculated by its own function
     */
    const VALUE_CALCULATED = 3;

    /**
     * Defines a XML value as string type
     */
    const TYPE_STRING = 'Horde_Kolab_Format_Xml_Type_String';

    /**
     * Defines a XML value as integer type
     */
    const TYPE_INTEGER = 'Horde_Kolab_Format_Xml_Type_Integer';

    /**
     * Defines a XML value as boolean type
     */
    const TYPE_BOOLEAN = 'Horde_Kolab_Format_Xml_Type_Boolean';

    /**
     * Defines a XML value as date type
     */
    const TYPE_DATE = 'Horde_Kolab_Format_Xml_Type_DateTime';

    /**
     * Defines a XML value as datetime type
     */
    const TYPE_DATETIME = 'Horde_Kolab_Format_Xml_Type_DateTime';

    /**
     * Defines a XML value as date or datetime type
     */
    const TYPE_DATE_OR_DATETIME = 'Horde_Kolab_Format_Xml_Type_DateTime';

    /**
     * Defines a XML value as color type
     */
    const TYPE_COLOR = 'Horde_Kolab_Format_Xml_Type_Color';

    /**
     * Defines a XML value as composite value type
     */
    const TYPE_COMPOSITE = 'Horde_Kolab_Format_Xml_Type_Composite';

    /**
     * Defines a XML value as array type
     */
    const TYPE_MULTIPLE = 'Horde_Kolab_Format_Xml_Type_Multiple';

    /**
     * Defines a XML value as raw XML
     */
    const TYPE_XML = 'Horde_Kolab_Format_Xml_Type_XmlAppend';

    /**
     * Represents the Kolab format root node
     */
    const TYPE_ROOT = 'Horde_Kolab_Format_Xml_Type_Root';

    /**
     * The parser dealing with the input.
     *
     * @var Horde_Kolab_Format_Xml_Parser
     */
    protected $_parser;

    /**
     * The factory for additional objects.
     *
     * @var Horde_Kolab_Format_Factory
     */
    protected $_factory;

    /**
     * Requested version of the data array to return
     *
     * @var int
     */
    protected $_version = 2;

    /**
     * The XML document this driver works with.
     *
     * @var DOMDocument
     */
    protected $_xmldoc = null;

    /**
     * The name of the root element.
     *
     * @var string
     */
    protected $_root_name = 'kolab';

    /**
     * Kolab format version of the root element.
     *
     * @var string
     */
    protected $_root_version = '1.0';

    /**
     * Fields for a simple person
     *
     * @var array
     *
     * @todo Make protected (fix the XmlTest for that)
     */
    public $_fields_simple_person = array(
        'type'    => self::TYPE_COMPOSITE,
        'value'   => self::VALUE_MAYBE_MISSING,
        'array'   => array(
            'display-name' => array(
                'type'    => self::TYPE_STRING,
                'value'   => self::VALUE_DEFAULT,
                'default' => '',
            ),
            'smtp-address' => array(
                'type'    => self::TYPE_STRING,
                'value'   => self::VALUE_DEFAULT,
                'default' => '',
            ),
            'uid' => array(
                'type'    => self::TYPE_STRING,
                'value'   => self::VALUE_DEFAULT,
                'default' => '',
            ),
        ),
    );

    /**
     * Fields for an attendee
     *
     * @var array
     *
     * @todo Make protected (fix the XmlTest for that)
     */
    public $_fields_attendee = array(
        'type'    => self::TYPE_MULTIPLE,
        'value'   => self::VALUE_DEFAULT,
        'default' => array(),
        'array'   => array(
            'type'    => self::TYPE_COMPOSITE,
            'value'   => self::VALUE_MAYBE_MISSING,
            'array'   => array(
                'display-name' => array(
                    'type'    => self::TYPE_STRING,
                    'value'   => self::VALUE_DEFAULT,
                    'default' => '',
                ),
                'smtp-address' => array(
                    'type'    => self::TYPE_STRING,
                    'value'   => self::VALUE_DEFAULT,
                    'default' => '',
                ),
                'status' => array(
                    'type'    => self::TYPE_STRING,
                    'value'   => self::VALUE_DEFAULT,
                    'default' => 'none',
                ),
                'request-response' => array(
                    'type'    => self::TYPE_BOOLEAN,
                    'value'   => self::VALUE_DEFAULT,
                    'default' => true,
                ),
                'role' => array(
                    'type'    => self::TYPE_STRING,
                    'value'   => self::VALUE_DEFAULT,
                    'default' => 'required',
                ),
            ),
        ),
    );

    /**
     * Fields for a recurrence
     *
     * @var array
     */
    protected $_fields_recurrence = array(
        // Attribute on root node: cycle
        // Attribute on root node: type
        'interval' => array(
            'type'    => self::TYPE_INTEGER,
            'value'   => self::VALUE_MAYBE_MISSING,
        ),
        'day' => array(
            'type'    => self::TYPE_MULTIPLE,
            'value'   => self::VALUE_MAYBE_MISSING,
            'array'   => array(
                'type' => self::TYPE_STRING,
                'value' => self::VALUE_MAYBE_MISSING,
            ),
        ),
        'daynumber' => array(
            'type'    => self::TYPE_INTEGER,
            'value'   => self::VALUE_MAYBE_MISSING,
        ),
        'month' => array(
            'type'    => self::TYPE_STRING,
            'value'   => self::VALUE_MAYBE_MISSING,
        ),
        // Attribute on range: type
        'range' => array(
            'type'    => self::TYPE_STRING,
            'value'   => self::VALUE_DEFAULT,
            'default' => '',
        ),
        'exclusion' => array(
            'type'    => self::TYPE_MULTIPLE,
            'value'   => self::VALUE_MAYBE_MISSING,
            'array'   => array(
                'type' => self::TYPE_STRING,
                'value' => self::VALUE_MAYBE_MISSING,
            ),
        ),
        'complete' => array(
            'type'    => self::TYPE_MULTIPLE,
            'value'   => self::VALUE_MAYBE_MISSING,
            'array'   => array(
                'type' => self::TYPE_STRING,
                'value' => self::VALUE_MAYBE_MISSING,
            ),
        ),
    );

    /**
     * Constructor
     *
     * @param Horde_Kolab_Format_Xml_Parser $parser  The XML parser.
     * @param Horde_Kolab_Format_Factory    $factory The factory for helper
     *                                               objects.
     * @param array                         $params  Any additional options.
     */
    public function __construct(
        Horde_Kolab_Format_Xml_Parser $parser,
        Horde_Kolab_Format_Factory $factory,
        $params = null
    ) {
        $this->_parser = $parser;
        $this->_factory = $factory;

        if (is_array($params) && isset($params['version'])) {
            $this->_version = $params['version'];
        } else {
            $this->_version = 2;
        }

    }

    /**
     * Throw the parser instance away.
     *
     * @return NULL
     */
    private function _refreshParser()
    {
        $this->_parser = null;
    }

    /**
     * Fetch the XML parser.
     *
     * @return Horde_Kolab_Format_Xml_Parser The parser.
     */
    private function _getParser()
    {
        if ($this->_parser === null) {
            $this->_parser = $this->_factory->createXmlParser();
        }
        return $this->_parser;
    }

    /**
     * Load an object based on the given XML stream. The stream may only contain
     * UTF-8 data.
     *
     * @param resource $xml     The XML stream of the message.
     * @param array    $options Additional options when parsing the XML.
     * <pre>
     * - relaxed: Relaxed error checking (default: false)
     * </pre>
     *
     * @return array The data array representing the object.
     *
     * @throws Horde_Kolab_Format_Exception If parsing the XML data failed.
     *
     * @todo Check encoding of the returned array. It seems to be ISO-8859-1 at
     * the moment and UTF-8 would seem more appropriate.
     */
    public function load($xml, $options = array())
    {
        $this->_xmldoc = $this->_getParser()->parse($xml, $options);
        $this->_refreshParser();
       
        $params = $this->_getParameters($options);
        $this->_getRoot()->load(
            $this->_root_name, $object, $this->_xmldoc, $params
        );
        return $object;
    }

    /**
     * Convert the data to a XML stream. Strings contained in the data array may
     * only be provided as UTF-8 data.
     *
     * @param array $object  The data array representing the object.
     * @param array $options Additional options when parsing the XML.
     * <pre>
     * - previos: The previous XML text (default: empty string)
     * - relaxed: Relaxed error checking (default: false)
     * </pre>
     *
     * @return resource The data as XML stream.
     *
     * @throws Horde_Kolab_Format_Exception If converting the data to XML failed.
     */
    public function save($object, $options = array())
    {
        if (!isset($options['previous'])) {
            $this->_xmldoc = $this->_getParser()->getDocument();
        } else {
            $parse_options = $options;
            unset($parse_options['previous']);
            $this->_xmldoc = $this->_getParser()->parse(
                $options['previous'], $parse_options
            );
        }
        $this->_refreshParser();

        $params = $this->_getParameters($options);
        $this->_getRoot()->save(
            $this->_root_name, $object, $this->_xmldoc, $params
        );
        return $this->_xmldoc->saveXML();
    }

    /**
     * Generate the internal parameter list for this operation.
     *
     * @param array $options The options for this operation.
     *
     * @return array
     */
    private function _getParameters($options)
    {
        $params = array_merge(
            $options,
            array(
                'expected-version' => $this->_root_version,
                'api-version' => $this->_version
            )
        );
        if (!empty($this->_fields_specific)) {
            $params['attributes-specific'] = $this->_fields_specific;
        }
        return $params;
    }

    /**
     * Return the root handler.
     *
     * @return Horde_Kolab_Xml_Type_Root The root handler.
     */
    private function _getRoot()
    {
        return $this->_factory->createXmlType(self::TYPE_ROOT);
    }

    /**
     * Load recurrence information.
     *
     * @param DOMNode $node    The original node if set.
     * @param boolean $missing Has the node been missing?
     *
     * @return array The recurrence information.
     *
     * @throws Horde_Kolab_Format_Exception If converting the data from XML failed.
     */
    protected function _loadRecurrence($node, $missing)
    {
        if ($missing) {
            return null;
        }

        // Collect all child nodes
        $children = $node->childNodes;

        $recurrence = $this->_loadArray($node, $this->_fields_recurrence);

        // Get the cycle type (must be present)
        $recurrence['cycle'] = $node->getAttribute('cycle');
        // Get the sub type (may be present)
        $recurrence['type'] = $node->getAttribute('type');

        // Exclusions.
        if (isset($recurrence['exclusion'])) {
            $exceptions = array();
            foreach ($recurrence['exclusion'] as $exclusion) {
                if (!empty($exclusion)) {
                    list($year, $month, $mday) = sscanf($exclusion, '%04d-%02d-%02d');

                    $exceptions[] = sprintf('%04d%02d%02d', $year, $month, $mday);
                }
            }
            $recurrence['exceptions'] = $exceptions;
        }

        // Completed dates.
        if (isset($recurrence['complete'])) {
            $completions = array();
            foreach ($recurrence['complete'] as $complete) {
                if (!empty($complete)) {
                    list($year, $month, $mday) = sscanf($complete, '%04d-%02d-%02d');

                    $completions[] = sprintf('%04d%02d%02d', $year, $month, $mday);
                }
            }
            $recurrence['completions'] = $completions;
        }

        // Range is special
        foreach ($children as $child) {
            if ($child->tagName == 'range') {
                $recurrence['range-type'] = $child->getAttribute('type');
            }
        }

        if (isset($recurrence['range']) && isset($recurrence['range-type'])
            && $recurrence['range-type'] == 'date') {
            $recurrence['range'] = Horde_Kolab_Format_Date::decodeDate($recurrence['range']);
        }

        // Sanity check
        $valid = $this->_validateRecurrence($recurrence);

        return $recurrence;
    }

    /**
     * Validate recurrence hash information.
     *
     * @param array &$recurrence Recurrence hash loaded from XML.
     *
     * @return boolean True on success.
     *
     * @throws Horde_Kolab_Format_Exception If the recurrence data is invalid.
     */
    protected function _validateRecurrence(&$recurrence)
    {
        if (!isset($recurrence['cycle'])) {
              throw new Horde_Kolab_Format_Exception('recurrence tag error: cycle attribute missing');
        }

        if (!isset($recurrence['interval'])) {
              throw new Horde_Kolab_Format_Exception('recurrence tag error: interval tag missing');
        }
        $interval = $recurrence['interval'];
        if ($interval < 0) {
            throw new Horde_Kolab_Format_Exception('recurrence tag error: interval cannot be below zero: '
                                      . $interval);
        }

        if ($recurrence['cycle'] == 'weekly') {
            // Check for <day>
            if (!isset($recurrence['day']) || count($recurrence['day']) == 0) {
                throw new Horde_Kolab_Format_Exception('recurrence tag error: day tag missing for weekly recurrence');
            }
        }

        // The code below is only for monthly or yearly recurrences
        if ($recurrence['cycle'] != 'monthly'
            && $recurrence['cycle'] != 'yearly') {
            return true;
        }

        if (!isset($recurrence['type'])) {
            throw new Horde_Kolab_Format_Exception('recurrence tag error: type attribute missing');
        }

        if (!isset($recurrence['daynumber'])) {
            throw new Horde_Kolab_Format_Exception('recurrence tag error: daynumber tag missing');
        }
        $daynumber = $recurrence['daynumber'];
        if ($daynumber < 0) {
            throw new Horde_Kolab_Format_Exception('recurrence tag error: daynumber cannot be below zero: '
                                      . $daynumber);
        }

        if ($recurrence['type'] == 'daynumber') {
            if ($recurrence['cycle'] == 'yearly' && $daynumber > 366) {
                throw new Horde_Kolab_Format_Exception('recurrence tag error: daynumber cannot be larger than 366 for yearly recurrences: ' . $daynumber);
            } else if ($recurrence['cycle'] == 'monthly' && $daynumber > 31) {
                throw new Horde_Kolab_Format_Exception('recurrence tag error: daynumber cannot be larger than 31 for monthly recurrences: ' . $daynumber);
            }
        } else if ($recurrence['type'] == 'weekday') {
            // daynumber is the week of the month
            if ($daynumber > 5) {
                throw new Horde_Kolab_Format_Exception('recurrence tag error: daynumber cannot be larger than 5 for type weekday: ' . $daynumber);
            }

            // Check for <day>
            if (!isset($recurrence['day']) || count($recurrence['day']) == 0) {
                throw new Horde_Kolab_Format_Exception('recurrence tag error: day tag missing for type weekday');
            }
        }

        if (($recurrence['type'] == 'monthday' || $recurrence['type'] == 'yearday')
            && $recurrence['cycle'] == 'monthly') {
            throw new Horde_Kolab_Format_Exception('recurrence tag error: type monthday/yearday is only allowed for yearly recurrences');
        }

        if ($recurrence['cycle'] == 'yearly') {
            if ($recurrence['type'] == 'monthday') {
                // daynumber and month
                if (!isset($recurrence['month'])) {
                    throw new Horde_Kolab_Format_Exception('recurrence tag error: month tag missing for type monthday');
                }
                if ($daynumber > 31) {
                    throw new Horde_Kolab_Format_Exception('recurrence tag error: daynumber cannot be larger than 31 for type monthday: ' . $daynumber);
                }
            } else if ($recurrence['type'] == 'yearday') {
                if ($daynumber > 366) {
                    throw new Horde_Kolab_Format_Exception('recurrence tag error: daynumber cannot be larger than 366 for type yearday: ' . $daynumber);
                }
            }
        }

        return true;
    }

    /**
     * Save recurrence information.
     *
     * @param DOMNode $parent_node The parent node to attach
     *                             the child to.
     * @param string  $name        The name of the node.
     * @param mixed   $value       The value to store.
     * @param boolean $missing     Has the value been missing?
     *
     * @return DOMNode The new child node.
     */
    protected function _saveRecurrence($parent_node, $name, $value, $missing)
    {
        $this->_removeNodes($parent_node, $name);

        if (empty($value)) {
            return false;
        }

        // Exclusions.
        if (isset($value['exceptions'])) {
            $exclusions = array();
            foreach ($value['exceptions'] as $exclusion) {
                if (!empty($exclusion)) {
                    list($year, $month, $mday) = sscanf($exclusion, '%04d%02d%02d');
                    $exclusions[]              = "$year-$month-$mday";
                }
            }
            $value['exclusion'] = $exclusions;
        }

        // Completed dates.
        if (isset($value['completions'])) {
            $completions = array();
            foreach ($value['completions'] as $complete) {
                if (!empty($complete)) {
                    list($year, $month, $mday) = sscanf($complete, '%04d%02d%02d');
                    $completions[]             = "$year-$month-$mday";
                }
            }
            $value['complete'] = $completions;
        }

        if (isset($value['range'])
            && isset($value['range-type']) && $value['range-type'] == 'date') {
            $value['range'] = Horde_Kolab_Format_Date::encodeDate($value['range']);
        }

        $r_node = $this->_xmldoc->createElement($name);
        $r_node = $parent_node->appendChild($r_node);

        // Save normal fields
        $this->_saveArray($r_node, $value, $this->_fields_recurrence);

        // Add attributes
        $r_node->setAttribute('cycle', $value['cycle']);
        if (isset($value['type'])) {
            $r_node->setAttribute('type', $value['type']);
        }

        $child = $this->_findNode($r_node->childNodes, 'range');
        if ($child) {
            $child->setAttribute('type', $value['range-type']);
        }

        return $r_node;
    }
}
