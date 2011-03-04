<?php
class HordeGroupUpgradeAutoIncrement extends Horde_Db_Migration_Base
{
    /**
     * Upgrade.
     */
    public function up()
    {
        $this->changeColumn('horde_groups', 'group_uid', 'primaryKey');
        try {
            $this->dropTable('horde_groups_seq');
        } catch (Horde_Db_Exception $e) {
        }
    }

    /**
     * Downgrade
     */
    public function down()
    {
        $this->changeColumn('horde_groups', 'group_uid', 'integer', array('null' => false, 'unsigned' => true));
    }

}