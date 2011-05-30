<?php
class HordePermsUpgradeAutoIncrement extends Horde_Db_Migration_Base
{
    public function up()
    {
        $this->changeColumn('horde_perms', 'perm_id', 'autoincrementKey');
        try {
            $this->dropTable('horde_perms_seq');
        } catch (Horde_Db_Exception $e) {
        }
    }

    public function down()
    {
        $this->changeColumn('horde_perms', 'perm_id', 'integer', array('null' => false));
    }
}