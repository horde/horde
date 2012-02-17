<?php
/**
 * Tests for the mailbox object.
 *
 * PHP version 5
 *
 * @category Horde
 * @package  Imap_Client
 * @author   Michael Slusarz <slusarz@horde.org>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Imap_Client
 */

/**
 * Tests for the mailbox object.
 *
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category Horde
 * @package  Imap_Client
 * @author   Michael Slusarz <slusarz@horde.org>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Imap_Client
 */
class Horde_Imap_Client_MailboxTest extends PHPUnit_Framework_TestCase
{
    public function testMailboxSerialize()
    {
        $mailbox = new Horde_Imap_Client_Mailbox('Envoyé');

        $this->assertEquals(
            'Envoyé',
            $mailbox->utf8
        );
        $this->assertEquals(
            'Envoy&AOk-',
            $mailbox->utf7imap
        );

        $a = serialize($mailbox);
        $b = unserialize($a);

        $this->assertEquals(
            'Envoyé',
            $b->utf8
        );
        $this->assertEquals(
            'Envoy&AOk-',
            $b->utf7imap
        );
    }

    public function testMailboxGet()
    {
        $a = Horde_Imap_Client_Mailbox::get('B');

        $this->assertEquals(
            'B',
            $a->utf7imap
        );

        $mailbox = new Horde_Imap_Client_Mailbox('A');

        $b = Horde_Imap_Client_Mailbox::get($mailbox);

        $this->assertEquals(
            $mailbox,
            $b
        );
    }

    public function testMailboxAutoDetect()
    {
        $mailbox = Horde_Imap_Client_Mailbox::get('Envoy&AOk-', null);

        $this->assertEquals(
            'Envoyé',
            $mailbox->utf8
        );
        $this->assertEquals(
            'Envoy&AOk-',
            $mailbox->utf7imap
        );
    }

    public function testBug10093()
    {
        $orig = 'Foo&Bar-2011';

        $mailbox = new Horde_Imap_Client_Mailbox($orig);

        $this->assertEquals(
            'Foo&Bar-2011',
            $mailbox->utf8
        );
        $this->assertEquals(
            'Foo&-Bar-2011',
            $mailbox->utf7imap
        );

        /* Auto-detection SHOULD be broken for this example - this is
         * Bug #10093. */
        $mailbox = Horde_Imap_Client_Mailbox::get($orig, null);

        $this->assertNotEquals(
            'Foo&Bar-2011',
            $mailbox->utf8
        );
        $this->assertNotEquals(
            'Foo&-Bar-2011',
            $mailbox->utf7imap
        );
    }

}
