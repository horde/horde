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
extends Horde_Imap_Client_Data_Format_TestBase
{
    protected function getTestObs()
    {
        return array(
            new Horde_Imap_Client_Data_Format_String('Foo'),
            new Horde_Imap_Client_Data_Format_String('Foo('),
            /* This is an invalid atom, but valid string. */
            new Horde_Imap_Client_Data_Format_String('Foo]'),
            /* This string requires a literal. */
            new Horde_Imap_Client_Data_Format_String("Foo\n]"),
            /* This string requires a binary literal. */
            new Horde_Imap_Client_Data_Format_String("12\x00\n3")
        );
    }

    /**
     * @dataProvider stringRepresentationProvider
     */
    public function testStringRepresentation($ob, $expected)
    {
        $this->assertEquals(
            $expected,
            strval($ob)
        );
    }

    public function stringRepresentationProvider()
    {
        return $this->createProviderArray(array(
            'Foo',
            'Foo(',
            'Foo]',
            "Foo\n]",
            "12\x00\n3"
        ));
    }

    /**
     * @dataProvider escapeProvider
     */
    public function testEscape($ob, $expected)
    {
        try {
            $this->assertEquals(
                $expected,
                $ob->escape()
            );
        } catch (Horde_Imap_Client_Data_Format_Exception $e) {
            if ($expected !== false) {
                $this->fail();
            }
        }
    }

    public function escapeProvider()
    {
        return $this->createProviderArray(array(
            '"Foo"',
            '"Foo("',
            '"Foo]"',
            false,
            false
        ));
    }

    /**
     * @dataProvider obsProvider
     */
    public function testVerify($ob)
    {
        // Don't throw Exception
        $ob->verify();
    }

    /**
     * @dataProvider binaryProvider
     */
    public function testBinary($ob, $expected)
    {
        if ($expected) {
            $this->assertTrue($ob->binary());
        } else {
            $this->assertFalse($ob->binary());
        }
    }

    public function binaryProvider()
    {
        return $this->createProviderArray(array(
            false,
            false,
            false,
            false,
            true
        ));
    }

    /**
     * @dataProvider literalProvider
     */
    public function testLiteral($ob, $expected)
    {
        if ($expected) {
            $this->assertTrue($ob->literal());
        } else {
            $this->assertFalse($ob->literal());
        }
    }

    public function literalProvider()
    {
        return $this->createProviderArray(array(
            false,
            false,
            false,
            true,
            true
        ));
    }

    /**
     * @dataProvider quotedProvider
     */
    public function testQuoted($ob, $expected)
    {
        if ($expected) {
            $this->assertTrue($ob->quoted());
        } else {
            $this->assertFalse($ob->quoted());
        }
    }

    public function quotedProvider()
    {
        return $this->createProviderArray(array(
            true,
            true,
            true,
            false,
            false
        ));
    }

    /**
     * @dataProvider escapeProvider
     */
    public function testEscapeStream($ob, $expected)
    {
        try {
            $this->assertEquals(
                $expected,
                stream_get_contents($ob->escapeStream(), -1, 0)
            );
        } catch (Horde_Imap_Client_Data_Format_Exception $e) {
            if ($expected !== false) {
                $this->fail();
            }
        }
    }

}
