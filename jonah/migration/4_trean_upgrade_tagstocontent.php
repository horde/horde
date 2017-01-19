<?php
/**
 * Move tags from trean to content storage.
 *
 * Copyright 2016-2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Michael J. Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  Trean
 */
class TreanUpgradeTagsToContent extends Horde_Db_Migration_Base
{
    public function up()
    {
        $GLOBALS['injector']->getInstance('Horde_Autoloader')
            ->addClassPathMapper(
                new Horde_Autoloader_ClassPathMapper_Prefix(
                    '/^Content_/',
                    $GLOBALS['registry']->get('fileroot', 'content') . '/lib/'
                )
            );

        if (!class_exists('Content_Tagger')) {
            throw new Trean_Exception('The Content_Tagger class could not be found. Make sure the Content application is installed.');
        }

        $type_mgr = $GLOBALS['injector']->getInstance('Content_Types_Manager');
        $types = $type_mgr->ensureTypes(array('story'));
        $this->_type_ids = array('story' => (int)$types[0]);
        $this->_tagger = $GLOBALS['injector']->getInstance('Content_Tagger');

        // An array of tag-ids => tag-name
        $story_tags = $this->select('select story_id, jonah_tags.tag_name FROM jonah_tags LEFT JOIN jonah_stories_tags ON jonah_tags.tag_id = jonah_stories_tags.tag_id;');
        $this->announce('Migrating story tags. This may take a while.');
        foreach ($story_tags as $row) {
            $this->_tagger->tag(
                null,
                array(
                    'object' => (string)$row['story_id'],
                    'type' => $this->_type_ids['story']
                ),
                $row['tag_name']
            );
        }

        $this->announce('Dropping Jonah tag tables');
        $this->dropTable('jonah_tags');
        $this->dropTable('jonah_stories_tags');
    }

    public function down()
    {
        $tableList = $this->tables();

        if (!in_array('jonah_stories_tags', $tableList)) {
            $t = $this->createTable('jonah_stories_tags', array('autoincrementKey' => false));
            $t->column('story_id', 'integer', array('null' => false));
            $t->column('channel_id', 'integer', array('null' => false));
            $t->column('tag_id', 'integer', array('null' => false));
            $t->primaryKey(array('story_id', 'channel_id', 'tag_id'));
            $t->end();
        }

        if (!in_array('jonah_tags', $tableList)) {
            $t = $this->createTable('jonah_tags', array('autoincrementKey' => array('tag_id')));
            $t->column('tag_id', 'integer', array('null' => false));
            $t->column('tag_name', 'string', array('limit' => 255, 'null' => false));
            $t->primaryKey(array('tag_id'));
            $t->end();
        }
    }

}