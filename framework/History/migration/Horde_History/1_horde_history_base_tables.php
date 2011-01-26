<?php
class HordeHistoryBaseTables extends Horde_Db_Migration_Base
{
    public function up()
    {
        if (!in_array('horde_histories', $this->tables())) {
            $t = $this->createTable('horde_histories', array('primaryKey' => array('history_id')));
            $t->column('history_id', 'integer', array('null' => false, 'unsigned' => true));
            $t->column('object_uid', 'string', array('limit' => 255, 'null' => false));
            $t->column('history_action', 'string', array('limit' => 32, 'null' => false));
            $t->column('history_ts', 'bigint', array('null' => false));
            $t->column('history_desc', 'text');
            $t->column('history_who', 'string', array('limit' => 255));
            $t->column('history_extra', 'text');
            $t->end();
            $this->addIndex('horde_histories', array('history_action'));
            $this->addIndex('horde_histories', array('history_ts'));
            $this->addIndex('horde_histories', array('object_uid'));
        }
    }

    public function down()
    {
        $this->dropTable('horde_histories');
    }
}
