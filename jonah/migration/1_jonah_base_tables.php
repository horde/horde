<?php
/**
 * Create jonah base tables as of
 *
 * Copyright 2010-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author   
 * @category Horde
 * @license  http://www.fsf.org/copyleft/gpl.html GPL
 * @package  Jonah
 */
class JonahBaseTables extends Horde_Db_Migration_Base
{
    /**
     * Upgrade.
     */
    public function up()
    {
        $tableList = $this->tables();

        if (!in_array('jonah_channels', $tableList)) {
            $t = $this->createTable('jonah_channels', array('primaryKey' => false));
            $t->column('channel_id', 'integer', array('null' => false));
            $t->column('channel_slug', 'string', array('limit' => 64, 'null' => false));
            $t->column('channel_name', 'string', array('limit' => 255, 'null' => false));
            $t->column('channel_type', 'integer');
            $t->column('channel_full_feed', 'integer', array('null' => false, 'default' => 0));
            $t->column('channel_desc', 'string', array('limit' => 255));
            $t->column('channel_interval', 'integer');
            $t->column('channel_url', 'string', array('limit' => 255));
            $t->column('channel_link', 'string', array('limit' => 255));
            $t->column('channel_page_link', 'string', array('limit' => 255));
            $t->column('channel_story_url', 'string', array('limit' => 255));
            $t->column('channel_img', 'string', array('limit' => 255));
            $t->column('channel_updated', 'integer');
            $t->primaryKey(array('channel_id'));
            $t->end();

            $this->addIndex('jonah_channels', array('channel_type'));
        }

        if (!in_array('jonah_stories', $tableList)) {
            $t = $this->createTable('jonah_stories', array('primaryKey' => false));
            $t->column('story_id', 'integer', array('null' => false));
            $t->column('channel_id', 'integer', array('null' => false));
            $t->column('story_author', 'string', array('limit' => 255, 'null' => false));
            $t->column('story_title', 'string', array('limit' => 255, 'null' => false));
            $t->column('story_desc', 'text');
            $t->column('story_body_type', 'string', array('limit' => 255, 'null' => false));
            $t->column('story_body', 'text');
            $t->column('story_url', 'string', array('limit' => 255));
            $t->column('story_permalink', 'string', array('limit' => 255));
            $t->column('story_published', 'integer');
            $t->column('story_updated', 'integer', array('null' => false));
            $t->column('story_read', 'integer', array('null' => false));
            $t->primaryKey(array('story_id'));
            $t->end();

            $this->addIndex('jonah_stories', array('channel_id'));
            $this->addIndex('jonah_stories', array('story_published'));
            $this->addIndex('jonah_stories', array('story_url'));
        }

        if (!in_array('jonah_stories_tags', $tableList)) {
            $t = $this->createTable('jonah_stories_tags', array('primaryKey' => false));
            $t->column('story_id', 'integer', array('null' => false));
            $t->column('channel_id', 'integer', array('null' => false));
            $t->column('tag_id', 'integer', array('null' => false));
            $t->primaryKey(array('story_id', 'channel_id', 'tag_id'));
            $t->end();
        }

        if (!in_array('jonah_tags', $tableList)) {
            $t = $this->createTable('jonah_tags', array('primaryKey' => false));
            $t->column('tag_id', 'integer', array('null' => false));
            $t->column('tag_name', 'string', array('limit' => 255, 'null' => false));
            $t->primaryKey(array('tag_id'));
            $t->end();
        }

    }

    /**
     * Downgrade to 0
     */
    public function down()
    {
        $this->dropTable('jonah_channels');
        $this->dropTable('jonah_stories');
        $this->dropTable('jonah_stories_tags');
        $this->dropTable('jonah_tags');
    }

}

