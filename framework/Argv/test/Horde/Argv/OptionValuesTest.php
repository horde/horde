<?php

require_once __DIR__ . '/TestCase.php';

/**
 * @author     Chuck Hagenbuch <chuck@horde.org>
 * @author     Mike Naberezny <mike@maintainable.com>
 * @license    http://www.horde.org/licenses/bsd BSD
 * @category   Horde
 * @package    Argv
 * @subpackage UnitTests
 */

class Horde_Argv_OptionValuesTest extends Horde_Argv_TestCase
{
    public function testBasics()
    {
        $values = new Horde_Argv_Values();
        $this->assertEquals(array(), iterator_to_array($values));
        $this->assertNotEquals(array('foo' => 'bar'), $values);
        $this->assertEquals('', (string)$values);

        $dict = array('foo' => 'bar', 'baz' => 42);
        $values = new Horde_Argv_Values($dict);
        $this->assertEquals($dict, iterator_to_array($values));
        $this->assertNotEquals(array('foo' => 'bar'), $values);
        $this->assertNotEquals(array(), $values);
        $this->assertNotEquals('', (string)$values);
    }
}
