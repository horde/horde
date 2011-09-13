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
 * Copyright 2011 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you did not
 * receive this file, see
 * http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html.
 *
 * @since Horde_Kolab_Format 1.1.0
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
     * The parameters required for the parsing operation.
     */
    protected $_required_parameters = array('helper');

    /**
     * The factory for any additional objects required.
     *
     * @var Horde_Kolab_Format_Factory
     */
    private $_factory;

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
     * @param string  $name        The name of the the attribute
     *                             to be fetched.
     * @param array   &$attributes The data array that holds all
     *                             attribute values.
     * @param DOMNode $parent_node The parent node of the node to be loaded.
     * @param array   $params      The parameters for this parse operation.
     *
     * @return DOMNode|boolean The named DOMNode or false if no node value was
     *                         found.
     */
    public function load($name, &$attributes, $parent_node, $params = array())
    {
        $this->checkParams($params, $name);
        if ($node = $params['helper']->findNodeRelativeTo('./' . $name, $parent_node)) {
            if (($value = $this->loadNodeValue($node, $params)) !== null) {
                $attributes[$name] = $value;
                return $node;
            }
        }
        return false;
    }

    /**
     * Load the value of a node.
     *
     * @param DOMNode $node   Retrieve value for this node.
     * @param array   $params The parameters for this parse operation.
     *
     * @return mixed|null The value or null if no value was found.
     */
    public function loadNodeValue($node, $params = array())
    {
        return $params['helper']->fetchNodeValue($node);
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
        if ($params['value'] == Horde_Kolab_Format_Xml::VALUE_NOT_EMPTY
            && !$this->isRelaxed($params)) {
            throw new Horde_Kolab_Format_Exception_MissingValue($name);
        }
        if ($params['value'] == Horde_Kolab_Format_Xml::VALUE_DEFAULT) {
            $this->checkMissing('default', $params, $name);
            return $params['default'];
        }
    }

    /**
     * Update the specified attribute.
     *
     * @param string  $name        The name of the the attribute
     *                             to be updated.
     * @param array   $attributes  The data array that holds all
     *                             attribute values.
     * @param DOMNode $parent_node The parent node of the node that
     *                             should be updated.
     * @param array   $params      The parameters for this write operation.
     *
     * @return DOMNode|boolean The new/updated child node or false if this
     *                         failed.
     *
     * @throws Horde_Kolab_Format_Exception If converting the data to XML failed.
     */
    public function save($name, $attributes, $parent_node, $params = array())
    {
        $this->checkParams($params, $name);
        $node = $params['helper']->findNodeRelativeTo(
            './' . $name, $parent_node
        );
        $result = $this->saveNodeValue(
            $name,
            $this->generateWriteValue($name, $attributes, $params),
            $parent_node,
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
     * @param string       $name        The name of the the attribute
     *                                  to be updated.
     * @param mixed        $value       The value to store.
     * @param DOMNode      $parent_node The parent node of the node that
     *                                  should be updated.
     * @param array        $params      The parameters for this write operation.
     * @param DOMNode|NULL $old_node    The previous value (or null if
     *                                  there is none).
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
        $params,
        $old_node = false
    ) {
        if ($old_node === false) {
            return $params['helper']->storeNewNodeValue(
                $parent_node, $name, $value
            );
        } else {
            $params['helper']->replaceFirstNodeTextValue($old_node, $value);
            return $old_node;
        }
    }

    /**
     * Validate that the parameter array contains all required parameters.
     *
     * @param array  $params    The parameters.
     * @param string $attribute The attribute name.

     * @return NULL
     *
     * @throws Horde_Kolab_Format_Exception In case required parameters are
     *                                      missing.
     */
    protected function checkParams($params, $attribute)
    {
        if (!empty($this->_required_parameters)) {
            foreach ($this->_required_parameters as $required) {
                $this->checkMissing($required, $params, $attribute);
            }
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
     * @return boolean True if the XML should not be strict.
     */
    protected function isRelaxed($params)
    {
        return !empty($params['relaxed']);
    }

    /**
     * Create a handler and the parameters for the sub type of this attribute.
     *
     * @param array $params     The parent parameters.
     * @param array $sub_params The parameters for creating the sub type handler.
     *
     * @return array An array with the sub type handler and the sub type
     *               parameters.
     */
    protected function createTypeAndParams($params, $sub_params)
    {
        $type_params = $params;
        unset($type_params['array']);
        unset($type_params['value']);
        if (is_array($sub_params)) {
            $sub_type = $this->createSubType($sub_params['type']);
            unset($sub_params['type']);
            $type_params = array_merge($type_params, $sub_params);
        } else {
            $sub_type = $this->createSubType($sub_params);
        }
        return array($sub_type, $type_params);
    }

    /**
     * Create a handler for the sub type of this attribute.
     *
     * @param string $type The sub type.
     *
     * @return Horde_Kolab_Format_Xml_Type The sub type handler.
     */
    protected function createSubType($type)
    {
        return $this->_factory->createXmlType($type);
    }

    /**
     * Create the XML helper instance.
     *
     * @param DOMDocument $xmldoc The XML document the helper works with.
     *
     * @return Horde_Kolab_Format_Xml_Helper The helper utility.
     */
    protected function createHelper($xmldoc)
    {
        return $this->_factory->createXmlHelper($xmldoc);
    }
}