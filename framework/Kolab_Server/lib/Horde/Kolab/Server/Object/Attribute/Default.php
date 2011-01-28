<?php
/**
 * A decorator to represent an object attribute with a default.
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
 * A decorator to represent an object attribute with a default.
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
class Horde_Kolab_Server_Object_Attribute_Default
extends Horde_Kolab_Server_Object_Attribute_Decorator
{
    /**
     * The default value for the attribute.
     *
     * @param mixed
     */
    private $_default;

    /**
     * Constructor
     *
     * @param Horde_Kolab_Server_Object_Attribute $attribute The decorated
     *                                                       attribute.
     * @param mixed                               $default   The default value.
     */
    public function __construct(
        Horde_Kolab_Server_Object_Attribute $attribute,
        $default
    ) {
        $this->_default   = $default;
        parent::__construct($attribute);
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
        if (!$this->_attribute->getObject()->exists()
            && !$this->_attribute->isEmpty($changes)) {
            $changes[$this->_attribute->getExternalName()] = $this->_default;
        }
        return $this->_attribute->changes($changes);
    }
}