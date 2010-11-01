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
class Horde_Log_Handler_DbTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->tableName = 'db-table-name';

        $this->db      = new Horde_Log_Handler_DbTest_MockDbAdapter();
        $this->handler = new Horde_Log_Handler_Db($this->db, $this->tableName);
    }

    public function testWriteWithDefaults()
    {
        // log to the mock db adapter
        $message = 'message-to-log';
        $level   = 2;
        $this->handler->write(array('message' => $message, 'level' => $level));

        // insert should be called once...
        $this->assertContains('insert', array_keys($this->db->calls));
        $this->assertEquals(1, count($this->db->calls['insert']));

        // ...with the correct table and binds for the database
        $binds = array('message' => $message,
                       'level' => $level);
        $this->assertEquals(array($this->tableName, $binds),
                            $this->db->calls['insert'][0]);
    }

    public function testWriteUsesOptionalCustomColumns()
    {
        $this->handler->setOption('fieldMessage', $messageField = 'new-message-field');
        $this->handler->setOption('fieldLevel',   $levelField   = 'new-level-field');

        // log to the mock db adapter
        $message = 'message-to-log';
        $level   = 2;
        $this->handler->write(array('message' => $message, 'level' => $level));

        // insert should be called once...
        $this->assertContains('insert', array_keys($this->db->calls));
        $this->assertEquals(1, count($this->db->calls['insert']));

        // ...with the correct table and binds for the database
        $binds = array($messageField => $message,
                       $levelField   => $level);
        $this->assertEquals(array($this->tableName, $binds),
                            $this->db->calls['insert'][0]);
    }
}


class Horde_Log_Handler_DbTest_MockDbAdapter
{
    public $calls = array();

    public function __call($method, $params)
    {
        $this->calls[$method][] = $params;
    }
}
