<?php
/**
 * Extension of the DataTreeObject_Group class for storing Group information.
 *
 * Copyright 2008-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Michael J. Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @package  Group
 */
class Horde_Group_ContactListObject extends Horde_Group_DataTreeObject
{
    /**
     * The unique name of this object.
     * These names have the same requirements as other object names - they
     * must be unique, etc.
     *
     * @var integer
     */
    public $id;

    /**
     * Constructor.
     *
     * @param string $name  The name of the group.
     */
    public function __construct($name)
    {
        $this->name = $name;
    }

    /**
     * Gets the ID of this object.
     *
     * @return string  The object's ID.
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Gets the name of this object.
     *
     * @return string The object name.
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Gets one of the attributes of the object, or null if it isn't defined.
     *
     * @param string $attribute  The attribute to get.
     *
     * @return mixed  The value of the attribute, or null.
     */
    public function get($attribute)
    {
        return isset($this->data[$attribute])
            ? $this->data[$attribute]
            : null;
    }

    /**
     * Sets one of the attributes of the object.
     *
     * @param string $attribute  The attribute to set.
     * @param mixed $value       The value for $attribute.
     *
     * @throws Horde_Group_Exception
     */
    public function set($attribute, $value)
    {
        throw new Horde_Group_Exception('Unsupported.');
    }

    /**
     * Save group.
     *
     * @throws Horde_Group_Exception
     */
    public function save()
    {
        throw new Horde_Group_Exception('Unsupported.');
    }

    /**
     * @throws Horde_Group_Exception
     */
    public function removeUser($username, $update = true)
    {
        throw new Horde_Group_Exception('Unsupported.');
    }

    /**
     * @throws Horde_Group_Exception
     */
    function addUser($username, $update = true)
    {
        throw new Horde_Group_Exception('Unsupported.');
    }

}
