<?php
/**
 * Move tags from jonah to content storage.
 *
 * Copyright 2010-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/asl.php.
 *
 * @author   Michael J. Rubinsky <mrubinsk@horde.org>
 * @author   Ian Roth <iron_hat@hotmail.com>
 * @category Horde
 * @license  http://www.horde.org/licenses/asl.php ASL
 * @package  Jonah
 */
class JonahUpgradeTagsToContent extends Horde_Db_Migration_Base
{
    public function __construct(Horde_Db_Adapter $connection, $version = null)
    {
        parent::__construct($connection, $version);

        // Can't use Jonah's tagger since we can't init jonah.
        $GLOBALS['injector']->getInstance('Horde_Autoloader')->addClassPathMapper(new Horde_Autoloader_ClassPathMapper_Prefix('/^Content_/', $GLOBALS['registry']->get('fileroot', 'content') . '/lib/'));
        if (!class_exists('Content_Tagger')) {
            throw new Horde_Exception('The Content_Tagger class could not be found. Make sure the Content application is installed.');
        }
        $type_mgr = $GLOBALS['injector']->getInstance('Content_Types_Manager');
        $types = $type_mgr->ensureTypes(array('feed', 'story'));;
        $this->_type_ids = array('calendar' => (int)$types[0], 'event' => (int)$types[1]);
        $this->_tagger = $GLOBALS['injector']->getInstance('Content_Tagger');
    }

    public function up()
    {
        $tableList = $this->tables();
        if (in_array('jonah_tags', $tableList)) {
            $GLOBALS['registry']->pushApp('jonah');

            /* Story tags */
            $sql = 'SELECT jonah_stories.story_id, tag_name, story_author FROM jonah_stories INNER JOIN '
                . 'jonah_stories_tags ON jonah_stories.story_id = jonah_stories_tags.story_id '
                . 'INNER JOIN jonah_tags ON jonah_tags.tag_id = jonah_stories_tags.tag_id';

            $this->announce('Migrating story tags. This may take a while.');
            $rows = $this->_connection->selectAll($sql);
            foreach ($rows as $row) {
                $this->_tagger->tag($row['story_id'], $row['tag_name'], $row['story_id'], 'story');
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
        $t = $this->createTable('jonah_stories_tags', array('autoincrementKey' => false));
        $t->column('story_id', 'integer', array('null' => false));
        $t->column('channel_id', 'integer', array('null' => false));
        $t->column('tag_id', 'integer', array('null' => false));
        $t->primaryKey(array('story_id', 'channel_id', 'tag_id'));
        $t->end();

        $t = $this->createTable('jonah_tags', array('autoincrementKey' => false));
        $t->column('tag_id', 'integer', array('null' => false));
        $t->column('tag_name', 'string', array('limit' => 255, 'null' => false));
        $t->primaryKey(array('tag_id'));
        $t->end();

        //@todo import tags
        //@todo delete tags from tagger?
    }

}
