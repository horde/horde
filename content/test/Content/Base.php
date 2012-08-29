<?php

require_once __DIR__ . '/Autoload.php';

/**
 * Copyright 2008-2012 Horde LLC (http://www.horde.org/)
 *
 * @author     Michael J Rubinsky <mrubinsk@horde.org>
 * @author     Chuck Hagenbuch <chuck@horde.org>
 * @category   Horde
 * @package    Content
 * @subpackage UnitTests
 */
class Content_Test_Base extends Horde_Test_Case
{
    /**
     * @static Content_Tagger
     */
    static $tagger;

    /**
     * Primes the fixture, and tests basic tagging functionality where all
     * bits of data are new (user, type, object, tag)..
     *
     */
    protected function _create()
    {

        $this->_testEmpty();

        // user alice tags an event named 'party' with the tag 'personal' and
        // an event named 'anniversary' with the tag 'personal'
        self::$tagger->tag('alice', array('type' => 'event', 'object' => 'party'), 'play', new Horde_Date('2008-01-01T00:10:00'));

        // user alice tags an event named 'office hours' with the tag 'work'
        self::$tagger->tag('alice', array('type' => 'event', 'object' => 'office hours'), 'work', new Horde_Date('2008-01-01T00:05:00'));

        // user bob tags a blog named 'daring fireball' with the tag 'apple'
        self::$tagger->tag('bob', array('type' => 'blog', 'object' => 'daring fireball'), 'apple', new Horde_Date('2008-01-01T00:20:00'));

        // Two users have tagged the same object, with the same tag
        self::$tagger->tag('alice', array('type' => 'event', 'object' => 'anniversary'), 'personal', new Horde_Date('2009-01-01T00:05:00'));
        self::$tagger->tag('bob', array('type' => 'event', 'object' => 'anniversary'), 'personal', new Horde_Date('2009-01-01T00:06:00'));
    }

    protected function _testEmpty()
    {
        // Basic check that no data exists.
        $this->assertEmpty(self::$tagger->getTags(array()));
        $this->assertEmpty(self::$tagger->getRecentTags());
        $this->assertEmpty(self::$tagger->getRecentObjects());
    }

    /**
     * Test ensureTags.
     *
     * 1 => play
     * 2 => work
     * 3 => apple
     * 4 => personal
     */
    protected function _testEnsureTags()
    {
        // Test passing tag_ids to ensureTags
        $this->assertEquals(array(1), self::$tagger->ensureTags(1));
        $this->assertEquals(array(1), self::$tagger->ensureTags(array(1)));
        $this->assertEquals(array(1, 2), self::$tagger->ensureTags(array(1, 2)));

        // Test passing tag names
        $this->assertEquals(array(2), self::$tagger->ensureTags('work'));
        $this->assertEquals(array(2), self::$tagger->ensureTags(array('work')));
        $this->assertEquals(array(2, 1), self::$tagger->ensureTags(array('work', 'play')));

        // Test mixed
        $this->assertEquals(array(1, 1), self::$tagger->ensureTags(array(1, 'play')));
        $this->assertEquals(array(2, 2), self::$tagger->ensureTags(array('work', 2)));
        $this->assertEquals(array(1, 2), self::$tagger->ensureTags(array(1, 'work')));
    }

    protected function _testFullTagCloudSimple()
    {
        $expected = array(
            '1' => array(
                'tag_id' => 1,
                'tag_name' => 'play',
                'count' => 1
            ),

            '2' => array(
                'tag_id' => 2,
                'tag_name' => 'work',
                'count' => 1
            ),

            '3' => array(
                'tag_id' => 3,
                'tag_name' => 'apple',
                'count' => 1
            ),

            '4' => array(
                'tag_id' => 4,
                'tag_name' => 'personal',
                'count' => 2
            )
        );

        $cloud = self::$tagger->getTagCloud();
        $this->assertEquals($expected, $cloud);
    }

    protected function _testTagCloudByType()
    {
        $expected = array(
            '3' => array(
                'tag_id' => 3,
                'tag_name' => 'apple',
                'count' => 1
            )
        );
        $cloud = self::$tagger->getTagCloud(array('typeId' => 'blog'));
        $this->assertEquals($expected, $cloud);
    }

    protected function _testTagCloudByUser()
    {
        $expected = array(
            '3' => array(
                'tag_id' => 3,
                'tag_name' => 'apple',
                'count' => 1
            ),
            '4' => array(
                'tag_id' => 4,
                'tag_name' => 'personal',
                'count' => 1
            )
        );
        $cloud = self::$tagger->getTagCloud(array('userId' => 'bob'));
        $this->assertEquals($expected, $cloud);
    }

