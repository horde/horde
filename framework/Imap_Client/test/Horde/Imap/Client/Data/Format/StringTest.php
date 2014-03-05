<?php
/**
 * Copyright 2011-2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category   Horde
 * @copyright  2011-2014 Horde LLC
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    Imap_Client
 * @subpackage UnitTests
 */

/**
 * Tests for the String data format object.
 *
 * @author     Michael Slusarz <slusarz@horde.org>
 * @category   Horde
 * @copyright  2011-2014 Horde LLC
 * @ignore
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    Imap_Client
 * @subpackage UnitTests
 */
class Horde_Imap_Client_Data_Format_StringTest
extends PHPUnit_Framework_TestCase
{
    private $ob;
    private $ob2;
    private $ob3;
    private $ob4;
    private $ob5;

    public function setUp()
    {
        $this->ob = new Horde_Imap_Client_Data_Format_String('Foo');
        $this->ob2 = new Horde_Imap_Client_Data_Format_String('Foo(');
        /* This is an invalid atom, but valid string. */
        $this->ob3 = new Horde_Imap_Client_Data_Format_String('Foo]');
        /* This string requires a literal. */
        $this->ob4 = new Horde_Imap_Client_Data_Format_String("Foo\n]");
        /* This string requires a binary literal. */
        $this->ob5 = new Horde_Imap_Client_Data_Format_String("12\x00\n3");
    }

    public function testStringRepresentation()
    {
        $this->assertEquals(
            'Foo',
            strval($this->ob)
        );

        $this->assertEquals(
            'Foo(',
            strval($this->ob2)
        );

        $this->assertEquals(
            'Foo]',
            strval($this->ob3)
        );

        $this->assertEquals(
            "Foo\n]",
            strval($this->ob4)
        );

        $this->assertEquals(
            "12\x00\n3",
            strval($this->ob5)
        );
    }

    public function testEscape()
    {
        $this->assertEquals(
            '"Foo"',
            $this->ob->escape()
        );

        $this->assertEquals(
            '"Foo("',
            $this->ob2->escape()
        );

        $this->assertEquals(
            '"Foo]"',
            $this->ob3->escape()
        );

        try {
            // Expected Exception
            $this->ob4->escape();
            $this->fail();
        } catch (Horde_Imap_Client_Data_Format_Exception $e) {}

        try {
            // Expected Exception
            $this->ob5->escape();
            $this->fail();
        } catch (Horde_Imap_Client_Data_Format_Exception $e) {}
    }

    public function testVerify()
    {
        // Don't throw Exception
        $this->ob->verify();
        $this->ob2->verify();
        $this->ob3->verify();
        $this->ob4->verify();
        $this->ob5->verify();
    }

    public function testBinary()
    {
        $this->assertFalse($this->ob->binary());
        $this->assertFalse($this->ob2->binary());
        $this->assertFalse($this->ob3->binary());
        $this->assertFalse($this->ob4->binary());
        $this->assertTrue($this->ob5->binary());
    }

    public function testLiteral()
    {
        $this->assertFalse($this->ob->literal());
        $this->assertFalse($this->ob2->literal());
        $this->assertFalse($this->ob3->literal());
        $this->assertTrue($this->ob4->literal());
        $this->assertTrue($this->ob5->literal());
    }

    public function testQuoted()
    {
        $this->assertTrue($this->ob->quoted());
        $this->assertTrue($this->ob2->quoted());
        $this->assertTrue($this->ob3->quoted());
        $this->assertFalse($this->ob4->quoted());
        $this->assertFalse($this->ob5->quoted());
    }

    public function testEscapeStream()
    {
        $this->assertEquals(
            '"Foo"',
            stream_get_contents($this->ob->escapeStream(), -1, 0)
        );

        $this->assertEquals(
            '"Foo("',
            stream_get_contents($this->ob2->escapeStream(), -1, 0)
        );

        $this->assertEquals(
            '"Foo]"',
            stream_get_contents($this->ob3->escapeStream(), -1, 0)
        );

        try {
            // Expected Exception
            $this->ob4->escapeStream();
            $this->fail();
        } catch (Horde_Imap_Client_Data_Format_Exception $e) {}

        try {
            // Expected Exception
            $this->ob5->escapeStream();
            $this->fail();
        } catch (Horde_Imap_Client_Data_Format_Exception $e) {}
    }

}
