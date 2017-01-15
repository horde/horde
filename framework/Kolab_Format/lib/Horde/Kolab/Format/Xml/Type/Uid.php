<?php
/**
 * Handles the UID attribute.
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
 * Handles the UID attribute.
 *
 * Copyright 2011-2017 Horde LLC (http://www.horde.org/)
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
class Horde_Kolab_Format_Xml_Type_Uid
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
        if (!$result && !$this->isRelaxed($params)) {
            throw new Horde_Kolab_Format_Exception_MissingUid();
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
        $node = $helper->findNodeRelativeTo(
            './' . $name, $parent_node
        );

        if ($node === false) {
            if (!isset($attributes[$name])) {
                if ($this->isRelaxed($params)) {
                    return false;
                } else {
                    throw new Horde_Kolab_Format_Exception_MissingUid();
                }
            }
        } else {
            if (isset($attributes[$name])) {
                if (($old = $this->loadNodeValue($node, $helper, $params)) != $attributes[$name]) {
                    if (!$this->isRelaxed($params)) {
                        throw new Horde_Kolab_Format_Exception(
                            sprintf(
                                'Not attempting to overwrite old %s %s with new value %s!',
                                $name,
                                $old,
                                $attributes['uid']
                            )
                        );
                    }
                } else {
                    return $node;
                }
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
}
