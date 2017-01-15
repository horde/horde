<?php
/**
 * Create beatnik base tables
 *
 * Copyright 2005-2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  Beatnik
 */
class BeatnikBaseTables extends Horde_Db_Migration_Base
{
    /**
     * Upgrade.
     */
    public function up()
    {
        $tableList = $this->tables();

        if (!in_array('beatnik_a', $tableList)) {
            $t = $this->createTable('beatnik_a', array('autoincrementKey' => 'id'));
            $t->column('zonename', 'string', array('limit' => 255, 'null' => false));
            $t->column('hostname', 'string', array('limit' => 255, 'null' => false));
            $t->column('ipaddr', 'string', array('limit' => 255, 'null' => false));
            $t->column('ttl', 'string', array('limit' => 255));
            $t->end();
        }

        if (!in_array('beatnik_cname', $tableList)) {
            $t = $this->createTable('beatnik_cname', array('autoincrementKey' => 'id'));
            $t->column('zonename', 'string', array('limit' => 255, 'null' => false));
            $t->column('hostname', 'string', array('limit' => 255, 'null' => false));
            $t->column('pointer', 'string', array('limit' => 255, 'null' => false));
            $t->column('ttl', 'string', array('limit' => 255));
            $t->end();
        }

        if (!in_array('beatnik_mx', $tableList)) {
            $t = $this->createTable('beatnik_mx', array('autoincrementKey' => 'id'));
            $t->column('zonename', 'string', array('limit' => 255, 'null' => false));
            $t->column('pointer', 'string', array('limit' => 255, 'null' => false));
            $t->column('pref', 'string', array('limit' => 255, 'null' => false));
            $t->column('ttl', 'string', array('limit' => 255));
            $t->end();
        }

        if (!in_array('beatnik_ns', $tableList)) {
            $t = $this->createTable('beatnik_ns', array('autoincrementKey' => 'id'));
            $t->column('zonename', 'string', array('limit' => 255, 'null' => false));
            $t->column('hostname', 'string', array('limit' => 255, 'null' => false));
            $t->column('pointer', 'string', array('limit' => 255, 'null' => false));
            $t->column('ttl', 'string', array('limit' => 255));
            $t->end();
        }

        if (!in_array('beatnik_ptr', $tableList)) {
            $t = $this->createTable('beatnik_ptr', array('autoincrementKey' => 'id'));
            $t->column('zonename', 'string', array('limit' => 255, 'null' => false));
            $t->column('hostname', 'string', array('limit' => 255, 'null' => false));
            $t->column('pointer', 'string', array('limit' => 255, 'null' => false));
            $t->column('ttl', 'string', array('limit' => 255));
            $t->end();
        }

        if (!in_array('beatnik_soa', $tableList)) {
            $t = $this->createTable('beatnik_soa', array('autoincrementKey' => 'id'));
            $t->column('zonename', 'string', array('limit' => 255, 'null' => false));
            $t->column('zonens', 'string', array('limit' => 255, 'null' => false));
            $t->column('zonecontact', 'string', array('limit' => 255));
            $t->column('serial', 'string', array('limit' => 255));
            $t->column('refresh', 'integer', array('unsigned' => true));
            $t->column('retry', 'integer', array('unsigned' => true));
            $t->column('expire', 'string', array('limit' => 255));
            $t->column('minimum', 'string', array('limit' => 255));
            $t->column('ttl', 'string', array('limit' => 255, 'default' => '3600', 'null' => false));
            $t->end();
            $this->addIndex('beatnik_soa', array('zonename'), array('unique' => true));
        }

        if (!in_array('beatnik_srv', $tableList)) {
            $t = $this->createTable('beatnik_srv', array('autoincrementKey' => 'id'));
            $t->column('zonename', 'string', array('limit' => 255, 'null' => false));
            $t->column('hostname', 'string', array('limit' => 255, 'null' => false));
            $t->column('pointer', 'string', array('limit' => 255, 'null' => false));
            $t->column('priority', 'string', array('limit' => 255, 'null' => false));
            $t->column('weight', 'string', array('limit' => 255, 'null' => false));
            $t->column('port', 'string', array('limit' => 255, 'null' => false));
            $t->column('ttl', 'string', array('limit' => 255));
            $t->end();
        }

        if (!in_array('beatnik_txt', $tableList)) {
            $t = $this->createTable('beatnik_txt', array('autoincrementKey' => 'id'));
            $t->column('zonename', 'string', array('limit' => 255, 'null' => false));
            $t->column('hostname', 'string', array('limit' => 255, 'null' => false));
            $t->column('txt', 'string', array('limit' => 255, 'null' => false));
            $t->column('ttl', 'string', array('limit' => 255));
            $t->end();
        }
    }

    /**
     * Downgrade to 0
     */
    public function down()
    {
        $this->dropTable('beatnik_a');
        $this->dropTable('beatnik_cname');
        $this->dropTable('beatnik_mx');
        $this->dropTable('beatnik_ns');
        $this->dropTable('beatnik_ptr');
        $this->dropTable('beatnik_soa');
        $this->dropTable('beatnik_srv');
        $this->dropTable('beatnik_txt');
    }

}