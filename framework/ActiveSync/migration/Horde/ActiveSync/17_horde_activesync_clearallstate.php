<?php
/**
 * Migration that clears all collection state when moving between version 1 and 2
 */
class HordeActiveSyncClearAllstate extends Horde_Db_Migration_Base
{
    public function up()
    {
        $this->delete('DELETE FROM horde_activesync_state');
        $this->delete('DELETE FROM horde_activesync_map');
        $this->delete('DELETE FROM horde_activesync_mailmap');
        $this->delete('DELETE FROM horde_actitvesync_cache');
    }

    public function down()
    {
        $this->delete('DELETE FROM horde_activesync_state');
        $this->delete('DELETE FROM horde_activesync_map');
        $this->delete('DELETE FROM horde_activesync_mailmap');
        $this->delete('DELETE FROM horde_actitvesync_cache');
    }

}