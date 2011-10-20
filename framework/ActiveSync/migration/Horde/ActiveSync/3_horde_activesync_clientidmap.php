<?php
class HordeActiveSyncClientidmap extends Horde_Db_Migration_Base
{
    public function up()
    {
        $this->addColumn(
            'horde_activesync_map',
            'sync_clientid',
            'string',
            array('limit' => 255));
    }

    public function down()
    {
        $this->removeColumn('horde_activesync_map', 'sync_clientid');
    }

}