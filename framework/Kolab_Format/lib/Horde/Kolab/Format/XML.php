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
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Server
 */

/** We need the DOM library for XML access. */
require_once 'Horde/DOM.php';

/** We need the NLS library for language support. */
require_once 'Horde/NLS.php';

/**
 * Kolab XML to array hash converter.
 *
 * For implementing a new format type you will have to inherit this
 * class and provide a _load/_save function.
 *
 * Copyright 2007-2009 Klarälvdalens Datakonsult AB
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html.
 *
 * @category Kolab
 * @package  Kolab_Format
 * @author   Thomas Jarosch <thomas.jarosch@intra2net.com>
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Server
 * @since    Horde 3.2
 */
class Horde_Kolab_Format_XML
{

    /**
     * Defines a XML value that should get a default value if missing
     */
    const PRODUCT_ID = 'Horde::Kolab';

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
     * Requested version of the data array to return
     *
     * @var int
     */
    protected $_version = 1;

    /**
     * The name of the resulting document.
     *
     * @var string
     */
    protected $_name = 'kolab.xml';

    /**
     * The XML document this driver works with.
     *
     * @var Horde_DOM_Document
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
     * Basic fields in any Kolab object
     *
     * @var array
     */
    protected $_fields_basic;

    /**
     * Automatically create categories if they are missing?
     *
     * @var boolean
     */
    protected $_create_categories = true;

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
     * @param array $params Any additional options
     */
    public function __construct($params = null)
    {
        if (is_array($params) && isset($params['version'])) {
            $this->_version = $params['version'];
        } else {
            $this->_version = 1;
        }

        /* Generic fields, in kolab format specification order */
        $this->_fields_basic = array(
            'uid' => array(
                'type'    => self::TYPE_STRING,
                'value'   => self::VALUE_NOT_EMPTY,
            ),
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
            'creation-date' => array(
                'type'    => self::TYPE_DATETIME,
                'value'   => self::VALUE_CALCULATED,
                'load'    => 'CreationDate',
                'save'    => 'CreationDate',
            ),
            'last-modification-date' => array(
                'type'    => self::TYPE_DATETIME,
                'value'   => self::VALUE_CALCULATED,
                'load'    => 'ModificationDate',
                'save'    => 'ModificationDate',
            ),
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
            'product-id' => array(
                'type'    => self::TYPE_STRING,
                'value'   => self::VALUE_CALCULATED,
                'load'    => 'ProductId',
                'save'    => 'ProductId',
            ),
        );
    }

    /**
     * Attempts to return a concrete Horde_Kolab_Format_XML instance.
     * based on $object_type.
     *
     * @param string    $object_type The object type that should be handled.
     * @param array     $params      Any additional parameters.
     *
     * @return Horde_Kolab_Format_XML The newly created concrete
     *                                Horde_Kolab_Format_XML instance.
     *
     * @throws Horde_Exception If the class for the object type could
     *                         not be loaded.
     */
    public function &factory($object_type = '', $params = null)
    {
        $object_type = ucfirst(str_replace('-', '', $object_type));
        $class       = 'Horde_Kolab_Format_XML_' . $object_type;

        if (class_exists($class)) {
            $driver = &new $class($params);
        } else {
            throw new Horde_Exception(sprintf(_("Failed to load Kolab XML driver %s"),
                                              $object_type));
        }

        return $driver;
    }

    /**
     * Return the name of the resulting document.
     *
     * @return string The name that may be used as filename.
     */
    public function getName()
    {
        return $this->_name;
    }

    /**
     * Return the mime type of the resulting document.
     *
     * @return string The mime type of the result.
     */
    public function getMimeType()
    {
        return 'application/x-vnd.kolab.' . $this->_root_name;
    }

    /**
     * Return the disposition of the resulting document.
     *
     * @return string The disportion of this document.
     */
    public function getDisposition()
    {
        return 'attachment';
    }

