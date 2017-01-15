<?php
/**
 * Copyright 2013-2017 Horde LLC (http://www.horde.org/)
 *
 * @category   Horde
 * @internal
 * @license    http://www.horde.org/licenses/bsd
 * @package    Support
 * @subpackage UnitTests
 */

/**
 * @category   Horde
 * @internal
 * @license    http://www.horde.org/licenses/bsd
 * @package    Support
 * @subpackage UnitTests
 */
class Horde_Support_CaseInsensitiveArrayTest extends PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider implementsProvider
     */
    public function testImplements($interface)
    {
        $o = new Horde_Support_CaseInsensitiveArray();
        $this->assertInstanceOf($interface, $o);
    }

    public function implementsProvider()
    {
        return array(
            array('ArrayAccess'),
            array('Traversable'),
            array('Countable')
        );
    }

    public function testOffsetGetReturnsValueAtOffset()
    {
        $o = new Horde_Support_CaseInsensitiveArray(array('foo' => 'bar'));
        $this->assertEquals('bar', $o['foo']);
    }

    public function testOffsetGetReturnsNullWhenOffsetDoesNotExist()
    {
        $o = new Horde_Support_CaseInsensitiveArray();
        $this->assertNull($o['foo']);
    }

    public function testCaseInsensitiveKeys()
    {
        $o = new Horde_Support_CaseInsensitiveArray(array('foo' => 'bar'));

        $this->assertTrue(isset($o['foo']));
        $this->assertTrue(isset($o['Foo']));
        $this->assertTrue(isset($o['FOO']));

        $this->assertEquals(
            'bar',
            $o['foo']
        );
        $this->assertEquals(
            'bar',
            $o['Foo']
        );
        $this->assertEquals(
            'bar',
            $o['FOO']
        );

        unset($o['FOO']);

        $this->assertFalse(isset($o['foo']));
    }

}
