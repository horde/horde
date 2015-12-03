<?php
class HordePermsUpgradeParentNull extends Horde_Db_Migration_Base
{
    public function up()
    {
        $this->changeColumn('horde_perms', 'perm_parents', 'string', array('limit' => 255, 'null' => true));
    }

    public function down()
    {
         $this->changeColumn('horde_perms', 'perm_parents', 'string', array('limit' => 255, 'null' => false));
    }

}