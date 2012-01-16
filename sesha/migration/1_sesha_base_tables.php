<?php
/**
 * Create sesha base tables
 *
 * Copyright 2011 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  Sesha
 */
class SeshaBaseTables extends Horde_Db_Migration_Base
{
    /**
     * Upgrade.
     */
    public function up()
    {
        $tableList = $this->tables();

        if (!in_array('sesha_inventory', $tableList)) {
            $t = $this->createTable('sesha_inventory', array('autoincrementKey' => false));
            $t->column('stock_id', 'integer', array('null' => false));
            $t->column('stock_name', 'string', array('limit' => 255));
            $t->column('note', 'text');
            $t->primaryKey(array('stock_id'));
            $t->end();
        }

        if (!in_array('sesha_categories', $tableList)) {
            $t = $this->createTable('sesha_categories', array('autoincrementKey' => false));
            $t->column('category_id', 'integer', array('null' => false));
            $t->column('category', 'string', array('limit' => 255));
            $t->column('description', 'text');
            $t->column('priority', 'integer', array('null' => false, 'default' => 0));
            $t->primaryKey(array('category_id'));
            $t->end();
        }

        if (!in_array('sesha_properties', $tableList)) {
            $t = $this->createTable('sesha_properties', array('autoincrementKey' => false));
            $t->column('property_id', 'integer', array('null' => false));
            $t->column('property', 'string', array('limit' => 256));
            $t->column('datatype', 'string', array('limit' => 128, 'null' => false, 'default' => 0));
            $t->column('parameters', 'text');
            $t->column('unit', 'string', array('limit' => 32));
            $t->column('description', 'text');
            $t->column('priority', 'integer', array('null' => false, 'default' => 0));
            $t->primaryKey(array('property_id'));
            $t->end();
        }

        if (!in_array('sesha_relations', $tableList)) {
            $t = $this->createTable('sesha_relations', array('autoincrementKey' => false));
            $t->column('category_id', 'integer', array('null' => false));
            $t->column('property_id', 'integer', array('null' => false));
            $t->end();
        }

        if (!in_array('sesha_inventory_categories', $tableList)) {
            $t = $this->createTable('sesha_inventory_categories', array('autoincrementKey' => false));
            $t->column('stock_id', 'integer', array('null' => false));
            $t->column('category_id', 'integer', array('null' => false));
            $t->end();
        }

        if (!in_array('sesha_inventory_properties', $tableList)) {
            $t = $this->createTable('sesha_inventory_properties', array('autoincrementKey' => false));
            $t->column('attribute_id', 'integer', array('null' => false));
            $t->column('property_id', 'integer');
            $t->column('stock_id', 'integer');
            $t->column('int_datavalue', 'integer');
            $t->column('txt_datavalue', 'text');
            $t->primaryKey(array('attribute_id'));
            $t->end();
        }

    }

    public function down()
    {
        $this->dropTable('sesha_inventory');
        $this->dropTable('sesha_categories');
        $this->dropTable('sesha_properties');
        $this->dropTable('sesha_relations');
        $this->dropTable('sesha_inventory_categories');
        $this->dropTable('sesha_inventory_properties');
    }
}
