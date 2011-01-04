<?php
/**
 * @category   Horde
 * @package    Support
 * @subpackage UnitTests
 * @copyright  2007-2010 The Horde Project (http://www.horde.org/)
 * @license    http://opensource.org/licenses/bsd-license.php
 */

/**
 * @category   Horde
 * @package    Support
 * @subpackage UnitTests
 * @copyright  2007-2010 The Horde Project (http://www.horde.org/)
 * @license    http://opensource.org/licenses/bsd-license.php
 */
class Horde_Support_ArrayTest extends PHPUnit_Framework_TestCase
{
    public function testImplementsArrayAccess()
    {
        $o = new Horde_Support_Array();
        $this->assertInstanceOf('ArrayAccess', $o);
    }

    public function testImplementsIterator()
    {
        $o = new Horde_Support_Array();
        $this->assertInstanceOf('Iterator', $o);
    }

    public function testImplementsCountable()
    {
        $o = new Horde_Support_Array();
        $this->assertInstanceOf('Countable', $o);
    }

    // offsetGet()

    public function testOffsetGetReturnsValueAtOffset()
    {
        $o = new Horde_Support_Array(array('foo' => 'bar'));
        $this->assertEquals('bar', $o->offsetGet('foo'));
    }

    public function testOffsetGetReturnsNullWhenOffsetDoesNotExist()
    {
        $o = new Horde_Support_Array();
        $this->assertNull($o->offsetGet('foo'));
    }

    // get()

    public function testGetReturnsValueAtOffset()
    {
        $o = new Horde_Support_Array(array('foo' => 'bar'));
        $this->assertEquals('bar', $o->get('foo'));
    }

    public function testGetReturnsNullByDefaultWhenOffsetDoesNotExist()
    {
        $o = new Horde_Support_Array();
        $this->assertNull($o->get('foo'));
    }

    public function testGetReturnsDefaultSpecifiedWhenOffsetDoesNotExist()
    {
        $o = new Horde_Support_Array();
        $this->assertEquals('bar', $o->get('foo', 'bar'));
    }

    public function testGetReturnsDefaultSpecifiedWhenValueAtOffsetIsNull()
    {
        $o = new Horde_Support_Array(array('foo' => null));
        $this->assertEquals('bar', $o->get('foo', 'bar'));
    }

    // getOrSet()

    public function testGetOrSetReturnsValueAtOffset()
    {
        $o = new Horde_Support_Array(array('foo' => 'bar'));
        $this->assertEquals('bar', $o->getOrSet('foo'));
    }

    public function testGetOrSetReturnsAndSetsNullWhenOffsetDoesNotExist()
    {
        $o = new Horde_Support_Array();
        $this->assertNull($o->getOrSet('foo'));
        $this->assertTrue($o->offsetExists('foo'));
        $this->assertNull($o->offsetGet('foo'));
    }

    public function testGetOrSetReturnsAndSetsDefaultSpecifiedWhenOffsetDoesNotExist()
    {
        $o = new Horde_Support_Array();
        $this->assertEquals('bar', $o->getOrSet('foo', 'bar'));
        $this->assertTrue($o->offsetExists('foo'));
        $this->assertEquals('bar', $o->offsetGet('foo'));
    }

    public function testGetOrSetReturnsAndSetsDefaultSpecifiedValueAtOffsetIsNull()
    {
        $o = new Horde_Support_Array(array('foo' => null));
        $this->assertEquals('bar', $o->getOrSet('foo', 'bar'));
        $this->assertTrue($o->offsetExists('foo'));
        $this->assertEquals('bar', $o->offsetGet('foo'));
    }

    // pop()

    public function testPopReturnsValueAtOffsetAndUnsetsIt()
    {
        $o = new Horde_Support_Array(array('foo' => 'bar'));
        $this->assertEquals('bar', $o->pop('foo'));
        $this->assertFalse($o->offsetExists('foo'));
    }

    public function testPopReturnsNullByDefaultWhenOffsetDoesNotExist()
    {
        $o = new Horde_Support_Array();
        $this->assertNull($o->pop('foo'));
    }

    public function testPopReturnsDefaultSpecifiedWhenOffsetDoesNotExist()
    {
        $o = new Horde_Support_Array();
        $this->assertEquals('bar', $o->pop('foo', 'bar'));
    }

    public function testPopReturnsDefaultSpecifiedWhenValueAtOffsetIsNull()
    {
        $o = new Horde_Support_Array(array('foo' => null));
        $this->assertEquals('bar', $o->pop('foo', 'bar'));
    }

    // update()

    public function testUpdateDoesNotThrowWhenArgumentIsAnArray()
    {
        $o = new Horde_Support_Array();
        $o->update(array());
    }

    public function testUpdateDoesNotThrowWhenArgumentIsTraversable()
    {
        $o = new Horde_Support_Array();
        $o->update(new ArrayObject());
    }

    public function testUpdateMergesNewValuesFromArayInArgument()
    {
        $o = new Horde_Support_Array();
        $o->update(array('foo' => 'bar'));
        $this->assertEquals('bar', $o->offsetGet('foo'));
    }

    public function testUpdateMergesAndOverwritesExistingOffsets()
    {
        $o = new Horde_Support_Array(array('foo' => 'bar'));
        $o->update(array('foo' => 'baz'));
        $this->assertEquals('baz', $o->offsetGet('foo'));
    }

    public function testUpdateMergeDoesNotAffectUnrelatedKeys()
    {
        $o = new Horde_Support_Array(array('foo' => 'bar'));
        $o->update(array('baz' => 'qux'));
        $this->assertEquals('qux', $o->offsetGet('baz'));
    }

    // clear()

    public function testClearErasesTheArray()
    {
        $o = new Horde_Support_Array(array('foo' => 'bar'));
        $o->clear();
        $this->assertEquals(0, $o->count());
    }

    // getKeys()

    public function testGetKeysReturnsEmptyArrayWhenArrayIsEmpty()
    {
        $o = new Horde_Support_Array();
        $this->assertSame(array(), $o->getKeys());
    }

    public function testGetKeysReturnsArrayOfKeysInTheArray()
    {
        $o = new Horde_Support_Array(array('foo'=> 1, 'bar' => 2));
        $this->assertSame(array('foo', 'bar'), $o->getKeys());
    }

    // getValues()

    public function testGetValuesReturnsEmptyArrayWhenArrayIsEmpty()
    {
        $o = new Horde_Support_Array();
        $this->assertSame(array(), $o->getValues());
    }

    public function testGetValuesReturnsArrayOfValuesInTheArray()
    {
        $o = new Horde_Support_Array(array('foo' => 1, 'bar' => 2));
        $this->assertSame(array(1, 2), $o->getValues());
    }

}
