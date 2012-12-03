<?php
class HordeActiveSyncIntegerimapuidfield extends Horde_Db_Migration_Base
{
    public function up()
    {
        $this->changeColumn(
            'horde_activesync_mailmap',
            'message_uid',
            'integer');
    }

    public function down()
    {
        $this->changeColumn(
            'horde_activesync_mailmap',
            'message_uid',
            'string',
            array('limit' => 255, 'null' => false)
        );
    }

}