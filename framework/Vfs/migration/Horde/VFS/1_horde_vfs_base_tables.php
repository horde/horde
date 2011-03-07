<?php
class HordeVfsBaseTables extends Horde_Db_Migration_Base
{
    public function up()
    {
        if (!in_array('horde_vfs', $this->tables())) {
            $t = $this->createTable('horde_vfs', array('primaryKey' => array('vfs_id')));
            $t->column('vfs_id', 'int', array('null' => false, 'unsigned' => true));
            $t->column('vfs_type', 'smallint', array('null' => false, 'unsigned' => true));
            $t->column('vfs_path', 'string', array('limit' => 255, 'null' => false));
            $t->column('vfs_name', 'string', array('limit' => 255, 'null' => false));
            $t->column('vfs_modified', 'bigint', array('null' => false));
            $t->column('vfs_owner', 'string', array('limit' => 255, 'null' => false));
            $t->column('vfs_data', 'binary');
            $t->end();
            $this->addIndex('horde_vfs', array('vfs_path'));
            $this->addIndex('horde_vfs', array('vfs_name'));
        }
        if (!in_array('horde_muvfs', $this->tables())) {
            $t = $this->createTable('horde_muvfs', array('primaryKey' => array('vfs_id')));
            $t->column('vfs_id', 'int', array('null' => false, 'unsigned' => true));
            $t->column('vfs_type', 'smallint', array('null' => false, 'unsigned' => true));
            $t->column('vfs_path', 'string', array('limit' => 255, 'null' => false));
            $t->column('vfs_name', 'string', array('limit' => 255, 'null' => false));
            $t->column('vfs_modified', 'bigint', array('null' => false));
            $t->column('vfs_owner', 'string', array('limit' => 255, 'null' => false));
            $t->column('vfs_perms', 'smallint', array('null' => false, 'unsigned' => true));
            $t->column('vfs_data', 'binary');
            $t->end();
            $this->addIndex('horde_muvfs', array('vfs_path'));
            $this->addIndex('horde_muvfs', array('vfs_name'));
        }
    }

    public function down()
    {
        $this->dropTable('horde_muvfs');
        $this->dropTable('horde_vfs');
    }
}