    protected function _testTagCloudByUserType()
    {
        $expected = array(
            '1' => array(
                'tag_id' => 1,
                'tag_name' => 'play',
                'count' => 1
            ),
            '2' => array(
                'tag_id' => 2,
                'tag_name' => 'work',
                'count' => 1
            ),
            '4' => array(
                'tag_id' => 4,
                'tag_name' => 'personal',
                'count' => 1
            )
        );
        $cloud = self::$tagger->getTagCloud(array('userId' => 'alice', 'typeId' => 'event'));
        $this->assertEquals($expected, $cloud);
    }

    protected function _testTagCloudByTagType()
    {
        $expected = array(
            '2' => array(
                'tag_id' => 2,
                'tag_name' => 'work',
                'count' => 1
            )
        );
        $cloud = self::$tagger->getTagCloud(array('tagIds' => array(2), 'typeId' => 'event'));
        $this->assertEquals($expected, $cloud);
    }

    protected function _testTagCloudByTagIds()
    {
        $expected = array(
            '2' => array(
                'tag_id' => 2,
                'tag_name' => 'work',
                'count' => 1
            ),
            '4' => array(
                'tag_id' => 4,
                'tag_name' => 'personal',
                'count' => 2
            )
        );
        $cloud = self::$tagger->getTagCloud(array('tagIds' => array(2, 4)));
        $this->assertEquals($expected, $cloud);
    }

    protected function _testGetRecentTags()
    {
        $recent = self::$tagger->getRecentTags();
        $this->assertEquals(4, count($recent));
        $this->assertEquals(4, $recent[0]['tag_id']);
        $this->assertEquals('personal', $recent[0]['tag_name']);
        $this->assertEquals('2009-01-01 00:06:00', $recent[0]['created']);
    }

    protected function _testGetRecentTagsByUser()
    {
        $recent = self::$tagger->getRecentTags(array('userId' => 1));
        $this->assertEquals(3, count($recent));

        $recent = self::$tagger->getRecentTags(array('userId' => 2));
        $this->assertEquals(2, count($recent));

        $recent = self::$tagger->getRecentTags(array('userId' => 'alice'));
        $this->assertEquals(3, count($recent));
    }

    protected function _testGetRecentTagsByType()
    {
        $recent = self::$tagger->getRecentTags(array('typeId' => 'event'));
        $this->assertEquals(3, count($recent));
    }

    protected function _testGetRecentObjects()
    {
        $recent = self::$tagger->getRecentObjects();
        $this->assertEquals(4, count($recent));
        $this->assertEquals(4, $recent[0]['object_id']);
        $this->assertEquals('2009-01-01 00:06:00', $recent[0]['created']);
    }

    protected function _testUntag()
    {
        self::$tagger->untag('alice', array('type' => 'event', 'object' => 'party'), 'play');
        $count = self::$tagger->getRecentTags();
        $this->assertEquals(3, count($count));

        //readd
        self::$tagger->tag('alice', array('type' => 'event', 'object' => 'party'), 'play', new Horde_Date('2008-01-01T00:10:00'));
        $count = self::$tagger->getRecentTags();
        $this->assertEquals(4, count($count));
    }
    /**
     * @TODO: SHould validate the values too, not just the count.
     */
    protected function _testGetRecentObjectsByUser()
    {
        // alice has 3 recent objects
        $recent = self::$tagger->getRecentObjects(array('userId' => 'alice'));
        $this->assertEquals(3, count($recent));

        // bob has 2
        $recent = self::$tagger->getRecentObjects(array('userId' => 'bob'));
        $this->assertEquals(2, count($recent));

        // just for kicks, test using the user id, not name.
        $recent = self::$tagger->getRecentObjects(array('userId' => 1));
        $this->assertEquals(3, count($recent));
    }

    protected function _testGetRecentObjectsByType()
    {
        $recent = self::$tagger->getRecentObjects(array('typeId' => 1));
        $this->assertEquals(3, count($recent));

        $recent = self::$tagger->getRecentObjects(array('typeId' => 2));
        $this->assertEquals(1, count($recent));
    }

    protected function _testGetRecentUsers()
    {
        $recent = self::$tagger->getRecentUsers();
        $this->assertEquals(2, count($recent));
    }

    protected function _testGetRecentUsersByType()
    {
        $recent = self::$tagger->getRecentUsers(array('typeId' => 1));
        $this->assertEquals(2, count($recent));

        $recent = self::$tagger->getRecentUsers(array('typeId' => 2));
        $this->assertEquals(1, count($recent));
    }

