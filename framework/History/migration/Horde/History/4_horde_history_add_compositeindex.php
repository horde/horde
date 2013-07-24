<?php
class HordeHistoryAddCompositeIndex extends Horde_Db_Migration_Base
{
    public function up()
    {
        $this->addIndex('horde_histories', array('history_modseq', 'object_uid'));
    }

    public function down()
    {
        $this->dropIndex('horde_histories', array('history_modseq', 'object_uid'));
    }

}
