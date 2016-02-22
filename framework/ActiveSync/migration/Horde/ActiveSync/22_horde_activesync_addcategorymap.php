<?php
class HordeActiveSyncAddcategorymap extends Horde_Db_Migration_Base
{
    public function up()
    {
        $this->addColumn(
            'horde_activesync_mailmap',
            'sync_category',
            'string');
    }

    public function down()
    {
        $this->removeColumn('horde_activesync_mailmap', 'sync_category');
    }

}