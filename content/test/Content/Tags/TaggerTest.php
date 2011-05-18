<?php
 require_once dirname(__FILE__) . '/../Base.php';

/**
 * Copyright 2008-2011 The Horde Project (http://www.horde.org/)
 *
 * @author     Chuck Hagenbuch <chuck@horde.org>
 * @category   Horde
 * @package    Content
 * @subpackage UnitTests
 */
class Content_Tags_TaggerTest extends Content_Test_Base
{
    protected $_db;
    protected $_injector;
    protected $_migrator;
    protected $_tagger;

    protected function setUp()
    {
        $this->_injector = new Horde_Injector(new Horde_Injector_TopLevel());
        $this->_db = new Horde_Db_Adapter_Pdo_Sqlite(array('dbname' => ':memory:'));
        $this->_injector->setInstance('Horde_Db_Adapter', $this->_db);

        // FIXME: get migration directory if not running from Git checkout.
        $this->_migrator = new Horde_Db_Migration_Migrator(
            $this->_db,
            null, //$logger,
            array('migrationsPath' => dirname(__FILE__) . '/../../../migration',
                  'schemaTableName' => 'content_test_schema'));

        $this->_migrator->up();
        $this->_tagger = $this->_injector->getInstance('Content_Tagger');
    }

    public function tearDown()
    {
        if ($this->_migrator) {
            $this->_migrator->down();
        }
        $this->_db = null;
        parent::tearDown();
    }

    public function testSplitTags()
    {
        $this->assertEquals(array('this', 'somecompany, llc', 'and "this" w,o.rks', 'foo bar'),
                            $this->_tagger->splitTags('this, "somecompany, llc", "and ""this"" w,o.rks", foo bar'));
    }

    public function testEnsureTags()
    {
        $this->assertEquals(array(1), $this->_tagger->ensureTags(1));
        $this->assertEquals(array(1), $this->_tagger->ensureTags(array(1)));
        $this->assertEquals(array(1), $this->_tagger->ensureTags('work'));
        $this->assertEquals(array(1), $this->_tagger->ensureTags(array('work')));

        $this->assertEquals(array(1, 2), $this->_tagger->ensureTags(array(1, 2)));
        $this->assertEquals(array(1, 2), $this->_tagger->ensureTags(array(1, 'play')));
        $this->assertEquals(array(1, 2), $this->_tagger->ensureTags(array('work', 2)));
        $this->assertEquals(array(1, 2), $this->_tagger->ensureTags(array('work', 'play')));
    }

    public function testDuplicateTags()
    {
        // These tests don't work at the moment, because SQLite sucks at
        // non-ascii comparing.
        /*
        $this->_tagger->tag('mike', 1, 'TYÖ');
        $this->_tagger->tag('mike', 1, 'TYÖ');
        $this->_tagger->tag('mike', 1, 'työ');
        $this->_tagger->tag('mike', 1, 'työ');
        */
        $this->_tagger->tag('mike', 1, 'foo');
        $this->_tagger->tag('mike', 1, 'FOO');
        $this->_tagger->tag('mike', 1, array('test', 'TEST'));
        $this->assertEquals(2, count($this->_tagger->getTags(array('objectId' => 1))));
    }

    public function testFullTagCloudSimple()
    {
        $this->assertEquals(array(), $this->_tagger->getTagCloud());

        $this->_tagger->tag(1, 1, 'work');
        $cloud = $this->_tagger->getTagCloud();
        $this->assertEquals(1, $cloud[1]['tag_id']);
        $this->assertEquals('work', $cloud[1]['tag_name']);
        $this->assertEquals(1, $cloud[1]['count']);
    }

    public function testGetRecentTags()
    {
        $this->_fixture();
        $this->assertEquals(array(), $this->_tagger->getRecentTags());

        $this->_tagger->tag(1, 1, 1, new Horde_Date('2008-01-01T00:00:00'));
        $this->_tagger->tag(2, 1, 1, new Horde_Date('2007-01-01T00:00:00'));

        $recent = $this->_tagger->getRecentTags();
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
            $this->_tagger->tag(1, 1, "t$i", new Horde_Date(strtotime('now - ' . $i . ' minutes')));
        }

