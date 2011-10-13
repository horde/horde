<?php
/**
 * Fixes column width of lock_owner (Bug #10608).
 */
class HordeLockFixOwnerWidth extends Horde_Db_Migration_Base
{
    public function up()
    {
        $this->changeColumn('horde_locks', 'lock_owner', 'string', array('limit' => 255, 'null' => false));
    }

    public function down()
    {
        $this->changeColumn('horde_locks', 'lock_owner', 'string', array('limit' => 32, 'null' => false));
    }
}
