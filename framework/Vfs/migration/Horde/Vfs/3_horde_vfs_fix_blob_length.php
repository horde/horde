<?php
class HordeVfsFixBlobLength extends Horde_Db_Migration_Base
{
    public function up()
    {
        $this->changeColumn('horde_vfs', 'vfs_data', 'binary');
        $this->changeColumn('horde_muvfs', 'vfs_data', 'binary');
    }

    public function down()
    {
    }
}
