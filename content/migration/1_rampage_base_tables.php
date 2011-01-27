<?php
class RampageBaseTables extends Horde_Db_Migration_Base
{
    public function up()
    {
        $tableList = $this->tables();

        if (!in_array('rampage_types', $tableList)) {
            // rampage_types
            $t = $this->createTable('rampage_types', array('primaryKey' => 'type_id'));
            $t->column('type_name', 'string', array('limit' => 255, 'null' => false));
            $t->end();
            $this->addIndex('rampage_types', array('type_name'), array('name' => 'rampage_objects_type_name', 'unique' => true));
        }

        if (!in_array('rampage_objects', $tableList)) {
            // rampage_objects
            $t = $this->createTable('rampage_objects', array('primaryKey' => 'object_id'));
            $t->column('object_name', 'string',  array('limit' => 255, 'null' => false));
            $t->column('type_id',     'integer', array('null' => false, 'unsigned' => true));
            $t->end();
            $this->addIndex('rampage_objects', array('type_id', 'object_name'), array('name' => 'rampage_objects_type_object_name', 'unique' => true));
        }

        if (!in_array('rampage_users', $tableList)) {
            // rampage_users
            $t = $this->createTable('rampage_users', array('primaryKey' => 'user_id'));
            $t->column('user_name', 'string', array('limit' => 255, 'null' => false));
            $t->end();
            $this->addIndex('rampage_users', array('user_name'), array('name' => 'rampage_users_user_name', 'unique' => true));
        }
    }

    public function down()
    {
        $this->dropTable('rampage_types');
        $this->dropTable('rampage_objects');
        $this->dropTable('rampage_users');
    }
}
