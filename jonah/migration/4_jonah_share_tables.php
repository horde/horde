<?php
/**
 * Create jonah share tables
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author   Ian Roth <iron_hat@hotmail.com>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/gpl.html GPL
 * @package  Jonah
 */
class JonahShareTables extends Horde_Db_Migration_Base
{
    /**
     * Upgrade.
     */
    public function up()
    {
        $tableList = $this->tables();

        if (!in_array('jonah_shares', $tableList)) {
            $t = $this->createTable('jonah_shares', array('primaryKey' => 'share_id'));
            $t->column('share_name', 'string', array('limit' => 255, 'null' => false));
            $t->column('share_owner', 'string', array('limit' => 255, 'null' => false));
            $t->column('share_flags', 'integer', array('default' => 0, 'null' => false));
            $t->column('perm_creator', 'integer', array('default' => 0, 'null' => false));
            $t->column('perm_default', 'integer', array('default' => 0, 'null' => false));
            $t->column('perm_guest', 'integer', array('default' => 0, 'null' => false));
            $t->column('attribute_name', 'string', array('limit' => 255, 'null' => false));
            $t->column('attribute_desc', 'string', array('limit' => 255));
            $t->column('attribute_slug', 'string', array('limit' => 64));
            $t->column('attribute_full_feed', 'integer', array('default' => 0, 'null' =>false));
            $t->column('attribute_interval', 'integer');
            $t->column('attribute_url', 'string', array('limit' => 255));
            $t->column('attribute_link', 'string', array('limit' => 255));
            $t->column('attribute_page_link', 'string', array('limit' => 255));
            $t->column('attribute_story_url', 'string', array('limit' => 255));
            $t->column('attribute_img', 'string', array('limit' => 255));
            $t->column('attribute_updated', 'integer');
            $t->end();

            $this->addIndex('jonah_shares', array('share_name'));
            $this->addIndex('jonah_shares', array('share_owner'));
            $this->addIndex('jonah_shares', array('perm_creator'));
            $this->addIndex('jonah_shares', array('perm_default'));
            $this->addIndex('jonah_shares', array('perm_guest'));
        }

        if (!in_array('jonah_shares_groups', $tableList)) {
            $t = $this->createTable('jonah_shares_groups');
            $t->column('share_id', 'integer', array('null' => false));
            $t->column('group_uid', 'string', array('limit' => 255, 'null' => false));
            $t->column('perm', 'integer', array('null' => false));
            $t->end();

            $this->addIndex('jonah_shares_groups', array('share_id'));
            $this->addIndex('jonah_shares_groups', array('group_uid'));
            $this->addIndex('jonah_shares_groups', array('perm'));
        }

        if (!in_array('jonah_shares_users', $tableList)) {
            $t = $this->createTable('jonah_shares_users');

            $t->column('share_id', 'integer', array('null' => false));
            $t->column('user_uid', 'string', array('limit' => 255));
            $t->column('perm', 'integer', array('null' => false));
            $t->end();

            $this->addIndex('jonah_shares_users', array('share_id'));
            $this->addIndex('jonah_shares_users', array('user_uid'));
            $this->addIndex('jonah_shares_users', array('perm'));
        }

        //convert from channel table
        if (in_array('jonah_channels', $tableList)) {
            /* Feeds */
            $sql = 'SELECT jonah_channels.channel_id, jonah_channels.channel_slug, jonah_channels.channels_name,' .
                ' jonah_channels.channel_full_feed, jonah_channels.channel_desc, jonah_channels.channel_interval,' .
                ' jonah_channels.channel_url, jonah_channels.channel_link, jonah_channels.channel_page_link,' .
                ' jonah_channels.channel_story_url, jonah_channels.channel_img, jonah_channels.channel_updated' .
                ' FROM jonah_channels WHERE jonah_channels.channel_type = 0';

            $this->announce('Migrating feeds. This may take a while.');
            $rows = $this->_connection->selectAll($sql);
            foreach ($rows as $row) {
                $info = array();
                $info['slug'] = $row['channel_slug'];
                $info['name'] = $row['channel_name'];
                $info['desc'] = $row['channel_desc'];
                $info['full_feed'] = $row['channel_full_feed'];
                $info['interval'] = $row['channel_interval'];
                $info['url'] = $row['channel_url'];
                $info['page_link'] = $row['channel_page_link'];
                $info['story_url'] = $row['channel_story_url'];
                $info['img'] = $row['channel_img'];
                $info['updated'] = $row['channel_updated'];
                $feed = $GLOBALS['injector']->getInstance('Jonah')->addShare($info);
                $sql = 'UPDATE jonah_stories SET jonah_stories.channel_id = ? WHERE jonah_stories.channel_id = ?';
                $this->_connection->update($sql,$feed->getId(),$row['channel_id']);
            }
            $this->announce('Feed migration finished.');

            $this->announce('Dropping jonah channels tables');
            $this->dropTable('jonah_channels');
        }
    }

    /**
     * Downgrade to 3
     */
    public function down()
    {
    //forward only
    }
}
