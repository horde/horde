<?php
class HordeSyncmlBaseTables extends Horde_Db_Migration_Base
{
    public function up()
    {
        if (!in_array('horde_syncml_map', $this->tables())) {
            $t = $this->createTable('horde_syncml_map', array('primaryKey' => false));
            $t->column('syncml_syncpartner', 'string', array('limit' => 255, 'null' => false));
            $t->column('syncml_db', 'string', array('limit' => 255, 'null' => false));
            $t->column('syncml_uid', 'string', array('limit' => 255, 'null' => false));
            $t->column('syncml_cuid', 'string', array('limit' => 255));
            $t->column('syncml_suid', 'string', array('limit' => 255));
            $t->column('syncml_timestamp', 'integer');
            $t->end();
            $this->addIndex('horde_syncml_map', array('syncml_syncpartner'));
            $this->addIndex('horde_syncml_map', array('syncml_db'));
            $this->addIndex('horde_syncml_map', array('syncml_uid'));
            $this->addIndex('horde_syncml_map', array('syncml_cuid'));
            $this->addIndex('horde_syncml_map', array('syncml_suid'));
        }
        if (!in_array('horde_syncml_anchors', $this->tables())) {
            $t = $this->createTable('horde_syncml_anchors', array('primaryKey' => false));
            $t->column('syncml_syncpartner', 'string', array('limit' => 255, 'null' => false));
            $t->column('syncml_db', 'string', array('limit' => 255, 'null' => false));
            $t->column('syncml_uid', 'string', array('limit' => 255, 'null' => false));
            $t->column('syncml_clientanchor', 'string', array('limit' => 255));
            $t->column('syncml_serveranchor', 'string', array('limit' => 255));
            $t->end();
            $this->addIndex('horde_syncml_anchors', array('syncml_syncpartner'));
            $this->addIndex('horde_syncml_anchors', array('syncml_db'));
            $this->addIndex('horde_syncml_anchors', array('syncml_uid'));
        }
    }

    public function down()
    {
        $this->dropTable('horde_syncml_anchors');
        $this->dropTable('horde_syncml_map');
    }
}
