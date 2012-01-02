<?php
/**
 * Handles api version 1 of the recurrence range attribute.
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
 * Handles api version 1 of the recurrence range attribute.
 *
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you did not
 * receive this file, see
 * http://www.horde.org/licenses/lgpl21.
 *
 * @since Horde_Kolab_Format 1.1.0
 *
 * @category Kolab
 * @package  Kolab_Format
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL
 * @link     http://www.horde.org/libraries/Horde_Kolab_Format
 */
class Horde_Kolab_Format_Xml_Type_RecurrenceRange_V1
extends Horde_Kolab_Format_Xml_Type_String
{
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
        $result = parent::load($name, $attributes, $parent_node, $helper, $params);
        if ($result !== false) {
            $type = $result->getAttribute('type');
            if (!empty($type) && $type != 'none') {
                $attributes['range-type'] = $type;
            }
        }
        return $result;
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
        $result = $helper->fetchNodeValue($node);
        $type = $node->getAttribute('type');
        if (empty($type) || $type == 'none') {
            return null;
        }
        if ($type == 'date') {
            return Horde_Kolab_Format_Date::decodeDate($result);
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
        if (isset($attributes['range-type']) &&
            $attributes['range-type'] == 'date') {
            $attributes[$name] = Horde_Kolab_Format_Date::encodeDate($attributes[$name]);
        }
        $node = parent::save(
            $name, $attributes, $parent_node, $helper, $params
        );
        if (isset($attributes['range-type'])) {
            $type = $attributes['range-type'];
        } else {
            $type = 'none';
        }
        $node->setAttribute('type', $type);
    }
}
