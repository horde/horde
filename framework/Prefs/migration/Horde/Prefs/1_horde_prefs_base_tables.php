<?php
class HordePrefsBaseTables extends Horde_Db_Migration_Base
{
    public function up()
    {
        if (!in_array('horde_prefs', $this->tables())) {
            $t = $this->createTable('horde_prefs', array('autoincrementKey' => array('pref_uid', 'pref_scope', 'pref_name')));
            $t->column('pref_uid', 'string', array('limit' => 255, 'null' => false));
            $t->column('pref_scope', 'string', array('limit' => 16, 'null' => false, 'default' => ''));
            $t->column('pref_name', 'string', array('limit' => 32, 'null' => false));
            $t->column('pref_value', 'text');
            $t->end();
            $this->addIndex('horde_prefs', array('pref_uid'));
            $this->addIndex('horde_prefs', array('pref_scope'));
        }
    }

    public function down()
    {
        $this->dropTable('horde_prefs');
    }
}
