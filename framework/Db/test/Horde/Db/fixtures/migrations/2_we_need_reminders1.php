<?php

class WeNeedReminders1 extends Mad_Model_Migration_Base 
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