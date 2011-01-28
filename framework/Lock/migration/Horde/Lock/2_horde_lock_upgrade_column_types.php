<?php
class HordeLockUpgradeColumnTypes extends Horde_Db_Migration_Base
{
    public function up()
    {
        $this->changeColumn('horde_locks', 'lock_type', 'tinyint', array('null' => false));
    }

    public function down()
    {
        $this->changeColumn('horde_locks', 'lock_type', 'smallint', array('null' => false, 'unsigned' => true));
    }
}
