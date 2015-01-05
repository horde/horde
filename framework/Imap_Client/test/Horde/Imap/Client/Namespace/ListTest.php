<?php
/**
 * Copyright 2014-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category   Horde
 * @copyright  2014-2015 Horde LLC
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    Imap_Client
 * @subpackage UnitTests
 */

/**
 * Tests for the Namespace list object.
 *
 * @author     Michael Slusarz <slusarz@horde.org>
 * @category   Horde
 * @copyright  2014-2015 Horde LLC
 * @ignore
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    Imap_Client
 * @subpackage UnitTests
 */
class Horde_Imap_Client_Namespace_ListTest
extends PHPUnit_Framework_TestCase
{
    private $ob;

    public function setUp()
    {
        $this->ob = new Horde_Imap_Client_Namespace_List();

        $ob2 = new Horde_Imap_Client_Data_Namespace();
        $ob2->delimiter = '.';
        $ob2->type = $ob2::NS_SHARED;
        $this->ob[''] = $ob2;

        $ob3 = new Horde_Imap_Client_Data_Namespace();
        $ob3->delimiter = '.';
        $ob3->hidden = true;
        $ob3->name = 'foo';
        $this->ob['foo'] = $ob3;
    }

    /**
     * @dataProvider arrayProvider
     */
    public function testArrayAccess($name, $exists = true)
    {
        if ($exists) {
            $this->assertTrue(isset($this->ob[$name]));
            $this->assertInstanceof(
                'Horde_Imap_Client_Data_Namespace',
                $this->ob[$name]
            );
        } else {
            $this->assertFalse(isset($this->ob[$name]));
            $this->assertNull($this->ob[$name]);
        }
    }

    /**
     */
    public function testCountable()
    {
        $this->assertEquals(
            2,
            count($this->ob)
        );
    }

    /**
     */
    public function testIterator()
    {
        foreach ($this->ob as $val) {
            $this->assertInstanceof(
                'Horde_Imap_Client_Data_Namespace',
                $val
            );
        }
    }

    /**
     */
    public function testSerialize()
    {
        $ob2 = unserialize(serialize($this->ob));

        $this->assertEquals(
            2,
            count($this->ob)
        );
    }

    /**
     * @dataProvider getNamespaceProvider
     */
    public function testGetNamespace($mbox, $personal, $expected)
    {
        if (is_null($expected)) {
            $this->assertNull($this->ob->getNamespace($mbox, $personal));
        } else {
            $this->assertEquals(
                $expected,
                strval($this->ob->getNamespace($mbox, $personal))
            );
        }
    }

    /**
     */
    public function arrayProvider()
    {
        return array(
            array(''),
            array('foo'),
            array('bar', false)
        );
    }

    /**
     */
    public function getNamespaceProvider()
    {
        return array(
            array('baz', false, ''),
            array('baz', true, null),
            array('foo.bar', false, 'foo'),
            array('foo.bar', true, 'foo'),
            array('baz.bar', false, ''),
            array('baz.bar', true, null)
        );
    }

}
