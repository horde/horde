<?php
/**
 * The Sesha_Entity_StockMapper class contains all functions related to handling
 * stock mapping in Sesha.
 *
 * Copyright 2012-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Ralf Lang <lang@b1-systems.de>
 * @category Horde
 * @package  Sesha
 * @license  http://www.horde.org/licenses/gpl GPL
 */


/**
 * The Sesha_Entity_StockMapper class contains all functions related to handling
 * stock mapping in Sesha.
 *
 * Copyright 2012-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Ralf Lang <lang@b1-systems.de>
 * @category Horde
 * @package  Sesha
 * @license  http://www.horde.org/licenses/gpl GPL
 */
class Sesha_Entity_StockMapper extends Horde_Rdo_Mapper
{
    /**
     * Inflector doesn't support Horde-style tables yet
     * @var string
     * @access protected
     */
    protected $_table = 'sesha_inventory';

    /**
     * Relationships loaded on-demand
     * @var array
     * @access protected
     */
    protected $_lazyRelationships = array(
       'categories' => array('type' => Horde_Rdo::MANY_TO_MANY,
                        'mapper' => 'Sesha_Entity_CategoryMapper',
                        'through' => 'sesha_inventory_categories'),
       'values' => array('type' => Horde_Rdo::ONE_TO_MANY,
                        'mapper' => 'Sesha_Entity_ValueMapper',
                        'foreignKey' => 'stock_id',
                        ),
        );


    /**
     * Deletes a stock item from the backend. $object can be either a
     * primary key, an Rdo_Query object, or a Sesha_Entity_Stock object.
     * This also cleans up attached attributes and categories
     *
     * @param string|Sesha_Entity_Stock|Horde_Rdo_Query $object The Rdo object,
     * Horde_Rdo_Query, or unique id to delete.
     *
     * @return integer Number of objects deleted.
     */
    public function delete($object)
    {
        if (!($object instanceof Sesha_Entity_Stock)) {
            $object = $this->findOne($object);
        }
        foreach ($object->values as $value) {
            $value->delete();
        }
        $object->removeRelation('categories');
        return parent::delete($object);
    }
}

