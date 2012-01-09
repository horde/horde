<?php
class Horde_Rdo_Test_Objects_SomeLazyBaseObjectMapper extends Horde_Rdo_Mapper
{
    /**
     * Inflector doesn't support Horde-style tables yet
     */
    protected $_table = 'test_somelazybaseobjects';
    protected $_lazyRelationships = array(
        'lazyRelatedThing'  => array('type' => Horde_Rdo::ONE_TO_ONE,
                'foreignKey' => 'relatedthing_id',
                'mapper' => 'Horde_Rdo_Test_Objects_RelatedThingMapper'),
            );
}
