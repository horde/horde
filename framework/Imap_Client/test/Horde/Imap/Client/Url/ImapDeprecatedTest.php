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
 * Tests for the deprecated Horde_Imap_Client_Url IMAP URL parsing.
 *
 * @author     Michael Slusarz <slusarz@horde.org>
 * @category   Horde
 * @copyright  2011-2016 Horde LLC
 * @ignore
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    Imap_Client
 * @subpackage UnitTests
 */
class Horde_Imap_Client_Url_ImapDeprecatedTest
extends Horde_Imap_Client_Url_TestBase
{
    protected $classname = 'Horde_Imap_Client_Url';
    protected $protocol = 'imap';

    public function testUrlProvider()
    {
        return array(
            array(
                'imap://test.example.com/',
                null,
                array(
                    'hostspec' => 'test.example.com',
                    'port' => 143,
                    'relative' => false,
                    'mailbox' => '',
                    'protocol' => 'imap'
                )
            ),
            array(
                'imap://test.example.com:143/',
                'imap://test.example.com/',
                array(
                    'hostspec' => 'test.example.com',
                    'port' => 143,
                    'relative' => false,
                    'mailbox' => '',
                    'protocol' => 'imap'
                )
            ),
            array(
                'imap://testuser@test.example.com/',
                null,
                array(
                    'hostspec' => 'test.example.com',
                    'port' => 143,
                    'relative' => false,
                    'username' => 'testuser',
                    'mailbox' => '',
                    'protocol' => 'imap'
                )
            ),
            array(
                'imap://testuser@test.example.com:14300/',
                null,
                array(
                    'hostspec' => 'test.example.com',
                    'port' => 14300,
                    'relative' => false,
                    'username' => 'testuser',
                    'mailbox' => '',
                    'protocol' => 'imap'
                )
            ),
            array(
                'imap://testuser;AUTH=*@test.example.com:143/',
                'imap://testuser@test.example.com/',
                array(
                    'hostspec' => 'test.example.com',
                    'port' => 143,
                    'username' => 'testuser',
                    'relative' => false,
                    'mailbox' => '',
                    'protocol' => 'imap'
                )
            ),
            array(
                'imap://testuser;AUTH=PLAIN@test.example.com:14300/',
                null,
                array(
                    'hostspec' => 'test.example.com',
                    'port' => 14300,
                    'username' => 'testuser',
                    'relative' => false,
                    'auth' => 'PLAIN',
                    'mailbox' => '',
                    'protocol' => 'imap'
                )
            ),
            array(
                'imap://test.example.com:14300/INBOX.Quarant%26AOQ-ne;UIDVALIDITY=1240054819/;UID=39193/;SECTION=HEADER/;PARTIAL=0.1024',
                null,
                array(
                    'hostspec' => 'test.example.com',
                    'partial' => '0.1024',
                    'port' => 14300,
                    'relative' => false,
                    'section' => 'HEADER',
                    'uid' => 39193,
                    'uidvalidity' => 1240054819,
                    'mailbox' => 'INBOX.Quarant&AOQ-ne',
                    'protocol' => 'imap'
                )
            ),
            array(
                'imap://test.example.com:14300/INBOX;UIDVALIDITY=123/;UID=456?FLAGGED%20SINCE%201-Feb-1994%20NOT%20FROM%20%22Smith%22',
                'imap://test.example.com:14300/INBOX;UIDVALIDITY=123?FLAGGED%20SINCE%201-Feb-1994%20NOT%20FROM%20%22Smith%22',
                array(
                    'hostspec' => 'test.example.com',
                    'port' => 14300,
                    'relative' => false,
                    'uidvalidity' => 123,
                    'mailbox' => 'INBOX',
                    // Ignore extra data after UIDVALIDITY
                    'uid' => '',
                    // Search example from RFC 3501 [6.4.4]
                    'search' => 'FLAGGED SINCE 1-Feb-1994 NOT FROM "Smith"',
                    'protocol' => 'imap'
                )
            )
        );
    }

    public function serializeProvider()
    {
        return array(
            array('imap://test.example.com/')
        );
    }

}
