<?php
class HordeCacheFixBlobLength extends Horde_Db_Migration_Base
{
    public function up()
    {
        $this->changeColumn('horde_cache', 'cache_data', 'binary');
    }

    public function down()
    {
    }
}
