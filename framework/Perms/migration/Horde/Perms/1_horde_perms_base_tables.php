<?php
class HordePermsBaseTables extends Horde_Db_Migration_Base
{
    public function up()
    {
        if (!in_array('horde_perms', $this->tables())) {
            $t = $this->createTable('horde_perms', array('primaryKey' => array('perm_id')));
            $t->column('perm_id', 'integer', array('null' => false));
            $t->column('perm_name', 'string', array('limit' => 255, 'null' => false));
            $t->column('perm_parents', 'string', array('limit' => 255, 'null' => false));
            $t->column('perm_data', 'text');
            $t->end();
            $this->addIndex('horde_perms', array('perm_name'), array('unique' => true));
        }
    }

    public function down()
    {
        $this->dropTable('horde_perms');
    }
}
