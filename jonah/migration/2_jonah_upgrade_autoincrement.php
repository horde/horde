<?php
/**
 * Adds autoincrement flags
 *
 * Copyright 2010-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Ian Roth <iron_hat@hotmail.com>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  Jonah
 */
class JonahUpgradeAutoIncrement extends Horde_Db_Migration_Base
{
    /**
     * Upgrade.
     */
    public function up()
    {
        $this->changeColumn('jonah_channels', 'channel_id', 'autoincrementKey');
        try {
            $this->dropTable('jonah_channels_seq');
        } catch (Horde_Db_Exception $e) {
        }
        $this->changeColumn('jonah_stories', 'story_id', 'autoincrementKey');
        try {
            $this->dropTable('jonah_stories_seq');
        } catch (Horde_Db_Exception $e) {
        }
        $this->changeColumn('jonah_tags', 'tag_id', 'autoincrementKey');
        try {
            $this->dropTable('jonah_tags_seq');
        } catch (Horde_Db_Exception $e) {
        }
    }

    /**
     * Downgrade
     */
    public function down()
    {
        $this->changeColumn('jonah_channels', 'channel_id', 'integer', array('null' => false));
        $this->changeColumn('jonah_stories', 'story_id', 'integer', array('null' => false));
        $this->changeColumn('jonah_tags', 'tag_id', 'integer', array('null' => false));
    }

}
