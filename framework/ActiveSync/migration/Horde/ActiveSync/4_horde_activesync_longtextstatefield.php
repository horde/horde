<?php
class HordeActiveSyncLongtextstatefield extends Horde_Db_Migration_Base
{
    public function up()
    {
        $this->changeColumn(
            'horde_activesync_state',
            'sync_data',
            'mediumtext');
    }

    public function down()
    {
        $this->changeColumn(
            'horde_activesync_state',
            'sync_data',
            'text');
    }

}