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
extends Horde_Imap_Client_Data_Format_TestBase
{
    protected function getTestObs()
    {
        return array(
            new Horde_Imap_Client_Data_Format_Nil(),
            /* Argument is ignored. */
            new Horde_Imap_Client_Data_Format_Nil('Foo')
        );
    }

    /**
     * @dataProvider obsProvider
     */
    public function testStringRepresentation($ob)
    {
        $this->assertEquals(
            '',
            strval($ob)
        );
    }

    /**
     * @dataProvider obsProvider
     */
    public function testEscape($ob)
    {
        $this->assertEquals(
            'NIL',
            $ob->escape()
        );
    }

}
