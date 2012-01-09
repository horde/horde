<?php
class Horde_Rdo_Test_Objects_SomeBaseObjectMapper extends Horde_Rdo_Mapper
{
    /**
     * Inflector doesn't support Horde-style tables yet
     */
    protected $_table = 'test_somebaseobjects';
    protected $_lazyRelationships = array(
        'relatedthing'  => array('type' => Horde_Rdo::ONE_TO_ONE,
                'foreignKey' => 'relatedthing_id',
                'mapper' => 'Horde_Rdo_Test_Objects_RelatedThingMapper'),
            );
}
