<?php
/**
 * The name of a person in "firstname lastname" format.
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
 * The name of a person in "firstname lastname" format.
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
class Horde_Kolab_Server_Object_Attribute_Firstnamelastname
extends Horde_Kolab_Server_Object_Attribute_Value
{
    /**
     * Constructor
     *
     * @param Horde_Kolab_Server_Structure_Attribute_Interface $object The object
     *  this attribute belongs to.
     * @param string $name The name of this attribute.
     */
    public function __construct(
        Horde_Kolab_Server_Structure_Attribute_Double $attribute,
        $name
    ) {
        parent::__construct($attribute, $name);
    }

    /**
     * Return the value of this attribute.
     *
     * @return array The value(s) of this attribute.
     *
     * @throws Horde_Kolab_Server_Exception If retrieval of the value failed.
     */
    public function value()
    {
        $values = $this->attribute->value();
        return sprintf('%s %s', $values[0], $values[1]);
    }

}