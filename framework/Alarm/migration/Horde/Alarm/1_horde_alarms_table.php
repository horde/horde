<?php
class HordeAlarmsTable extends Horde_Db_Migration_Base
{
    public function up()
    {
        if (!in_array('horde_alarms', $this->tables())) {
            $t = $this->createTable('horde_alarms');
            $t->column('alarm_id', 'string', array('limit' => 255, 'null' => false));
            $t->column('alarm_uid', 'string', array('limit' => 255));
            $t->column('alarm_start', 'datetime', array('null' => false));
            $t->column('alarm_end', 'datetime');
            $t->column('alarm_methods', 'string', array('limit' => 255));
            $t->column('alarm_params', 'text');
            $t->column('alarm_title', 'string', array('limit' => 255, 'null' => false));
            $t->column('alarm_text', 'text');
            $t->column('alarm_snooze', 'datetime');
            $t->column('alarm_dismissed', 'integer', array('limit' => 1, 'null' => false, 'default' => 0));
            $t->column('alarm_internal', 'text');
            $t->end();

            $this->addIndex('horde_alarms', array('alarm_id'));
            $this->addIndex('horde_alarms', array('alarm_uid'));
            $this->addIndex('horde_alarms', array('alarm_start'));
            $this->addIndex('horde_alarms', array('alarm_end'));
            $this->addIndex('horde_alarms', array('alarm_snooze'));
            $this->addIndex('horde_alarms', array('alarm_dismissed'));
        }
    }

    public function down()
    {
        $this->dropTable('horde_alarms');
    }
}
