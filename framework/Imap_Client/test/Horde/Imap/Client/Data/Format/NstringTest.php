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
 * Tests for the Nstring data format object.
 *
 * @author     Michael Slusarz <slusarz@horde.org>
 * @category   Horde
 * @copyright  2011-2014 Horde LLC
 * @ignore
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    Imap_Client
 * @subpackage UnitTests
 */
class Horde_Imap_Client_Data_Format_NstringTest
extends Horde_Imap_Client_Data_Format_TestBase
{
    protected function getTestObs()
    {
        return array(
            new Horde_Imap_Client_Data_Format_Nstring('Foo'),
            new Horde_Imap_Client_Data_Format_Nstring('Foo('),
            /* This is an invalid atom, but valid nstring. */
            new Horde_Imap_Client_Data_Format_Nstring('Foo]'),
            new Horde_Imap_Client_Data_Format_Nstring()
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
            ''
        ));
    }

    /**
     * @dataProvider escapeProvider
     */
    public function testEscape($ob, $expected)
    {
        $this->assertEquals(
            $expected,
            $ob->escape()
        );
    }

    public function escapeProvider()
    {
        return $this->createProviderArray(array(
            '"Foo"',
            '"Foo("',
            '"Foo]"',
            'NIL'
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
     * @dataProvider obsProvider
     */
    public function testBinary($ob)
    {
        $this->assertFalse($ob->binary());
    }

    /**
     * @dataProvider obsProvider
     */
    public function testLiteral($ob)
    {
        $this->assertFalse($ob->literal());
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
            false
        ));
    }

    /**
     * @dataProvider escapeProvider
     */
    public function testEscapeStream($ob, $expected)
    {
        $this->assertEquals(
            $expected,
            stream_get_contents($ob->escapeStream(), -1, 0)
        );
    }

}
