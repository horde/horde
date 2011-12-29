<?php
class HordeActiveSyncPeruserpolicykey extends Horde_Db_Migration_Base
{
    public function up()
    {
        $this->addColumn(
            'horde_activesync_device_users',
            'device_policykey',
            'bigint',
            array('default' => 0));
        $this->removeColumn('horde_activesync_device', 'device_policykey');
    }

    public function down()
    {
        $this->addColumn(
            'horde_activesync_device',
            'device_policykey',
            'bigint',
            array('default' => 0));

        $this->removeColumn('horde_activesync_device_users', 'device_policykey');
    }

}