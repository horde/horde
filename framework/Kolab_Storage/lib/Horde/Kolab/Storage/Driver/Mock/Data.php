<?php
/**
 * Data storage for the mock driver.
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_Storage
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Storage
 */

/**
 * Data storage for the mock driver.
 *
 * Copyright 2010-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category Kolab
 * @package  Kolab_Storage
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Storage
 */
class Horde_Kolab_Storage_Driver_Mock_Data
implements ArrayAccess
{
    /**
     * The data array.
     *
     * @var array
     */
    private $_data;

    /**
     * Constructor.
     *
     * @param array $data The initial data.
     */
    public function __construct($data)
    {
        $this->_data = $data;
    }

    /**
     * Returns the value of the given offset in this array.
     *
     * @param string|int $offset The array offset.
     *
     * @return mixed The data value.
     */
    public function offsetGet($offset)
    {
        return $this->_data[$offset];
    }

    /**
     * Sets the value of the given offset in this array.
     *
     * @param string|int $offset The array offset.
     * @param mi $offset The array offset.
     *
     * @return NULL
     */
    public function offsetSet($offset, $value)
    {
        $this->_data[$offset] = $value;
    }

    /**
     * Tests if the value of the given offset exists in this array.
     *
     * @param string|int $offset The array offset.
     *
     * @return boolean True if the offset exists.
     */
    public function offsetExists($offset)
    {
        return isset($this->_data[$offset]);
    }

    /**
     * Removes the given offset exists from this array.
     *
     * @param string|int $offset The array offset.
     *
     * @return NULL
     */
    public function offsetUnset($offset)
    {
        unset($this->_data[$offset]);
    }

    /**
     * Returns the array keys of this array.
     *
     * @return array The keys of this array.
     */
    public function arrayKeys()
    {
        return array_keys($this->_data);
    }

    public function hasPermissions($folder)
    {
        return isset($this->_data[$folder]['permissions']);
    }

    public function getPermissions($folder)
    {
        return $this->_data[$folder]['permissions'];
    }

    public function hasUserPermissions($folder, $user)
    {
        return isset($this->_data[$folder]['permissions'][$user]);
    }

    public function getUserPermissions($folder, $user)
    {
        return $this->_data[$folder]['permissions'][$user];
    }

    public function setUserPermissions($folder, $user, $acl)
    {
        $this->_data[$folder]['permissions'][$user] = $acl;
    }

    public function deleteUserPermissions($folder, $user)
    {
        unset($this->_data[$folder]['permissions'][$user]);
    }

    public function hasAnnotation($folder, $annotation)
    {
        return isset($this->_data[$folder]['annotations'][$annotation]);
    }

    public function getAnnotation($folder, $annotation)
    {
        return $this->_data[$folder]['annotations'][$annotation];
    }

    public function setAnnotation($folder, $annotation, $value)
    {
        $this->_data[$folder]['annotations'][$annotation] = $value;
    }

    public function deleteAnnotation($folder, $annotation)
    {
        unset($this->_data[$folder]['annotations'][$annotation]);
    }
}