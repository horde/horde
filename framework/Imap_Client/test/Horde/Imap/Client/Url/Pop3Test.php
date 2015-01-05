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
 * Tests for Horde_Imap_Client_Url_Pop3 POP3 URL parsing.
 *
 * @author     Michael Slusarz <slusarz@horde.org>
 * @category   Horde
 * @copyright  2014-2015 Horde LLC
 * @ignore
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    Imap_Client
 * @subpackage UnitTests
 */
class Horde_Imap_Client_Url_Pop3Test
extends Horde_Imap_Client_Url_TestBase
{
    protected $classname = 'Horde_Imap_Client_Url_Pop3';
    protected $host = 'host';
    protected $protocol = 'pop';

    public function testUrlProvider()
    {
        return array(
            array(
                'pop://test.example.com/',
                null,
                array(
                    'host' => 'test.example.com',
                    'port' => 110
                )
            ),
            array(
                'pop://test.example.com:110/',
                'pop://test.example.com/',
                array(
                    'host' => 'test.example.com',
                    'port' => 110
                )
            ),
            array(
                'pop://testuser@test.example.com/',
                null,
                array(
                    'host' => 'test.example.com',
                    'port' => 110,
                    'username' => 'testuser'
                )
            ),
            // This is the default port for IMAP, not POP3
            array(
                'pop://testuser@test.example.com:143/',
                null,
                array(
                    'host' => 'test.example.com',
                    'port' => 143,
                    'username' => 'testuser'
                )
            ),
            array(
                'pop://testuser;AUTH=*@test.example.com:110/',
                'pop://testuser@test.example.com/',
                array(
                    'auth' => null,
                    'host' => 'test.example.com',
                    'port' => 110,
                    'username' => 'testuser'
                )
            ),
            array(
                'pop://testuser;AUTH=PLAIN@test.example.com/',
                null,
                array(
                    'host' => 'test.example.com',
                    'port' => 110,
                    'username' => 'testuser',
                    'auth' => 'PLAIN'
                )
            ),
            // Ignore everything after the port.
            array(
                'pop://test.example.com:110/INBOX.Quarant%26AOQ-ne;UIDVALIDITY=1240054819/;UID=39193/;SECTION=HEADER',
                'pop://test.example.com/',
                array(
                    'host' => 'test.example.com',
                    'port' => 110,
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
