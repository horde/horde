<?php
class Horde_Rdo_Test_Objects_ManyToManyAMapper extends Horde_Rdo_Mapper
{
    /**
     * Inflector doesn't support Horde-style tables yet
     */
    protected $_table = 'test_manytomanya';
    protected $_lazyRelationships = array(
        'manybs' => array(
            'type' => Horde_Rdo::MANY_TO_MANY,
            'through' => 'test_manythrough',
            'mapper' => 'Horde_Rdo_Test_Objects_ManyToManyBMapper'));
}
