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
    const TYPE_STRING = 0;

    /**
     * Defines a XML value as integer type
     */
    const TYPE_INTEGER = 1;

    /**
     * Defines a XML value as boolean type
     */
    const TYPE_BOOLEAN = 2;

    /**
     * Defines a XML value as date type
     */
    const TYPE_DATE = 3;

    /**
     * Defines a XML value as datetime type
     */
    const TYPE_DATETIME = 4;

    /**
     * Defines a XML value as date or datetime type
     */
    const TYPE_DATE_OR_DATETIME = 5;

    /**
     * Defines a XML value as color type
     */
    const TYPE_COLOR = 6;

    /**
     * Defines a XML value as composite value type
     */
    const TYPE_COMPOSITE = 7;

    /**
     * Defines a XML value as array type
     */
    const TYPE_MULTIPLE = 8;

    /**
     * Defines a XML value as raw XML
     */
    const TYPE_XML = 9;

    /**
     * Represents the Kolab format root node
     */
    const TYPE_ROOT = 10;

    /**
     * Represents the Kolab object UID value
     */
    const TYPE_UID = 11;

    /**
     * Represents the creation date
     */
    const TYPE_CREATION_DATE = 12;

    /**
     * Represents the modification date
     */
    const TYPE_MODIFICATION_DATE = 13;

    /**
     * Represents the product id
     */
    const TYPE_PRODUCT_ID = 14;

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
     *
     * @todo Make protected (fix the XmlTest for that)
     */
    public $_xmldoc = null;

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
     * Kolab format root element.
     *
     * @var Horde_Kolab_Format_Xml_Type_Root
     */
    protected $_root;

    /**
     * Basic fields in any Kolab object
     *
     * @var array
     */
    protected $_fields_basic;

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

        /* Generic fields, in kolab format specification order */
        $this->_fields_basic = array(
            'uid' => self::TYPE_UID,
            'body' => array(
                'type'    => self::TYPE_STRING,
                'value'   => self::VALUE_DEFAULT,
                'default' => '',
            ),
            'categories' => array(
                'type'    => self::TYPE_STRING,
                'value'   => self::VALUE_DEFAULT,
                'default' => '',
            ),
            'creation-date' => self::TYPE_CREATION_DATE,
            'last-modification-date' => self::TYPE_MODIFICATION_DATE,
            'sensitivity' => array(
                'type'    => self::TYPE_STRING,
                'value'   => self::VALUE_DEFAULT,
                'default' => 'public',
            ),
            'inline-attachment' => array(
                'type'    => self::TYPE_MULTIPLE,
                'value'   => self::VALUE_MAYBE_MISSING,
                'array'   => array(
                    'type'  => self::TYPE_STRING,
                    'value' => self::VALUE_MAYBE_MISSING,
                ),
            ),
            'link-attachment' => array(
                'type'    => self::TYPE_MULTIPLE,
                'value'   => self::VALUE_MAYBE_MISSING,
                'array'   => array(
                    'type'  => self::TYPE_STRING,
                    'value' => self::VALUE_MAYBE_MISSING,
                ),
            ),
            'product-id' => self::TYPE_PRODUCT_ID,
        );
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
        $this->_xmldoc = $this->_parser->parse($xml, $options);
        $params = array_merge(
            $options,
            array(
                'type' => $this->_root_name,
                'version' => $this->_root_version
            )
        );
        $this->_root = $this->_factory->createXmlType(
            self::TYPE_ROOT, $this->_xmldoc, $params
        );
        $rootNode = $this->_root->load();

        // fresh object data
        $object = array();

        $result = $this->_loadArray($rootNode, $this->_fields_basic, $options);
        $object = array_merge($object, $result);

        $result = $this->_load($rootNode, $options);
        $object = array_merge($object, $result);

        return $object;
    }

    /**
     * Load the groupware object based on the specifc XML values.
     *
     * @param array &$children An array of XML nodes.
     *
     * @return array The data array representing the object.
     *
     * @throws Horde_Kolab_Format_Exception If parsing the XML data failed.
     */
    protected function _load($node, $options = array())
    {
        if (!empty($this->_fields_specific)) {
            return $this->_loadArray($node, $this->_fields_specific, $options);
        } else {
            return array();
        }
    }

    /**
     * Load an array with data from the XML nodes.
     *
     * @param array &$children An array of XML nodes.
     * @param array $fields    The fields to populate in the object array.
     *
     * @return boolean True on success.
     *
     * @throws Horde_Kolab_Format_Exception If parsing the XML data failed.
     */
    protected function _loadArray($parent_node, $fields, $options = array())
    {
        $object = array();

        // basic fields below the root node
        foreach ($fields as $field => $params) {
            if (!is_array($params)) {
                $type_options = array_merge(
                    $options,
                    array(
                        'type' => $this->_root_name,
                        'version' => $this->_root_version,
                        'api_version' => $this->_version,
                        'factory' => $this->_factory,
                    )
                );
                $node = $this->_factory->createXmlType(
                    $params, $this->_xmldoc, $type_options
                );
                $node->load($field, $object, $parent_node);
            } else {
                $type_options = array_merge(
                    $options,
                    $params,
                    array(
                        'type' => $this->_root_name,
                        'version' => $this->_root_version,
                        'api_version' => $this->_version,
                        'factory' => $this->_factory,
                    )
                );
                $node = $this->_factory->createXmlType(
                    $params['type'], $this->_xmldoc, $type_options
                );
                $node->load($field, $object, $parent_node);
            }
        }
        return $object;
    }

    /**
     * Get the text content of the named data node among the specified
     * children.
     *
     * @param array  &$children The children to search.
     * @param string $name      The name of the node to return.
     * @param array  $params    Parameters for the data conversion.
     *
     * @return string The content of the specified node or an empty
     *                string.
     *
     * @throws Horde_Kolab_Format_Exception If parsing the XML data failed.
     *
     * @todo Make protected (fix the XmlTest for that)
     */
    public function _getXmlData(&$children, $name, $params)
    {
        if ($params['type'] == self::TYPE_MULTIPLE) {
            $result = array();
            foreach ($children as $child) {
                if ($child->nodeType == XML_ELEMENT_NODE && $child->tagName == $name) {
                    $child_a  = array($child);
                    $value    = $this->_getXmlData($child_a, $name,
                                                   $params['array']);
                    $result[] = $value;
                }
            }
            return $result;
        }

        $value   = null;
        $missing = false;

        // identify the child node
        $child = $this->_findNode($children, $name);

        // Handle empty values
        if (!$child) {
            if ($params['value'] == self::VALUE_MAYBE_MISSING) {
                // 'MAYBE_MISSING' means we should return null
                return null;
            } elseif ($params['value'] == self::VALUE_NOT_EMPTY) {
                // May not be empty. Return an error
                throw new Horde_Kolab_Format_Exception_MissingValue($name);
            } elseif ($params['value'] == self::VALUE_DEFAULT) {
                // Return the default
                return $params['default'];
            } elseif ($params['value'] == self::VALUE_CALCULATED) {
                $missing = true;
            }
        }

        // Do we need to calculate the value?
        if ($params['value'] == self::VALUE_CALCULATED && isset($params['load'])) {
            if (method_exists($this, '_load' . $params['load'])) {
                $value = call_user_func(array($this, '_load' . $params['load']),
                                        $child, $missing);
            } else {
                throw new Horde_Kolab_Format_Exception(sprintf("Kolab XML: Missing function %s!",
                                                               $params['load']));
            }
        } elseif ($params['type'] == self::TYPE_COMPOSITE) {
            return $this->_loadArray($child, $params['array']);
        } else {
            return $this->_loadDefault($child, $params);
        }

        // Nothing specified. Return the value as it is.
        return $value;
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
            $this->_xmldoc = $this->_parser->getDocument();
        } else {
            $parse_options = $options;
            unset($parse_options['previous']);
            $this->_xmldoc = $this->_parser->parse(
                $options['previous'], $parse_options
            );
        }
        $params = array_merge(
            $options,
            array(
                'type' => $this->_root_name,
                'version' => $this->_root_version
            )
        );
        $this->_root = $this->_factory->createXmlType(
            self::TYPE_ROOT, $this->_xmldoc, $params
        );
        $rootNode = $this->_root->save();

        $this->_saveArray($rootNode, $object, $this->_fields_basic, $options);
        $this->_save($rootNode, $object, $options);

        return $this->_xmldoc->saveXML();
    }

    /**
     * Save the specific XML values.
     *
     * @param array &$root  The XML document root.
     * @param array $object The resulting data array.
     *
     * @return boolean True on success.
     *
     * @throws Horde_Kolab_Format_Exception If converting the data to XML failed.
     */
    protected function _save(&$root, $object, $options)
    {
        if (!empty($this->_fields_specific)) {
            $this->_saveArray($root, $object, $this->_fields_specific, $options);
        }
        return true;
    }

    /**
     * Save a data array to XML nodes.
     *
     * @param array   $root   The XML document root.
     * @param array   $object The data array.
     * @param array   $fields The fields to write into the XML object.
     * @param boolean $append Should the nodes be appended?
     *
     * @return boolean True on success.
     *
     * @throws Horde_Kolab_Format_Exception If converting the data to XML failed.
     */
    protected function _saveArray($root, $object, $fields, $options = array(), $append = false)
    {
        // basic fields below the root node
        foreach ($fields as $field => $params) {
            $this->_updateNode($root, $object, $field, $params, $options, $append);
        }
        return true;
    }

    /**
     * Update the specified node.
     *
     * @param DOMNode $parent_node The parent node of the node that
     *                             should be updated.
     * @param array   $attributes  The data array that holds all
     *                             attribute values.
     * @param string  $name        The name of the the attribute
     *                             to be updated.
     * @param array   $params      Parameters for saving the node
     * @param boolean $append      Should the node be appended?
     *
     * @return DOMNode The new/updated child node.
     *
     * @throws Horde_Kolab_Format_Exception If converting the data to XML failed.
     *
     * @todo Make protected (fix the XmlTest for that)
     */
    public function _updateNode($parent_node, $attributes, $name, $params, $options,
                                $append = false)
    {
        if (!is_array($params)) {
            $type_options = array_merge(
                $options,
                array(
                    'type' => $this->_root_name,
                    'version' => $this->_root_version,
                    'api_version' => $this->_version,
                    'factory' => $this->_factory,
                )
            );
            $node = $this->_factory->createXmlType(
                $params, $this->_xmldoc, $type_options
            );
            $node->save($name, $attributes, $parent_node);
        } else {
            $type_options = array_merge(
                $options,
                $params,
                array(
                    'type' => $this->_root_name,
                    'version' => $this->_root_version,
                    'api_version' => $this->_version,
                    'factory' => $this->_factory,
                )
            );
            $node = $this->_factory->createXmlType(
                $params['type'], $this->_xmldoc, $type_options
            );
            $node->save($name, $attributes, $parent_node);
        }
        return;

        $value   = null;
        $missing = false;

        // Handle empty values
        if (!isset($attributes[$name])) {
            // Do we have information if this may be empty?
            if ($params['value'] == self::VALUE_DEFAULT) {
                // Use the default
                $value = $params['default'];
            } elseif ($params['value'] == self::VALUE_NOT_EMPTY) {
                // May not be empty. Return an error
                throw new Horde_Kolab_Format_Exception_MissingValue($name);
            } elseif ($params['value'] == self::VALUE_MAYBE_MISSING) {
                /**
                 * 'MAYBE_MISSING' means we should not create an XML
                 * node here
                 */
                $this->_removeNodes($parent_node, $name);
                return false;
            } elseif ($params['value'] == self::VALUE_CALCULATED) {
                $missing = true;
            }
        } else {
            $value = $attributes[$name];
        }

        if ($params['value'] == self::VALUE_CALCULATED) {
            // Calculate the value
            if (method_exists($this, '_save' . $params['save'])) {
                return call_user_func(array($this, '_save' . $params['save']),
                                      $parent_node, $name, $value, $missing);
            } else {
                throw new Horde_Kolab_Format_Exception(sprintf("Kolab XML: Missing function %s!",
                                                  $params['save']));
            }
        } elseif ($params['type'] == self::TYPE_COMPOSITE) {
            // Possibly remove the old node first
            if (!$append) {
                $this->_removeNodes($parent_node, $name);
            }

            // Create a new complex node
            $composite_node = $this->_xmldoc->createElement($name);
            $composite_node = $parent_node->appendChild($composite_node);
            return $this->_saveArray($composite_node, $value, $params['array'], $options);
        } elseif ($params['type'] == self::TYPE_MULTIPLE) {
            // Remove the old nodes first
            $this->_removeNodes($parent_node, $name);

            // Add the new nodes
            foreach ($value as $add_node) {
                $this->_saveArray($parent_node,
                                  array($name => $add_node),
                                  array($name => $params['array']),
                                  $options,
                                  true);
            }
            return true;
        } else {
            return $this->_saveDefault($parent_node, $name, $value, $params,
                                       $append);
        }
    }

    /**
     * Create a text node.
     *
     * @param DOMNode $parent The parent of the new node.
     * @param string  $name   The name of the child node to create.
     * @param string  $value  The value of the child node to create.
     *
     * @return DOMNode The new node.
     */
    protected function _createTextNode($parent, $name, $value)
    {
        $node = $this->_xmldoc->createElement($name);
        $node = $parent->appendChild($node);

        // content
        $text = $this->_xmldoc->createTextNode($value);
        $text = $node->appendChild($text);

        return $node;
    }

    /**
     * Return the named node among a list of nodes.
     *
     * @param DOMNodeList $nodes The list of nodes.
     * @param string      $name  The name of the node to return.
     *
     * @return mixed The named DOMNode or false if no node was found.
     */
    protected function _findNode($nodes, $name)
    {
        foreach ($nodes as $node) {
            if ($node->nodeType == XML_ELEMENT_NODE && $node->tagName == $name) {
                return $node;
            }
        }
        return false;
    }

    /**
     * Retrieve a named child from a named parent if it has the given
     * value.
     *
     * @param array  $nodes       The list of nodes.
     * @param string $parent_name The name of the parent node.
     * @param string $child_name  The name of the child node.
     * @param string $value       The value of the child node
     *
     * @return mixed The specified DOMNode or false if no node was found.
     */
    protected function _findNodeByChildData($nodes, $parent_name, $child_name,
                                            $value)
    {
        foreach ($nodes as $node) {
            if ($node->nodeType == XML_ELEMENT_NODE
                && $node->tagName == $parent_name) {
                $children = $node->childNodes;
                foreach ($children as $child) {
                    if ($child->nodeType == XML_ELEMENT_NODE
                        && $child->tagName == $child_name
                        && $child->textContent == $value) {
                        return $node;
                    }
                }
            }
        }

        return false;
    }

    /**
     * Retrieve the content of a DOMNode.
     *
     * @param DOMNode $node The node that should be read.
     *
     * @return string The content of the node.
     */
    protected function _getNodeContent($node)
    {
        return $node->textContent;
    }


    /**
     * Create a new named node on a parent node.
     *
     * @param DOMNode $parent The parent node.
     * @param string  $name   The name of the new child node.
     *
     * @return DOMNode The new child node.
     */
    protected function _createChildNode($parent, $name)
    {
        $node = $this->_xmldoc->createElement($name);
        $node = $parent->appendChild($node);

        return $node;
    }

    /**
     * Remove named nodes from a parent node.
     *
     * @param DOMNode $parent_node The parent node.
     * @param string  $name        The name of the children to be removed.
     *
     * @return NULL
     */
    protected function _removeNodes($parent_node, $name)
    {
        while ($old_node = $this->_findNode($parent_node->childNodes, $name)) {
            $parent_node->removeChild($old_node);
        }
    }

    /**
     * Create a new named node on a parent node if it is not already
     * present in the given children.
     *
     * @param DOMNode $parent   The parent node.
     * @param array   $children The children that might already
     *                          contain the node.
     * @param string  $name     The name of the new child node.
     *
     * @return DOMNode The new or already existing child node.
     */
    protected function _createOrFindChildNode($parent, $children, $name)
    {
        // look for existing node
        $old_node = $this->_findNode($children, $name);
        if ($old_node !== false) {
            return $old_node;
        }

        // create new parent node
        return $this->_createChildNode($parent, $name);
    }

    /**
     * Load the different XML types.
     *
     * @param string $node   The node to load the data from
     * @param array  $params Parameters for loading the value
     *
     * @return string The loaded value.
     *
     * @throws Horde_Kolab_Format_Exception If converting the data from XML failed.
     */
    protected function _loadDefault($node, $params)
    {
        $content = $this->_getNodeContent($node);

        switch($params['type']) {
        case self::TYPE_DATE:
            return Horde_Kolab_Format_Date::decodeDate($content);

        case self::TYPE_DATETIME:
            return Horde_Kolab_Format_Date::decodeDateTime($content);

        case self::TYPE_DATE_OR_DATETIME:
            return Horde_Kolab_Format_Date::decodeDateOrDateTime($content);

        case self::TYPE_INTEGER:
            return (int) $content;

        case self::TYPE_BOOLEAN:
            return (bool) $content;

        default:
            // Strings and colors are returned as they are
            return $content;
        }
    }

    /**
     * Save a data array as a XML node attached to the given parent node.
     *
     * @param DOMNode $parent_node The parent node to attach
     *                             the child to
     * @param string  $name        The name of the node
     * @param mixed   $value       The value to store
     * @param array   $params      Field parameters
     * @param boolean $append      Should the node be appended?
     *
     * @return DOMNode The new child node.
     *
     * @throws Horde_Kolab_Format_Exception If converting the data to XML failed.
     */
    protected function _saveDefault($parent_node, $name, $value, $params,
                                    $append = false)
    {
        switch ($params['type']) {
        case self::TYPE_DATE:
            $value = Horde_Kolab_Format_Date::encodeDate($value);
            break;

        case self::TYPE_DATETIME:
        case self::TYPE_DATE_OR_DATETIME:
            $value = Horde_Kolab_Format_Date::encodeDateTime($value);
            break;

        case self::TYPE_INTEGER:
            $value = (string) $value;
            break;

        case self::TYPE_BOOLEAN:
            if ($value) {
                $value = 'true';
            } else {
                $value = 'false';
            }

            break;
        case self::TYPE_XML:
            $type = $this->_factory->createXmlType(self::TYPE_XML, $this->_xmldoc);
            $type->save($parent_node, $value);
        }

        if (!$append) {
            $this->_removeNodes($parent_node, $name);
        }

        // create the node
        return $this->_createTextNode($parent_node, $name, $value);
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
