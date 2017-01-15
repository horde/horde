<?php
/**
 * Utilities for the various XML handlers.
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_Format
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL
 * @link     http://www.horde.org/libraries/Horde_Kolab_Format
 */

/**
 * Utilities for the various XML handlers.
 *
 * Copyright 2011-2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you did not
 * receive this file, see
 * http://www.horde.org/licenses/lgpl21.
 *
 * @category Kolab
 * @package  Kolab_Format
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL
 * @link     http://www.horde.org/libraries/Horde_Kolab_Format
 */
class Horde_Kolab_Format_Xml_Type_Base
{
    /**
     * The factory for any additional objects required.
     *
     * @var Horde_Kolab_Format_Factory
     */
    private $_factory;

    /**
     * Indicate which value type is expected.
     *
     * @var int
     */
    protected $value = Horde_Kolab_Format_Xml::VALUE_MAYBE_MISSING;

    /**
     * A default value if required.
     *
     * @var string
     */
    protected $default;

    /**
     * Collects xml types already created.
     *
     * @var array
     */
    private static $_xml_types;

    /**
     * Constructor
     *
     * @param Horde_Kolab_Format_Factory $factory The factory for any additional
     *                                            objects required.
     */
    public function __construct($factory)
    {
        $this->_factory = $factory;
    }

    /**
     * Load the node value from the Kolab object.
     *
     * @param string                        $name        The name of the the
     *                                                   attribute to be fetched.
     * @param array                         &$attributes The data array that
     *                                                   holds all attribute
     *                                                   values.
     * @param DOMNode                       $parent_node The parent node of the
     *                                                   node to be loaded.
     * @param Horde_Kolab_Format_Xml_Helper $helper      A XML helper instance.
     * @param array                         $params      Additiona parameters for
     *                                                   this parse operation.
     *
     * @return DOMNode|boolean The named DOMNode or false if no node value was
     *                         found.
     */
    public function load($name, &$attributes, $parent_node,
                         Horde_Kolab_Format_Xml_Helper $helper,
                         $params = array())
    {
        if ($node = $helper->findNodeRelativeTo('./' . $name, $parent_node)) {
            if (($value = $this->loadNodeValue($node, $helper, $params)) !== null) {
                $attributes[$name] = $value;
                return $node;
            }
        }
        return false;
    }

    /**
     * Load the value of a node.
     *
     * @param DOMNode                       $node   Retrieve value for this node.
     * @param Horde_Kolab_Format_Xml_Helper $helper A XML helper instance.
     * @param array                         $params Additiona parameters for
     *                                              this parse operation.
     *
     * @return mixed|null The value or null if no value was found.
     */
    public function loadNodeValue($node, Horde_Kolab_Format_Xml_Helper $helper,
                                  $params = array())
    {
        return $helper->fetchNodeValue($node);
    }

    /**
     * Load a default value for a node.
     *
     * @param string $name   The attribute name.
     * @param array  $params The parameters for the current operation.
     *
     * @return mixed The default value.
     *
     * @throws Horde_Kolab_Format_Exception In case the attribute may not be
     *                                      missing or the default value was
     *                                      left undefined.
     */
    protected function loadMissing($name, $params)
    {
        if ($this->value == Horde_Kolab_Format_Xml::VALUE_NOT_EMPTY
            && !$this->isRelaxed($params)) {
            throw new Horde_Kolab_Format_Exception_MissingValue($name);
        }
        if ($this->value == Horde_Kolab_Format_Xml::VALUE_DEFAULT) {
            return $this->default;
        }
    }

