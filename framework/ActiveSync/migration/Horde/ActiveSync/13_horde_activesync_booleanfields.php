<?php
class HordeActiveSyncBooleanfields extends Horde_Db_Migration_Base
{
    public function up()
    {
        $this->changeColumn(
            'horde_activesync_mailmap',
            'sync_deleted',
            'boolean'
        );
        $this->changeColumn(
            'horde_activesync_mailmap',
            'sync_flagged',
            'boolean'
        );
        $this->changeColumn(
            'horde_activesync_mailmap',
            'sync_read',
            'boolean'
        );
    }

    public function down()
    {
        $this->changeColumn(
            'horde_activesync_mailmap',
            'sync_deleted',
            'integer'
        );
        $this->changeColumn(
            'horde_activesync_mailmap',
            'sync_flagged',
            'integer'
        );
        $this->changeColumn(
            'horde_activesync_mailmap',
            'sync_read',
            'integer'
        );
    }

}