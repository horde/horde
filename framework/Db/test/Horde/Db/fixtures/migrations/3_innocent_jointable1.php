<?php

class InnocentJointable1 extends Mad_Model_Migration_Base 
{
    public function up()
    {
        $t = $this->createTable('users_reminders', array('id' => false));
            $t->column('reminder_id', 'integer');
            $t->column('user_id',     'integer');
        $t->end();
    }

    public function down()
    {
        $this->dropTable('users_reminders');
    }
}