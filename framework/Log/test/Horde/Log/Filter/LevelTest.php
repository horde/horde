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
class Horde_Log_Filter_LevelTest extends PHPUnit_Framework_TestCase
{

    public function setUp()
    {
        // accept at or below level 2
        $this->filter = new Horde_Log_Filter_Level(2);
    }

    public function testLevelFilterAccept()
    {
        $this->assertTrue($this->filter->accept(array('message' => '', 'level' => 2)));
        $this->assertTrue($this->filter->accept(array('message' => '', 'level' => 1)));
    }

    public function testLevelFilterReject()
    {
        $this->assertFalse($this->filter->accept(array('message' => '', 'level' => 3)));
    }

    public function testConstructorThrowsOnInvalidLevel()
    {
        try {
            new Horde_Log_Filter_Level('foo');
            $this->fail();
        } catch (Exception $e) {
            $this->assertType('InvalidArgumentException', $e);
            $this->assertRegExp('/must be an integer/i', $e->getMessage());
        }
    }
}
