<?php
class HordeActiveSyncBaseTables extends Horde_Db_Migration_Base
{
    public function up()
    {
        if (!in_array('horde_activesync_state', $this->tables())) {
            $t = $this->createTable('horde_activesync_state', array('primaryKey' => array('sync_key')));
            $t->column('sync_time', 'integer');
            $t->column('sync_key', 'string', array('limit' => 255, 'null' => false));
            $t->column('sync_data', 'text');
            $t->column('sync_devid', 'string', array('limit' => 255));
            $t->column('sync_folderid', 'string', array('limit' => 255));
            $t->column('sync_user', 'string', array('limit' => 255));
            $t->end();

            $this->addIndex('horde_activesync_state', array('sync_folderid'));
            $this->addIndex('horde_activesync_state', array('sync_devid'));
        }
        if (!in_array('horde_activesync_map', $this->tables())) {
            $t = $this->createTable('horde_activesync_map', array('primaryKey' => false));
            $t->column('message_uid', 'string', array('limit' => 255, 'null' => false));
            $t->column('sync_modtime', 'integer');
            $t->column('sync_key', 'string', array('limit' => 255, 'null' => false));
            $t->column('sync_devid', 'string', array('limit' => 255, 'null' => false));
            $t->column('sync_folderid', 'string', array('limit' => 255, 'null' => false));
            $t->column('sync_user', 'string', array('limit' => 255));
            $t->end();

            $this->addIndex('horde_activesync_map', array('sync_devid'));
            $this->addIndex('horde_activesync_map', array('message_uid'));
            $this->addIndex('horde_activesync_map', array('sync_user'));
        }
        if (!in_array('horde_activesync_device', $this->tables())) {
            $t = $this->createTable('horde_activesync_device', array('primaryKey' => array('device_id')));
            $t->column('device_id', 'string', array('limit' => 255, 'null' => false));
            $t->column('device_type', 'string', array('limit' => 255, 'null' => false));
            $t->column('device_agent', 'string', array('limit' => 255, 'null' => false));
            $t->column('device_supported', 'text');
            $t->column('device_policykey', 'bigint', array('default' => 0));
            $t->column('device_rwstatus', 'integer');
            $t->end();
        }
        if (!in_array('horde_activesync_device_users', $this->tables())) {
            $t = $this->createTable('horde_activesync_device_users', array('primaryKey' => false));
            $t->column('device_id', 'string', array('limit' => 255, 'null' => false));
            $t->column('device_user', 'string', array('limit' => 255, 'null' => false));
            $t->column('device_ping', 'text');
            $t->column('device_folders', 'text');
            $t->end();

            $this->addIndex('horde_activesync_device_users', array('device_user'));
            $this->addIndex('horde_activesync_device_users', array('device_id'));
        }
    }

    public function down()
    {
        $this->dropTable('horde_activesync_device_users');
        $this->dropTable('horde_activesync_device');
        $this->dropTable('horde_activesync_map');
        $this->dropTable('horde_activesync_state');
    }
}
