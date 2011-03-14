<?php
class HordeVfsUpgradeAutoIncrement extends Horde_Db_Migration_Base
{
    public function up()
    {
        $this->changeColumn('horde_vfs', 'vfs_id', 'primaryKey');
        try {
            $this->dropTable('horde_vfs_seq');
        } catch (Horde_Db_Exception $e) {
        }
        $this->changeColumn('horde_muvfs', 'vfs_id', 'primaryKey');
        try {
            $this->dropTable('horde_muvfs_seq');
        } catch (Horde_Db_Exception $e) {
        }
    }

    public function down()
    {
        $this->changeColumn('horde_muvfs', 'vfs_id', 'integer', array('null' => false, 'unsigned' => true));
        $this->changeColumn('horde_vfs', 'vfs_id', 'integer', array('null' => false, 'unsigned' => true));
    }
}