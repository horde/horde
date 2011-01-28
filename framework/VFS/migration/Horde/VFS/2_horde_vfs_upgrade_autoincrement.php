<?php
class HordeVfsUpgradeAutoIncrement extends Horde_Db_Migration_Base
{
    public function up()
    {
        $this->changeColumn('horde_vfs', 'vfs_id', 'integer', array('null' => false, 'unsigned' => true, 'default' => null, 'autoincrement' => true));
        $this->changeColumn('horde_muvfs', 'vfs_id', 'integer', array('null' => false, 'unsigned' => true, 'default' => null, 'autoincrement' => true));
    }

    public function down()
    {
        $this->changeColumn('horde_muvfs', 'vfs_id', 'integer', array('null' => false, 'unsigned' => true));
        $this->changeColumn('horde_vfs', 'vfs_id', 'integer', array('null' => false, 'unsigned' => true));
    }
}