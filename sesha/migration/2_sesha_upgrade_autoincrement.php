<?php
/**
 * Adds autoincrement flags
 *
 * Copyright 2011 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Ian Roth <iron_hat@hotmail.com>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  Sesha 
 */
class SeshaUpgradeAutoIncrement extends Horde_Db_Migration_Base
{
    /**
     * Upgrade.
     */
    public function up()
    {
        $this->changeColumn('sesha_inventory', 'stock_id', 'autoincrementKey');
        try {
            $this->dropTable('sesha_inventory_seq');
        } catch (Horde_Db_Exception $e) {
        }
        $this->changeColumn('sesha_categories', 'category_id', 'autoincrementKey');
        try {
            $this->dropTable('sesha_categories_seq');
        } catch (Horde_Db_Exception $e) {
        }
        $this->changeColumn('sesha_properties', 'property_id', 'autoincrementKey');
        try {
            $this->dropTable('sesha_properties_seq');
        } catch (Horde_Db_Exception $e) {
        }
        $this->changeColumn('sesha_inventory_properties', 'attribute_id', 'autoincrementKey');
        try {
            $this->dropTable('sesha_inventory_properties_seq');
        } catch (Horde_Db_Exception $e) {
        }
    }

    /**
     * Downgrade
     */
    public function down()
    {
        $this->changeColumn('sesha_inventory', 'stock_id', 'integer', array('null' => false));
        $this->changeColumn('sesha_categories', 'category_id', 'integer', array('null' => false));
        $this->changeColumn('sesha_properties', 'property_id', 'integer', array('null' => false));
        $this->changeColumn('sesha_inventory_properties', 'attribute_id', 'integer', array('null' => false));
    }

}
