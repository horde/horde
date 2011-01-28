<?php
/**
 * A base class for attribute decorators.
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
 * A base class for attribute decorators.
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
class Horde_Kolab_Server_Object_Attribute_Decorator
implements Horde_Kolab_Server_Object_Attribute_Interface
{
    /**
     * The decorated attribute.
     *
     * @param Horde_Kolab_Server_Object_Attribute
     */
    protected $_attribute;

    /**
     * Constructor
     *
     * @param Horde_Kolab_Server_Object_Attribute $attribute The decorated
     *                                                       attribute.
     */
    public function __construct(
        Horde_Kolab_Server_Object_Attribute_Interface $attribute
    ) {
        $this->_attribute = $attribute;
    }

    /**
     * Return the value of this attribute.
     *
     * @return array The value(s) of this attribute.
     */
    public function value()
    {
        return $this->_attribute->value();
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
        return $this->_attribute->update($changes);
    }

    /**
     * Return the internal attribute adapter.
     *
     * @return Horde_Kolab_Server_Structure_Attribute_Interface The internal
     */
    public function getAttribute()
    {
        return $this->_attribute->getAttribute();
    }

    /**
     * Return the name of this attribute.
     *
     * @return string The name of this attribute.
     */
    public function getName()
    {
        return $this->_attribute->getName();
    }

    /**
     * Return if this attribute is undefined in the given data array.
     *
     * @param array $changes The data array to test.
     *
     * @return string The name of this object.
     */
    public function isEmpty(array $changes)
    {
        return $this->_attribute->isEmpty($changes);
    }
}