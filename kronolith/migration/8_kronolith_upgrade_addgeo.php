<?php
/**
 * Adds geospatial data table for NON-MYSQL SPATIAL EXTENSIONS ONLY.
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
class KronolithUpgradeAddGeo extends Horde_Db_Migration_Base
{
    /**
     * Upgrade.
     */
    public function up()
    {
        $tableList = $this->tables();
        if (!in_array('kronolith_events_geo', $tableList)) {
            $t = $this->createTable('kronolith_events_geo', array('primaryKey' => false));
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