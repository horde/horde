<?php
class HordeActiveSyncLongtextcachefield extends Horde_Db_Migration_Base
{
    public function up()
    {
        $this->changeColumn(
            'horde_activesync_cache',
            'cache_data',
            'mediumtext');
    }

    public function down()
    {
        $this->changeColumn(
            'horde_activesync_cache',
            'cache_data',
            'text');
    }

}