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
class Horde_Log_Filter_ChainingTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        date_default_timezone_set('America/New_York');

        $this->log = fopen('php://memory', 'w');
        $this->logger = new Horde_Log_Logger();
        $this->logger->addHandler(new Horde_Log_Handler_Stream($this->log));
    }

    public function tearDown()
    {
        fclose($this->log);
    }

    public function testFilterAllHandlers()
    {
        // filter out anything above a WARNing for all handlers
        $this->logger->addFilter(Horde_Log::WARN);

        $this->logger->info($ignored = 'info-message-ignored');
        $this->logger->warn($logged  = 'warn-message-logged');

        rewind($this->log);
        $logdata = stream_get_contents($this->log);

        $this->assertNotContains($ignored, $logdata);
        $this->assertContains($logged, $logdata);
    }


    public function testFilterOnSpecificHandler()
    {
        $log2 = fopen('php://memory', 'w');
        $handler2 = new Horde_Log_Handler_Stream($log2);
        $handler2->addFilter(Horde_Log::ERR);

        $this->logger->addHandler($handler2);

        $this->logger->warn($warn = 'warn-message');
        $this->logger->err($err = 'err-message');

        rewind($this->log);
        $logdata = stream_get_contents($this->log);
        $this->assertContains($warn, $logdata);
        $this->assertContains($err, $logdata);

        rewind($log2);
        $logdata = stream_get_contents($log2);
        $this->assertContains($err, $logdata);
        $this->assertNotContains($warn, $logdata);
    }

}
