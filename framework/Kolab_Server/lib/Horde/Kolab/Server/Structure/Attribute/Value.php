<?php
/**
 * The base class representing internal Kolab object attributes.
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
 * The base class representing internal Kolab object attributes.
 *
 * Copyright 2009-2011 The Horde Project (http://www.horde.org/)
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
class Horde_Kolab_Server_Structure_Attribute_Value
implements Horde_Kolab_Server_Structure_Attribute_Interface
{
    /**
     * The attribute name.
     *
     * @param string
     */
    protected $name;

    /**
     * The object the attribute belongs to.
     *
     * @param Horde_Kolab_Server_Object_Interface
     */
    protected $object;

    /**
     * Constructor
     *
     * @param Horde_Kolab_Server_Object_Interface $object The object
     *  this attribute belongs to.
     * @param string $name The name of this attribute.
     */
    public function __construct(
        Horde_Kolab_Server_Object_Interface $object,
        $name
    ) {
        $this->object = $object;
        $this->name   = $name;
    }

    /**
     * Return the internal attribute adapter.
     *
     * @return Horde_Kolab_Server_Object_Interface The object the attribute belongs to.
     */
    public function getObject()
    {
        return $this->object;
    }

    /**
     * Return the name of this attribute.
     *
     * @return string The name of this attribute.
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Return the value of this attribute.
     *
     * @return array The value of the attribute
     */
    public function value()
    {
        return $this->object->getInternal((array) $this->name);
    }

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
        return array();
    }
}