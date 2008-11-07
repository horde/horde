<?php
/**
 * Horde Log package
 *
 * This package is based on Zend_Log from the Zend Framework
 * (http://framework.zend.com).  Both that package and this
 * one were written by Mike Naberezny and Chuck Hagenbuch.
 *
 * @category Horde
 * @package  Horde_Log
 * @subpackage UnitTests
 * @author   Mike Naberezny <mike@maintainable.com>
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @license  http://opensource.org/licenses/bsd-license.php BSD
 */

/**
 * @category Horde
 * @package  Horde_Log
 * @subpackage UnitTests
 * @author   Mike Naberezny <mike@maintainable.com>
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @license  http://opensource.org/licenses/bsd-license.php BSD
 */
class Horde_Log_Formatter_SimpleTest extends PHPUnit_Framework_TestCase
{
    public function testConstructorThrowsOnBadFormatString()
    {
        try {
            new Horde_Log_Formatter_Simple(1);
            $this->fail();
        } catch (Exception $e) {
            $this->assertType('Horde_Log_Exception', $e);
            $this->assertRegExp('/must be a string/i', $e->getMessage());
        }
    }

    public function testDefaultFormat()
    {
        $f = new Horde_Log_Formatter_Simple();
        $line = $f->format(array('message' => $message = 'message',
                                 'level' => $level = Horde_Log::ALERT,
                                 'levelName' => $levelName = 'ALERT'));

        $this->assertContains($message, $line);
        $this->assertContains($levelName, $line);
    }
}
