<?php
class Horde_Rdo_Test_Objects_ManyToManyBMapper extends Horde_Rdo_Mapper
{
    /**
     * Inflector doesn't support Horde-style tables yet
     */
    protected $_table = 'test_manytomanyb';
    protected $_lazyRelationships = array();
}
