<?php
/**
 * @category Horde
 * @package Feed
 * @subpackage UnitTests
 */

/** Setup testing */
require_once __DIR__ . '/Autoload.php';

class Horde_Feed_BlogrollTest extends PHPUnit_Framework_TestCase
{
    protected $_feedDir;

    public function setUp()
    {
        $this->_feedDir = __DIR__ . '/fixtures/';
    }

    /**
     * @dataProvider getValidBlogrollTests
     */
    public function testValidBlogrolls($file)
    {
        $feed = Horde_Feed::readFile($this->_feedDir . $file);
        $this->assertInstanceOf('Horde_Feed_Blogroll', $feed);
        $this->assertTrue(count($feed) > 0);
        foreach ($feed as $entry) {
            break;
        }

        $this->assertInstanceOf('Horde_Feed_Entry_Blogroll', $entry);
        $this->assertGreaterThan(0, strlen($entry->text));
        $this->assertGreaterThan(0, strlen($entry->xmlurl));

        $this->assertEquals($entry->text, $entry['text']);
        $this->assertEquals($entry->description, $entry['description']);
        $this->assertEquals($entry->title, $entry['title']);
        $this->assertEquals($entry->htmlurl, $entry['htmlurl']);
        $this->assertEquals($entry->xmlurl, $entry['xmlurl']);
    }

    public function testGroupedBlogrolls()
    {
        $this->markTestSkipped();
        $feed = Horde_Feed::readFile($this->_feedDir . 'MySubscriptionsGrouped.opml');
    }

    public static function getValidBlogrollTests()
    {
        return array(
            array('BlogRollTestSample1.xml'),
            array('BlogRollTestSample2.xml'),
            array('MySubscriptions.opml'),
            array('MySubscriptionsGrouped.opml'),
        );
    }
}
