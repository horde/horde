<?php
/**
 * Migration that clears all collection state when moving between version 1 and 2
 */
class HordeActiveSyncClearstate extends Horde_Db_Migration_Base
{
    public function up()
    {
        $this->delete('DELETE from horde_activesync_state');
        $this->delete('DELETE from horde_activesync_map');
    }

    public function down()
    {
       $this->delete('DELETE from horde_activesync_state');
       $this->delete('DELETE from horde_activesync_map');
    }

}