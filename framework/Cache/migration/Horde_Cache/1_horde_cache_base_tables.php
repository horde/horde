<?php
class HordeCacheBaseTables extends Horde_Db_Migration_Base
{
    public function up()
    {
        if (!in_array('horde_cache', $this->tables())) {
            $t = $this->createTable('horde_cache', array('primaryKey' => array('cache_id')));
            $t->column('cache_id', 'string', array('limit' => 32, 'null' => false));
            $t->column('cache_timestamp', 'bigint', array('null' => false));
            $t->column('cache_expiration', 'bigint', array('null' => false));
            $t->column('cache_data', 'binary');
            $t->end();
        }
    }

    public function down()
    {
        $this->dropTable('horde_cache');
    }
}
