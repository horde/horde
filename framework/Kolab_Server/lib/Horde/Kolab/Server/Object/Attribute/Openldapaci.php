<?php
/**
 * The "OpenLDAPaci" attribute.
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
 * The "OpenLDAPaci" attribute.
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
class Horde_Kolab_Server_Object_Attribute_Openldapaci
extends Horde_Kolab_Server_Object_Attribute_Value
{
    /**
     * Constructor
     *
     * @param Horde_Kolab_Server_Object    $object    The object this attribute
     *                                                belongs to.
     * @param Horde_Kolab_Server_Composite $composite The link to the server.
     */
    public function __construct(
        Horde_Kolab_Server_Object $object,
        Horde_Kolab_Server_Composite $composite
    ) {
        parent::__construct($object, $composite, 'Openldapaci');
    }
}