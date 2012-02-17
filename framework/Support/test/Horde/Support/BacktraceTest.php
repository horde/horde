<?php
/**
 * Copyright 2007-2012 Horde LLC (http://www.horde.org/)
 *
 * @category   Horde
 * @package    Support
 * @subpackage UnitTests
 * @license    http://www.horde.org/licenses/bsd
 */

/**
 * Prepare the test setup.
 */
require_once dirname(__FILE__) . '/Autoload.php';

function backtraceTestFunction()
{
    return debug_backtrace(false);
}

/**
 * @category   Horde
 * @package    Support
 * @subpackage UnitTests
 * @license    http://www.horde.org/licenses/bsd
 */
class Horde_Support_BacktraceTest extends PHPUnit_Framework_TestCase
{
    // Keep these two methods on the top so that the line numbers don't change
    // when new tests are added.
    public function instanceMethod()
    {
        return Horde_Support_BacktraceTest::staticMethod();
    }

    public static function staticMethod()
    {
        return backtraceTestFunction();
    }

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

    public function testToString()
    {
        $backtrace = new Horde_Support_Backtrace(array_slice($this->instanceMethod(), 0, 4));
        $file = __FILE__;
        $this->assertEquals("1. Horde_Support_BacktraceTest->testToString()
2. Horde_Support_BacktraceTest->instanceMethod() $file:93
3. Horde_Support_BacktraceTest::staticMethod() $file:33
4. backtraceTestFunction() $file:38
",
                            (string)$backtrace);
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
