<?php
class HordeHistoryAddCompositeIndex extends Horde_Db_Migration_Base
{
    public function up()
    {
        $this->addIndex('horde_histories', array('history_modseq', 'object_uid'));
    }

    public function down()
    {
        // Older installs may have indexes differently named.
        $indexes = $this->indexes('horde_histories');
        $idx_name = $this->indexName('horde_histories', array('column' => array('history_modseq', 'object_uid')));
        $modseq_idx_name = $this->indexName('horde_histories', 'history_modseq');
        $object_idx_name = $this->indexName('horde_histories', 'object_uid');
        foreach ($indexes as $idx) {
            switch ($idx->name) {
            case $idx_name:
            case $modseq_idx_name:
            case $object_idx_name:
                $this->removeIndex('horde_histories', array('name' => $idx->name));
                break;
            }
        }
    }

}
