<?php
/**
 * Adds resource table.
 * 
 * Copyright 2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author   Michael J. Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/gpl.html GPL
 * @package  Kronolith
 */
class KronolithUpgradeAddResources extends Horde_Db_Migration_Base
{
    /**
     * Upgrade.
     */
    public function up()
    {
        $tableList = $this->tables();
        if (!in_array('kronolith_resources', $tableList)) {
            $t = $this->createTable('kronolith_resources', array('primaryKey' => false));
            $t->column('resource_id', 'integer', array('null' => false));
            $t->column('resource_name', 'string', array('limit' => 255));
            $t->column('resource_calendar', 'string', array('limit' => 255));
            $t->column('resource_description', 'text');
            $t->column('resource_response_type', 'integer', array('default' => 0));
            $t->column('resource_type', 'string', array('limit' => 255, 'null' => false));
            $t->column('resource_members', 'text');
            $t->primaryKey(array('resource_id'));
            $t->end();

            $this->addIndex('kronolith_resources', array('resource_calendar'));
            $this->addIndex('kronolith_resources', array('resource_type'));
        }
    }

    /**
     * Downgrade
     */
    public function down()
    {
        $this->dropTable('kronolith_resources');
    }

}