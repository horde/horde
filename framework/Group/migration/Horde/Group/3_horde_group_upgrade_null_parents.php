<?php
class HordeGroupUpgradeNullParents extends Horde_Db_Migration_Base
{
    /**
     * Upgrade.
     */
    public function up()
    {
        $this->changeColumn('horde_groups', 'group_parents', 'string', array('limit' => 255));
    }

    /**
     * Downgrade
     */
    public function down()
    {
        $this->changeColumn('horde_groups', 'group_parents', 'string', array('limit' => 255, 'null' => false));
    }

}