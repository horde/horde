<?php
class HordeLockBaseTables extends Horde_Db_Migration_Base
{
    public function up()
    {
        if (!in_array('horde_locks', $this->tables())) {
            $t = $this->createTable('horde_locks', array('primaryKey' => array('lock_id')));
            $t->column('lock_id', 'string', array('limit' => 36, 'null' => false));
            $t->column('lock_owner', 'string', array('limit' => 32, 'null' => false));
            $t->column('lock_scope', 'string', array('limit' => 32, 'null' => false));
            $t->column('lock_principal', 'string', array('limit' => 255, 'null' => false));
            $t->column('lock_origin_timestamp', 'bigint', array('null' => false));
            $t->column('lock_update_timestamp', 'bigint', array('null' => false));
            $t->column('lock_expiry_timestamp', 'bigint', array('null' => false));
            $t->column('lock_type', 'smallint', array('null' => false, 'unsigned' => true));
            $t->end();
        }
    }

    public function down()
    {
        $this->dropTable('horde_locks');
    }
}