    /**
     * Load an object based on the given XML string.
     *
     * @todo Check encoding of the returned array. It seems to be ISO-8859-1 at
     * the moment and UTF-8 would seem more appropriate.
     *
     * @param string $xmltext The XML of the message as string.
     *
     * @return array The data array representing the object.
     *
     * @throws Horde_Exception If parsing the XML data failed.
     */
    public function load(&$xmltext)
    {
        try {
            $noderoot = $this->_parseXml($xmltext);
        } catch (Horde_Exception $e) {
            /**
             * If the first call does not return successfully this might mean we
             * got an attachment with broken encoding. There are some Kolab
             * client versions in the wild that might have done that. So the
             * next section starts a second attempt by guessing the encoding and
             * trying again.
             */
            if (strcasecmp(mb_detect_encoding($xmltext,
                                              'UTF-8, ISO-8859-1'), 'UTF-8') !== 0) {
                $xmltext = mb_convert_encoding($xmltext, 'UTF-8', 'ISO-8859-1');
            }
            $noderoot = $this->_parseXml($xmltext);
        }
        if (empty($noderoot)) {
            return false;
        }

        if (!$noderoot->has_child_nodes()) {
            throw new Horde_Exception(_("No or unreadable content in Kolab XML object"));
        }

        $children = $noderoot->child_nodes();

        // fresh object data
        $object = array();

        $result = $this->_loadArray($children, $this->_fields_basic);
        $object = array_merge($object, $result);
        $this->_loadMultipleCategories($object);

        $result = $this->_load($children);
        $object = array_merge($object, $result);

        // uid is vital
        if (!isset($object['uid'])) {
            throw new Horde_Exception(_("UID not found in Kolab XML object"));
        }

        return $object;
    }

    /**
     * Load the groupware object based on the specifc XML values.
     *
     * @param array $children An array of XML nodes.
     *
     * @return array The data array representing the object.
     *
     * @throws Horde_Exception If parsing the XML data failed.
     */
    protected function _load(&$children)
    {
        if (!empty($this->_fields_specific)) {
            return $this->_loadArray($children, $this->_fields_specific);
        } else {
            return array();
        }
    }

    /**
     * Load an array with data from the XML nodes.
     *
     * @param array $object   The resulting data array.
     * @param array $children An array of XML nodes.
     * @param array $fields   The fields to populate in the object array.
     *
     * @return boolean True on success.
     *
     * @throws Horde_Exception If parsing the XML data failed.
     */
    protected function _loadArray(&$children, $fields)
    {
        $object = array();

        // basic fields below the root node
        foreach($fields as $field => $params) {
            $result = $this->_getXmlData($children, $field, $params);
            if (isset($result)) {
                $object[$field] = $result;
            }
        }
        return $object;
    }

