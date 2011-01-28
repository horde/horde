<?php
class HordeAuthBaseTables extends Horde_Db_Migration_Base
{
    public function up()
    {
        if (!in_array('horde_users', $this->tables())) {
            $t = $this->createTable('horde_users', array('primaryKey' => array('user_uid')));
            $t->column('user_uid', 'string', array('limit' => 255, 'null' => false));
            $t->column('user_pass', 'string', array('limit' => 255, 'null' => false));
            $t->column('user_soft_expiration_date', 'integer');
            $t->column('user_hard_expiration_date', 'integer');
            $t->end();
        }
    }

    public function down()
    {
        $this->dropTable('horde_users');
    }
}
