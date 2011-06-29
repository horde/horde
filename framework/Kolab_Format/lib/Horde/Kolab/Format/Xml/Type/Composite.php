<?php
/**
 * Handles composite attributes.
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_Format
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Format
 */

/**
 * Handles composite attributes.
 *
 * Copyright 2011 The Horde Project (http://www.horde.org/)
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
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Format
 */
class Horde_Kolab_Format_Xml_Type_Composite
extends Horde_Kolab_Format_Xml_Type_Base
{
    /**
     * Load the node value from the Kolab object.
     *
     * @param string  $name        The name of the the attribute
     *                             to be fetched.
     * @param array   &$attributes The data array that holds all
     *                             attribute values.
     * @param DOMNode $parent_node The parent node of the node to be loaded.
     *
     * @return NULL
     */
    public function load($name, &$attributes, $parent_node)
    {
        if ($node = $this->findNodeRelativeTo('./' . $name, $parent_node)) {
            $result = array();
            foreach ($this->getParam('array') as $sub_name => $sub_params) {
                $sub_type = $this->createSubType($sub_params);
                $sub_type->load($sub_name, $result, $node);
            }
            $attributes[$name] = $result;
        } else {
            if ($this->getParam('value') == Horde_Kolab_Format_Xml::VALUE_NOT_EMPTY
                && !$this->isRelaxed()) {
                throw new Horde_Kolab_Format_Exception_MissingValue($name);
            }
            if ($this->getParam('value') == Horde_Kolab_Format_Xml::VALUE_DEFAULT) {
                $attributes[$name] = $this->getParam('default');
            }
        }
        return false;
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
     *
     * @return DOMNodeList|array|boolean The new/updated child nodes or false
     *                                   if the operation failed.
     *
     * @throws Horde_Kolab_Format_Exception If converting the data to XML failed.
     */
    public function save($name, $attributes, $parent_node)
    {
        if (!($node = $this->findNodeRelativeTo('./' . $name, $parent_node))) {
            if (!isset($attributes[$name])) {
                if ($this->getParam('value') == Horde_Kolab_Format_Xml::VALUE_NOT_EMPTY
                    && !$this->isRelaxed()) {
                    throw new Horde_Kolab_Format_Exception_MissingValue($name);
                }
                if ($this->getParam('value') == Horde_Kolab_Format_Xml::VALUE_DEFAULT) {
                    $value = $this->getParam('default');
                } else {
                    return false;
                }
            } else {
                $value = $attributes[$name];
            }
            $node = $this->createNewNode($parent_node, $name);
            return $this->_writeComposite($node, $name, $value);
        }
        if (isset($attributes[$name])) {
            return $this->_writeComposite($node, $name, $attributes[$name]);

        } else if ($this->getParam('value') == Horde_Kolab_Format_Xml::VALUE_MAYBE_MISSING) {
            /** Client indicates that the value should get removed */
            $this->removeNodes($parent_node, $name);
        }
        return $node;
    }

    /**
     * Write a composite value to a parent node.
     *
     * @param DOMNode $parent_node The parent node of the node that
     *                             should be updated.
     * @param string  $name        The name of the the attribute
     *                             to be updated.
     * @param array   $values      The values to write.
     *
     * @return array The list of new/updated child nodes.
     *
     * @throws Horde_Kolab_Format_Exception If converting the data to XML failed.
     */
    private function _writeComposite(
        $parent_node,
        $name,
        $values
    ) {
        
        foreach ($this->getParam('array') as $name => $sub_params) {
            $sub_type = $this->createSubType($sub_params);
            $sub_type->save($name, $values, $parent_node);
        }
    }
}
