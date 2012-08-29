<?php
class HordeActiveSyncAddpendingfield extends Horde_Db_Migration_Base
{
    public function up()
    {
        $this->addColumn(
            'horde_activesync_state',
            'sync_pending',
            'mediumtext');
    }

    public function down()
    {
        $this->removeColumn('horde_activesync_state', 'sync_pending');
    }

}