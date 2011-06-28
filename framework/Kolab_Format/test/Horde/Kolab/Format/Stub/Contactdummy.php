<?php
/**
 * A modification to the original contact handler. This prevents unpredictable
 * date entries.
 *
 * PHP version 5
 *
 * @category   Kolab
 * @package    Kolab_Format
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link       http://pear.horde.org/index.php?package=Kolab_Format
 */

/**
 * A modification to the original contact handler. This prevents unpredictable
 * date entries.
 *
 * Copyright 2007-2011 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category   Kolab
 * @package    Kolab_Format
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link       http://pear.horde.org/index.php?package=Kolab_Format
 */
class Horde_Kolab_Format_Xml_Contactdummy
extends Horde_Kolab_Format_Xml_Contact
{

    /**
     * Save the object modification date.
     *
     * @param DOMNode $parent_node The parent node to attach
     *                             the child to.
     * @param string  $name        The name of the node.
     * @param mixed   $value       The value to store.
     * @param boolean $missing     Has the value been missing?
     *
     * @return DOMNode The new child node.
     */
    function _saveModificationDate($parent_node, $name, $value, $missing)
    {
        // Always store now as modification date
        return $this->_saveDefault($parent_node,
                                   $name,
                                   0,
                                   array('type' => self::TYPE_DATETIME));
    }
}
