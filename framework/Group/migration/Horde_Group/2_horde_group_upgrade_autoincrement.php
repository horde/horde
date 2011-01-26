<?php
class HordeGroupUpgradeAutoIncrement extends Horde_Db_Migration_Base
{
    /**
     * Upgrade.
     */
    public function up()
    {
        $this->changeColumn('horde_groups', 'group_uid', 'integer', array('null' => false, 'unsigned' => true, 'autoincrement' => true));
    }

    /**
     * Downgrade
     */
    public function down()
    {
        $this->changeColumn('horde_groups', 'group_uid', 'integer', array('null' => false, 'unsigned' => true));
    }

}