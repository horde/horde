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
 * Tests for the Imap Client DateTime object.
 *
 * @author     Michael Slusarz <slusarz@horde.org>
 * @category   Horde
 * @copyright  2011-2014 Horde LLC
 * @ignore
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    Imap_Client
 * @subpackage UnitTests
 */
class Horde_Imap_Client_DateTimeTest extends PHPUnit_Framework_TestCase
{
    public function testBug5717()
    {
        $date = '12 Sep 2007 15:49:12 UT';
        $ob = new Horde_Imap_Client_DateTime($date);

        $this->assertEquals(
            1189612152,
            intval(strval($ob))
        );
    }

    public function testBug9847()
    {
        $date = 'Fri, 06 Oct 2006 12:15:13 +0100 (GMT+01:00)';
        $ob = new Horde_Imap_Client_DateTime($date);

        $this->assertEquals(
            1160133313,
            intval(strval($ob))
        );
    }

    public function testBadDate()
    {
        $date = 'This is a bad date';
        $ob = new Horde_Imap_Client_DateTime($date);

        $this->assertEquals(
            0,
            intval(strval($ob))
        );

        $this->assertTrue($ob->error());
    }

}
