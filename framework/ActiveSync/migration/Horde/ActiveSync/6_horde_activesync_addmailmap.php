<?php
class HordeActiveSyncAddmailmap extends Horde_Db_Migration_Base
{
    public function up()
    {
        $t = $this->createTable('horde_activesync_mailmap', array('autoincrementKey' => false));
        $t->column('message_uid', 'string', array('limit' => 255, 'null' => false));
        $t->column('sync_key', 'string', array('limit' => 255, 'null' => false));
        $t->column('sync_devid', 'string', array('limit' => 255, 'null' => false));
        $t->column('sync_folderid', 'string', array('limit' => 255, 'null' => false));
        $t->column('sync_user', 'string', array('limit' => 255));
        $t->column('sync_read', 'integer');
        $t->column('sync_deleted', 'integer');
        $t->end();

        $this->addIndex('horde_activesync_mailmap', array('message_uid'));
        $this->addIndex('horde_activesync_mailmap', array('sync_devid'));
        $this->addIndex('horde_activesync_mailmap', array('sync_folderid'));
    }

    public function down()
    {
        $this->dropTable('horde_activesync_mailmap');
    }

}