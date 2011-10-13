<?php
/**
 * @category Horde
 * @package Feed
 * @subpackage UnitTests
 */

/** Setup testing */
require_once dirname(__FILE__) . '/Autoload.php';

class Horde_Feed_CountTest extends PHPUnit_Framework_TestCase {

    public function testCount()
    {
        $f = Horde_Feed::readFile(dirname(__FILE__) . '/fixtures/TestAtomFeed.xml');
        $this->assertEquals($f->count(), 2, 'Feed count should be 2');
    }

}
