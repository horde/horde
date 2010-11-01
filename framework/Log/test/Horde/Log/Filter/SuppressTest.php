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
class Horde_Log_Filter_SuppressTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->filter = new Horde_Log_Filter_Suppress();
    }

    public function testSuppressIsInitiallyOff()
    {
        $this->assertTrue($this->filter->accept(array()));
    }

    public function testSuppressOn()
    {
        $this->filter->suppress(true);
        $this->assertFalse($this->filter->accept(array()));
        $this->assertFalse($this->filter->accept(array()));
    }

    public function testSuppressOff()
    {
        $this->filter->suppress(false);
        $this->assertTrue($this->filter->accept(array()));
        $this->assertTrue($this->filter->accept(array()));
    }

    public function testSuppressCanBeReset()
    {
        $this->filter->suppress(true);
        $this->assertFalse($this->filter->accept(array()));
        $this->filter->suppress(false);
        $this->assertTrue($this->filter->accept(array()));
        $this->filter->suppress(true);
        $this->assertFalse($this->filter->accept(array()));
    }
}
