<?php
class HordeActiveSyncAddmailflagged extends Horde_Db_Migration_Base
{
    public function up()
    {
        $this->addColumn(
            'horde_activesync_mailmap',
            'sync_flagged',
            'integer');
    }

    public function down()
    {
        $this->removeColumn('horde_activesync_mailmap', 'sync_flagged');
    }

}