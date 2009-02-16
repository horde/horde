<?php

class WeNeedReminders2 extends Horde_Db_Migration_Base
{
    public function up()
    {
        $t = $this->createTable('reminders');
            $t->column('content',   'text');
            $t->column('remind_at', 'datetime');
        $t->end();
    }

    public function down()
    {
        $this->dropTable('reminders');
    }
}