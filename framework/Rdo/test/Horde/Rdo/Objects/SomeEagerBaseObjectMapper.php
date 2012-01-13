<?php
class Horde_Rdo_Test_Objects_SomeEagerBaseObjectMapper extends Horde_Rdo_Mapper
{
    /**
     * Inflector doesn't support Horde-style tables yet
     */
    protected $_table = 'test_someeagerbaseobjects';
    protected $_relationships = array(
        'eagerRelatedThing'  => array('type' => Horde_Rdo::ONE_TO_ONE,
                'foreignKey' => 'relatedthing_id',
                'mapper' => 'Horde_Rdo_Test_Objects_RelatedThingMapper'),
            );
}
