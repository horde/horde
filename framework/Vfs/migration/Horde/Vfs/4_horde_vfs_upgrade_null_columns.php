<?php
class HordeVfsUpgradeNullColumns extends Horde_Db_Migration_Base
{
    public function up()
    {
        $this->changeColumn('horde_vfs', 'vfs_path', 'string', array('limit' => 255, 'null' => true));
        $this->changeColumn('horde_vfs', 'vfs_owner', 'string', array('limit' => 255, 'null' => true));
        $this->changeColumn('horde_muvfs', 'vfs_path', 'string', array('limit' => 255, 'null' => true));
        $this->changeColumn('horde_muvfs', 'vfs_owner', 'string', array('limit' => 255, 'null' => true));
        $this->update('UPDATE horde_vfs SET vfs_path = NULL WHERE vfs_path = \'\'');
        $this->update('UPDATE horde_vfs SET vfs_owner = NULL WHERE vfs_path = \'\'');
        $this->update('UPDATE horde_muvfs SET vfs_path = NULL WHERE vfs_path = \'\'');
        $this->update('UPDATE horde_muvfs SET vfs_owner = NULL WHERE vfs_path = \'\'');
    }

    public function down()
    {
        $this->changeColumn('horde_vfs', 'vfs_path', 'string', array('limit' => 255, 'null' => false));
        $this->changeColumn('horde_vfs', 'vfs_owner', 'string', array('limit' => 255, 'null' => false));
        $this->changeColumn('horde_muvfs', 'vfs_path', 'string', array('limit' => 255, 'null' => false));
        $this->changeColumn('horde_muvfs', 'vfs_owner', 'string', array('limit' => 255, 'null' => false));
        $this->update('UPDATE horde_vfs SET vfs_path = \'\' WHERE vfs_path IS NULL');
        $this->update('UPDATE horde_vfs SET vfs_owner = \'\' WHERE vfs_path IS NULL');
        $this->update('UPDATE horde_muvfs SET vfs_path = \'\' WHERE vfs_path IS NULL');
        $this->update('UPDATE horde_muvfs SET vfs_owner = \'\' WHERE vfs_path IS NULL');
    }
}
