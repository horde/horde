<?php
/**
 * @category   Horde
 * @package    Support
 * @subpackage UnitTests
 * @copyright  1999-2009 The Horde Project (http://www.horde.org/)
 * @license    http://opensource.org/licenses/bsd-license.php
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
 * @copyright  1999-2009 The Horde Project (http://www.horde.org/)
 * @license    http://opensource.org/licenses/bsd-license.php
 */
class Horde_Support_TimerTest extends PHPUnit_Framework_TestCase
{
    /**
     * test instantiating a normal timer
     */
    public function testNormalTiming()
    {
        $t = new Horde_Support_Timer;
        $start = $t->push();
        $elapsed = $t->pop();

        $this->assertTrue(is_float($start));
        $this->assertTrue(is_float($elapsed));
        $this->assertTrue($elapsed > 0);
    }

    /**
     * test getting the finish time before starting the timer
     * @expectedException Exception
     */
    public function testNotStartedYetThrowsException()
    {
        $t = new Horde_Support_Timer();
        $t->pop();
    }

}
