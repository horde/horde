<?php
/**
 * The "createTimestamp" attribute converted to Horde_Date.
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
 * The "createTimestamp" attribute converted to Horde_Date.
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
class Horde_Kolab_Server_Object_Attribute_Createtimestampdate
extends Horde_Kolab_Server_Object_Attribute_Createtimestamp
{
    /**
     * Return the value of this attribute.
     *
     * @return mixed The value of this attribute.
     */
    public function value()
    {
        return new Horde_Date(parent::value());
    }
}