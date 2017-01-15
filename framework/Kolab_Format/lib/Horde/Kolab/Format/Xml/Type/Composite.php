<?php
/**
 * Handles composite attributes.
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
 * Handles composite attributes.
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
class Horde_Kolab_Format_Xml_Type_Composite
extends Horde_Kolab_Format_Xml_Type_Base
{
    /**
     * The elements of the composite attribute.
     *
     * @var array
     */
    protected $elements;

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
     * Should the values be merged into the parent attributes?
     *
     * @var boolean
     */
    protected $merge = false;

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
    public function load(
        $name,
        &$attributes,
        $parent_node,
        Horde_Kolab_Format_Xml_Helper $helper,
        $params = array()
    )
    {
        if ($node = $helper->findNodeRelativeTo('./' . $name, $parent_node)) {
            $result = $this->loadNodeValue($node, $helper, $params);
        } else {
            $result = $this->loadMissing($name, $params);
        }
        if (!$this->merge) {
            $attributes[$name] = $result;
        } else {
            $attributes = array_merge($attributes, $result);
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
    public function loadNodeValue(
        $node,
        Horde_Kolab_Format_Xml_Helper $helper,
        $params = array()
    )
    {
        $result = array();
        foreach ($this->elements as $sub_name => $sub_type) {
            $this->createSubType($sub_type, $params)
                ->load($sub_name, $result, $node, $helper, $params);
        }
        return $result;
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
    public function save(
        $name,
        $attributes,
        $parent_node,
        Horde_Kolab_Format_Xml_Helper $helper,
        $params = array()
    )
    {
        $node = $helper->findNodeRelativeTo(
            './' . $name, $parent_node
        );

        if (!$this->merge && !isset($attributes[$name])) {
            if ($node === false) {
                if ($this->value == Horde_Kolab_Format_Xml::VALUE_MAYBE_MISSING ||
                    ($this->value == Horde_Kolab_Format_Xml::VALUE_NOT_EMPTY &&
                     $this->isRelaxed($params))) {
                    return false;
                }
            } else {
                if ($this->value == Horde_Kolab_Format_Xml::VALUE_MAYBE_MISSING) {
                    /** Client indicates that the value should get removed */
                    $helper->removeNodes($parent_node, $name);
                    return false;
                } else {
                    return $node;
                }
            }
        }

        return $this->saveNodeValue(
            $name,
            $this->generateWriteValue($name, $attributes, $params),
            $parent_node,
            $helper,
            $params,
            $node
        );
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
            $node = $helper->createNewNode($parent_node, $name);
            $this->_writeComposite($node, $name, $value, $helper, $params);
            return $node;
        } else {
            $this->_writeComposite($old_node, $name, $value, $helper, $params);
            return $old_node;
        }
    }

    /**
     * Write a composite value to a parent node.
     *
     * @param DOMNode                       $parent_node The parent node of the
     *                                                   node that should be
     *                                                   updated.
     * @param string                        $name        The name of the the
     *                                                   attribute to be updated.
     * @param array                         $values      The values to write.
     * @param Horde_Kolab_Format_Xml_Helper $helper      A XML helper instance.
     * @param array                         $params      The parameters for this
     *                                                   write operation.
     *
     * @return array The list of new/updated child nodes.
     *
     * @throws Horde_Kolab_Format_Exception If converting the data to XML failed.
     */
    private function _writeComposite($parent_node, $name, $values,
                                     Horde_Kolab_Format_Xml_Helper $helper,
                                     $params)
    {
        foreach ($this->elements as $sub_name => $sub_type) {
            $this->createSubType($sub_type, $params)
                ->save($sub_name, $values, $parent_node, $helper, $params);
        }
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
        if ($this->merge) {
            return $attributes;
        }
        if (isset($attributes[$name])) {
            return $attributes[$name];
        } else {
            return $this->loadMissing($name, $params);
        }
    }
}
