<?php
/**
 * Handles a comma-separated string list attribute that defaults to an empty
 * string.
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_Format
 * @author   Jan Schneider <jan@horde.org>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL
 * @link     http://www.horde.org/libraries/Horde_Kolab_Format
 */

/**
 * Handles a string attribute that defaults to an empty string.
 *
 * Copyright 2012-2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you did not
 * receive this file, see
 * http://www.horde.org/licenses/lgpl21.
 *
 * @category Kolab
 * @package  Kolab_Format
 * @author   Jan Schneider <jan@horde.org>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL
 * @link     http://www.horde.org/libraries/Horde_Kolab_Format
 */
class Horde_Kolab_Format_Xml_Type_String_Empty_List
extends Horde_Kolab_Format_Xml_Type_String_Empty
{
    /**
     * A default value if required.
     *
     * @var array
     */
    protected $default = array();

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
        $result = parent::loadNodeValue($node, $helper, $params);
        $result = preg_split('/(?<!\\\\),/', $result, 0, PREG_SPLIT_NO_EMPTY);
        return array_map(array($this, '_unescapeString'), $result);
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
        $values = parent::generateWriteValue($name, $attributes, $params);
        $values = array_map(array($this, '_escapeString'), $values);
        return implode(',', $values);
    }

    /**
     * Escapes a string to fit into a comma-separated string.
     *
     * @param string $value  The original value.
     *
     * @return string  The escaped value.
     */
    protected function _escapeString($value)
    {
        return str_replace(',', '\\,', $value);
    }

    /**
     * Unescapes a string from a comma-separated string.
     *
     * @param string $value  The original value.
     *
     * @return string  The unescaped value.
     */
    protected function _unescapeString($value)
    {
        return str_replace('\\,', ',', $value);
    }
}