        $recentLimit = $this->_tagger->getRecentTags(array('limit' => 25));
        $this->assertEquals(25, count($recentLimit));
        $this->assertEquals('t1', $recentLimit[0]['tag_name']);
    }

    public function testGetRecentTagsOffset()
    {
        // Create 100 tags on 100 tag_ids, with tag_id = t1 being applied
        // most recently, and so on. Prepend "t" to each tag to force the
        // creation of tags that don't yet exist in the test database.
        for ($i = 1; $i <= 100; $i++) {
            $this->_tagger->tag(1, 1, "t$i", new Horde_Date(strtotime('now - ' . $i . ' minutes')));
        }

        $recentOffset = $this->_tagger->getRecentTags(array('limit' => 25, 'offset' => 25));
        $this->assertEquals(25, count($recentOffset));
        $this->assertEquals('t26', $recentOffset[0]['tag_name']);
    }

    public function testGetRecentTagsByUser()
    {
        $this->_fixture();
        $this->_tagger->tag(1, 1, 1);

        $recent = $this->_tagger->getRecentTags();
        $recentByUser = $this->_tagger->getRecentTags(array('userId' => 1));
        $this->assertEquals(1, count($recentByUser));
        $this->assertEquals($recent, $recentByUser);

        $recent = $this->_tagger->getRecentTags(array('userId' => 2));
        $this->assertEquals(0, count($recent));
    }

    public function testGetRecentTagsByType()
    {
        $this->_fixture();
        $this->_tagger->tag(1, 1, 1);

        $recent = $this->_tagger->getRecentTags();
        $recentByType = $this->_tagger->getRecentTags(array('typeId' => 1));
        $this->assertEquals(1, count($recentByType));
        $this->assertEquals($recent, $recentByType);

        $recent = $this->_tagger->getRecentTags(array('typeId' => 2));
        $this->assertEquals(0, count($recent));
    }

    public function testGetRecentObjects()
    {
        $this->_fixture();
        $this->assertEquals(array(), $this->_tagger->getRecentObjects());

        $this->_tagger->tag(1, 1, 1, new Horde_Date('2008-01-01T00:00:00'));
        $this->_tagger->tag(2, 1, 1, new Horde_Date('2007-01-01T00:00:00'));

        $recent = $this->_tagger->getRecentObjects();
        $this->assertEquals(1, count($recent));
        $this->assertEquals(1, $recent[0]['object_id']);
        $this->assertEquals('2008-01-01T00:00:00', $recent[0]['created']);
    }

    public function testGetRecentObjectsLimit()
    {
        // Create 100 tags on 100 object_ids, with object_id = 1 being tagged
        // most recently, and so on.
        for ($i = 1; $i <= 100; $i++) {
            $this->_tagger->tag(1, $i, 1, new Horde_Date(strtotime('now - ' . $i . ' minutes')));
        }

        $recentLimit = $this->_tagger->getRecentObjects(array('limit' => 25));
        $this->assertEquals(25, count($recentLimit));
        $this->assertEquals(1, $recentLimit[0]['object_id']);
    }

    public function testGetRecentObjectsOffset()
    {
        // Create 100 tags on 100 object_ids, with object_id = 1 being tagged
        // most recently, and so on.
        for ($i = 1; $i <= 100; $i++) {
            $this->_tagger->tag(1, $i, 1, new Horde_Date(strtotime('now - ' . $i . ' minutes')));
        }

        $recentOffset = $this->_tagger->getRecentObjects(array('limit' => 25, 'offset' => 25));
        $this->assertEquals(25, count($recentOffset));
        $this->assertEquals(26, $recentOffset[0]['object_id']);
    }

    public function testGetRecentObjectsByUser()
    {
        $this->_tagger->tag(1, 1, 1);

        $recent = $this->_tagger->getRecentObjects();
        $recentByUser = $this->_tagger->getRecentObjects(array('userId' => 1));
        $this->assertEquals(1, count($recentByUser));
        $this->assertEquals($recent, $recentByUser);

        $recent = $this->_tagger->getRecentObjects(array('userId' => 2));
        $this->assertEquals(0, count($recent));
    }

    public function testGetRecentObjectsByType()
    {
        $this->_fixture();
        $this->_tagger->tag(1, 1, 1);

        $recent = $this->_tagger->getRecentObjects();
        $recentByType = $this->_tagger->getRecentObjects(array('typeId' => 1));
        $this->assertEquals(1, count($recentByType));
        $this->assertEquals($recent, $recentByType);

        $recent = $this->_tagger->getRecentObjects(array('typeId' => 2));
        $this->assertEquals(0, count($recent));
    }

    public function testGetRecentUsers()
    {
        $this->assertEquals(array(), $this->_tagger->getRecentUsers());

        $this->_tagger->tag(1, 1, 1, new Horde_Date('2008-01-01T00:00:00'));
        $this->_tagger->tag(1, 2, 1, new Horde_Date('2007-01-01T00:00:00'));

        $recent = $this->_tagger->getRecentUsers();
        $this->assertEquals(1, count($recent));
        $this->assertEquals(1, $recent[0]['user_id']);
        $this->assertEquals('2008-01-01T00:00:00', $recent[0]['created']);
    }

    public function testGetRecentUsersLimit()
    {
        // Create 100 tags by 100 user_ids, with user_id = 1 tagging
        // most recently, and so on.
        for ($i = 1; $i <= 100; $i++) {
            $this->_tagger->tag($i, 1, 1, new Horde_Date(strtotime('now - ' . $i . ' minutes')));
        }

        $recentLimit = $this->_tagger->getRecentUsers(array('limit' => 25));
        $this->assertEquals(25, count($recentLimit));
        $this->assertEquals(1, $recentLimit[0]['user_id']);
    }

    public function testGetRecentUsersOffset()
    {
        // Create 100 tags by 100 user_ids, with user_id = 1 tagging
        // most recently, and so on.
        for ($i = 1; $i <= 100; $i++) {
            $this->_tagger->tag($i, 1, 1, new Horde_Date(strtotime('now - ' . $i . ' minutes')));
        }

        $recentOffset = $this->_tagger->getRecentUsers(array('limit' => 25, 'offset' => 25));
        $this->assertEquals(25, count($recentOffset));
        $this->assertEquals(26, $recentOffset[0]['user_id']);
    }

    public function testGetRecentUsersByType()
    {
        $this->_fixture();
        $this->_tagger->tag('alice', array('object' => 1, 'type' => 1), array('test'));

        $recent = $this->_tagger->getRecentUsers();
        $recentByType = $this->_tagger->getRecentUsers(array('typeId' => 1));
        $this->assertEquals(1, count($recentByType));
        $this->assertEquals($recent, $recentByType);

        $recent = $this->_tagger->getRecentUsers(array('typeId' => 2));
        $this->assertEquals(0, count($recent));
    }

    /**
     * Populates some dummy tag data
     */
    private function _fixture()
    {
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
            $this->_db->execute($stmt);
        }
    }

}
