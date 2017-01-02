<?php
/**
 * Copyright 2011-2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category   Horde
 * @copyright  2011-2016 Horde LLC
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    Imap_Client
 * @subpackage UnitTests
 */

/**
 * Tests for the Imap Client DateTime object.
 *
 * @author     Michael Slusarz <slusarz@horde.org>
 * @category   Horde
 * @copyright  2011-2016 Horde LLC
 * @ignore
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    Imap_Client
 * @subpackage UnitTests
 */
class Horde_Imap_Client_DateTimeTest extends PHPUnit_Framework_TestCase
{
    public function provider()
    {
        return array(
            // Bug #5715
            array('12 Sep 2007 15:49:12 UT', 1189612152),
            // Bug #9847
            array('Fri, 06 Oct 2006 12:15:13 +0100 (GMT+01:00)', 1160133313),
            // Bug #13114; This should resolve to 4/13 8:04:48pm UTC of the
            // current year.
            array('Apr 13 20:4:48', gmmktime(20, 4, 48, 4, 13)),
            // Bad date input
            array('This is a bad date', 0),
            // Bug #14381
            array('Thu, 1 Aug 2013 20:22:47 0000', 1375388567)
        );
    }

    /**
     * @dataProvider provider
     */
    public function testDateTimeParsing($date, $expected)
    {
        $ob = new Horde_Imap_Client_DateTime($date);

        $this->assertEquals(
            $expected,
            intval(strval($ob))
        );
    }

    public function testClone()
    {
        $ob = new Horde_Imap_Client_DateTime('12 Sep 2007 15:49:12 UTC');

        $ob2 = clone $ob;

        $ob2->modify('+1 minute');

        $this->assertEquals(
            1189612152,
            intval(strval($ob))
        );

        $this->assertEquals(
            1189612152 + 60,
            intval(strval($ob2))
        );
    }

    public function testSerialize()
    {
        $ob = new Horde_Imap_Client_DateTime('12 Sep 2007 15:49:12 UTC');

        $ob2 = unserialize(serialize($ob));

        $this->assertEquals(
            1189612152,
            intval(strval($ob2))
        );
    }

}
