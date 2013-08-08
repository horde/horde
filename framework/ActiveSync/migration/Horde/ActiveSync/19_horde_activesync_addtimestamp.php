<?php
class HordeActiveSyncAddTimestamp extends Horde_Db_Migration_Base
{
    public function up()
    {
        $this->renameColumn('horde_activesync_state', 'sync_time', 'sync_mod');
        $this->addColumn('horde_activesync_state', 'sync_timestamp', 'integer');
    }

    public function down()
    {
        $this->removeColumn('horde_activesync_state', 'sync_timestamp');
        $this->renameColumn('horde_activesync_state', 'sync_mod', 'sync_time');
    }

}
