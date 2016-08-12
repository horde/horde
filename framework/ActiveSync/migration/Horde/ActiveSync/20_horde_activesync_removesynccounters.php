<?php
class HordeActiveSyncRemoveSyncCounters extends Horde_Db_Migration_Base
{
    public function up()
    {
        $this->announce('Removing SyncKeyCounter data from cache.', 'cli.message');
        $sql = 'SELECT * FROM horde_activesync_cache';
        $rows = $this->_connection->select($sql);
        $insert_sql = 'UPDATE horde_activesync_cache SET cache_data = ? WHERE cache_devid = ? AND cache_user = ?';
        foreach ($rows as $row) {
            $data = unserialize($row['cache_data']);
            unset($data['synckeycounter']);
            $row['cache_data'] = serialize($data);
            $this->_connection->update($insert_sql, array($row['cache_data'], $row['cache_devid'], $row['cache_user']));
        }
    }

    public function down()
    {
        // noop
    }

}
