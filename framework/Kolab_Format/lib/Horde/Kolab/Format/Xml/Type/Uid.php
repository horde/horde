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
     * @throws Horde_Kolab_Format_Exception_MissingValue In case the uid node is
     * missing.
     */
    public function load($name, &$attributes, $parent_node)
    {
        if ($uid = $this->findNodeRelativeTo('./uid', $parent_node)) {
            foreach ($uid->childNodes as $child) {
                if ($child->nodeType === XML_TEXT_NODE) {
                    $attributes['uid'] = $child->nodeValue;
                    return;
                }
            }
        }
        if (!$this->isRelaxed()) {
            throw new Horde_Kolab_Format_Exception_MissingValue('uid');
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
        if (!($uid = $this->findNodeRelativeTo('./uid', $parent_node))) {
            if (!isset($attributes['uid'])) {
                if ($this->isRelaxed()) {
                    return false;
                } else {
                    throw new Horde_Kolab_Format_Exception_MissingValue('uid');
                }
            }
            $uid = $this->_xmldoc->createElement('uid');
            $parent_node->appendChild($uid);
            $uid->appendChild(
                $this->_xmldoc->createTextNode($attributes['uid'])
            );
            return $uid;
        }
        if (isset($attributes['uid'])) {
            foreach ($uid->childNodes as $child) {
                if ($child->nodeType === XML_TEXT_NODE) {
                    if (!$this->isRelaxed() && $child->nodeValue !== $attributes['uid']) {
                        throw new Horde_Kolab_Format_Exception(
                            sprintf(
                                'Not attempting to overwrite old uid %s with new uid %s!',
                        $child->nodeValue,
                        $attributes['uid']
                    )
                        );
                    }
                    $uid->removeChild($child);
                    break;
                }
            }
            $new_node = $this->_xmldoc->createTextNode($attributes['uid']);
            if (empty($uid->childNodes)) {
                $uid->appendChild($new_node);
            } else {
                $uid->insertBefore($new_node, $uid->childNodes->item(0));
            }
        }
        return $uid;
    }
}
