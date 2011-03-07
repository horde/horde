<?php
/**
 * @category Horde
 * @package Feed
 * @subpackage UnitTests
 */

/** Horde_Feed_TestCase */
require_once dirname(__FILE__) . '/TestCase.php';

class Horde_Feed_IteratorTest extends PHPUnit_Framework_TestCase {

    private $feed;
    private $nsfeed;

    public function setUp()
    {
        $this->feed = Horde_Feed::readFile(dirname(__FILE__) . '/fixtures/TestAtomFeed.xml');
        $this->nsfeed = Horde_Feed::readFile(dirname(__FILE__) . '/fixtures/TestAtomFeedNamespaced.xml');
    }

    public function testRewind()
    {
        $times = 0;
        foreach ($this->feed as $f) {
            ++$times;
        }

        $times2 = 0;
        foreach ($this->feed as $f) {
            ++$times2;
        }

        $this->assertEquals($times, $times2, 'Feed should have the same number of iterations multiple times through');

        $times = 0;
        foreach ($this->nsfeed as $f) {
            ++$times;
        }

        $times2 = 0;
        foreach ($this->nsfeed as $f) {
            ++$times2;
        }

        $this->assertEquals($times, $times2, 'Feed should have the same number of iterations multiple times through');
    }

    public function testCurrent()
    {
        foreach ($this->feed as $f) {
            $this->assertTrue($f instanceof Horde_Feed_Entry_Atom, 'Each feed entry should be an instance of Horde_Feed_Entry_Atom');
            break;
        }

        foreach ($this->nsfeed as $f) {
            $this->assertTrue($f instanceof Horde_Feed_Entry_Atom, 'Each feed entry should be an instance of Horde_Feed_Entry_Atom');
            break;
        }
    }

    public function testKey()
    {
        $keys = array();
        foreach ($this->feed as $k => $f) {
            $keys[] = $k;
        }
        $this->assertEquals($keys, array(0, 1), 'Feed should have keys 0 and 1');

        $keys = array();
        foreach ($this->nsfeed as $k => $f) {
            $keys[] = $k;
        }
        $this->assertEquals($keys, array(0, 1), 'Feed should have keys 0 and 1');
    }

    public function testNext()
    {
        $last = null;
        foreach ($this->feed as $current) {
            $this->assertFalse($last === $current, 'Iteration should produce a new object each entry');
            $last = $current;
        }

        $last = null;
        foreach ($this->nsfeed as $current) {
            $this->assertFalse($last === $current, 'Iteration should produce a new object each entry');
            $last = $current;
        }
    }

}
