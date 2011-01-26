<?php
class HordeGroupBaseTables extends Horde_Db_Migration_Base
{
    public function up()
    {
        if (!in_array('horde_groups', $this->tables())) {
            $t = $this->createTable('horde_groups', array('primaryKey' => array('group_uid')));
            $t->column('group_uid', 'integer', array('null' => false, 'unsigned' => true));
            $t->column('group_name', 'string', array('limit' => 255, 'null' => false));
            $t->column('group_parents', 'string', array('limit' => 255, 'null' => false));
            $t->column('group_email', 'string', array('limit' => 255));
            $t->end();
            $this->addIndex('horde_groups', array('group_name'), array('unique' => true));
        }
        if (!in_array('horde_groups_members', $this->tables())) {
            $t = $this->createTable('horde_groups_members', array('primaryKey' => false));
            $t->column('group_uid', 'integer', array('null' => false, 'unsigned' => true));
            $t->column('user_uid', 'string', array('limit' => 255, 'null' => false));
            $t->end();
            $this->addIndex('horde_groups_members', array('group_uid'));
            $this->addIndex('horde_groups_members', array('user_uid'));
        }
    }

    public function down()
    {
        $this->dropTable('horde_groups');
    }
}
