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
        if ($result !== false) {
            return $result;
        } else {
            if ($this->getParam('value') == Horde_Kolab_Format_Xml::VALUE_NOT_EMPTY
                && !$this->isRelaxed()) {
                throw new Horde_Kolab_Format_Exception_MissingValue($name);
            }
            if ($this->getParam('value') == Horde_Kolab_Format_Xml::VALUE_DEFAULT) {
                $attributes[$name] = $this->getParam('default');
            }
            return false;
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
     *
     * @return DOMNode|boolean The new/updated child node or false if this
     *                         failed.
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
            return $this->storeNewNodeValue($parent_node, $name, $value);
        }
        if (isset($attributes[$name])) {
            $this->replaceFirstNodeTextValue($node, $attributes[$name]);
        }
        return $node;
    }
}
