<?php
/**
 * Copyright 2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category   Horde
 * @copyright  2014 Horde LLC
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    Imap_Client
 * @subpackage UnitTests
 */

/**
 * Tests for the Namespace data object.
 *
 * @author     Michael Slusarz <slusarz@horde.org>
 * @category   Horde
 * @copyright  2014 Horde LLC
 * @ignore
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    Imap_Client
 * @subpackage UnitTests
 */
class Horde_Imap_Client_Namespace_DataTest
extends PHPUnit_Framework_TestCase
{
    private $ob;

    public function setUp()
    {
        $this->ob = new Horde_Imap_Client_Data_Namespace();
    }

    /**
     * @dataProvider defaultProvider
     */
    public function testDefaultValues($name, $value)
    {
        $this->assertEquals(
            $value,
            $this->ob->$name
        );
    }

    /**
     * @dataProvider settingProvider
     */
    public function testSettingValues($name, $value, $expected = null)
    {
        if (is_null($expected)) {
            $expected = $value;
        }

        $this->assertFalse(isset($this->ob->$name));

        $this->ob->$name = $value;

        $this->assertEquals(
            $expected,
            $this->ob->$name
        );

        if (!is_null($value)) {
            $this->assertTrue(isset($this->ob->$name));
        }
    }

    /**
     */
    public function testStringVal()
    {
        $this->assertEquals(
            '',
            strval($this->ob)
        );

        $this->ob->name = 123;
        $this->assertEquals(
            '123',
            strval($this->ob)
        );
    }

    /**
     */
    public function testBaseReturn()
    {
        $this->ob->delimiter = '.';
        $this->ob->name = 'foo.';

        $this->assertEquals(
            'foo',
            $this->ob->base
        );
    }

    /**
     */
    public function testSerialize()
    {
        $this->ob->delimiter = '.';
        $this->ob->name = 'foo.';

        $ob2 = unserialize(serialize($this->ob));

        $this->assertEquals(
            $this->ob->delimiter,
            $ob2->delimiter
        );
        $this->assertEquals(
            $this->ob->name,
            $ob2->name
        );
        $this->assertEquals(
            $this->ob->translation,
            $ob2->translation
        );
    }

    /**
     * @dataProvider stripProvider
     */
    public function testStripNamespace($name, $delimiter, $mbox, $expected)
    {
        $this->ob->name = $name;
        $this->ob->delimiter = $delimiter;

        $this->assertEquals(
            $expected,
            $this->ob->stripNamespace($mbox)
        );
    }

    /**
     */
    public function defaultProvider()
    {
        return array(
            array('base', ''),
            array('delimiter', ''),
            array('hidden', false),
            array('name', ''),
            array('translation', ''),
            array('type', Horde_Imap_Client_Data_Namespace::NS_PERSONAL),
            // Bogus value
            array('foo', null)
        );
    }

    /**
     */
    public function settingProvider()
    {
        return array(
            array('delimiter', '.'),
            array('delimiter', '/'),
            array('delimiter', 1, '1'),
            array('hidden', false),
            array('hidden', 0, false),
            array('hidden', true),
            array('hidden', 1, true),
            array('name', 'foo'),
            array('name', 123, '123'),
            array('translation', 'foo'),
            array('translation', 123, '123'),
            array('type', Horde_Imap_Client_Data_Namespace::NS_PERSONAL),
            array('type', Horde_Imap_Client_Data_Namespace::NS_OTHER),
            array('type', Horde_Imap_Client_Data_Namespace::NS_SHARED),
            // Bogus value
            array('foo', null)
        );
    }

    /**
     */
    public function stripProvider()
    {
        return array(
            array('foo.', '.', 'foo.bar', 'bar'),
            array('foo.', '.', 'foo2.bar', 'foo2.bar'),
            array('foo.bar.', '.', 'foo.bar.baz', 'baz'),
            array('', '.', 'foo.bar', 'foo.bar')
        );
    }

}
