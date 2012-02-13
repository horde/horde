<?php
class HordeActiveSyncAddmapflag extends Horde_Db_Migration_Base
{
    public function up()
    {
        $this->addColumn(
            'horde_activesync_map',
            'sync_flag',
            'integer');
    }

    public function down()
    {
        $this->removeColumn('horde_activesync_map', 'sync_flag');
    }

}