<?php
/**
 * @category   Horde
 * @package    Support
 * @subpackage UnitTests
 * @copyright  2007-2009 The Horde Project (http://www.horde.org/)
 * @license    http://opensource.org/licenses/bsd-license.php
 */

/**
 * @group      support
 * @category   Horde
 * @package    Support
 * @subpackage UnitTests
 * @copyright  2007-2009 The Horde Project (http://www.horde.org/)
 * @license    http://opensource.org/licenses/bsd-license.php
 */
class Horde_Support_BacktraceTest extends PHPUnit_Framework_TestCase
{
    public function testCreateFromDefaultBacktrace()
    {
        $trace = new Horde_Support_Backtrace();

        $caller = $trace->getCurrentContext();
        $this->assertEquals(__FUNCTION__, $caller['function']);
    }

    public function testCreateFromGeneratedBacktrace()
    {
        $trace = new Horde_Support_Backtrace($this->returnBacktrace());

        $caller = $trace->getCurrentContext();
        $this->assertEquals('returnBacktrace', $caller['function']);

        $caller = $trace->getCallingContext();
        $this->assertEquals(__FUNCTION__, $caller['function']);
    }

    public function testCreateFromException()
    {
        try {
            $this->generateUncaughtException();
        } catch (Exception $e) {
        }

        $trace = new Horde_Support_Backtrace($e);

        $caller = $trace->getCurrentContext();
        $this->assertEquals('generateUncaughtException', $caller['function']);

        $caller = $trace->getCallingContext();
        $this->assertEquals(__FUNCTION__, $caller['function']);
    }

    public function testNestingLevelOfDefaultVsGeneratedBacktrace()
    {
        $t1 = new Horde_Support_Backtrace();
        $t2 = new Horde_Support_Backtrace($this->returnBacktrace());

        $this->assertEquals($t1->getCurrentContext(), $t2->getCallingContext());
    }

    public function testNestingLevel()
    {
        $backtrace = new Horde_Support_Backtrace();
        $dbt = debug_backtrace();
        $this->assertEquals(count($dbt), $backtrace->getNestingLevel());
    }

    public function returnBacktrace()
    {
        return debug_backtrace();
    }

    public function generateUncaughtException()
    {
        throw new Exception();
    }
}