    /**
     * Get the text content of the named data node among the specified
     * children.
     *
     * @param array  $children     The children to search.
     * @param string $name         The name of the node to return.
     * @param array  $params       Parameters for the data conversion.
     *
     * @return string The content of the specified node or an empty
     *                string.
     *
     * @throws Horde_Exception If parsing the XML data failed.
     *
     * @todo Make protected (fix the XmlTest for that)
     */
    public function _getXmlData($children, $name, $params)
    {
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
                throw new Horde_Exception(sprintf(_("Data value for %s is empty in Kolab XML object!"),
                                                  $name));
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
                throw new Horde_Exception(sprintf("Kolab XML: Missing function %s!",
                                                  $params['load']));
            }
        } elseif ($params['type'] == self::TYPE_COMPOSITE) {
            return $this->_loadArray($child->child_nodes(), $params['array']);
        } elseif ($params['type'] == self::TYPE_MULTIPLE) {
            $result = array();
            foreach($children as $child) {
                if ($child->type == XML_ELEMENT_NODE && $child->tagname == $name) {
                    $value    = $this->_getXmlData(array($child), $name,
						   $params['array']);
                    $result[] = $value;
                }
            }
            return $result;
        } else {
            return $this->_loadDefault($child, $params);
        }

        // Nothing specified. Return the value as it is.
        return $value;
    }

    /**
     * Parse the XML string. The root node is returned on success.
     *
     * @param string $xmltext The XML of the message as string.
     *
     * @return Horde_DOM_Node The root node of the document.
     *
     * @throws Horde_Exception If parsing the XML data failed.
     *
     * @todo Make protected (fix the XmlTest for that)
     */
    public function _parseXml(&$xmltext)
    {
        $params = array(
            'xml' => $xmltext,
            'options' => HORDE_DOM_LOAD_REMOVE_BLANKS,
        );

        $result = Horde_DOM_Document::factory($params);
        if (is_a($result, 'PEAR_Error')) {
            throw new Horde_Exception($result);
        }
        $this->_xmldoc = $result;
        return $this->_xmldoc->document_element();
    }

    /**
     * Convert the data to a XML string.
     *
     * @param array $attributes  The data array representing the note.
     *
     * @return string The data as XML string.
     *
     * @throws Horde_Exception If converting the data to XML failed.
     */
    public function save($object)
    {
        $root = $this->_prepareSave();

        $this->_saveMultipleCategories($object);
        $this->_saveArray($root, $object, $this->_fields_basic);
        $this->_save($root, $object);

        return $this->_xmldoc->dump_mem(true);
    }

    /**
     * Save the specific XML values.
     *
     * @param array $root     The XML document root.
     * @param array $object   The resulting data array.
     *
     * @return boolean True on success.
     *
     * @throws Horde_Exception If converting the data to XML failed.
     */
    protected function _save($root, $object)
    {
        if (!empty($this->_fields_specific)) {
            $this->_saveArray($root, $object, $this->_fields_specific);
        }
        return true;
    }

    /**
     * Creates a new XML document if necessary.
     *
     * @param string $xmltext  The XML of the message as string.
     *
     * @return Horde_DOM_Node The root node of the document.
     *
     * @todo Make protected (fix the XmlTest for that)
     */
    public function _prepareSave()
    {
        if ($this->_xmldoc != null) {
            $root = $this->_xmldoc->document_element();
        } else {
            // create new XML
            $this->_xmldoc = Horde_DOM_Document::factory();
            $root          = $this->_xmldoc->create_element($this->_root_name);
            $root          = $this->_xmldoc->append_child($root);
            $root->set_attribute("version", $this->_root_version);
        }

        return $root;
    }

    /**
     * Save a data array to XML nodes.
     *
     * @param array   $root     The XML document root.
     * @param array   $object   The data array.
     * @param array   $fields   The fields to write into the XML object.
     * @param boolean $append   Should the nodes be appended?
     *
     * @return boolean True on success.
     *
     * @throws Horde_Exception If converting the data to XML failed.
     */
    protected function _saveArray($root, $object, $fields, $append = false)
    {
        // basic fields below the root node
        foreach($fields as $field => $params) {
            $this->_updateNode($root, $object, $field, $params, $append);
        }
        return true;
    }

    /**
     * Update the specified node.
     *
     * @param Horde_DOM_Node $parent_node  The parent node of the node that
     *                                     should be updated.
     * @param array          $attributes   The data array that holds all
     *                                     attribute values.
     * @param string         $name         The name of the the attribute
     *                                     to be updated.
     * @param array          $params       Parameters for saving the node
     * @param boolean        $append       Should the node be appended?
     *
     * @return Horde_DOM_Node The new/updated child node.
     *
     * @throws Horde_Exception If converting the data to XML failed.
     *
     * @todo Make protected (fix the XmlTest for that)
     */
    public function _updateNode($parent_node, $attributes, $name, $params,
                                   $append = false)
    {
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
                throw new Horde_Exception(sprintf(_("Data value for %s is empty in Kolab XML object!"),
                                                  $name));
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
                throw new Horde_Exception(sprintf("Kolab XML: Missing function %s!",
                                                  $params['save']));
            }
        } elseif ($params['type'] == self::TYPE_COMPOSITE) {
            // Possibly remove the old node first
            if (!$append) {
                $this->_removeNodes($parent_node, $name);
            }

            // Create a new complex node
            $composite_node = $this->_xmldoc->create_element($name);
            $composite_node = $parent_node->append_child($composite_node);
            return $this->_saveArray($composite_node, $value, $params['array']);
        } elseif ($params['type'] == self::TYPE_MULTIPLE) {
            // Remove the old nodes first
            $this->_removeNodes($parent_node, $name);

            // Add the new nodes
            foreach($value as $add_node) {
                $this->_saveArray($parent_node,
                                  array($name => $add_node),
                                  array($name => $params['array']),
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
     * @param Horde_DOM_Node  $parent   The parent of the new node.
     * @param string          $name     The name of the child node to create.
     * @param string          $value    The value of the child node to create.
     *
     * @return Horde_DOM_Node The new node.
     */
    protected function _createTextNode($parent, $name, $value)
    {
        $value = Horde_String::convertCharset($value, NLS::getCharset(), 'utf-8');

        $node = $this->_xmldoc->create_element($name);

        $node = $parent->append_child($node);

        // content
        $text = $this->_xmldoc->create_text_node($value);
        $text = $node->append_child($text);

        return $node;
    }

    /**
     * Return the named node among a list of nodes.
     *
     * @param array  $nodes  The list of nodes.
     * @param string $name   The name of the node to return.
     *
     * @return mixed The named Horde_DOM_Node or false if no node was found.
     */
    protected function _findNode($nodes, $name)
    {
        foreach($nodes as $node) {
            if ($node->type == XML_ELEMENT_NODE && $node->tagname == $name) {
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
     * @return mixed The specified Horde_DOM_Node or false if no node was found.
     */
    protected function _findNodeByChildData($nodes, $parent_name, $child_name,
                                            $value)
    {
        foreach($nodes as $node)
        {
            if ($node->type == XML_ELEMENT_NODE && $node->tagname == $parent_name) {
                $children = $node->child_nodes();
                foreach ($children as $child)
                    if ($child->type == XML_ELEMENT_NODE
                        && $child->tagname == $child_name
                        && $child->get_content() == $value) {
                        return $node;
                    }
            }
        }

        return false;
    }

    /**
     * Retrieve the content of a Horde_DOM_Node.
     *
     * @param Horde_DOM_Node  $nodes  The node that should be read.
     *
     * @return string The content of the node.
     */
    protected function _getNodeContent($node)
    {
        return Horde_String::convertCharset($node->get_content(), 'utf-8');
    }


    /**
     * Create a new named node on a parent node.
     *
     * @param Horde_DOM_Node $parent  The parent node.
     * @param string         $name    The name of the new child node.
     *
     * @return Horde_DOM_Node The new child node.
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
     * @param Horde_DOM_Node $parent  The parent node.
     * @param string         $name    The name of the children to be removed.
     */
    protected function _removeNodes($parent_node, $name)
    {
        while ($old_node = $this->_findNode($parent_node->child_nodes(), $name)) {
            $parent_node->remove_child($old_node);
        }
    }

    /**
     * Create a new named node on a parent node if it is not already
     * present in the given children.
     *
     * @param Horde_DOM_Node $parent    The parent node.
     * @param array          $children  The children that might already
     *                                  contain the node.
     * @param string         $name      The name of the new child node.
     *
     * @return Horde_DOM_Node The new or already existing child node.
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
     * @param string $node    The node to load the data from
     * @param array  $params  Parameters for loading the value
     *
     * @return string The loaded value.
     *
     * @throws Horde_Exception If converting the data from XML failed.
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
     * @param Horde_DOM_Node $parent_node The parent node to attach
     *                                    the child to
     * @param string         $name        The name of the node
     * @param mixed          $value       The value to store
     * @param boolean        $missing     Has the value been missing?
     * @param boolean        $append      Should the node be appended?
     *
     * @return Horde_DOM_Node The new child node.
     *
     * @throws Horde_Exception If converting the data to XML failed.
     */
    protected function _saveDefault($parent_node, $name, $value, $params,
                                    $append = false)
    {
        if (!$append) {
            $this->_removeNodes($parent_node, $name);
        }

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
        }

        // create the node
        return $this->_createTextNode($parent_node, $name, $value);
    }

    /**
     * Handle loading of categories. Preserve multiple categories in a hidden
     * object field. Optionally creates categories unknown to the Horde user.
     *
     * @param array $object Array of strings, containing the 'categories' field.
     *
     * @return NULL
     */
    protected function _loadMultipleCategories(&$object)
    {
        global $prefs;

        if (empty($object['categories'])) {
            return;
        }

        // Create horde category if needed
        @include_once 'Horde/Prefs/CategoryManager.php';
        if ($this->_create_categories
            && class_exists('Prefs_CategoryManager')
            && isset($prefs) && is_a($prefs, 'Prefs')) {
            $cManager         = new Prefs_CategoryManager();
            $horde_categories = $cManager->get();
        } else {
            $cManager         = null;
            $horde_categories = null;
        }

        $kolab_categories = explode (',', $object['categories']);

        $primary_category = '';
        foreach ($kolab_categories as $kolab_category) {
            $kolab_category = trim($kolab_category);

            $valid_category = true;
            if ($cManager && 
                array_search($kolab_category, $horde_categories) === false) {
                // Unknown category -> Add
                if ($cManager->add($kolab_category) === false) {
                    // categories might be locked
                    $valid_category = false;
                }
            }

            // First valid category becomes primary category
            if ($valid_category && empty($primary_category)) {
                $primary_category = $kolab_category;
            }
        }

        // Backup multiple categories
        if (count($kolab_categories) > 1) {
            $object['_categories_all']     = $object['categories'];
            $object['_categories_primary'] = $primary_category;
        }
        // Make default category visible to Horde
        $object['categories'] = $primary_category;
    }

    /**
     * Preserve multiple categories on save if "categories" didn't change.
     * The name "categories" currently refers to one primary category.
     *
     * @param array  $object Array of strings, containing the 'categories' field.
     *
     * @return NULL
     */
    protected function _saveMultipleCategories(&$object)
    {
        // Check for multiple categories.
        if (!isset($object['_categories_all'])
            || !isset($object['_categories_primary'])
            || !isset($object['categories']))
        {
            return;
        }

        // Preserve multiple categories if "categories" didn't change
        if ($object['_categories_primary'] == $object['categories']) {
            $object['categories'] = $object['_categories_all'];
        }
    }

    /**
     * Load the object creation date.
     *
     * @param Horde_DOM_Node  $node    The original node if set.
     * @param boolean         $missing Has the node been missing?
     *
     * @return string The creation date.
     *
     * @throws Horde_Exception If converting the data from XML failed.
     */
    protected function _loadCreationDate($node, $missing)
    {
        if ($missing) {
            // Be gentle and accept a missing creation date.
            return time();
        }
        return $this->_loadDefault($node,
                                   array('type' => self::TYPE_DATETIME));
    }

    /**
     * Save the object creation date.
     *
     * @param Horde_DOM_Node $parent_node The parent node to attach the child
     *                                    to.
     * @param string         $name        The name of the node.
     * @param mixed          $value       The value to store.
     * @param boolean        $missing     Has the value been missing?
     *
     * @return Horde_DOM_Node The new child node.
     */
    protected function _saveCreationDate($parent_node, $name, $value, $missing)
    {
        // Only create the creation date if it has not been set before
        if ($missing) {
            $value = time();
        }
        return $this->_saveDefault($parent_node,
                                   $name,
                                   $value,
                                   array('type' => self::TYPE_DATETIME));
    }

    /**
     * Load the object modification date.
     *
     * @param Horde_DOM_Node  $node    The original node if set.
     * @param boolean         $missing Has the node been missing?
     *
     * @return string The last modification date.
     */
    protected function _loadModificationDate($node, $missing)
    {
        if ($missing) {
            // Be gentle and accept a missing modification date.
            return time();
        }
        return $this->_loadDefault($node,
                                   array('type' => self::TYPE_DATETIME));
    }

    /**
     * Save the object modification date.
     *
     * @param Horde_DOM_Node $parent_node The parent node to attach
     *                                    the child to.
     * @param string         $name        The name of the node.
     * @param mixed          $value       The value to store.
     * @param boolean        $missing     Has the value been missing?
     *
     * @return Horde_DOM_Node The new child node.
     */
    protected function _saveModificationDate($parent_node, $name, $value, $missing)
    {
        // Always store now as modification date
        return $this->_saveDefault($parent_node,
                                   $name,
                                   time(),
                                   array('type' => self::TYPE_DATETIME));
    }

    /**
     * Load the name of the last client that modified this object
     *
     * @param Horde_DOM_Node  $node    The original node if set.
     * @param boolean         $missing Has the node been missing?
     *
     * @return string The last modification date.
     */
    protected function _loadProductId($node, $missing)
    {
        if ($missing) {
            // Be gentle and accept a missing product id
            return '';
        }
        return $this->_getNodeContent($node);
    }

    /**
     * Save the name of the last client that modified this object.
     *
     * @param Horde_DOM_Node $parent_node The parent node to attach
     *                                    the child to.
     * @param string         $name        The name of the node.
     * @param mixed          $value       The value to store.
     * @param boolean        $missing     Has the value been missing?
     *
     * @return Horde_DOM_Node The new child node.
     */
    protected function _saveProductId($parent_node, $name, $value, $missing)
    {
        // Always store now as modification date
        return $this->_saveDefault($parent_node,
                                   $name,
                                   self::PRODUCT_ID,
                                   array('type' => self::TYPE_STRING));
    }

    /**
     * Load recurrence information.
     *
     * @param Horde_DOM_Node  $node    The original node if set.
     * @param boolean         $missing Has the node been missing?
     *
     * @return array The recurrence information.
     *
     * @throws Horde_Exception If converting the data from XML failed.
     */
    protected function _loadRecurrence($node, $missing)
    {
        if ($missing) {
            return null;
        }

        // Collect all child nodes
        $children = $node->child_nodes();

        $recurrence = $this->_loadArray($children, $this->_fields_recurrence);

        // Get the cycle type (must be present)
        $recurrence['cycle'] = $node->get_attribute('cycle');
        // Get the sub type (may be present)
        $recurrence['type'] = $node->get_attribute('type');

        // Exclusions.
        if (isset($recurrence['exclusion'])) {
            $exceptions = array();
            foreach($recurrence['exclusion'] as $exclusion) {
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
            foreach($recurrence['complete'] as $complete) {
                if (!empty($complete)) {
                    list($year, $month, $mday) = sscanf($complete, '%04d-%02d-%02d');

                    $completions[] = sprintf('%04d%02d%02d', $year, $month, $mday);
                }
            }
            $recurrence['completions'] = $completions;
        }

        // Range is special
        foreach($children as $child) {
            if ($child->tagname == "range") {
                $recurrence['range-type'] = $child->get_attribute('type');
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
     * @param array  $recurrence  Recurrence hash loaded from XML.
     *
     * @return boolean True on success.
     *
     * @throws Horde_Exception If the recurrence data is invalid.
     */
    protected function _validateRecurrence(&$recurrence)
    {
        if (!isset($recurrence['cycle'])) {
              throw new Horde_Exception('recurrence tag error: cycle attribute missing');
        }

        if (!isset($recurrence['interval'])) {
              throw new Horde_Exception('recurrence tag error: interval tag missing');
        }
        $interval = $recurrence['interval'];
        if ($interval < 0) {
            throw new Horde_Exception('recurrence tag error: interval cannot be below zero: '
                                      . $interval);
        }

        if ($recurrence['cycle'] == 'weekly') {
            // Check for <day>
            if (!isset($recurrence['day']) || count($recurrence['day']) == 0) {
                throw new Horde_Exception('recurrence tag error: day tag missing for weekly recurrence');
            }
        }

        // The code below is only for monthly or yearly recurrences
        if ($recurrence['cycle'] != 'monthly'
            && $recurrence['cycle'] != 'yearly')
            return true;

        if (!isset($recurrence['type'])) {
            throw new Horde_Exception('recurrence tag error: type attribute missing');
        }

        if (!isset($recurrence['daynumber'])) {
            throw new Horde_Exception('recurrence tag error: daynumber tag missing');
        }
        $daynumber = $recurrence['daynumber'];
        if ($daynumber < 0) {
            throw new Horde_Exception('recurrence tag error: daynumber cannot be below zero: '
                                      . $daynumber);
        }

        if ($recurrence['type'] == 'daynumber') {
            if ($recurrence['cycle'] == 'yearly' && $daynumber > 366) {
                throw new Horde_Exception('recurrence tag error: daynumber cannot be larger than 366 for yearly recurrences: ' . $daynumber);
            } else if ($recurrence['cycle'] == 'monthly' && $daynumber > 31) {
                throw new Horde_Exception('recurrence tag error: daynumber cannot be larger than 31 for monthly recurrences: ' . $daynumber);
            }
        } else if ($recurrence['type'] == 'weekday') {
            // daynumber is the week of the month
            if ($daynumber > 5) {
                throw new Horde_Exception('recurrence tag error: daynumber cannot be larger than 5 for type weekday: ' . $daynumber);
            }

            // Check for <day>
            if (!isset($recurrence['day']) || count($recurrence['day']) == 0) {
                throw new Horde_Exception('recurrence tag error: day tag missing for type weekday');
            }
        }

        if (($recurrence['type'] == 'monthday' || $recurrence['type'] == 'yearday')
            && $recurrence['cycle'] == 'monthly')
        {
            throw new Horde_Exception('recurrence tag error: type monthday/yearday is only allowed for yearly recurrences');
        }

        if ($recurrence['cycle'] == 'yearly') {
            if ($recurrence['type'] == 'monthday') {
                // daynumber and month
                if (!isset($recurrence['month'])) {
                    throw new Horde_Exception('recurrence tag error: month tag missing for type monthday');
                }
                if ($daynumber > 31) {
                    throw new Horde_Exception('recurrence tag error: daynumber cannot be larger than 31 for type monthday: ' . $daynumber);
                }
            } else if ($recurrence['type'] == 'yearday') {
                if ($daynumber > 366) {
                    throw new Horde_Exception('recurrence tag error: daynumber cannot be larger than 366 for type yearday: ' . $daynumber);
                }
            }
        }

        return true;
    }

    /**
     * Save recurrence information.
     *
     * @param Horde_DOM_Node $parent_node The parent node to attach
     *                                    the child to.
     * @param string         $name        The name of the node.
     * @param mixed          $value       The value to store.
     * @param boolean        $missing     Has the value been missing?
     *
     * @return Horde_DOM_Node The new child node.
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
            foreach($value['exceptions'] as $exclusion) {
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
            foreach($value['completions'] as $complete) {
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

        $r_node = $this->_xmldoc->create_element($name);
        $r_node = $parent_node->append_child($r_node);

        // Save normal fields
        $this->_saveArray($r_node, $value, $this->_fields_recurrence);

        // Add attributes
        $r_node->set_attribute("cycle", $value['cycle']);
        if (isset($value['type'])) {
            $r_node->set_attribute("type", $value['type']);
        }

        $child = $this->_findNode($r_node->child_nodes(), "range");
        if ($child) {
            $child->set_attribute("type", $value['range-type']);
        }

        return $r_node;
    }
}
