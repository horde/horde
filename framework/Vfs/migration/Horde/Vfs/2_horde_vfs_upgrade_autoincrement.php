<?php
class HordeVfsUpgradeAutoIncrement extends Horde_Db_Migration_Base
{
    public function up()
    {
        $this->changeColumn('horde_vfs', 'vfs_id', 'autoincrementKey');
        try {
            $this->dropTable('horde_vfs_seq');
        } catch (Horde_Db_Exception $e) {
        }
        $this->changeColumn('horde_muvfs', 'vfs_id', 'autoincrementKey');
        try {
            $this->dropTable('horde_muvfs_seq');
        } catch (Horde_Db_Exception $e) {
        }
    }

    public function down()
    {
        try {
            $this->changeColumn('horde_muvfs', 'vfs_id', 'integer', array('null' => false, 'unsigned' => true));
        } catch (Horde_Db_Exception $e) {}

        try {
            $this->changeColumn('horde_vfs', 'vfs_id', 'integer', array('null' => false, 'unsigned' => true));
        } catch (Horde_Db_Exception $e) {}
    }
}
