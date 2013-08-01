<?php
class HordeSessionHandlerFixBlobLength extends Horde_Db_Migration_Base
{
    public function up()
    {
        $this->changeColumn('horde_sessionhandler', 'session_data', 'binary');
    }

    public function down()
    {
    }
}
