<?php
/**
 * The Sesha_Entity_ValueMapper class contains all functions related to handling
 * a property's value for a specific stock item in Sesha.
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
 * The Sesha_Entity_ValueMapper class contains all functions related to handling
 * a property's value for a specific stock item in Sesha.
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
class Sesha_Entity_ValueMapper extends Horde_Rdo_Mapper
{
    /**
     * Inflector doesn't support Horde-style tables yet
     * @var string
     * @access protected
     */
    protected $_table = 'sesha_inventory_properties';

    /**
     * Relationships loaded on-demand
     * @var array
     * @access protected
     */
    protected $_lazyRelationships = array(
        'stock'  => array(
                'type' => Horde_Rdo::ONE_TO_ONE,
                'foreignKey' => 'stock_id',
                'mapper' => 'Sesha_Entity_StockMapper'
                ),
        'property'  => array(
                'type' => Horde_Rdo::ONE_TO_ONE,
                'foreignKey' => 'property_id',
                'mapper' => 'Sesha_Entity_PropertyMapper'
                ),
            );

}

