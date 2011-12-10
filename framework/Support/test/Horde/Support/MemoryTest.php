<?php
/**
 * @category   Horde
 * @package    Support
 * @subpackage UnitTests
 * @copyright  2011 Horde LLC (http://www.horde.org/)
 * @license    http://www.horde.org/licenses/bsd
 */

/**
 * Prepare the test setup.
 */
require_once dirname(__FILE__) . '/Autoload.php';

/**
 * @group      support
 * @category   Horde
 * @package    Support
 * @subpackage UnitTests
 * @copyright  2011 Horde LLC (http://www.horde.org/)
 * @license    http://www.horde.org/licenses/bsd
 */
class Horde_Support_MemoryTest extends PHPUnit_Framework_TestCase
{
    public function testMemoryStart()
    {
        $t = new Horde_Support_Memory;
        $this->assertInternalType('array', $t->push());
    }

    public function testMemoryEnd()
    {
        $t = new Horde_Support_Memory;
        $t->push();
        $this->assertInternalType('array', $t->pop());
    }

    public function testStartValues()
    {
        $t = new Horde_Support_Memory;
        $this->assertEquals(4, count($t->push()));
    }

    public function testEndValues()
    {
        $t = new Horde_Support_Memory;
        $t->push();
        $this->assertEquals(4, count($t->pop()));
    }

    public function testOnlyIncrease()
    {
        $t = new Horde_Support_Memory;
        $t->push();
        $end = $t->pop();
        $this->assertTrue($end[1] >= 0);
        $this->assertTrue($end[3] >= 0);
    }

    /**
     * @expectedException Exception
     */
    public function testNotPushedThrowsException()
    {
        $t = new Horde_Support_Memory();
        $t->pop();
    }

}
