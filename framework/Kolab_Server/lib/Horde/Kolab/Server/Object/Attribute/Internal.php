<?php
/**
 * The class represents internal-only object attributes.
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_Server
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Server
 */

/**
 * The class represents internal-only object attributes.
 *
 * Copyright 2008-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category Kolab
 * @package  Kolab_Server
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Server
 */
class Horde_Kolab_Server_Object_Attribute_Internal
extends Horde_Kolab_Server_Object_Attribute_Value
{
    /**
     * Return the value of this attribute.
     *
     * @return array The value(s) of this attribute.
     */
    public function value()
    {
        throw new Horde_Kolab_Server_Exception(
            sprintf(
                "Attribute \"%s\" is not visible!",
                $this->_name
            )
        );
    }
}