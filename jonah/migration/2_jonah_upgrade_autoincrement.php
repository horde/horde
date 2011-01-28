<?php
/**
 * Adds autoincrement flags
 *
 * Copyright 2010-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author   Ian Roth <iron_hat@hotmail.com>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/gpl.html GPL
 * @package  Jonah
 */
class JonahUpgradeAutoIncrement extends Horde_Db_Migration_Base
{
    /**
     * Upgrade.
     */
    public function up()
    {
        $this->changeColumn('jonah_channels', 'channel_id', 'integer', array('default' => null, 'null' => false, 'autoincrement' => true));
        $this->changeColumn('jonah_stories', 'story_id', 'integer', array('default' => null, 'null' => false, 'autoincrement' => true));
        $this->changeColumn('jonah_tags', 'tag_id', 'integer', array('default' => null, 'null' => false, 'autoincrement' => true));
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
