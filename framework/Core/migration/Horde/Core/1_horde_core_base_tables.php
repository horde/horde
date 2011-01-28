<?php
class HordeCoreBaseTables extends Horde_Db_Migration_Base
{
    public function up()
    {
        if (!in_array('horde_signups', $this->tables())) {
            $t = $this->createTable('horde_signups', array('primaryKey' => array('user_name')));
            $t->column('user_name', 'string', array('limit' => 255, 'null' => false));
            $t->column('signup_date', 'integer', array('null' => false));
            $t->column('signup_host', 'string', array('limit' => 255, 'null' => false));
            $t->column('signup_data', 'text', array('null' => false));
            $t->end();
        }
    }

    public function down()
    {
        $this->dropTable('horde_signups');
    }
}
