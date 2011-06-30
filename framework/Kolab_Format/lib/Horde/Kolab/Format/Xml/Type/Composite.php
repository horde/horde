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
     * The parameters required for the parsing operation.
     */
    protected $_required_parameters = array('helper', 'array', 'value');

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
            $result = array();
            foreach ($params['array'] as $sub_name => $sub_params) {
                list($sub_type, $type_params) = $this->createTypeAndParams(
                    $params, $sub_params
                );
                $sub_type->load($sub_name, $result, $node, $type_params);
            }
            $attributes[$name] = $result;
        } else {
            $attributes[$name] = $this->loadMissing($name, $params);
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

        if (!isset($attributes[$name])) {
            if ($node === false) {
                if ($params['value'] == Horde_Kolab_Format_Xml::VALUE_MAYBE_MISSING
                    || ($params['value'] == Horde_Kolab_Format_Xml::VALUE_NOT_EMPTY
                        && $this->isRelaxed($params))) {
                    return false;
                }
            } else {
                if ($params['value'] == Horde_Kolab_Format_Xml::VALUE_MAYBE_MISSING) {
                    /** Client indicates that the value should get removed */
                    $params['helper']->removeNodes($parent_node, $name);
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
            $params,
            $node
        );
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
            $node = $params['helper']->createNewNode($parent_node, $name);
            return $this->_writeComposite($node, $name, $value, $params);
        } else {
            return $this->_writeComposite($old_node, $name, $value, $params);
        }
    }

    /**
     * Write a composite value to a parent node.
     *
     * @param DOMNode $parent_node The parent node of the node that
     *                             should be updated.
     * @param string  $name        The name of the the attribute
     *                             to be updated.
     * @param array   $values      The values to write.
     * @param array   $params      The parameters for this write operation.
     *
     * @return array The list of new/updated child nodes.
     *
     * @throws Horde_Kolab_Format_Exception If converting the data to XML failed.
     */
    private function _writeComposite(
        $parent_node,
        $name,
        $values,
        $params
    ) {
        
        foreach ($params['array'] as $name => $sub_params) {
            list($sub_type, $type_params) = $this->createTypeAndParams(
                $params, $sub_params
            );
            $sub_type->save($name, $values, $parent_node, $type_params);
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
        if (isset($attributes[$name])) {
            return $attributes[$name];
        } else {
            return $this->loadMissing($name, $params);
        }
    }
}
