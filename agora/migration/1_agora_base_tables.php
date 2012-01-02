<?php
/**
 * Creates Agora base tables.
 *
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Vilius Šumskas <vilius@lnk.lt>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  Agora
 */
class AgoraBaseTables extends Horde_Db_Migration_Base
{
    /**
     * Upgrade.
     */
    public function up()
    {
        $tableList = $this->tables();

        if (!in_array('agora_files', $tableList)) {
            $t = $this->createTable('agora_files', array('autoincrementKey' => false));
            $t->column('file_id', 'integer', array('null' => false));
            $t->column('file_name', 'string', array('limit' => 255, 'null' => false));
            $t->column('file_size', 'integer', array('default' => 0, 'null' => false));
            $t->column('file_type', 'string', array('limit' => 32, 'null' => false));
            $t->column('message_id', 'integer', array('default' => 0, 'null' => false));
            $t->primaryKey(array('file_id'));
            $t->end();

            $this->addIndex('agora_files', array('message_id'));
        }

        if (!in_array('agora_forums', $tableList)) {
            $t = $this->createTable('agora_forums', array('autoincrementKey' => false));
            $t->column('forum_id', 'integer', array('null' => false));
            $t->column('scope', 'string', array('limit' => 10, 'null' => false));
            $t->column('forum_name', 'string', array('limit' => 255, 'null' => false));
            $t->column('active', 'integer', array('null' => false));
            $t->column('forum_description', 'string', array('limit' => 255));
            $t->column('forum_parent_id', 'integer');
            $t->column('author', 'string', array('limit' => 32, 'null' => false));
            $t->column('forum_moderated', 'integer');
            $t->column('forum_attachments', 'integer', array('default' => 0, 'null' => false));
            $t->column('message_count', 'integer', array('default' => 0));
            $t->column('thread_count', 'integer', array('default' => 0));
            $t->column('count_views', 'integer');
            $t->column('last_message_id', 'integer', array('default' => 0, 'null' => false));
            $t->column('last_message_author', 'string', array('limit' => 255, 'default' => 0, 'null' => false));
            $t->column('last_message_timestamp', 'integer', array('default' => 0, 'null' => false));
            $t->column('forum_distribution_address', 'string', array('limit' => 255, 'default' => '', 'null' => false));
            $t->primaryKey(array('forum_id'));
            $t->end();

            $this->addIndex('agora_forums', array('scope', 'active'));
        }

        if (!in_array('agora_messages', $tableList)) {
            $t = $this->createTable('agora_messages', array('autoincrementKey' => false));
            $t->column('message_id', 'integer', array('null' => false));
            $t->column('forum_id', 'integer', array('default' => 0, 'null' => false));
            $t->column('message_thread', 'integer', array('default' => 0, 'null' => false));
            $t->column('parents', 'string', array('limit' => 255));
            $t->column('message_author', 'string', array('limit' => 32, 'null' => false));
            $t->column('message_subject', 'string', array('limit' => 85, 'null' => false));
            $t->column('body', 'text', array('null' => false));
            $t->column('attachments', 'integer', array('default' => 0, 'null' => false));
            $t->column('ip', 'string', array('limit' => 30, 'null' => false));
            $t->column('status', 'integer', array('default' => 2, 'null' => false));
            $t->column('message_seq', 'integer', array('default' => 0, 'null' => false));
            $t->column('approved', 'integer', array('default' => 0, 'null' => false));
            $t->column('message_timestamp', 'integer', array('default' => 0, 'null' => false));
            $t->column('view_count', 'integer', array('default' => 0, 'null' => false));
            $t->column('locked', 'integer', array('default' => 0, 'null' => false));
            $t->column('message_modifystamp', 'integer', array('default' => 0, 'null' => false));
            $t->column('last_message_id', 'integer', array('default' => 0, 'null' => false));
            $t->column('last_message_author', 'string', array('limit' => 255));
            $t->primaryKey(array('message_id'));
            $t->end();

            $this->addIndex('agora_messages', array('forum_id'));
            $this->addIndex('agora_messages', array('message_thread'));
            $this->addIndex('agora_messages', array('parents'));
        }

        if (!in_array('agora_moderators', $tableList)) {
            $t = $this->createTable('agora_moderators', array('autoincrementKey' => false));
            $t->column('forum_id', 'integer', array('default' => 0, 'null' => false));
            $t->column('horde_uid', 'string', array('limit' => 32, 'null' => false));
            $t->primaryKey(array('forum_id', 'horde_uid'));
            $t->end();
        }
    }

    /**
     * Downgrade to 0
     */
    public function down()
    {
        $this->dropTable('agora_files');
        $this->dropTable('agora_forums');
        $this->dropTable('agora_messages');
        $this->dropTable('agora_moderators');
    }

}
