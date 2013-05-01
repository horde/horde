<?php
class HordeActiveSyncFixBlobLength extends Horde_Db_Migration_Base
{
    public function up()
    {
        $this->changeColumn('horde_activesync_state', 'sync_data', 'binary');
    }

    public function down()
    {
    }
}
