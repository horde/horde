<?php
class TurbaFixBlobLength extends Horde_Db_Migration_Base
{
    public function up()
    {
        $this->changeColumn('turba_objects', 'object_photo', 'binary');
        $this->changeColumn('turba_objects', 'object_logo', 'binary');
    }

    public function down()
    {
    }
}
