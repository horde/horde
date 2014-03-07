<?php
/**
 * Copyright 2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category   Horde
 * @copyright  2014 Horde LLC
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    Imap_Client
 * @subpackage UnitTests
 */

/**
 * Tests for the Horde_Imap_Client_Base_Mailbox object.
 *
 * @author     Michael Slusarz <slusarz@horde.org>
 * @category   Horde
 * @copyright  2014 Horde LLC
 * @ignore
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    Imap_Client
 * @subpackage UnitTests
 */
class Horde_Imap_Client_Base_MailboxTest extends PHPUnit_Framework_TestCase
{
    private $ob;

    public function setUp()
    {
        $this->ob = new Horde_Imap_Client_Base_Mailbox();
    }

    public function testInitialStatus()
    {
        $this->assertInstanceOf(
            'Horde_Imap_Client_Ids_Map',
            $this->ob->map
        );
    }

    public function testFirstUnseen()
    {
        $this->assertFalse(
            $this->ob->getStatus(Horde_Imap_Client::STATUS_FIRSTUNSEEN)
        );

        $this->ob->setStatus(Horde_Imap_Client::STATUS_MESSAGES, 1);

        $this->assertNull(
            $this->ob->getStatus(Horde_Imap_Client::STATUS_FIRSTUNSEEN)
        );

        $this->ob->setStatus(Horde_Imap_Client::STATUS_FIRSTUNSEEN, 1);

        $this->assertEquals(
            1,
            $this->ob->getStatus(Horde_Imap_Client::STATUS_FIRSTUNSEEN)
        );
    }

    public function testDefaultPermFlags()
    {
        $this->assertTrue(
            in_array('\\*', $this->ob->getStatus(Horde_Imap_Client::STATUS_PERMFLAGS))
        );
    }

    public function testUnseen()
    {
        $this->assertEquals(
            0,
            $this->ob->getStatus(Horde_Imap_Client::STATUS_UNSEEN)
        );

        $this->ob->setStatus(Horde_Imap_Client::STATUS_MESSAGES, 1);

        $this->assertNull(
            $this->ob->getStatus(Horde_Imap_Client::STATUS_FIRSTUNSEEN)
        );

        $this->ob->setStatus(Horde_Imap_Client::STATUS_UNSEEN, 1);

        $this->assertEquals(
            1,
            $this->ob->getStatus(Horde_Imap_Client::STATUS_UNSEEN)
        );
    }

    public function testStatusRecent()
    {
        $this->ob->setStatus(Horde_Imap_Client::STATUS_RECENT, 1);
        $this->ob->setStatus(Horde_Imap_Client::STATUS_RECENT, 1);
        $this->ob->setStatus(Horde_Imap_Client::STATUS_RECENT, 1);

        $this->assertEquals(
            3,
            $this->ob->getStatus(Horde_Imap_Client::STATUS_RECENT_TOTAL)
        );
    }

    public function testSyncModseqIsOnlySetOnce()
    {
        $this->ob->setStatus(Horde_Imap_Client::STATUS_SYNCMODSEQ, 1);
        $this->ob->setStatus(Horde_Imap_Client::STATUS_SYNCMODSEQ, 2);

        $this->assertEquals(
            1,
            $this->ob->getStatus(Horde_Imap_Client::STATUS_SYNCMODSEQ)
        );
    }

    public function testStatusEntriesAreAdditive()
    {
        $testing = array(
            Horde_Imap_Client::STATUS_SYNCFLAGUIDS,
            Horde_Imap_Client::STATUS_SYNCVANISHED
        );

        foreach ($testing as $val) {
            $this->ob->setStatus($val, array(1));
            $this->ob->setStatus($val, array(2));

            $this->assertEquals(
                array(1, 2),
                $this->ob->getStatus($val)
            );
        }
    }

    public function testReset()
    {
        $this->ob->map->update((array(1 => 2)));
        $this->ob->setStatus(Horde_Imap_Client::STATUS_SYNCMODSEQ, 1);
        $this->ob->setStatus(Horde_Imap_Client::STATUS_RECENT_TOTAL, 1);

        $this->ob->reset();

        $this->assertEquals(
            0,
            count($this->ob->map)
        );
        $this->assertEquals(
            1,
            $this->ob->getStatus(Horde_Imap_Client::STATUS_SYNCMODSEQ)
        );
        $this->assertEquals(
            0,
            $this->ob->getStatus(Horde_Imap_Client::STATUS_RECENT_TOTAL)
        );
    }

}
