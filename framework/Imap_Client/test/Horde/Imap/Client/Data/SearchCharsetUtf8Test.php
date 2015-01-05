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
 * Tests for the SearchCharset_Utf8 object.
 *
 * @author     Michael Slusarz <slusarz@horde.org>
 * @category   Horde
 * @copyright  2014-2015 Horde LLC
 * @ignore
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    Imap_Client
 * @subpackage UnitTests
 */
class Horde_Imap_Client_Data_SearchCharsetUtf8Test
extends PHPUnit_Framework_TestCase
{
    public function testQuery()
    {
        $s = new Horde_Imap_Client_Data_SearchCharset_Utf8();
        $s->setValid('ISO-8859-1', false);

        $this->assertTrue($s->query('UTF-8', true));
        $this->assertTrue($s->query('US-ASCII', true));
        $this->assertFalse($s->query('iso-8859-1', true));
    }

    public function testRemoval()
    {
        $s = new Horde_Imap_Client_Data_SearchCharset_Utf8();
        $s->setValid('UTF-8');

        $this->assertTrue($s->query('UTF-8', true));

        $s->setValid('utf-8', false);

        $this->assertTrue($s->query('UTF-8', true));
    }

    public function testCharsetsProperty()
    {
        $s = new Horde_Imap_Client_Data_SearchCharset_Utf8();
        $s->setValid('UTF-8');
        $s->setValid('UTF-8');

        $this->assertEquals(
            array('US-ASCII', 'UTF-8'),
            $s->charsets
        );
    }

    public function testObserver()
    {
        $s = new Horde_Imap_Client_Data_SearchCharset_Utf8();

        $mock = $this->getMock('SplObserver');
        $mock->expects($this->never())
            ->method('update')
            ->with($this->equalTo($s));
        $s->attach($mock);

        $s->setValid('utf-8');
        /* This should be ignored. */
        $s->setValid('UTF-8');
    }

    public function testSerialize()
    {
        $s = new Horde_Imap_Client_Data_SearchCharset_Utf8();

        $s_copy = unserialize(serialize($s));

        $s_copy->query('UTF-8', true);
    }

}
