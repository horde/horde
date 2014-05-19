<?php
class HordeHistoryRemoveCompositeIndex extends Horde_Db_Migration_Base
{
    public function up()
    {
        // Older installs may have indexes differently named.
        $indexes = $this->indexes('horde_histories');
        foreach ($indexes as $idx) {
            if ($idx->columns == array('history_modseq') ||
                $idx->columns == array('object_uid')) {
                $this->removeIndex('horde_histories', array('name' => $idx->name));
            }
        }
    }

    public function down()
    {
        $this->addIndex('horde_histories', 'history_modseq');
        $this->addIndex('horde_histories', 'object_uid');
    }

}
