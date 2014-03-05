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
 * Tests for the Number data format object.
 *
 * @author     Michael Slusarz <slusarz@horde.org>
 * @category   Horde
 * @copyright  2011-2014 Horde LLC
 * @ignore
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    Imap_Client
 * @subpackage UnitTests
 */
class Horde_Imap_Client_Data_Format_NumberTest
extends PHPUnit_Framework_TestCase
{
    private $ob;
    private $ob2;
    private $ob3;

    public function setUp()
    {
        $this->ob = new Horde_Imap_Client_Data_Format_Number(1);
        $this->ob2 = new Horde_Imap_Client_Data_Format_Number('1');
        /* Invalid number. */
        $this->ob3 = new Horde_Imap_Client_Data_Format_Number('Foo');
    }

    public function testStringRepresentation()
    {
        $this->assertEquals(
            '1',
            strval($this->ob)
        );

        $this->assertEquals(
            '1',
            strval($this->ob2)
        );

        $this->assertEquals(
            '0',
            strval($this->ob3)
        );
    }

    public function testEscape()
    {
        $this->assertEquals(
            '1',
            $this->ob->escape()
        );

        $this->assertEquals(
            '1',
            $this->ob2->escape()
        );

        $this->assertEquals(
            '0',
            $this->ob3->escape()
        );
    }

    public function testVerify()
    {
        // Don't throw Exception
        $this->ob->verify();
        $this->ob2->verify();

        // Expected exception
        try {
            $this->ob3->verify();
            $this->fail();
        } catch (Horde_Imap_Client_Data_Format_Exception $e) {}
    }

}
