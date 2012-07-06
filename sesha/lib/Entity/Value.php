<?php
/**
 * A Sesha_Entity_Value is an object representing the value(s) for a property in the context of a specific inventory item
 * This is the ORM encapsulation of a row in the sesha_inventory_properties table.
 *
 * @author   Ralf Lang <lang@b1-systems.de>
 * @category Horde
 * @package  Sesha
 */
class Sesha_Entity_Value extends Horde_Rdo_Base
{

    /**
     * Retrieve the txt_datavalue or int_datavalue depending on context
     */
    public function getDataValue()
    {
        return $this->txt_datavalue;
    }

    /**
     * Save the txt_datavalue or int_datavalue depending on context
     */
    public function setDataValue($value)
    {
        return $this->txt_datavalue = $value;
    }
}

