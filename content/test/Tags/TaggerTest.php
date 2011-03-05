<?php
/**
 * Copyright 2008-2011 The Horde Project (http://www.horde.org/)
 *
 * @author     Chuck Hagenbuch <chuck@horde.org>
 * @category   Horde
 * @package    Content
 * @subpackage UnitTests
 */
class Content_Tags_TaggerTest extends PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        $injector = new Horde_Injector(new Horde_Injector_TopLevel());

        $db = new Horde_Db_Adapter_Pdo_Sqlite(array('dbname' => ':memory:'));
        $injector->setInstance('Horde_Db_Adapter', $db);
        //$this->_migrate($db);
        $this->tagger = $injector->getInstance('Content_Tagger');

        // Read sql schema file
        $statements = array();
        $current_stmt = '';
        $fp = fopen(dirname(__FILE__) . '/../fixtures/schema.sql', 'r');
        while ($line = fgets($fp, 8192)) {
            $line = rtrim(preg_replace('/^(.*)--.*$/s', '\1', $line));
            if (!$line) {
                continue;
            }

            $current_stmt .= $line;

            if (substr($line, -1) == ';') {
                // leave off the ending ;
                $statements[] = substr($current_stmt, 0, -1);
                $current_stmt = '';
            }
        }

        // Run statements
        foreach ($statements as $stmt) {
            $db->execute($stmt);
        }
    }

    public function testSplitTags()
    {
        $this->assertEquals(array('this', 'somecompany, llc', 'and "this" w,o.rks', 'foo bar'),
                            $this->tagger->splitTags('this, "somecompany, llc", "and ""this"" w,o.rks", foo bar'));
    }

    public function testEnsureTags()
    {
        $this->assertEquals(array(1), $this->tagger->ensureTags(1));
        $this->assertEquals(array(1), $this->tagger->ensureTags(array(1)));
        $this->assertEquals(array(1), $this->tagger->ensureTags('work'));
        $this->assertEquals(array(1), $this->tagger->ensureTags(array('work')));

        $this->assertEquals(array(1, 2), $this->tagger->ensureTags(array(1, 2)));
        $this->assertEquals(array(1, 2), $this->tagger->ensureTags(array(1, 'play')));
        $this->assertEquals(array(1, 2), $this->tagger->ensureTags(array('work', 2)));
        $this->assertEquals(array(1, 2), $this->tagger->ensureTags(array('work', 'play')));
    }

    public function testDuplicateTags()
    {
        $this->tagger->tag('mike', 1, 'TYÖ');
        $this->tagger->tag('mike', 1, 'työ');
        $this->tagger->tag('mike', 1, 'työ');

        $this->tagger->tag('mike', 1, array('test', 'TEST'));
    }

    public function testFullTagCloudSimple()
    {
        $this->assertEquals(array(), $this->tagger->getTagCloud());

        $this->tagger->tag(1, 1, 'work');
        $cloud = $this->tagger->getTagCloud();
        $this->assertEquals(1, $cloud[1]['tag_id']);
        $this->assertEquals('work', $cloud[1]['tag_name']);
        $this->assertEquals(1, $cloud[1]['count']);
    }

    public function testGetRecentTags()
    {
        $this->assertEquals(array(), $this->tagger->getRecentTags());

        $this->tagger->tag(1, 1, 1, new Horde_Date('2008-01-01T00:00:00'));
        $this->tagger->tag(2, 1, 1, new Horde_Date('2007-01-01T00:00:00'));

        $recent = $this->tagger->getRecentTags();
        $this->assertEquals(1, count($recent));
        $this->assertEquals(1, $recent[0]['tag_id']);
        $this->assertEquals('2008-01-01T00:00:00', $recent[0]['created']);
    }

    public function testGetRecentTagsLimit()
    {
        // Create 100 tags on 100 tag_ids, with tag_id = t1 being applied
        // most recently, and so on. Prepend "t" to each tag to force the
        // creation of tags that don't yet exist in the test database.
        for ($i = 1; $i <= 100; $i++) {
            $this->tagger->tag(1, 1, "t$i", new Horde_Date(strtotime('now - ' . $i . ' minutes')));
        }

        $recentLimit = $this->tagger->getRecentTags(array('limit' => 25));
        $this->assertEquals(25, count($recentLimit));
        $this->assertEquals('t1', $recentLimit[0]['tag_name']);
    }

    public function testGetRecentTagsOffset()
    {
        // Create 100 tags on 100 tag_ids, with tag_id = t1 being applied
        // most recently, and so on. Prepend "t" to each tag to force the
        // creation of tags that don't yet exist in the test database.
        for ($i = 1; $i <= 100; $i++) {
            $this->tagger->tag(1, 1, "t$i", new Horde_Date(strtotime('now - ' . $i . ' minutes')));
        }

        $recentOffset = $this->tagger->getRecentTags(array('limit' => 25, 'offset' => 25));
        $this->assertEquals(25, count($recentOffset));
        $this->assertEquals('t26', $recentOffset[0]['tag_name']);
    }

    public function testGetRecentTagsByUser()
    {
        $this->tagger->tag(1, 1, 1);

        $recent = $this->tagger->getRecentTags();
        $recentByUser = $this->tagger->getRecentTags(array('userId' => 1));
        $this->assertEquals(1, count($recentByUser));
        $this->assertEquals($recent, $recentByUser);

        $recent = $this->tagger->getRecentTags(array('userId' => 2));
        $this->assertEquals(0, count($recent));
    }

    public function testGetRecentTagsByType()
    {
        $this->tagger->tag(1, 1, 1);

        $recent = $this->tagger->getRecentTags();
        $recentByType = $this->tagger->getRecentTags(array('typeId' => 1));
        $this->assertEquals(1, count($recentByType));
        $this->assertEquals($recent, $recentByType);

        $recent = $this->tagger->getRecentTags(array('typeId' => 2));
        $this->assertEquals(0, count($recent));
    }

    public function testGetRecentObjects()
    {
        $this->assertEquals(array(), $this->tagger->getRecentObjects());

        $this->tagger->tag(1, 1, 1, new Horde_Date('2008-01-01T00:00:00'));
        $this->tagger->tag(2, 1, 1, new Horde_Date('2007-01-01T00:00:00'));

        $recent = $this->tagger->getRecentObjects();
        $this->assertEquals(1, count($recent));
        $this->assertEquals(1, $recent[0]['object_id']);
        $this->assertEquals('2008-01-01T00:00:00', $recent[0]['created']);
    }

    public function testGetRecentObjectsLimit()
    {
        // Create 100 tags on 100 object_ids, with object_id = 1 being tagged
        // most recently, and so on.
        for ($i = 1; $i <= 100; $i++) {
            $this->tagger->tag(1, $i, 1, new Horde_Date(strtotime('now - ' . $i . ' minutes')));
        }

        $recentLimit = $this->tagger->getRecentObjects(array('limit' => 25));
        $this->assertEquals(25, count($recentLimit));
        $this->assertEquals(1, $recentLimit[0]['object_id']);
    }

    public function testGetRecentObjectsOffset()
    {
        // Create 100 tags on 100 object_ids, with object_id = 1 being tagged
        // most recently, and so on.
        for ($i = 1; $i <= 100; $i++) {
            $this->tagger->tag(1, $i, 1, new Horde_Date(strtotime('now - ' . $i . ' minutes')));
        }

        $recentOffset = $this->tagger->getRecentObjects(array('limit' => 25, 'offset' => 25));
        $this->assertEquals(25, count($recentOffset));
        $this->assertEquals(26, $recentOffset[0]['object_id']);
    }

    public function testGetRecentObjectsByUser()
    {
        $this->tagger->tag(1, 1, 1);

        $recent = $this->tagger->getRecentObjects();
        $recentByUser = $this->tagger->getRecentObjects(array('userId' => 1));
        $this->assertEquals(1, count($recentByUser));
        $this->assertEquals($recent, $recentByUser);

        $recent = $this->tagger->getRecentObjects(array('userId' => 2));
        $this->assertEquals(0, count($recent));
    }

    public function testGetRecentObjectsByType()
    {
        $this->tagger->tag(1, 1, 1);

        $recent = $this->tagger->getRecentObjects();
        $recentByType = $this->tagger->getRecentObjects(array('typeId' => 1));
        $this->assertEquals(1, count($recentByType));
        $this->assertEquals($recent, $recentByType);

        $recent = $this->tagger->getRecentObjects(array('typeId' => 2));
        $this->assertEquals(0, count($recent));
    }

    public function testGetRecentUsers()
    {
        $this->assertEquals(array(), $this->tagger->getRecentUsers());

        $this->tagger->tag(1, 1, 1, new Horde_Date('2008-01-01T00:00:00'));
        $this->tagger->tag(1, 2, 1, new Horde_Date('2007-01-01T00:00:00'));

        $recent = $this->tagger->getRecentUsers();
        $this->assertEquals(1, count($recent));
        $this->assertEquals(1, $recent[0]['user_id']);
        $this->assertEquals('2008-01-01T00:00:00', $recent[0]['created']);
    }

    public function testGetRecentUsersLimit()
    {
        // Create 100 tags by 100 user_ids, with user_id = 1 tagging
        // most recently, and so on.
        for ($i = 1; $i <= 100; $i++) {
            $this->tagger->tag($i, 1, 1, new Horde_Date(strtotime('now - ' . $i . ' minutes')));
        }

        $recentLimit = $this->tagger->getRecentUsers(array('limit' => 25));
        $this->assertEquals(25, count($recentLimit));
        $this->assertEquals(1, $recentLimit[0]['user_id']);
    }

    public function testGetRecentUsersOffset()
    {
        // Create 100 tags by 100 user_ids, with user_id = 1 tagging
        // most recently, and so on.
        for ($i = 1; $i <= 100; $i++) {
            $this->tagger->tag($i, 1, 1, new Horde_Date(strtotime('now - ' . $i . ' minutes')));
        }

        $recentOffset = $this->tagger->getRecentUsers(array('limit' => 25, 'offset' => 25));
        $this->assertEquals(25, count($recentOffset));
        $this->assertEquals(26, $recentOffset[0]['user_id']);
    }

    public function testGetRecentUsersByType()
    {
        $this->tagger->tag(1, 1, 1);

        $recent = $this->tagger->getRecentUsers();
        $recentByType = $this->tagger->getRecentUsers(array('typeId' => 1));
        $this->assertEquals(1, count($recentByType));
        $this->assertEquals($recent, $recentByType);

        $recent = $this->tagger->getRecentUsers(array('typeId' => 2));
        $this->assertEquals(0, count($recent));
    }

    private function _migrate(Horde_Db_Adapter_Base $db)
    {
        $migration = new Horde_Db_Migrator($db);

            // rampage_types
        $t = $migration->createTable('rampage_types', array('primaryKey' => 'type_id'));
        $t->column('type_name', 'string', array('limit' => 255, 'null' => false));
        $t->end();
        $migration->addIndex('rampage_types', array('type_name'), array('name' => 'rampage_objects_type_name', 'unique' => true));

        // rampage_objects
        $t = $migration->createTable('rampage_objects', array('primaryKey' => 'object_id'));
        $t->column('object_name', 'string',  array('limit' => 255, 'null' => false));
        $t->column('type_id',     'integer', array('null' => false, 'unsigned' => true));
        $t->end();
        $migration->addIndex('rampage_objects', array('type_id', 'object_name'), array('name' => 'rampage_objects_type_object_name', 'unique' => true));

        // rampage_users
        $t = $migration->createTable('rampage_users', array('primaryKey' => 'user_id'));
        $t->column('user_name', 'string', array('limit' => 255, 'null' => false));
        $t->end();
        $migration->addIndex('rampage_users', array('user_name'), array('name' => 'rampage_users_user_name', 'unique' => true));

        // rampage_tags
        $t = $migration->createTable('rampage_tags', array('primaryKey' => 'tag_id'));
        $t->column('tag_name', 'string', array('limit' => 255, 'null' => false));
        $t->end();
        $migration->addIndex('rampage_tags', array('tag_name'), array('name' => 'rampage_tags_tag_name', 'unique' => true));

        // rampage_tagged
        $t = $migration->createTable('rampage_tagged', array('primaryKey' => array('user_id', 'object_id', 'tag_id')));
        $t->column('user_id',   'integer', array('null' => false, 'unsigned' => true));
        $t->column('object_id', 'integer', array('null' => false, 'unsigned' => true));
        $t->column('tag_id',    'integer', array('null' => false, 'unsigned' => true));
        $t->column('created',   'datetime');
        $t->end();
        $migration->addIndex('rampage_tagged', array('object_id'), array('name' => 'rampage_tagged_object_id'));
        $migration->addIndex('rampage_tagged', array('tag_id'), array('name' => 'rampage_tagged_tag_id'));
        $migration->addIndex('rampage_tagged', array('created'), array('name' => 'rampage_tagged_created'));

        // rampage_tag_stats
        $t = $migration->createTable('rampage_tag_stats', array('primaryKey' => 'tag_id'));
        $t->column('count', 'integer', array('unsigned' => true));
        $t->end();


        // rampage_user_tag_stats
        $t = $migration->createTable('rampage_user_tag_stats', array('primaryKey' => array('user_id', 'tag_id')));
        $t->column('user_id', 'integer', array('null' => false, 'unsigned' => true));
        $t->column('tag_id',  'integer', array('null' => false, 'unsigned' => true));
        $t->column('count',   'integer', array('unsigned' => true));
        $t->end();
        $migration->addIndex('rampage_user_tag_stats', array('tag_id'), array('name' => 'rampage_user_tag_stats_tag_id'));
    }

}
