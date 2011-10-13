<?php
/**
 * Handles the recurrence range attribute.
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
 * Handles the recurrence range attribute.
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
class Horde_Kolab_Format_Xml_Type_Range
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
        $result = $params['helper']->fetchNodeValue($node);
        $type = $node->getAttribute('type');
        if (empty($type) || $type == 'none') {
            return null;
        }
        if ($type == 'date') {
            $tz = $node->getAttribute('tz');
            if (empty($tz)) {
                /**
                 * @todo Be more strict once KEP2 has been completely adopted if
                 * (!$this->isRelaxed()) throw new Horde_Kolab_Format_Exception();
                 */
                $tz = 'UTC';
            }
            return Horde_Kolab_Format_Date::readDate($result, $tz);
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
        $tz = false;
        if (empty($value)) {
            $type = 'none';
            $value = '';
        } else if ($value instanceOf DateTime) {
            $type = 'date';
            $tz = $value->getTimezone()->getName();
            $value = Horde_Kolab_Format_Date::writeDate($value);
        } else {
            $type = 'number';
        }
        $node = parent::saveNodeValue(
            $name, $value, $parent_node, $params, $old_node
        );
        $node->setAttribute('type', $type);
        if ($tz !== false) {
            $node->setAttribute('tz', $tz);
        }
        return $node;
    }
}
