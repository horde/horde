<?php
/**
 * Handles attributes with multiple values.
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
 * Handles attributes with multiple values.
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
class Horde_Kolab_Format_Xml_Type_Multiple
extends Horde_Kolab_Format_Xml_Type_Base
{
    /**
     * The class name representing the element that can occur multiple times.
     *
     * @var string
     */
    protected $element;

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
        $children = $helper->findNodesRelativeTo('./' . $name, $parent_node);
        if ($children->length > 0) {
            $sub_type = $this->createSubType($this->element, $params);
            $result = array();
            foreach ($children as $child) {
                $result[] = $sub_type->loadNodeValue(
                    $child, $helper, $params
                );
            }
            $attributes[$name] = $result;
        } else {
            $attributes[$name] = $this->loadMissing($name, $params);
        }
        return false;
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
    public function save(
        $name,
        $attributes,
        $parent_node,
        Horde_Kolab_Format_Xml_Helper $helper,
        $params = array()
    )
    {
        $children = $helper->findNodesRelativeTo(
            './' . $name, $parent_node
        );

        if (!isset($attributes[$name])) {
            if ($children->length == 0) {
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
                    return $children;
                }
            }
        }

        //@todo What is required to get around this overwriting of the old values?
        $helper->removeNodes($parent_node, $name);
        return $this->_writeMultiple(
            $parent_node,
            $name,
            $this->generateWriteValue($name, $attributes, $params),
            $helper,
            $params
        );
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
            return $this->loadMissing($name, $params);
        }
    }

    /**
     * Write multiple values to one parent node.
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
    private function _writeMultiple(
        $parent_node,
        $name,
        $values,
        Horde_Kolab_Format_Xml_Helper $helper,
        $params
    )
    {
        $sub_type = $this->createSubType($this->element, $params);
        $result = array();
        foreach ($values as $value) {
            $result[] = $sub_type->saveNodeValue(
                $name, $value, $parent_node, $helper, $params
            );
        }
        return $result;
    }
}
