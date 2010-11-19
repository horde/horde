<?php
/**
 * Move tags from jonah to content storage.
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author   Michael J. Rubinsky <mrubinsk@horde.org>
 * @author   Ian Roth <iron_hat@hotmail.com>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/gpl.html GPL
 * @package  Jonah
 */
class JonahUpgradeTagsToContent extends Horde_Db_Migration_Base
{
    public function up()
    {
        $tableList = $this->tables();
        if (in_array('jonah_tags', $tableList)) {
            $GLOBALS['registry']->pushApp('jonah');

            /* Gallery tags */
            $sql = 'SELECT jonah_stories.story_id, tag_name, story_author FROM jonah_stories INNER JOIN '
                . 'jonah_stories_tags ON jonah_stories.story_id = jonah_stories_tags.story_id '
                . 'INNER JOIN jonah_tags ON jonah_tags.tag_id = jonah_stories_tags.tag_id';

            $this->announce('Migrating story tags. This may take a while.');
            $rows = $this->_connection->selectAll($sql);
            foreach ($rows as $row) {
                $GLOBALS['injector']->getInstance('Jonah_Tagger')->tag($row['story_id'], $row['tag_name'], $row['story_id'], 'story');
            }
            $this->announce('Story tags finished.');

            $this->announce('Dropping jonah tag tables');
            $this->dropTable('jonah_stories_tags');
            $this->dropTable('jonah_tags');
        } else {
            $this->announce('Tags ALREADY migrated to content system.');
        }
    }

    public function down()
    {
        // Not supported. One way upgrade.
    }

}
