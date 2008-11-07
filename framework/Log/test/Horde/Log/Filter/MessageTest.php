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
class Horde_Log_Filter_MessageTest extends PHPUnit_Framework_TestCase
{

    public function testMessageFilterRecognizesInvalidRegularExpression()
    {
        try {
            $filter = new Horde_Log_Filter_Message('invalid regexp');
            $this->fail();
        } catch (Horde_Log_Exception $e) {
            $this->assertRegexp('/invalid reg/i', $e->getMessage());
        }
    }

    public function testMessageFilter()
    {
        $filter = new Horde_Log_Filter_Message('/accept/');
        $this->assertTrue($filter->accept(array('message' => 'foo accept bar', 'level' => 0)));
        $this->assertFalse($filter->accept(array('message' => 'foo reject bar', 'level' => 0)));
    }

}
