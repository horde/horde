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
 * Tests for the Astring data format object.
 *
 * @author     Michael Slusarz <slusarz@horde.org>
 * @category   Horde
 * @copyright  2011-2014 Horde LLC
 * @ignore
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    Imap_Client
 * @subpackage UnitTests
 */
class Horde_Imap_Client_Data_Format_AstringTest
extends PHPUnit_Framework_TestCase
{
    private $ob;
    private $ob2;
    private $ob3;
    private $ob4;

    public function setUp()
    {
        $this->ob = new Horde_Imap_Client_Data_Format_Astring('Foo');
        $this->ob2 = new Horde_Imap_Client_Data_Format_Astring('Foo(');
        /* This is an invalid atom, but valid (non-quoted) astring. */
        $this->ob3 = new Horde_Imap_Client_Data_Format_Astring('Foo]');
        $this->ob4 = new Horde_Imap_Client_Data_Format_Astring('');
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
            '',
            strval($this->ob4)
        );
    }

    public function testEscape()
    {
        $this->assertEquals(
            'Foo',
            $this->ob->escape()
        );

        // Require quoting
        $this->assertEquals(
            '"Foo("',
            $this->ob2->escape()
        );

        $this->assertEquals(
            'Foo]',
            $this->ob3->escape()
        );

        /* Empty string should be quoted. */
        $this->assertEquals(
            '""',
            $this->ob4->escape()
        );
    }

    public function testVerify()
    {
        // Don't throw Exception
        $this->ob->verify();
        $this->ob2->verify();
        $this->ob3->verify();
        $this->ob4->verify();
    }

    public function testBinary()
    {
        $this->assertFalse($this->ob->binary());
        $this->assertFalse($this->ob2->binary());
        $this->assertFalse($this->ob3->binary());
        $this->assertFalse($this->ob4->binary());
    }

    public function testLiteral()
    {
        $this->assertFalse($this->ob->literal());
        $this->assertFalse($this->ob2->literal());
        $this->assertFalse($this->ob3->literal());
        $this->assertFalse($this->ob4->literal());
    }

    public function testQuoted()
    {
        $this->assertFalse($this->ob->quoted());
        $this->assertTrue($this->ob2->quoted());
        $this->assertFalse($this->ob3->quoted());
        $this->assertTrue($this->ob4->quoted());
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

        $this->assertEquals(
            '""',
            stream_get_contents($this->ob4->escapeStream(), -1, 0)
        );
    }

}
