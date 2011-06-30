<?php
/**
 * Handles a color attribute.
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
 * Handles a color attribute.
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
class Horde_Kolab_Format_Xml_Type_Color
extends Horde_Kolab_Format_Xml_Type_String
{
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
        if ($result !== null) {
            $this->_checkColor($result, $params);
        }
        return $result;
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
        if (isset($value)) {
            $this->_checkColor($value, $params);
        }
        return parent::saveNodeValue(
            $name, $value, $parent_node, $params, $old_node
        );
    }

    /**
     * Test if the input seems to be a real color.
     *
     * @param string $color  The string to check.
     * @param array  $params The parameters for this operation.
     *
     * @return NULL
     *
     * @throws Horde_Kolab_Format_Exception If the input is no color.
     */
    private function _checkColor($color, $params)
    {
        if (!preg_match('/^#[0-9a-fA-F]{6}$/', $color)
            && !$this->isRelaxed($params)) {
            throw new Horde_Kolab_Format_Exception(
                sprintf('Invalid color input "%s"!', $color)
            );
        }
    }

}
