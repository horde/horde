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
class Horde_Log_Handler_StreamTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        date_default_timezone_set('America/New_York');
    }

    public function testConstructorThrowsWhenResourceIsNotStream()
    {
        $resource = xml_parser_create();
        try {
            new Horde_Log_Handler_Stream($resource);
            $this->fail();
        } catch (Exception $e) {
            $this->assertType('Horde_Log_Exception', $e);
            $this->assertRegExp('/not a stream/i', $e->getMessage());
        }
        xml_parser_free($resource);
    }

    public function testConstructorWithValidStream()
    {
        $stream = fopen('php://memory', 'a');
        new Horde_Log_Handler_Stream($stream);
    }

    public function testConstructorWithValidUrl()
    {
        new Horde_Log_Handler_Stream('php://memory');
    }

    public function testConstructorThrowsWhenModeSpecifiedForExistingStream()
    {
        $stream = fopen('php://memory', 'a');
        try {
            new Horde_Log_Handler_Stream($stream, 'w');
            $this->fail();
        } catch (Exception $e) {
            $this->assertType('Horde_Log_Exception', $e);
            $this->assertRegExp('/existing stream/i', $e->getMessage());
        }
    }

    public function testConstructorThrowsWhenStreamCannotBeOpened()
    {
        try {
            new Horde_Log_Handler_Stream('');
            $this->fail();
        } catch (Exception $e) {
            $this->assertType('Horde_Log_Exception', $e);
            $this->assertRegExp('/cannot be opened/i', $e->getMessage());
        }
    }

    public function testSettingBadOptionThrows()
    {
        try {
            $handler = new Horde_Log_Handler_Stream('php://memory');
            $handler->setOption('foo', 42);
            $this->fail();
        } catch (Exception $e) {
            $this->assertType('Horde_Log_Exception', $e);
            $this->assertRegExp('/unknown option/i', $e->getMessage());
        }
    }

    public function testWrite()
    {
        $stream = fopen('php://memory', 'a');

        $handler = new Horde_Log_Handler_Stream($stream);
        $handler->write(array('message' => $message = 'message-to-log',
                              'level' => $level = Horde_Log::ALERT,
                              'levelName' => $levelName = 'ALERT',
                              'timestamp' => date('c')));

        rewind($stream);
        $contents = stream_get_contents($stream);
        fclose($stream);

        $date = '\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}-\d{2}:\d{2}';

        $this->assertRegExp("/$date $levelName: $message/", $contents);
    }

    public function testWriteThrowsWhenStreamWriteFails()
    {
        $stream = fopen('php://memory', 'a');
        $handler = new Horde_Log_Handler_Stream($stream);
        fclose($stream);

        try {
            $handler->write(array('message' => 'foo', 'level' => 1));
            $this->fail();
        } catch (Exception $e) {
            $this->assertType('Horde_Log_Exception', $e);
            $this->assertRegExp('/unable to write/i', $e->getMessage());
        }
    }

}
