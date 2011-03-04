<?php
class HordeHistoryUpgradeAutoIncrement extends Horde_Db_Migration_Base
{
    public function up()
    {
        $this->changeColumn('horde_histories', 'history_id', 'primaryKey');
        try {
            $this->dropTable('horde_histories_seq');
        } catch (Horde_Db_Exception $e) {
        }
    }

    public function down()
    {
        $this->changeColumn('horde_histories', 'history_id', 'integer', array('null' => false, 'unsigned' => true));
    }
}