<?php
/**
 * Prepare the test setup.
 */
require_once __DIR__ . '/Autoload.php';

/**
 * @author     Jan Schneider <jan@horde.org>
 * @license    http://www.horde.org/licenses/gpl GPL
 * @category   Horde
 * @package    Text_Diff
 * @subpackage UnitTests
 */
class Horde_Text_Diff_EngineTest extends PHPUnit_Framework_TestCase
{
    protected $_lines = array();

    public function setUp()
    {
        $this->_lines = array(
            1 => file(__DIR__ . '/fixtures/1.txt'),
            2 => file(__DIR__ . '/fixtures/2.txt'));
    }

    protected function _testDiff($diff)
    {
        $edits = $diff->getDiff();
        $this->assertEquals(3, count($edits));
        $this->assertInstanceof('Horde_Text_Diff_Op_Copy', $edits[0]);
        $this->assertInstanceof('Horde_Text_Diff_Op_Change', $edits[1]);
        $this->assertInstanceof('Horde_Text_Diff_Op_Copy', $edits[2]);
        $this->assertEquals('This line is the same.', $edits[0]->orig[0]);
        $this->assertEquals('This line is the same.', $edits[0]->final[0]);
        $this->assertEquals('This line is different in 1.txt', $edits[1]->orig[0]);
        $this->assertEquals('This line is different in 2.txt', $edits[1]->final[0]);
        $this->assertEquals('This line is the same.', $edits[2]->orig[0]);
        $this->assertEquals('This line is the same.', $edits[2]->final[0]);
    }

    public function testNativeEngine()
    {
        $diff = new Horde_Text_Diff('Native', array($this->_lines[1], $this->_lines[2]));
        $this->_testDiff($diff);
    }

    public function testShellEngine()
    {
        if (!exec('which diff')) {
            $this->markTestSkipped('diff executable not found');
        }
        $diff = new Horde_Text_Diff('Shell', array($this->_lines[1], $this->_lines[2]));
        $this->_testDiff($diff);
    }

    public function testStringEngine()
    {
        $patch = file_get_contents(__DIR__ . '/fixtures/unified.patch');
        $diff = new Horde_Text_Diff('String', array($patch));
        $this->_testDiff($diff);

        $patch = file_get_contents(__DIR__ . '/fixtures/unified2.patch');
        try {
            $diff = new Horde_Text_Diff('String', array($patch));
            $this->fail('Horde_Text_Diff_Exception expected');
        } catch (Horde_Text_Diff_Exception $e) {
        }
        $diff = new Horde_Text_Diff('String', array($patch, 'unified'));
        $edits = $diff->getDiff();
        $this->assertEquals(1, count($edits));
        $this->assertInstanceof('Horde_Text_Diff_Op_Change', $edits[0]);
        $this->assertEquals('For the first time in U.S. history number of private contractors and troops are equal', $edits[0]->orig[0]);
        $this->assertEquals('Number of private contractors and troops are equal for first time in U.S. history', $edits[0]->final[0]);

        $patch = file_get_contents(__DIR__ . '/fixtures/context.patch');
        $diff = new Horde_Text_Diff('String', array($patch));
        $this->_testDiff($diff);
    }

    public function testXdiffEngine()
    {
        try {
            $diff = new Horde_Text_Diff('Xdiff', array($this->_lines[1], $this->_lines[2]));
            $this->_testDiff($diff);
        } catch (Horde_Text_Diff_Exception $e) {
            if (extension_loaded('xdiff')) {
                throw $e;
            }
        }
    }
}
