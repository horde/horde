<?php
class HordePermsUpgradeAutoIncrement extends Horde_Db_Migration_Base
{
    public function up()
    {
        $this->changeColumn('horde_perms', 'perm_id', 'integer', array('null' => false, 'unsigned' => true, 'default' => null, 'autoincrement' => true));
    }

    public function down()
    {
        $this->changeColumn('horde_perms', 'perm_id', 'integer', array('null' => false));
    }
}