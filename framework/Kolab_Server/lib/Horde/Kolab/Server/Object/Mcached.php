<?php
/**
 * Low level caching for the Kolab object.
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
 * Low level caching for the Kolab object.
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
class Horde_Kolab_Server_Object_Mcached
implements Horde_Kolab_Server_Object_Interface
{
    /**
     * Link to the decorated object.
     *
     * @var Horde_Kolab_Server_Object
     */
    private $_object;

    /**
     * The external attributes supported by this class.
     *
     * @var array
     */
    protected $_attributes_ext;

    /**
     * The internal attributes required for this class.
     *
     * @var array
     */
    protected $_attributes_int;

    /**
     * Does the object exist?
     *
     * @return boolean True if the object exists, false otherwise.
     */
    private $_exists;

    /**
     * The cached internal result
     *
     * @var array
     */
    private $_cache_int = array();

    /**
     * The cached external attribute values
     *
     * @var array
     */
    private $_cache_ext = array();

    /**
     * A cache for the list of actions this object supports.
     *
     * @var array
     */
    protected $_actions;

    /**
     * Initialize the Kolab Object. Provide either the GUID
     *
     * @param Horde_Kolab_Server_Composite $composite The link to the Kolab server.
     * @param string                       $guid      GUID of the object.
     */
    public function __construct(
        Horde_Kolab_Server_Object $object
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
     * @return array The external attributes supported by this object. This is a
     * list of abbreviated attribute class names.
     */
    public function getExternalAttributes()
    {
        if (empty($this->_attributes_ext)) {
            $this->_attributes_ext = $this->_object->getExternalAttributes();
        }
        return $this->_attributes_ext;
    }

    /**
     * Get the internal attributes supported by this object.
     *
     * @return array The internal attributes supported by this object. This is
     * an association of internal attribute names an the correspodning attribute
     * class names.
     */
    public function getInternalAttributes()
    {
        if (empty($this->_attributes_int)) {
            $this->_attributes_int = $this->_object->getInternalAttributes();
        }
        return $this->_attributes_int;
    }

    /**
     * Set the internal data of this object.
     *
     * @param array $data A data array for the object.
     *
     * @return NULL
     */
    public function setInternalData(array $data)
    {
        $this->_cache_int = $data;
    }

    /**
     * Does the object exist?
     *
     * @return NULL
     */
    public function exists()
    {
        if ($this->_exists === null) {
            $this->_exists = $this->_object->exists();
        }
        return $this->_exists;
    }

    /**
     * Read the object data.
     *
     * @return array The read data.
     */
    public function readInternal()
    {
        $this->_cache_int = array_merge(
            $this->_cache_int,
            $this->_object->readInternal()
        );
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
        if (!isset($this->_cache_int[$attr])) {
            if (!in_array($attr, array_keys($this->getInternalAttributes()))) {
                throw new Horde_Kolab_Server_Exception(sprintf("Attribute \"%s\" not supported!",
                                                               $attr));
            }
            $this->_object->readInternal();
            if (!isset($this->_cache_int[$attr])) {
                throw new Horde_Kolab_Server_Exception(sprintf("Failed to read attribute \"%s\"!",
                                                               $attr));
            }
        }
        return $this->_cache_int[$attr];
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
        if (!isset($this->_cache_ext[$attr])) {
            $this->_cache_ext[$attr] = $this->_object->getExternal($attr);
        }
        return $this->_cache_ext[$attr];
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
        return $this->_object->getSingle($attr);
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
        return $this->_object->toHash($attrs, $single);
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

        /** Mark the object as existing */
        $this->_exists = true;

        /**
         * Throw away the cache data to ensure it gets refetched in case we need
         * to access it again
         */
        $this->_cache_ext = array();
        $this->_cache_int = array();
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

        /** Mark the object as missing */
        $this->_exists = false;

        /**
         * Throw away the cache data to ensure it gets refetched in case we need
         * to access it again
         */
        $this->_cache_ext = array();
        $this->_cache_int = array();
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
        if (!isset($this->_actions)) {
            $this->_actions = $this->_object->getActions();
        }
        return $this->_actions;
    }
}
