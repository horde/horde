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
 * Tests for the DateTime data format object.
 *
 * @author     Michael Slusarz <slusarz@horde.org>
 * @category   Horde
 * @copyright  2011-2014 Horde LLC
 * @ignore
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    Imap_Client
 * @subpackage UnitTests
 */
class Horde_Imap_Client_Data_Format_DateTimeTest
extends PHPUnit_Framework_TestCase
{
    private $ob;
    private $ob2;
    private $ob3;

    public function setUp()
    {
        $this->ob = new Horde_Imap_Client_DateTime('January 1, 2010');
        $this->ob2 = new Horde_Imap_Client_Data_Format_DateTime($this->ob);
        $this->ob3 = new Horde_Imap_Client_Data_Format_DateTime('@1262304000');
    }

    public function testConstructor()
    {
        $this->assertSame(
            $this->ob,
            $this->ob2->getData()
        );
    }

    public function testStringRepresentation()
    {
        $this->assertEquals(
            '1-Jan-2010 00:00:00 +0000',
            strval($this->ob2)
        );

        $this->assertEquals(
            '1-Jan-2010 00:00:00 +0000',
            strval($this->ob3)
        );
    }


    public function testEscape()
    {
        $this->assertEquals(
            '"1-Jan-2010 00:00:00 +0000"',
            $this->ob2->escape()
        );

        $this->assertEquals(
            '"1-Jan-2010 00:00:00 +0000"',
            $this->ob3->escape()
        );
    }

}
