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
class Horde_Log_Handler_FirebugTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        date_default_timezone_set('America/New_York');
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
        ob_start();

        $handler = new Horde_Log_Handler_Firebug();
        $handler->write(array('message' => $message = 'message-to-log',
                              'level' => $level = Horde_Log::ALERT,
                              'levelName' => $levelName = 'ALERT',
                              'timestamp' => date('c')));

        $contents = ob_get_clean();

        $date = '\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}-\d{2}:\d{2}';

        $this->assertRegExp("/console.error\(\"$date $levelName: $message\"\);/", $contents);
    }

}
