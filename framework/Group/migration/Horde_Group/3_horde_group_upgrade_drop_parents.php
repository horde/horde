<?php
class HordeGroupUpgradeDropParents extends Horde_Db_Migration_Base
{
    /**
     * Upgrade.
     */
    public function up()
    {
        $this->removeColumn('horde_groups', 'group_parents');
    }

    /**
     * Downgrade
     */
    public function down()
    {
        $this->addColumn('horde_groups', 'group_parents', 'string', array('limit' => 255, 'null' => false, 'default' => false));
    }
}
