<?php
/**
 * @category Horde
 * @package Feed
 * @subpackage UnitTests
 */

/** Horde_Feed_TestCase */
require_once dirname(__FILE__) . '/TestCase.php';

class Horde_Feed_CountTest extends PHPUnit_Framework_TestCase {

    public function testCount()
    {
        $f = Horde_Feed::readFile(dirname(__FILE__) . '/fixtures/TestAtomFeed.xml');
        $this->assertEquals($f->count(), 2, 'Feed count should be 2');
    }

}
