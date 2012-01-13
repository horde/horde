<?php
/**
 * Adds geospatial data table for NON-MYSQL SPATIAL EXTENSIONS ONLY.
 * 
 * Copyright 2010-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Michael J. Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  Kronolith
 */
class KronolithUpgradeAddGeo extends Horde_Db_Migration_Base
{
    /**
     * Upgrade.
     */
    public function up()
    {
        $tableList = $this->tables();
        if (!in_array('kronolith_events_geo', $tableList)) {
            $t = $this->createTable('kronolith_events_geo', array('autoincrementKey' => false));
            $t->column('event_id', 'string', array('limit' => 32, 'null' => false));
            $t->column('event_lat', 'string', array('limit' => 32, 'null' => false));
            $t->column('event_lon', 'string', array('limit' => 32, 'null' => false));
            $t->primaryKey(array('event_id'));
            $t->end();
        }
    }

    /**
     * Downgrade
     */
    public function down()
    {
        $this->dropTable('kronolith_events_geo');
    }

}