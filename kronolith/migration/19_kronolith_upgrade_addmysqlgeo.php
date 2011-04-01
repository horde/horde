<?php
/**
 * Adds geospatial data table for MYSQL SPATIAL EXTENSIONS ONLY.
 *
 * Copyright 2010-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author   Michael J. Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/gpl.html GPL
 * @package  Kronolith
 */
class KronolithUpgradeAddMysqlGeo extends Horde_Db_Migration_Base
{
    /**
     * Upgrade.
     */
    public function up()
    {
        /* Only run this migration if we are using a Mysql adapter */
        if (get_class($this->_connection) == 'Horde_Db_Adapter_Mysql' ||
            get_class($this->_connection) == 'Horde_Db_Adapter_Pdo_Mysql') {
            $t = $this->createTable('kronolith_events_mysqlgeo', array('autoincrementKey' => false));
            $t->column('event_id', 'string', array('limit' => 32, 'null' => false));
            $t->column('event_coordinates', 'point', array('null' => false));
            $t->column('event_zoom', 'integer', array('default' => 0, 'null' => false));
            $t->primaryKey(array('event_id'));
            $t->end();
        }
    }

    /**
     * Downgrade
     */
    public function down()
    {
        if (get_class($this->_connection) == 'Horde_Db_Adapter_Mysql' ||
            get_class($this->_connection) == 'Horde_Db_Adapter_Pdo_Mysql') {

            $this->dropTable('kronolith_events_mysqlgeo');
        }
    }

}