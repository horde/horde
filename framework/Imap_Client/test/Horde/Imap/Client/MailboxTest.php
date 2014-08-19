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
 * Tests for the mailbox object.
 *
 * @author     Michael Slusarz <slusarz@horde.org>
 * @category   Horde
 * @copyright  2011-2014 Horde LLC
 * @ignore
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    Imap_Client
 * @subpackage UnitTests
 */
class Horde_Imap_Client_MailboxTest extends PHPUnit_Framework_TestCase
{
    public function testMailboxSerialize()
    {
        $mailbox = unserialize(
            serialize(new Horde_Imap_Client_Mailbox('Envoyé'))
        );

        $this->assertEquals(
            'Envoyé',
            $mailbox->utf8
        );
        $this->assertEquals(
            'Envoy&AOk-',
            $mailbox->utf7imap
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
    }

    /**
     * @dataProvider listEscapeProvider
     */
    public function testListEscape($orig, $expected)
    {
        $mailbox = new Horde_Imap_Client_Mailbox($orig);

        $this->assertEquals(
            $expected,
            $mailbox->list_escape
        );
    }

    public function listEscapeProvider()
    {
        return array(
            array('***Foo***', '%Foo%'),
            array('IN.***Foo**.Bar.Test**', 'IN.%Foo%.Bar.Test%')
        );
    }

    public function testInboxCaseInsensitive()
    {
        $mailbox = new Horde_Imap_Client_Mailbox('inbox');

        $this->assertEquals(
            'INBOX',
            $mailbox
        );
    }

}
