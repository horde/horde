<?php
/**
 * A decorator to represent a Kolab object attribute that can only be written on
 * object creation and is immutable afterwards.
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
 * A decorator to represent a Kolab object attribute that can only be written on
 * object creation and is immutable afterwards.
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
class Horde_Kolab_Server_Object_Attribute_Single
extends Horde_Kolab_Server_Object_Attribute_Decorator
{
    /**
     * Return the value of this attribute.
     *
     * @return array The value(s) of this attribute.
     */
    public function value()
    {
        $value = $this->_attribute->value();
        if (is_array($value)) {
            return array_shift($value);
        } else {
            return $value;
        }
    }
}