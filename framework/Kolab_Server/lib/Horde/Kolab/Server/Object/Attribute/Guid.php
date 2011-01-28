<?php
/**
 * The GUID attribute.
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
 * The GUID attribute.
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
class Horde_Kolab_Server_Object_Attribute_Guid
extends Horde_Kolab_Server_Object_Attribute_External
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
        parent::__construct($object, $composite, 'Guid');
    }

    /**
     * Return the value of this attribute.
     *
     * @return array The value(s) of this attribute.
     */
    public function value()
    {
        return $this->_object->getGuid();
    }
}