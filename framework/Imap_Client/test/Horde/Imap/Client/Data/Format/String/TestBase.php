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
 * Base class for tests of the Horde_Imap_Client_Data_Format_String object.
 *
 * @author     Michael Slusarz <slusarz@horde.org>
 * @category   Horde
 * @copyright  2014-2015 Horde LLC
 * @ignore
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    Imap_Client
 * @subpackage UnitTests
 */
abstract class Horde_Imap_Client_Data_Format_String_TestBase
extends Horde_Imap_Client_Data_Format_TestBase
{
    protected $cname;

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

    abstract public function stringRepresentationProvider();

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

    abstract public function escapeProvider();

    /**
     * @dataProvider verifyProvider
     */
    public function testVerify($ob, $result)
    {
        try {
            $ob->verify();
            if (!$result) {
                $this->fail();
            }
        } catch (Horde_Imap_Client_Data_Format_Exception $e) {
            if ($result) {
                $this->fail();
            }
        }
    }

    abstract public function verifyProvider();

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

    abstract public function binaryProvider();

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

    abstract public function literalProvider();

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

    abstract public function quotedProvider();

    /**
     * @dataProvider escapeStreamProvider
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

    abstract public function escapeStreamProvider();

    /**
     * @dataProvider nonasciiInputProvider
     */
    public function testNonasciiInput($result)
    {
        try {
            new $this->cname('Envoyé');
            if (!$result) {
                $this->fail();
            }
        } catch (Horde_Imap_Client_Data_Format_Exception $e) {
            if ($result) {
                $this->fail();
            }
        }
    }

    abstract public function nonasciiInputProvider();

}
