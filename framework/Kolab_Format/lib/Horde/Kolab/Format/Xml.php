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
 * @link     http://www.horde.org/libraries/Horde_Kolab_Format
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
 * @link     http://www.horde.org/libraries/Horde_Kolab_Format
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
    const TYPE_DATE = 'Horde_Kolab_Format_Xml_Type_Date';

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
     * Specific data fields for the contact object
     *
     * @var array
     */
    protected $_fields_specific;

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
    )
    {
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
        $this->_getRoot($params)->load(
            $this->_root_name,
            $object,
            $this->_xmldoc,
            $this->_factory->createXmlHelper($this->_xmldoc),
            $params
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
        $this->_getRoot($params)->save(
            $this->_root_name,
            $object,
            $this->_xmldoc,
            $this->_factory->createXmlHelper($this->_xmldoc),
            $params
        );
        return $this->_xmldoc->saveXML();
    }

    /**
     * Return the API version of the data structures that are being used for in-
     * and output.
     *
     * @since Horde_Kolab_Format 1.1.0
     *
     * @return int The version number;
     */
    public function getVersion()
    {
        return $this->_version;
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
     * @param array  $params Additional parameters.
     *
     * @return Horde_Kolab_Xml_Type_Root The root handler.
     */
    private function _getRoot($params = array())
    {
        return $this->_factory->createXmlType(self::TYPE_ROOT, $params);
    }
}
