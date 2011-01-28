<?php
/**
 * The base class representing Kolab objects stored in the server
 * database.
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
 * This class provides methods to deal with Kolab objects stored in
 * the Kolab db.
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
abstract class Horde_Kolab_Server_Object_Base
implements Horde_Kolab_Server_Object_Interface
{
    /**
     * Link to the Kolab server.
     *
     * @var Horde_Kolab_Server_Composite
     */
    private $_composite;

    /**
     * GUID of this object on the Kolab server.
     *
     * @var string
     */
    protected $guid;

    /**
     * Initialize the Kolab Object. Provide either the GUID
     *
     * @param Horde_Kolab_Server_Composite $composite The link to the Kolab server.
     * @param string                       $guid      GUID of the object.
     */
    public function __construct(
        Horde_Kolab_Server_Composite $composite,
        $guid = null
    ) {
        $this->_composite = $composite;
        $this->guid       = $guid;
    }

    /**
     * Get the GUID of this object
     *
     * @return string the GUID of this object
     */
    public function getGuid()
    {
        if ($this->guid === null) {
            throw new Horde_Kolab_Server_Exception(
                'Uninitialized object is missing GUID!'
            );
        }
        return $this->guid;
    }

    /**
     * Get the external attributes supported by this object.
     *
     * @return array The external attributes supported by this object. This is a
     * list of abbreviated attribute class names.
     */
    public function getExternalAttributes()
    {
        return $this->_composite->structure->getExternalAttributes($this);
    }

    /**
     * Get the internal attributes supported by this object.
     *
     * @return array The internal attributes supported by this object.
     */
    public function getInternalAttributes()
    {
        return $this->_composite->structure->getInternalAttributes($this);
    }

    /**
     * Does the object exist?
     *
     * @return boolean True if the object exists, false otherwise.
     */
    public function exists()
    {
        try {
            $this->readInternal();
            return true;
        } catch (Horde_Kolab_Server_Exception $e) {
            return false;
        }
    }

    /**
     * Read the object data.
     *
     * @return array The read data.
     */
    public function readInternal()
    {
        return $this->_composite->server->readAttributes(
            $this->getGuid(), $this->getInternalAttributes()
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
        $result = $this->readInternal();

        $values = array();
        foreach ($attributes as $attribute) {

            if (!isset($result[$attribute])) {
                throw new Horde_Kolab_Server_Exception_Novalue(
                    sprintf(
                        "No value for attribute \"%s\"!",
                        $attribute
                    )
                );
            }
            $values[$attribute] = $result[$attribute];
        }
        return $values;
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
        if (!in_array($attr, $this->getExternalAttributes())) {
            throw new Horde_Kolab_Server_Exception(
                sprintf("Attribute \"%s\" not supported!", $attr)
            );
        }
        $attribute = $this->_composite->structure->getExternalAttribute(
            $attr, $this
        );
        return $attribute->value();
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
        /** Handle all class specific transformations of the provided data */
        $this->prepareObjectInformation($info);

        $internal = $this->getNewInternal($info);

        $guid = $this->_composite->structure->generateServerGuid(
            get_class($this), $this->generateId($internal), $internal
        );

        if ($this->exists()) {
            if ($guid != $this->guid) {
                $this->_composite->server->rename($this->guid, $guid);
                $this->guid = $guid;
            }
            $result = $this->_composite->server->save($this, $internal);
        } else {
            $this->guid = $guid;
            $this->_composite->server->add($this, $internal);
        }
    }

    /**
     * Transform the given data array into the new internal dataset.
     *
     * @param array $info The information about the object.
     *
     * @return NULL
     *
     * @throws Horde_Kolab_Server_Exception If transforming the data failed.
     */
    protected function getNewInternal($info)
    {
        $internal   = array();
        foreach ($info as $external => $value) {
            $attribute = $this->_composite->structure->getExternalAttribute(
                $external, $this
            );
            $internal = array_merge($internal, $attribute->update($info));
        }
        return $internal;
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
        $this->_composite->server->delete($this->getGuid());
    }
}
