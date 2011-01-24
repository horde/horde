<?php
/**
 * Horde Log package
 *
 * This package is based on Zend_Log from the Zend Framework
 * (http://framework.zend.com).  Both that package and this
 * one were written by Mike Naberezny and Chuck Hagenbuch.
 *
 * @author     Mike Naberezny <mike@maintainable.com>
 * @author     Chuck Hagenbuch <chuck@horde.org>
 * @category   Horde
 * @license    http://opensource.org/licenses/bsd-license.php BSD
 * @package    Log
 * @subpackage UnitTests
 */

/**
 * @author     Mike Naberezny <mike@maintainable.com>
 * @author     Chuck Hagenbuch <chuck@horde.org>
 * @category   Horde
 * @license    http://opensource.org/licenses/bsd-license.php BSD
 * @package    Log
 * @subpackage UnitTests
 */
class Horde_Log_LogTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        date_default_timezone_set('America/New_York');

        $this->log = fopen('php://memory', 'a');
        $this->handler = new Horde_Log_Handler_Stream($this->log);
    }

    // Handlers

    public function testHandlerCanBeAddedWithConstructor()
    {
        $logger = new Horde_Log_Logger($this->handler);
        $logger->log($message = 'message-to-long', Horde_Log::INFO);

        rewind($this->log);
        $this->assertContains($message, stream_get_contents($this->log));
    }

    public function testaddHandler()
    {
        $logger = new Horde_Log_Logger();
        $logger->addHandler($this->handler);
        $logger->log($message = 'message-to-log', Horde_Log::INFO);

        rewind($this->log);
        $this->assertContains($message, stream_get_contents($this->log));
    }

    public function testaddHandlerAddsMultipleHandlers()
    {
        $logger = new Horde_Log_Logger();

        // create handlers for two separate streams of temporary memory
        $log1    = fopen('php://memory', 'a');
        $handler1 = new Horde_Log_Handler_Stream($log1);
        $log2    = fopen('php://memory', 'a');
        $handler2 = new Horde_Log_Handler_Stream($log2);

        // add the handlers
        $logger->addHandler($handler1);
        $logger->addHandler($handler2);

        // log to both handlers
        $logger->log($message = 'message-sent-to-both-logs', Horde_Log::INFO);

        // verify both handlers were called by the logger
        rewind($log1);
        $this->assertContains($message, stream_get_contents($log1));
        rewind($log2);
        $this->assertContains($message, stream_get_contents($log2));

        // prove the two memory streams are different
        // and both handlers were indeed called
        fwrite($log1, 'foo');
        $this->assertNotEquals(ftell($log1), ftell($log2));
    }

    public function testLoggerThrowsWhenNoHandlers()
    {
        $logger = new Horde_Log_Logger();
        try {
            $logger->log('message', Horde_Log::INFO);
            $this->fail();
        } catch (Horde_Log_Exception $e) {
            $this->assertRegexp('/no handler/i', $e->getMessage());
        }
    }

    // Levels

    public function testLogThrowsOnBadLogLevel()
    {
        $logger = new Horde_Log_Logger($this->handler);
        try {
            $logger->log('foo', 42);
            $this->fail();
        } catch (Exception $e) {
            $this->assertInstanceOf('Horde_Log_Exception', $e);
            $this->assertRegExp('/bad log level/i', $e->getMessage());
        }
    }

    public function testLogThrough__callThrowsOnBadLogLevel()
    {
        $logger = new Horde_Log_Logger($this->handler);
        try {
            $logger->nonexistantLevel('');
            $this->fail();
        } catch (Exception $e) {
            $this->assertInstanceOf('Horde_Log_Exception', $e);
            $this->assertRegExp('/bad log level/i', $e->getMessage());
        }
    }

    public function testAddingLevelThrowsWhenOverridingBuiltinLogLevel()
    {
        try {
            $logger = new Horde_Log_Logger($this->handler);
            $logger->addLevel('BOB', 0);
            $this->fail();
        } catch (Exception $e) {
            $this->assertInstanceOf('Horde_Log_Exception', $e);
            $this->assertRegExp('/existing log level/i', $e->getMessage());
        }

    }

    public function testAddLogLevel()
    {
        $logger = new Horde_Log_Logger($this->handler);
        $logger->addLevel($levelName = 'EIGHT', $level = 8);

        $logger->eight($message = 'eight message');

        rewind($this->log);
        $logdata = stream_get_contents($this->log);
        $this->assertContains($levelName, $logdata);
        $this->assertContains($message, $logdata);
    }
}
