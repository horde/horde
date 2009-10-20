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
 * Copyright 2008-2009 The Horde Project (http://www.horde.org/)
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
implements Horde_Kolab_Server_Object_Attribute
{
    /**
     * The attribute name on the internal side.
     *
     * @param string
     */
    protected $_internal;

    /**
     * The attribute name on the external side.
     *
     * @param string
     */
    private $_external;

    /**
     * The object this attribute belongs to.
     *
     * @param Horde_Kolab_Server_Object
     */
    protected $_object;

    /**
     * Link to the Kolab server.
     *
     * @var Horde_Kolab_Server_Composite
     */
    protected $_composite;

    /**
     * Constructor
     *
     * @param Horde_Kolab_Server_Object    $object    The object this attribute
     *                                                belongs to.
     * @param Horde_Kolab_Server_Composite $composite The link to the server.
     * @param string                       $name      The name of this attribute.
     */
    public function __construct(
        Horde_Kolab_Server_Object $object,
        Horde_Kolab_Server_Composite $composite,
        $internal,
        $external = null
    ) {
        $this->_internal  = $internal;
        $this->_object    = $object;
        $this->_composite = $composite;
        $this->_external  = $external;
    }

    /**
     * Return the object this attribute belongs to.
     *
     * @return Horde_Kolab_Server_Object The object.
     */
    public function getObject()
    {
        return $this->_object;
    }

    /**
     * Return the internal name of this attribute.
     *
     * @return string The name of this object.
     */
    public function getInternalName()
    {
        return $this->_internal;
    }

    /**
     * Return the external name of this attribute.
     *
     * @return string The name of this object.
     */
    public function getExternalName()
    {
        if (empty($this->_external)) {
            $this->_external = substr(get_class($this), 36);
        }
        return $this->_external;
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
        $name = $this->getExternalName();
        if ((!in_array($name, array_keys($changes))
             || $changes[$name] === null
             || $changes[$name] === ''
             || $changes[$name] === array())) {
            return true;
        }
        return false;
    }
}