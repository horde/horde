<?php
class HordeTokenBaseTables extends Horde_Db_Migration_Base
{
    public function up()
    {
        if (!in_array('horde_tokens', $this->tables())) {
            $t = $this->createTable('horde_tokens', array('primaryKey' => array('token_address', 'token_id')));
            $t->column('token_address', 'string', array('limit' => 100, 'null' => false));
            $t->column('token_id', 'string', array('limit' => 32, 'null' => false));
            $t->column('token_timestamp', 'bigint', array('null' => false));
            $t->end();
        }
    }

    public function down()
    {
        $this->dropTable('horde_tokens');
    }
}
