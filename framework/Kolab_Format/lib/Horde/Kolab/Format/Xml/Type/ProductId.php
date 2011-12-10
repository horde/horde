<?php
/**
 * Handles the product ID attribute.
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
 * Handles the product ID attribute.
 *
 * Copyright 2011 Horde LLC (http://www.horde.org/)
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
class Horde_Kolab_Format_Xml_Type_ProductId
extends Horde_Kolab_Format_Xml_Type_Base
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
            return $result;
        } else {
            $attributes[$name] = '';
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
        return Horde_Kolab_Format_Xml::PRODUCT_ID . '-'
            . Horde_Kolab_Format::VERSION
            . ' (api version: ' .  $params['api-version']
            . ')';
    }
}
