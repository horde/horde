<?php
/**
 * Handles the UID attribute.
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
 * Handles the UID attribute.
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
class Horde_Kolab_Format_Xml_Type_Uid
extends Horde_Kolab_Format_Xml_Type_Base
{
    /**
     * Load the uid of the Kolab object.
     *
     * @param string  $name        The name of the the attribute
     *                             to be fetched.
     * @param array   &$attributes The data array that holds all
     *                             attribute values.
     * @param DOMNode $parent_node The parent node of the node to be loaded.
     *
     * @return NULL
     *
     * @throws Horde_Kolab_Format_Exception_MissingUid In case the uid node is
     * missing.
     */
    public function load($name, &$attributes, $parent_node)
    {
        $result = parent::load($name, $attributes, $parent_node);
        if (!$result && !$this->isRelaxed()) {
            throw new Horde_Kolab_Format_Exception_MissingUid();
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
        if (!($node = $this->findNodeRelativeTo('./' . $name, $parent_node))) {
            if (!isset($attributes['uid'])) {
                if ($this->isRelaxed()) {
                    return false;
                } else {
                    throw new Horde_Kolab_Format_Exception_MissingUid();
                }
            }
            return $this->storeNewNodeValue(
                $parent_node, $name, $attributes['uid']
            );
        }
        if (isset($attributes[$name])) {
            if (($old = $this->fetchNodeValue($node)) != $attributes[$name]) {
                if (!$this->isRelaxed()) {
                    throw new Horde_Kolab_Format_Exception(
                        sprintf(
                            'Not attempting to overwrite old %s %s with new value %s!',
                            $name,
                            $old,
                            $attributes['uid']
                        )
                    );
                } else {
                    $this->replaceFirstNodeTextValue($node, $attributes[$name]);
                }
            }
        }
        return $node;
    }
}
