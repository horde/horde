<?php
class Sesha_Entity_Property extends Horde_Rdo_Base
{
    /**
     * Explicit getter for the parameters variable interface.
     * Internalizes the (un)serialization of the parameters array for backend storage
     * returns mixed
     */
    public function getParameters()
    {
        return unserialize($this->_fields['parameters']);
    }

    /**
     * Explicit setter for the parameters variable interface.
     * Internalizes the (un)serialization of the parameters array for backend storage
     * returns mixed
     */
    public function setParameters($parameters)
    {
        return $this->_fields['parameters'] = serialize($parameters);
    }

    /**
     * Save any changes to the backend.
     * Overridden because the default save() method passes the external representation to backend, not the serialized representation
     * @return boolean Success.
     */
    public function save()
    {
        return $this->getMapper()->update($this->property_id, $this->_fields) == 1;
    }

}

