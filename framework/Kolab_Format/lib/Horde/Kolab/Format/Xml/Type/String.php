<?php
/**
 * Handles a string attribute.
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
 * Handles a string attribute.
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
class Horde_Kolab_Format_Xml_Type_String
extends Horde_Kolab_Format_Xml_Type_Base
{
    /**
     * The parameters required for the parsing operation.
     */
    protected $_required_parameters = array('helper', 'value');

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
        $result = parent::load($name, $attributes, $parent_node, $params);
        if ($result !== false) {
            return $result;
        } else {
            if ($params['value'] != Horde_Kolab_Format_Xml::VALUE_MAYBE_MISSING) {
                $attributes[$name] = $this->loadMissing($name, $params);
            }
            return false;
        }
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
        $result = $params['helper']->fetchNodeValue($node);;
        if ($result === null) {
            $result = '';
        }
        return $result;
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
