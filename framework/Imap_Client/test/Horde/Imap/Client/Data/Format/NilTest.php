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
 * Tests for the Nil data format object.
 *
 * @author     Michael Slusarz <slusarz@horde.org>
 * @category   Horde
 * @copyright  2011-2014 Horde LLC
 * @ignore
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    Imap_Client
 * @subpackage UnitTests
 */
class Horde_Imap_Client_Data_Format_NilTest
extends PHPUnit_Framework_TestCase
{
    private $ob;
    private $ob2;

    public function setUp()
    {
        $this->ob = new Horde_Imap_Client_Data_Format_Nil();
        /* Argument is ignored. */
        $this->ob2 = new Horde_Imap_Client_Data_Format_Nil('Foo');
    }

    public function testStringRepresentation()
    {
        $this->assertEquals(
            '',
            strval($this->ob)
        );

        $this->assertEquals(
            '',
            strval($this->ob2)
        );
    }

    public function testEscape()
    {
        $this->assertEquals(
            'NIL',
            $this->ob->escape()
        );

        $this->assertEquals(
            'NIL',
            $this->ob2->escape()
        );
    }

}
