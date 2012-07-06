<?php
class HordeActiveSyncAddDeviceProperties extends Horde_Db_Migration_Base
{
    public function up()
    {
        $this->addColumn(
            'horde_activesync_device',
            'device_properties',
            'text');
    }

    public function down()
    {
        $this->removeColumn('horde_activesync_device', 'device_properties');
    }

}
