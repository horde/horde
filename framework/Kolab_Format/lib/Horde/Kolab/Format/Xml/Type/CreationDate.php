<?php
/**
 * Handles the creation date attribute.
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
 * Handles the creation date attribute.
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
class Horde_Kolab_Format_Xml_Type_CreationDate
extends Horde_Kolab_Format_Xml_Type_Base
{
    /**
     * Load the node value from the Kolab object.
     *
     * @param string  $name        The name of the the attribute
     *                             to be fetched.
     * @param array   &$attributes The data array that holds all
     *                             attribute values.
     * @param DOMNode $parent_node The parent node of the node to be loaded.
     *
     * @return DOMNode|boolean The named DOMNode or false if no node value was
     *                         found.
     */
    public function load($name, &$attributes, $parent_node)
    {
        $result = parent::load($name, $attributes, $parent_node);
        if ($result !== false) {
            return $result;
        } else {
            $attributes[$name] = new DateTime();
        }
    }

    /**
     * Load the value of a node.
     *
     * @param DOMNode $node Retrieve value for this node.
     *
     * @return mixed|null The value or null if no value was found.
     */
    public function loadNodeValue($node)
    {
        $result = $this->fetchNodeValue($node);
        if ($result !== null) {
            $date = Horde_Kolab_Format_Date::readUtcDateTime($result);
            if ($date === false && !$this->isRelaxed()) {
                throw new Horde_Kolab_Format_Exception(
                    sprintf('Invalid date input "%s"!', $result)
                );
            }
            return $date;
        } else {
            return $result;
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
                $creation_date = new DateTime();
            } else {
                $creation_date = $attributes[$name];
            }
            return $this->storeNewNodeValue(
                $parent_node,
                $name,
                Horde_Kolab_Format_Date::writeUtcDateTime($creation_date)
            );
        }
        if (isset($attributes[$name])) {
            if (($old = $this->loadNodeValue($node)) != $attributes[$name]) {
                if (!$this->isRelaxed()) {
                    throw new Horde_Kolab_Format_Exception(
                        sprintf(
                            'Not attempting to overwrite old %s %s with new value %s!',
                            $name,
                            Horde_Kolab_Format_Date::writeUtcDateTime($old),
                            Horde_Kolab_Format_Date::writeUtcDateTime($attributes[$name])
                        )
                    );
                } else {
                    $this->replaceFirstNodeTextValue(
                        $node, 
                        Horde_Kolab_Format_Date::writeUtcDateTime(
                            $attributes[$name]
                        )
                    );
                }
            }
        }
        return $node;
    }
}
