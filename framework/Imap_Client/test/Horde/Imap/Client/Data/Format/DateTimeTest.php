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
 * Tests for the DateTime data format object.
 *
 * @author     Michael Slusarz <slusarz@horde.org>
 * @category   Horde
 * @copyright  2011-2016 Horde LLC
 * @ignore
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    Imap_Client
 * @subpackage UnitTests
 */
class Horde_Imap_Client_Data_Format_DateTimeTest
extends Horde_Imap_Client_Data_Format_TestBase
{
    protected function getTestObs()
    {
        return array(
            new Horde_Imap_Client_Data_Format_DateTime('January 1, 2010'),
            new Horde_Imap_Client_Data_Format_DateTime('@1262304000')
        );
    }

    /**
     * @dataProvider obsProvider
     */
    public function testConstructor($ob)
    {
        $this->assertEquals(
            new Horde_Imap_Client_DateTime('January 1, 2010'),
            $ob->getData()
        );
    }

    /**
     * @dataProvider obsProvider
     */
    public function testStringRepresentation($ob)
    {
        $this->assertEquals(
            '1-Jan-2010 00:00:00 +0000',
            strval($ob)
        );
    }

    /**
     * @dataProvider obsProvider
     */
    public function testEscape($ob)
    {
        $this->assertEquals(
            '"1-Jan-2010 00:00:00 +0000"',
            $ob->escape()
        );
    }

}