    /**
     * Test obtaining objects that are tagged with the same tags as the provided
     * object.
     *
     * See Bug: 10439
     */
    public function testGetObjectsByObjectId()
    {
        self::$tagger->tag('mike', array('type' => 'event', 'object' => 'irene'), 'hurricane', new Horde_Date('2011-08-28T00:01:00'));
        self::$tagger->tag('mike', array('type' => 'event', 'object' => 'floyd'), 'hurricane', new Horde_Date('1999-09-07T00:02:00'));
        $object = self::$tagger->getObjects(array('objectId' => array('type' => 'event', 'object' => 'irene')));
        $this->assertEquals('floyd', current($object));
    }

    public function testDuplicateTagsByCase()
    {
        // These tests don't work at the moment, because SQLite sucks at
        // non-ascii comparing.
        /*
        self::$tagger->tag('mike', 1, 'TYÖ');
        self::$tagger->tag('mike', 1, 'TYÖ');
        self::$tagger->tag('mike', 1, 'työ');
        self::$tagger->tag('mike', 1, 'työ');
        */
        // Use older timestamps to avoid interfering with the later tests
        self::$tagger->tag('mike', array('type' => 'foo', 'object' => 'xyz'), 'foo', new Horde_Date('2008-01-01T00:05:00'));
        self::$tagger->tag('alice', array('type' => 'foo', 'object' => 'xyz'), 'FOO', new Horde_Date('2008-01-01T00:05:00'));
        self::$tagger->tag('alice', array('type' => 'foo', 'object' => 'xyz'), array('test', 'TEST'), new Horde_Date('2008-01-01T00:05:00'));
        $this->assertEquals(2, count(self::$tagger->getTags(array('objectId' => array('type' => 'foo', 'object' => 'xyz')))));
    }

    public function testGetRecentTagsLimit()
    {
        // Create 100 tags on 100 tag_ids, with tag_id = t1 being applied
        // most recently, and so on. Prepend "t" to each tag to force the
        // creation of tags that don't yet exist in the test database.
        for ($i = 1; $i <= 100; $i++) {
            self::$tagger->tag(1, 1, "t$i", new Horde_Date(strtotime('now - ' . $i . ' minutes')));
        }

        $recentLimit = self::$tagger->getRecentTags(array('limit' => 25));
        $this->assertEquals(25, count($recentLimit));
        $this->assertEquals('t1', $recentLimit[0]['tag_name']);
    }

    /**
     * @depends testGetRecentTagsLimit
     */
    public function testGetRecentTagsOffset()
    {
        $recentOffset = self::$tagger->getRecentTags(array('limit' => 25, 'offset' => 25));
        $this->assertEquals(25, count($recentOffset));
        $this->assertEquals('t26', $recentOffset[0]['tag_name']);
    }

    public function testGetRecentObjectsLimit()
    {
        // Create 100 tags on 100 object_ids, with object_id = 1 being tagged
        // most recently, and so on.
        for ($i = 1; $i <= 100; $i++) {
            self::$tagger->tag(1, $i, 1, new Horde_Date(strtotime('now - ' . $i . ' minutes')));
        }

        $recentLimit = self::$tagger->getRecentObjects(array('limit' => 25));
        $this->assertEquals(25, count($recentLimit));
        $this->assertEquals(1, $recentLimit[0]['object_id']);
    }

    /**
     * @depend testGetRecentTagsOffset
     */
    public function testGetRecentObjectsOffset()
    {
        $recentOffset = self::$tagger->getRecentObjects(array('limit' => 25, 'offset' => 25));
        $this->assertEquals(25, count($recentOffset));
        $this->assertEquals(26, $recentOffset[0]['object_id']);
    }

    public function testGetRecentUsersLimit()
    {
        // Create 100 tags by 100 user_ids, with user_id = 1 tagging
        // most recently, and so on.
        for ($i = 1; $i <= 100; $i++) {
            self::$tagger->tag($i, 1, 1, new Horde_Date(strtotime('now - ' . $i . ' minutes')));
        }

        $recentLimit = self::$tagger->getRecentUsers(array('limit' => 25));
        $this->assertEquals(25, count($recentLimit));
        $this->assertEquals(1, $recentLimit[0]['user_id']);
    }

    /**
     * @depend testGetRecentUsersLimit
     */
    public function testGetRecentUsersOffset()
    {
        $recentOffset = self::$tagger->getRecentUsers(array('limit' => 25, 'offset' => 25));
        $this->assertEquals(25, count($recentOffset));
        $this->assertEquals(26, $recentOffset[0]['user_id']);
    }

}