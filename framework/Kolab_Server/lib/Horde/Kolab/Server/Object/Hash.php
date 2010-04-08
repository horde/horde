<?php
/**
 * Provides array access to Kolab objects.
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
 * Provides array access to Kolab objects.
 *
 * Copyright 2008-2010 The Horde Project (http://www.horde.org/)
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
class Horde_Kolab_Server_Object_Hash
implements Horde_Kolab_Server_Object_Interface
//@todo: Implement ArrayAccess
{
    /**
     * Link to the decorated object.
     *
     * @var Horde_Kolab_Server_Object
     */
    private $_object;

    /**
     * Initialize the Kolab Object. Provide either the GUID
     *
     * @param Horde_Kolab_Server_Object $object The represented object.
     */
    public function __construct(
        Horde_Kolab_Server_Object_Interface $object
    ) {
        $this->_object = $object;
    }

    /**
     * Get the GUID of this object
     *
     * @return string the GUID of this object
     */
    public function getGuid()
    {
        return $this->_object->getGuid();
    }

    /**
     * Get the external attributes supported by this object.
     *
     * @return array The external attributes supported by this object. This is
     * an association of attribute names and attribute handler class names.
     */
    public function getExternalAttributes()
    {
        return $this->_object->getExternalAttributes();
    }

    /**
     * Get the internal attributes supported by this object.
     *
     * @return array The internal attributes supported by this object.
     */
    public function getInternalAttributes()
    {
        return $this->_object->getInternalAttributes();
    }

    /**
     * Does the object exist?
     *
     * @return NULL
     */
    public function exists()
    {
        return $this->_object->exists();
    }

    /**
     * Read the object into the cache
     *
     * @return array The read data.
     */
    public function readInternal()
    {
        return $this->_object->readInternal();
    }

    /**
     * Get the specified internal attributes.
     *
     * @param array $attributes The internal attribute.
     *
     * @return array The value(s) of these attribute
     */
    public function getInternal(array $attributes)
    {
        return $this->_object->getInternal($attributes);
    }

    /**
     * Get the specified attribute of this object.
     *
     * @param string $attr The attribute to read.
     *
     * @return mixed The value of this attribute.
     */
    public function getExternal($attr)
    {
        return $this->_object->getExternal($attr);
    }

    /**
     * Get the specified attribute of this object and ensure that only a single
     * value is being returned.
     *
     * @param string $attr The attribute to read.
     *
     * @return mixed The value of this attribute.
     */
    public function getSingle($attr)
    {
        $value = $this->getExternal($attr);
        //@todo: Check if that can actually be something other than an array.
        if (is_array($value)) {
            return array_shift($value);
        } else {
            return $value;
        }
    }

    /**
     * Convert the object attributes to a hash.
     *
     * @param array   $attrs  The attributes to return.
     * @param boolean $single Should only a single attribute be returned?
     *
     * @return array|PEAR_Error The hash representing this object.
     */
    public function toHash(array $attrs = array(), $single = true)
    {
        $result = array();

        /**
         * Return all supported attributes if no specific attributes were
         * requested.
         */
        if (empty($attrs)) {
            $attrs = array_keys($this->attributes);
        }

        foreach ($attrs as $key) {
            if ($single) {
                $result[$key] = $this->getSingle($key);
            } else {
                $result[$key] = $this->getExternal($key);
            }
        }
        return $result;
    }

    /**
     * Saves object information. This may either create a new entry or modify an
     * existing entry.
     *
     * Please note that fields with multiple allowed values require the callee
     * to provide the full set of values for the field. Any old values that are
     * not resubmitted will be considered to be deleted.
     *
     * @param array $info The information about the object.
     *
     * @return NULL
     *
     * @throws Horde_Kolab_Server_Exception If saving the data failed.
     */
    public function save(array $info)
    {
        $this->_object->save($info);
    }

    /**
     * Delete this object.
     *
     * @return NULL
     *
     * @throws Horde_Kolab_Server_Exception If deleting the object failed.
     */
    public function delete()
    {
        $this->_object->delete();
    }

    /**
     * Generates an ID for the given information.
     *
     * @param array &$info The data of the object.
     *
     * @return string The ID.
     */
    public function generateId(array &$info)
    {
        $this->_object->generateId($info);
    }

    /**
     * Distill the server side object information to save.
     *
     * @param array &$info The information about the object.
     *
     * @return NULL.
     *
     * @throws Horde_Kolab_Server_Exception If the given information contains errors.
     */
    public function prepareObjectInformation(array &$info)
    {
        $this->_object->prepareObjectInformation($info);
    }

    /**
     * Returns the set of actions supported by this object type.
     *
     * @return array An array of supported actions.
     */
    public function getActions()
    {
        $this->_object->getActions();
    }
}
