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
 * Tests for relative IMAP URL parsing.
 *
 * @author     Michael Slusarz <slusarz@horde.org>
 * @category   Horde
 * @copyright  2014-2015 Horde LLC
 * @ignore
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    Imap_Client
 * @subpackage UnitTests
 */
class Horde_Imap_Client_Url_ImapRelativeTest
extends Horde_Imap_Client_Url_TestBase
{
    protected $classname = 'Horde_Imap_Client_Url_Imap_Relative';
    protected $protocol = 'imap';

    public function testUrlProvider()
    {
        return array(
            array(
                'imap://test.example.com/',
                '',
                array(
                    'host' => 'test.example.com',
                    'port' => 143,
                    'mailbox' => null
                )
            ),
            array(
                'imap://test.example.com:143/',
                '',
                array(
                    'host' => 'test.example.com',
                    'port' => 143,
                    'mailbox' => null
                )
            ),
            array(
                'imap://testuser@test.example.com/',
                '',
                array(
                    'host' => 'test.example.com',
                    'port' => 143,
                    'username' => 'testuser',
                    'mailbox' => null
                )
            ),
            array(
                'imap://testuser@test.example.com:14300/',
                '',
                array(
                    'host' => 'test.example.com',
                    'port' => 14300,
                    'username' => 'testuser',
                    'mailbox' => null,
                )
            ),
            array(
                'imap://testuser;AUTH=*@test.example.com:143/',
                '',
                array(
                    'auth' => null,
                    'host' => 'test.example.com',
                    'port' => 143,
                    'username' => 'testuser',
                    'mailbox' => null
                )
            ),
            array(
                'imap://testuser;AUTH=PLAIN@test.example.com:14300/',
                '',
                array(
                    'host' => 'test.example.com',
                    'port' => 14300,
                    'username' => 'testuser',
                    'auth' => 'PLAIN',
                    'mailbox' => null
                )
            ),
            array(
                'imap://test.example.com:14300/Quarant%26AOQ-ne;UIDVALIDITY=1240054819/;UID=39193/;SECTION=HEADER/;PARTIAL=0.1024',
                '/Quarant%26AOQ-ne;UIDVALIDITY=1240054819/;UID=39193/;SECTION=HEADER/;PARTIAL=0.1024',
                array(
                    'host' => 'test.example.com',
                    'partial' => '0.1024',
                    'port' => 14300,
                    'section' => 'HEADER',
                    'uid' => 39193,
                    'uidvalidity' => 1240054819,
                    'mailbox' => new Horde_Imap_Client_Mailbox('Quarant&AOQ-ne', true)
                )
            ),
            array(
                'imap://test.example.com:14300/INBOX;UIDVALIDITY=123/;UID=456?FLAGGED%20SINCE%201-Feb-1994%20NOT%20FROM%20%22Smith%22',
                '/INBOX;UIDVALIDITY=123?FLAGGED%20SINCE%201-Feb-1994%20NOT%20FROM%20%22Smith%22',
                array(
                    'host' => 'test.example.com',
                    'port' => 14300,
                    'uidvalidity' => 123,
                    'mailbox' => new Horde_Imap_Client_Mailbox('INBOX', true),
                    // Ignore extra data after UIDVALIDITY
                    'uid' => '',
                    // Search example from RFC 3501 [6.4.4]
                    'search' => 'FLAGGED SINCE 1-Feb-1994 NOT FROM "Smith"'
                )
            ),
            array(
                ';UID=1240054819/;SECTION=HEADER',
                null,
                array()
            )
        );
    }

    public function serializeProvider()
    {
        return array(
            array(';UID=1240054819')
        );
    }

}
