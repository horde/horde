<?php
/**
 * A decorator to represent required Kolab object attributes.
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_Server
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Kolab_Server
 */

/**
 * A decorator to represent required Kolab object attributes.
 *
 * Copyright 2008-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category Kolab
 * @package  Kolab_Server
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Kolab_Server
 */
class Horde_Kolab_Server_Object_Attribute_Required
extends Horde_Kolab_Server_Object_Attribute_Decorator
{
    /**
     * Return the new internal state for this attribute.
     *
     * @param array $changes The object data that should be updated.
     *
     * @return array The resulting internal state.
     *
     * @throws Horde_Kolab_Server_Exception If storing the value failed.
     */
    public function update(array $changes)
    {
        $changes = $this->_attribute->update($changes);
        if (empty($changes) && !$this->_attribute->getObject()->exists()) {
            throw new Horde_Kolab_Server_Exception(
                sprintf(
                    "The value for \"%s\" is empty but required!",
                    $this->_attribute->getExternalName()
                )
            );
        }
        return $changes;
    }
}