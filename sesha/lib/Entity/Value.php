<?php
/**
 * A Sesha_Entity_Value is an object representing the value(s) for a property
 * in the context of a specific inventory item.
 *
 * This is the ORM encapsulation of a row in the sesha_inventory_properties
 * table.
 *
 * @author   Ralf Lang <lang@b1-systems.de>
 * @category Horde
 * @package  Sesha
 */
class Sesha_Entity_Value extends Horde_Rdo_Base
{
    /**
     * Retrieves the txt_datavalue or int_datavalue depending on context
     */
    public function getDataValue()
    {
        /* These field-specific handlers should better be delegated to field
         * definitions. */
        switch ($this->property->datatype) {
        case 'date':
        case 'datetime':
        case 'hourminutesecond':
        case 'monthdayyear':
        case 'monthyear':
        case 'time':
            if (strval(intval($this->txt_datavalue)) === $this->txt_datavalue) {
                return new Horde_Date($this->txt_datavalue);
            }
            $dt = new Horde_Date;
            foreach (Horde_Serialize::unserialize($this->txt_datavalue, Horde_Serialize::BASIC) as $marker => $content) {
                if (strlen($content)) {
                    $dt->$marker = $content;
                }
            }
            return $dt;

        case 'image':
            return array('hash' => $this->txt_datavalue);

        default:
            return $this->txt_datavalue;
        }
    }

    /**
     * Saves the txt_datavalue or int_datavalue depending on context.
     *
     * Folds special data types into a serializable, preferably search-friendly
     * format.
     */
    public function setDataValue($value)
    {
        /* These field-specific handlers should better be delegated to field
         * definitions. */
        switch ($this->property->datatype) {
        case 'date':
        case 'datetime':
        case 'hourminutesecond':
        case 'monthdayyear':
        case 'monthyear':
        case 'time':
            if (is_array($value)) {
                // Directly passing the array makes funny breakage :(
                $dt = new Horde_Date();
                foreach ($value as $marker => $content) {
                    if (strlen($content)) {
                        $dt->$marker = $content;
                    }
                }
                $value = $dt->datestamp();
            }
            break;

        case 'image':
            $value = $value['hash'];
            break;
        }

        return $this->txt_datavalue = $value;
    }
}

