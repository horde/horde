<?php
/**
 * Copyright 2007-2008 Maintainable Software, LLC
 * Copyright 2006-2011 The Horde Project (http://www.horde.org/)
 *
 * @author     Mike Naberezny <mike@maintainable.com>
 * @author     Derek DeVries <derek@maintainable.com>
 * @author     Chuck Hagenbuch <chuck@horde.org>
 * @license    http://opensource.org/licenses/bsd-license.php
 * @category   Horde
 * @package    Horde_View
 * @subpackage UnitTests
 */

/**
 * @group      view
 * @author     Mike Naberezny <mike@maintainable.com>
 * @author     Derek DeVries <derek@maintainable.com>
 * @author     Chuck Hagenbuch <chuck@horde.org>
 * @license    http://opensource.org/licenses/bsd-license.php
 * @category   Horde
 * @package    Horde_View
 * @subpackage UnitTests
 */
class Horde_View_Helper_BenchmarkTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->view = new Horde_View();
        $this->view->addHelper(new Horde_View_Helper_Benchmark($this->view));

        $log = new Horde_Log_Logger($this->mock = new Horde_Log_Handler_Mock());
        $this->view->logger = $log;
    }

    public function testWithoutLogger()
    {
        $this->view = new Horde_View();
        $this->view->addHelper(new Horde_View_Helper_Benchmark($this->view));

        $bench = $this->view->benchmark();
        $bench->end();
    }

    public function testDefaults()
    {
        $bench = $this->view->benchmark();
        $bench->end();
        $this->assertEquals(1, count($this->mock->events));
        $this->assertLastLogged();
    }

    public function testWithMessage()
    {
        $bench = $this->view->benchmark('test_run');
        $bench->end();
        $this->assertEquals(1, count($this->mock->events));
        $this->assertLastLogged('test_run');
    }

    public function testWithMessageAndLevelAsString()
    {
        $bench = $this->view->benchmark('debug_run', 'debug');
        $bench->end();
        $this->assertEquals(1, count($this->mock->events));
        $this->assertLastLogged('debug_run', 'debug');
    }

    public function testWithMessageAndLevelAsInteger()
    {
        $bench = $this->view->benchmark('debug_run', Horde_Log::DEBUG);
        $bench->end();
        $this->assertEquals(1, count($this->mock->events));
        $this->assertLastLogged('debug_run', 'debug');
    }

    public function assertLastLogged($message = 'Benchmarking', $level = 'info')
    {
        $last = end($this->mock->events);
        $this->assertEquals(strtoupper($level), $last['levelName']);
        $this->assertRegExp("/^$message \(.*\)$/", $last['message']);
    }

}
