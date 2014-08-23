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
 * Tests for the deprecated Horde_Imap_Client_Url POP3 URL parsing.
 *
 * @author     Michael Slusarz <slusarz@horde.org>
 * @category   Horde
 * @copyright  2011-2014 Horde LLC
 * @ignore
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    Imap_Client
 * @subpackage UnitTests
 */
class Horde_Imap_Client_Url_Pop3DeprecatedTest
extends Horde_Imap_Client_Url_TestBase
{
    protected $classname = 'Horde_Imap_Client_Url';
    protected $protocol = 'pop';

    public function testUrlProvider()
    {
        return array(
            array(
                'pop://test.example.com/',
                null,
                array(
                    'hostspec' => 'test.example.com',
                    'port' => 110,
                    'relative' => false,
                    'protocol' => 'pop'
                )
            ),
            array(
                'pop://test.example.com:110/',
                'pop://test.example.com/',
                array(
                    'hostspec' => 'test.example.com',
                    'port' => 110,
                    'relative' => false,
                    'protocol' => 'pop'
                )
            ),
            array(
                'pop://testuser@test.example.com/',
                null,
                array(
                    'hostspec' => 'test.example.com',
                    'port' => 110,
                    'relative' => false,
                    'username' => 'testuser',
                    'protocol' => 'pop'
                )
            ),
            // This is the default port for IMAP, not POP3
            array(
                'pop://testuser@test.example.com:143/',
                null,
                array(
                    'hostspec' => 'test.example.com',
                    'port' => 143,
                    'relative' => false,
                    'username' => 'testuser',
                    'protocol' => 'pop'
                )
            ),
            array(
                'pop://testuser;AUTH=*@test.example.com:110/',
                'pop://testuser@test.example.com/',
                array(
                    'hostspec' => 'test.example.com',
                    'port' => 110,
                    'username' => 'testuser',
                    'relative' => false,
                    'protocol' => 'pop'
                )
            ),
            array(
                'pop://testuser;AUTH=PLAIN@test.example.com/',
                null,
                array(
                    'hostspec' => 'test.example.com',
                    'port' => 110,
                    'username' => 'testuser',
                    'relative' => false,
                    'auth' => 'PLAIN',
                    'protocol' => 'pop'
                )
            ),
            // Ignore everything after the port.
            array(
                'pop://test.example.com:110/INBOX.Quarant%26AOQ-ne;UIDVALIDITY=1240054819/;UID=39193/;SECTION=HEADER',
                'pop://test.example.com/',
                array(
                    'hostspec' => 'test.example.com',
                    'port' => 110,
                    'relative' => false,
                    'section' => '',
                    'uid' => '',
                    'uidvalidity' => '',
                    'mailbox' => ''
                )
            )
        );
    }

    public function serializeProvider()
    {
        return array(
            array('pop://test.example.com/')
        );
    }

}
