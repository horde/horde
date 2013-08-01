<?php
class HordeHistoryFixBotchedIndexes extends Horde_Db_Migration_Base
{
    public function up()
    {
        $idx = $this->indexName('horde_histories', array('column' => array('history_modseq', 'object_uid')));
        $this->removeIndex('horde_histories', array('name' => $idx));

        // Re-add these.
        $this->addIndex('horde_histories', array('history_modseq'));
        $this->addIndex('horde_histories', array('object_uid'));

    }

    public function down()
    {
        $this->addIndex('horde_histories', array('history_modseq', 'object_uid'));
        $this->removeIndex('horde_histories', 'history_modseq');
        $this->removeIndex('horde_histories', 'object_uid');
    }

}
