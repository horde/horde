<?php
class HordeActiveSyncRemovepingstate extends Horde_Db_Migration_Base
{
    public function up()
    {
        $this->removeColumn('horde_activesync_device_users', 'device_ping');
        $this->removeColumn('horde_activesync_device_users', 'device_folders');
    }

    public function down()
    {
        $this->addColumn(
            'horde_activesync_device_users',
            'device_ping',
            'text');
        $this->addColumn(
            'horde_activesync_device_users',
            'device_folders',
            'text');
    }

}
