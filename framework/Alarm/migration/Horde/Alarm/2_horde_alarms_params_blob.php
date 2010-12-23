<?php
class HordeAlarmsParamsBlob extends Horde_Db_Migration_Base
{
    public function up()
    {
        $this->changeColumn('horde_alarms', 'alarm_params', 'binary');
    }

    public function down()
    {
        $this->changeColumn('horde_alarms', 'alarm_params', 'text');
    }
}
