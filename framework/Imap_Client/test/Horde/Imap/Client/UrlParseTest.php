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
 * Tests for IMAP URL parsing.
 *
 * @author     Michael Slusarz <slusarz@horde.org>
 * @category   Horde
 * @copyright  2011-2014 Horde LLC
 * @ignore
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    Imap_Client
 * @subpackage UnitTests
 */
class Horde_Imap_Client_UrlParseTest extends Horde_Test_Case
{
    public function testBadUrl()
    {
        $url = new Horde_Imap_Client_Url('NOT A VALID URL');

        $this->assertNotNull($url->mailbox);
        $this->assertEquals(
            'NOT A VALID URL',
            $url->mailbox
        );
    }

    /**
     * RFC 2384 URL parsing
     *
     * @dataProvider testUrlsProvider
     */
    public function testPopUrlParsing($url, $expected)
    {
        $url = new Horde_Imap_Client_Url('pop://' . $url);
        $expected['protocol'] = 'pop';

        foreach ($expected as $key => $val) {
            switch ($key) {
            case 'mailbox':
            case 'section':
            case 'uid':
            case 'uidvalidity':
                // Ignored.
                break;

            default:
                $this->assertEquals(
                    $val,
                    $url->$key
                );
            }
        }
    }

    /**
     * RFC 5092 URL parsing
     *
     * @dataProvider testUrlsProvider
     */
    public function testImapUrlParsing($url, $expected)
    {
        $url = new Horde_Imap_Client_Url('imap://' . $url);
        $expected['protocol'] = 'imap';

        foreach ($expected as $key => $val) {
            $this->assertEquals(
                $val,
                $url->$key
            );
        }
    }

    public function testSerialize()
    {
        $url = unserialize(serialize(
            new Horde_Imap_Client_Url('imap://test.example.com/')
        ));

        $this->assertEquals(
            'imap',
            $url->protocol
        );
    }

    public function testUrlsProvider()
    {
        return array(
            array(
                'test.example.com/',
                array(
                    'hostspec' => 'test.example.com',
                    'port' => 143,
                    'relative' => false,
                    'mailbox' => ''
                )
            ),
            array(
                'test.example.com:143/',
                array(
                    'hostspec' => 'test.example.com',
                    'port' => 143,
                    'relative' => false,
                    'mailbox' => ''
                )
            ),
            array(
                'testuser@test.example.com/',
                array(
                    'hostspec' => 'test.example.com',
                    'port' => 143,
                    'relative' => false,
                    'username' => 'testuser',
                    'mailbox' => ''
                )
            ),
            array(
                'testuser@test.example.com:143/',
                array(
                    'hostspec' => 'test.example.com',
                    'port' => 143,
                    'relative' => false,
                    'username' => 'testuser',
                    'mailbox' => ''
                )
            ),
            array(
                ';AUTH=PLAIN@test.example.com/',
                array(
                    'hostspec' => 'test.example.com',
                    'port' => 143,
                    'relative' => false,
                    'auth' => 'PLAIN',
                    'mailbox' => ''
                )
            ),
            array(
                ';AUTH=PLAIN@test.example.com:143/',
                array(
                    'hostspec' => 'test.example.com',
                    'port' => 143,
                    'relative' => false,
                    'auth' => 'PLAIN',
                    'mailbox' => ''
                )
            ),
            array(
                ';AUTH=*@test.example.com:143/',
                array(
                    'hostspec' => 'test.example.com',
                    'port' => 143,
                    'relative' => false,
                    'mailbox' => ''
                )
            ),
            array(
                'testuser;AUTH=*@test.example.com:143/',
                array(
                    'hostspec' => 'test.example.com',
                    'port' => 143,
                    'username' => 'testuser',
                    'relative' => false,
                    'mailbox' => ''
                )
            ),
            array(
                'testuser;AUTH=PLAIN@test.example.com:143/',
                array(
                    'hostspec' => 'test.example.com',
                    'port' => 143,
                    'relative' => false,
                    'username' => 'testuser',
                    'auth' => 'PLAIN',
                    'mailbox' => ''
                )
            ),
            array(
                'test.example.com/INBOX.Quarant%26AOQ-ne;UIDVALIDITY=1240054819/;UID=39193/;SECTION=HEADER',
                array(
                    'hostspec' => 'test.example.com',
                    'port' => 143,
                    'relative' => false,
                    'section' => 'HEADER',
                    'uid' => 39193,
                    'uidvalidity' => 1240054819,
                    'mailbox' => 'INBOX.Quarant&AOQ-ne'
                )
            )
        );
    }
}
