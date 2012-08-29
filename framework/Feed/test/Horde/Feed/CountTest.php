<?php
/**
 * @category Horde
 * @package Feed
 * @subpackage UnitTests
 */

/** Setup testing */
require_once __DIR__ . '/Autoload.php';

class Horde_Feed_CountTest extends PHPUnit_Framework_TestCase {

    public function testCount()
    {
        $f = Horde_Feed::readFile(__DIR__ . '/fixtures/TestAtomFeed.xml');
        $this->assertEquals($f->count(), 2, 'Feed count should be 2');
    }

}
