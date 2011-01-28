<?php
/**
 * The base class representing Kolab object attributes.
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
 * The base class representing Kolab object attributes.
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
abstract class Horde_Kolab_Server_Object_Attribute_Base
implements Horde_Kolab_Server_Object_Attribute_Interface
{
    /**
     * The attribute name.
     *
     * @param string
     */
    protected $name;

    /**
     * The internal attribute adapter.
     *
     * @param Horde_Kolab_Server_Structure_Attribute_Interface
     */
    protected $attribute;

    /**
     * Constructor
     *
     * @param Horde_Kolab_Server_Structure_Attribute_Interface $attribute The internal attribute adapter.
     * @param string                                           $name      The name of this attribute.
     */
    public function __construct(
        Horde_Kolab_Server_Structure_Attribute_Interface $attribute,
        $name
    ) {
        $this->attribute = $attribute;
        $this->name      = $name;
    }

    /**
     * Return the internal attribute adapter.
     *
     * @return Horde_Kolab_Server_Structure_Attribute_Interface The internal
     *                                                          attribute.
     */
    public function getAttribute()
    {
        return $this->attribute;
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
     * Return if this attribute is undefined in the given data array.
     *
     * @param array $changes The data array to test.
     *
     * @return string The name of this object.
     */
    public function isEmpty(array $changes)
    {
        if ((!in_array($this->name, array_keys($changes))
             || $changes[$this->name] === null
             || $changes[$this->name] === ''
             || $changes[$this->name] === array())
        ) {
            return true;
        }
        return false;
    }
}