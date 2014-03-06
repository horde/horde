<?php
class HordeAlarmsAddInstanceId extends Horde_Db_Migration_Base
{
    public function up()
    {
        $this->addColumn(
            'horde_alarms',
            'alarm_instanceid',
            'string',
            array('limit' => 255));
    }

    public function down()
    {
        $this->removeColumn('horde_alarms', 'alarm_instanceid');
    }

}
