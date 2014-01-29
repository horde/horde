<?php
/**
 * Handles api version 1 of the creation date attribute.
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
 * Handles api version 1 of the creation date attribute.
 *
 * Copyright 2011-2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you did not
 * receive this file, see
 * http://www.horde.org/licenses/lgpl21.
 *
 * @category Kolab
 * @package  Kolab_Format
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL
 * @link     http://www.horde.org/libraries/Horde_Kolab_Format
 */
class Horde_Kolab_Format_Xml_Type_CreationDate_V1
extends Horde_Kolab_Format_Xml_Type_AutomaticDate_V1
{
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
        $node = $helper->findNodeRelativeTo(
            './' . $name, $parent_node
        );
        if ($node !== false) {
            if (isset($attributes[$name]) &&
                ($old = $this->loadNodeValue($node, $helper, $params)) != $attributes[$name]) {
                if (!$this->isRelaxed($params)) {
                    throw new Horde_Kolab_Format_Exception(
                        sprintf(
                            'Not attempting to overwrite old %s %s with new value %s!',
                            $name,
                            Horde_Kolab_Format_Date::writeDateTime($old),
                            Horde_Kolab_Format_Date::writeDateTime($attributes[$name])
                        )
                    );
                }
            } else {
                return $node;
            }
        }
        $result = $this->saveNodeValue(
            $name,
            $this->generateWriteValue($name, $attributes, $params),
            $parent_node,
            $helper,
            $params,
            $node
        );
        return ($node !== false) ? $node : $result;
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
            return Horde_Kolab_Format_Date::writeUtcDateTime(
                $attributes[$name]
            );
        } else {
            return Horde_Kolab_Format_Date::writeUtcDateTime(new DateTime());
        }
    }

}
