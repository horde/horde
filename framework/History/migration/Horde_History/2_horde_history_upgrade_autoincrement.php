<?php
class HordeHistoryUpgradeAutoIncrement extends Horde_Db_Migration_Base
{
    public function up()
    {
        $this->changeColumn('horde_histories', 'history_id', 'integer', array('null' => false, 'unsigned' => true, 'default' => null, 'autoincrement' => true));
    }

    public function down()
    {
        $this->changeColumn('horde_histories', 'history_id', 'integer', array('null' => false, 'unsigned' => true));
    }
}