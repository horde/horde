<?php
class RampageTagTables extends Horde_Db_Migration_Base
{
    public function up()
    {
        $tableList = $this->tables();

        if (!in_array('rampage_tags', $tableList)) {
            // rampage_tags
            $t = $this->createTable('rampage_tags', array('autoincrementKey' => 'tag_id'));
            $t->column('tag_name', 'string', array('limit' => 255, 'null' => false));
            $t->end();
            $this->addIndex('rampage_tags', array('tag_name'), array('name' => 'rampage_tags_tag_name', 'unique' => true));
        }

        if (!in_array('rampage_tagged', $tableList)) {
            // rampage_tagged
            $t = $this->createTable('rampage_tagged', array('autoincrementKey' => false));
            $t->column('user_id',   'integer', array('null' => false, 'unsigned' => true));
            $t->column('object_id', 'integer', array('null' => false, 'unsigned' => true));
            $t->column('tag_id',    'integer', array('null' => false, 'unsigned' => true));
            $t->column('created',   'datetime');
            $t->primaryKey(array('user_id', 'object_id', 'tag_id'));
            $t->end();
            $this->addIndex('rampage_tagged', array('object_id'), array('name' => 'rampage_tagged_object_id'));
            $this->addIndex('rampage_tagged', array('tag_id'), array('name' => 'rampage_tagged_tag_id'));
            $this->addIndex('rampage_tagged', array('created'), array('name' => 'rampage_tagged_created'));
        }

        if (!in_array('rampage_tag_stats', $tableList)) {
            // rampage_tag_stats
            $t = $this->createTable('rampage_tag_stats', array('autoincrementKey' => false));
            $t->column('tag_id', 'integer', array('null' => false, 'unsigned' => true));
            $t->column('count', 'integer', array('unsigned' => true));
            $t->primaryKey(array('tag_id'));
            $t->end();
        }

        if (!in_array('rampage_user_tag_stats', $tableList)) {
            // rampage_user_tag_stats
            $t = $this->createTable('rampage_user_tag_stats', array('autoincrementKey' => false));
            $t->column('user_id', 'integer', array('null' => false, 'unsigned' => true));
            $t->column('tag_id',  'integer', array('null' => false, 'unsigned' => true));
            $t->column('count',   'integer', array('unsigned' => true));
            $t->primaryKey(array('user_id', 'tag_id'));
            $t->end();
            $this->addIndex('rampage_user_tag_stats', array('tag_id'), array('name' => 'rampage_user_tag_stats_tag_id'));
        }
    }

    public function down()
    {
        $this->dropTable('rampage_tags');
        $this->dropTable('rampage_tagged');
        $this->dropTable('rampage_tag_stats');
        $this->dropTable('rampage_user_tag_stats');
    }
}
