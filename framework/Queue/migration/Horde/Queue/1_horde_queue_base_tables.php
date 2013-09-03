<?php
class HordeQueueBaseTables extends Horde_Db_Migration_Base
{
    public function up()
    {
        $t = $this->createTable('horde_queue_tasks', array('autoincrementKey' => 'task_id'));
        $t->column('task_queue', 'string', array('limit' => 255, 'null' => false));
        $t->column('task_fields', 'text', array('null' => false));
        $t->end();
    }

    public function down()
    {
        $this->dropTable('horde_queue_tasks');
    }
}
