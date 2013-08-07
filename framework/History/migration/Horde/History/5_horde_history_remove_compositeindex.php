<?php
class HordeHistoryRemoveCompositeIndex extends Horde_Db_Migration_Base
{
    public function up()
    {
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

    public function down()
    {
        $this->addIndex('horde_histories', 'history_modseq');
        $this->addIndex('horde_histories', 'object_uid');
    }

}
