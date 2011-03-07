<?php
/**
 * @category Horde
 * @package Feed
 * @subpackage UnitTests
 */

/** Horde_Feed_TestCase */
require_once dirname(__FILE__) . '/TestCase.php';

class Horde_Feed_ReadTest extends PHPUnit_Framework_TestCase
{
    protected $_feedDir;

    public function setUp()
    {
        $this->_feedDir = dirname(__FILE__) . '/fixtures/';
    }

    /**
     * @dataProvider getValidAtomTests
     */
    public function testValidAtomFeeds($file)
    {
        $feed = Horde_Feed::readFile($this->_feedDir . $file);
        $this->assertType('Horde_Feed_Atom', $feed);
    }

    public static function getValidAtomTests()
    {
        return array(
            array('AtomTestGoogle.xml'),
            array('AtomTestMozillazine.xml'),
            array('AtomTestOReilly.xml'),
            array('AtomTestPlanetPHP.xml'),
            array('AtomTestSample1.xml'),
            array('AtomTestSample2.xml'),
            array('AtomTestSample4.xml'),
        );
    }

    /**
     * @dataProvider getValidRssTests
     */
    public function testValidRssFeeds($file)
    {
        $feed = Horde_Feed::readFile($this->_feedDir . $file);
        $this->assertType('Horde_Feed_Rss', $feed);
    }

    public static function getValidRssTests()
    {
        return array(
            array('RssTestHarvardLaw.xml'),
            array('RssTestPlanetPHP.xml'),
            array('RssTestSlashdot.xml'),
            array('RssTestCNN.xml'),
            array('RssTest091Sample1.xml'),
            array('RssTest092Sample1.xml'),
            array('RssTest100Sample1.xml'),
            array('RssTest100Sample2.xml'),
            array('RssTest200Sample1.xml'),
        );
    }

    public function testAtomWithUnbalancedTags()
    {
        $feed = Horde_Feed::readFile($this->_feedDir . 'AtomTestSample3.xml');
        $this->assertTrue($feed instanceof Horde_Feed_Base, 'Should be able to parse a feed with unmatched tags');
    }

    public function testNotAFeed()
    {
        try {
            $feed = Horde_Feed::readFile($this->_feedDir . 'NotAFeed.xml');
        } catch (Exception $e) {
            $this->assertType('Horde_Feed_Exception', $e);
            return;
        }

        $this->fail('Expected a Horde_Feed_Exception when parsing content that is not a feed of any kind');
    }

}
