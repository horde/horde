<?php
class Sesha_Entity_Stock extends Horde_Rdo_Base
{

    /**
     * Get a single Sesha_Entity_Value object by property
     * @params mixed property  Sesha_Entity_Property | integer property_id
     * @return Sesha_Entity_Value|null A Value if found
     */
    public function getValue($property)
    {
        $am = $this->_mapper->factory->create('Sesha_Entity_ValueMapper');
        return $am->findOne(array(
                    'stock_id' => $this->stock_id,
                    'property_id' => $property instanceof Sesha_Entity_Property ? $property->property_id : $property
                )
            );
    }

}

