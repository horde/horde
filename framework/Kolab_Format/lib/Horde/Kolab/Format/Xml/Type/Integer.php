<?php
/**
 * Handles a integer attribute.
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
 * Handles a integer attribute.
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
class Horde_Kolab_Format_Xml_Type_Integer
extends Horde_Kolab_Format_Xml_Type_String
{
    /**
     * Load the value of a node.
     *
     * @param string $query The query.
     *
     * @return DOMNode|false The named DOMNode or empty if no node value was
     *                       found.
     */
    protected function loadNodeValue($name, &$attributes, $parent_node)
    {
        $result = parent::loadNodeValue($name, $attributes, $parent_node);
        if (isset($attributes[$name]) && !is_int($attributes[$name])) {
            $this->_checkInteger($attributes[$name]);
            $attributes[$name] = (int)$attributes[$name];
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
     *
     * @return DOMNode|boolean The new/updated child node or false if this
     *                         failed.
     *
     * @throws Horde_Kolab_Format_Exception If converting the data to XML failed.
     */
    public function save($name, $attributes, $parent_node)
    {
        if (isset($attributes[$name])) {
            if (!is_string($attributes[$name])) {
                $attributes[$name] = (string)$attributes[$name];
            }
            $this->_checkInteger($attributes[$name]);
        }
        return parent::save($name, $attributes, $parent_node);
    }

    /**
     * Test if the input seems to be a real integer.
     *
     * @param string $integer The string to check.
     *
     * @return NULL
     *
     * @throws Horde_Kolab_Format_Exception If the input is no integer.
     */
    private function _checkInteger($integer)
    {
        if (((string)((int)$integer) !== $integer)
            && !$this->isRelaxed()) {
            throw new Horde_Kolab_Format_Exception(
                sprintf('Invalid integer input "%s"!', $integer)
            );
        }
    }

}
