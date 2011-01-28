<?php
class HordeSessionhandlerBaseTables extends Horde_Db_Migration_Base
{
    public function up()
    {
        if (!in_array('horde_sessionhandler', $this->tables())) {
            $t = $this->createTable('horde_sessionhandler', array('primaryKey' => array('session_id')));
            $t->column('session_id', 'string', array('limit' => 32, 'null' => false));
            $t->column('session_lastmodified', 'integer', array('null' => false));
            $t->column('session_data', 'binary');
            $t->end();
            $this->addIndex('horde_sessionhandler', array('session_lastmodified'));
        }
    }

    public function down()
    {
        $this->dropTable('horde_sessionhandler');
    }
}