    /**
     * Update the specified attribute.
     *
     * @param string                        $name        The name of the the
     *                                                   attribute to be updated.
     * @param array                         $attributes  The data array that holds
     *                                                   all attribute values.
     * @param DOMNode                       $parent_node The parent node of the
     *                                                   node that should be
     *                                                   updated.
     * @param Horde_Kolab_Format_Xml_Helper $helper      A XML helper instance.
     * @param array                         $params      Additional parameters
     *                                                   for this write operation.
     *
     * @return DOMNode|boolean The new/updated child node or false if this
     *                         failed.
     *
     * @throws Horde_Kolab_Format_Exception If converting the data to XML failed.
     */
    public function save($name, $attributes, $parent_node,
                         Horde_Kolab_Format_Xml_Helper $helper,
                         $params = array())
    {
        $node = $helper->findNodeRelativeTo(
            './' . $name, $parent_node
        );
        $result = $this->saveNodeValue(
            $name,
            $this->generateWriteValue($name, $attributes, $params),
            $parent_node,
            $helper,
            $params,
            $node
        );
        return ($node !== false) ? $node : $result;
    }

    /**
     * Generate the value that should be written to the node. Override in the
     * extending classes.
     *
     * @param string  $name        The name of the the attribute
     *                             to be updated.
     * @param array   $attributes  The data array that holds all
     *                             attribute values.
     * @param array   $params      The parameters for this write operation.
     *
     * @return mixed The value to be written.
     */
    protected function generateWriteValue($name, $attributes, $params)
    {
        if (isset($attributes[$name])) {
            return $attributes[$name];
        } else {
            return '';
        }
    }

    /**
     * Update the specified attribute.
     *
     * @param string                        $name        The name of the attribute
     *                                                   to be updated.
     * @param mixed                         $value       The value to store.
     * @param DOMNode                       $parent_node The parent node of the
     *                                                   node that should be
     *                                                   updated.
     * @param Horde_Kolab_Format_Xml_Helper $helper      A XML helper instance.
     * @param array                         $params      The parameters for this
     *                                                   write operation.
     * @param DOMNode|NULL                  $old_node    The previous value (or
     *                                                   null if there is none).
     *
     * @return DOMNode|boolean The new/updated child node or false if this
     *                         failed.
     *
     * @throws Horde_Kolab_Format_Exception If converting the data to XML failed.
     */
    public function saveNodeValue(
        $name,
        $value,
        $parent_node,
        Horde_Kolab_Format_Xml_Helper $helper,
        $params = array(),
        $old_node = false
    )
    {
        if ($old_node === false) {
            return $helper->storeNewNodeValue(
                $parent_node, $name, $value
            );
        } else {
            $helper->replaceFirstNodeTextValue($old_node, $value);
            return $old_node;
        }
    }

    /**
     * Validate that the parameter array contains all required parameters.
     *
     * @param string $key       The parameter name.
     * @param array  $params    The parameters.
     * @param string $attribute The attribute name.
     *
     * @throws Horde_Kolab_Format_Exception In case required parameters are
     *                                      missing.
     */
    protected function checkMissing($key, $params, $attribute)
    {
        if (!isset($params[$key])) {
            throw new Horde_Kolab_Format_Exception(
                sprintf(
                    'Required parameter "%s" missing (attribute: %s)!',
                    $key,
                    $attribute
                )
            );
        }
    }

    /**
     * Return a parameter value.
     *
     * @param string $name The parameter name.
     *
     * @return mixed The parameter value.
     */
    public function getParam($name)
    {
        return isset($this->_params[$name]) ? $this->_params[$name] : null;
    }

    /**
     * Returns if the XML handling should be relaxed.
     *
     * @param array $params The parameters.
     *
     * @return boolean True if the XML should not be strict.
     */
    protected function isRelaxed($params)
    {
        return !empty($params['relaxed']);
    }

    /**
     * Create a handler for the sub type of this attribute.
     *
     * @param string $type   The sub type.
     * @param array  $params Additional parameters.
     *
     * @return Horde_Kolab_Format_Xml_Type The sub type handler.
     */
    protected function createSubType($type, $params)
    {
        if (isset($params['api-version'])) {
            $class = $type . '_V' . $params['api-version'];
        } else {
            $class = $type;
        }
        if (!isset(self::$_xml_types[$class])) {
            self::$_xml_types[$class] = $this->_factory->createXmlType($type, $params);
        }
        return self::$_xml_types[$class];
    }
}
