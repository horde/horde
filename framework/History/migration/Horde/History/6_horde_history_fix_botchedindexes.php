<?php
class HordeHistoryFixBotchedIndexes extends Horde_Db_Migration_Base
{
    public function up()
    {
        $indexes = $this->indexes('horde_histories');
        $idx = $this->indexName('horde_histories', array('column' => array('history_modseq', 'object_uid')));
        foreach ($indexes as $index) {
            if ($index->name == $idx) {
                $this->removeIndex('horde_histories', array('name' => $idx));
            }
        }

        // Re-add these.
        $this->addIndex('horde_histories', array('history_modseq'));
        $this->addIndex('horde_histories', array('object_uid'));

    }

    public function down()
    {
        $this->addIndex('horde_histories', array('history_modseq', 'object_uid'));
        // Older installs may have indexes differently named.
        $indexes = $this->indexes('horde_histories');
        $modseq_idx_name = $this->indexName('horde_histories', 'history_modseq');
        $object_idx_name = $this->indexName('horde_histories', 'object_uid');
        foreach ($indexes as $idx) {
            switch ($idx->name) {
            case $modseq_idx_name:
            case $object_idx_name:
                $this->removeIndex('horde_histories', array('name' => $idx->name));
                break;
            }
        }
    }

}
