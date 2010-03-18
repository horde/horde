<?php

require_once dirname(__FILE__) . '/TestCase.php';

/**
 * @author     Chuck Hagenbuch <chuck@horde.org>
 * @author     Mike Naberezny <mike@maintainable.com>
 * @license    http://opensource.org/licenses/bsd-license.php BSD
 * @category   Horde
 * @package    Horde_Argv
 * @subpackage UnitTests
 */

class Horde_Argv_OptionValuesTest extends Horde_Argv_TestCase
{
    public function testBasics()
    {
        $values = new Horde_Argv_Values();
        $this->assertEquals(iterator_to_array($values), array());
        $this->assertNotEquals($values, array('foo' => 'bar'));
        $this->assertNotEquals($values, '');

        $dict = array('foo' => 'bar', 'baz' => 42);
        $values = new Horde_Argv_Values($dict);
        $this->assertEquals($dict, iterator_to_array($values));
        $this->assertNotEquals($values, array('foo' => 'bar'));
        $this->assertNotEquals($values, array());
        $this->assertNotEquals($values, "");
    }
}
