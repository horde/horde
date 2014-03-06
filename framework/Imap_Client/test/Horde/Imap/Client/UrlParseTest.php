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
    private $_testurls = array(
        'test.example.com/' => array(
            'hostspec' => 'test.example.com',
            'port' => 143,
            'relative' => false,
            'mailbox' => ''
        ),
        'test.example.com:143/' => array(
            'hostspec' => 'test.example.com',
            'port' => 143,
            'relative' => false,
            'mailbox' => ''
        ),
        'testuser@test.example.com/' => array(
            'hostspec' => 'test.example.com',
            'port' => 143,
            'relative' => false,
            'username' => 'testuser',
            'mailbox' => ''
        ),
        'testuser@test.example.com:143/' => array(
            'hostspec' => 'test.example.com',
            'port' => 143,
            'relative' => false,
            'username' => 'testuser',
            'mailbox' => ''
        ),
        ';AUTH=PLAIN@test.example.com/' => array(
            'hostspec' => 'test.example.com',
            'port' => 143,
            'relative' => false,
            'auth' => 'PLAIN',
            'mailbox' => ''
        ),
        ';AUTH=PLAIN@test.example.com:143/' => array(
            'hostspec' => 'test.example.com',
            'port' => 143,
            'relative' => false,
            'auth' => 'PLAIN',
            'mailbox' => ''
        ),
        ';AUTH=*@test.example.com:143/' => array(
            'hostspec' => 'test.example.com',
            'port' => 143,
            'relative' => false,
            'mailbox' => ''
        ),
        'testuser;AUTH=*@test.example.com:143/' => array(
            'hostspec' => 'test.example.com',
            'port' => 143,
            'username' => 'testuser',
            'relative' => false,
            'mailbox' => ''
        ),
        'testuser;AUTH=PLAIN@test.example.com:143/' => array(
            'hostspec' => 'test.example.com',
            'port' => 143,
            'relative' => false,
            'username' => 'testuser',
            'auth' => 'PLAIN',
            'mailbox' => ''
        ),
        'test.example.com/INBOX.Quarant%26AOQ-ne;UIDVALIDITY=1240054819/;UID=39193/;SECTION=HEADER' => array(
            'hostspec' => 'test.example.com',
            'port' => 143,
            'relative' => false,
            'section' => 'HEADER',
            'uid' => 39193,
            'uidvalidity' => 1240054819,
            'mailbox' => 'INBOX.Quarant&AOQ-ne'
        )
    );

    public function testBadUrl()
    {
        $url = new Horde_Imap_Client_Url('NOT A VALID URL');

        $this->assertNotNull($url->mailbox);
        $this->assertEquals(
            'NOT A VALID URL',
            $url->mailbox
        );
    }

    // RFC 2384 URL parsing
    public function testPopUrlParsing()
    {
        foreach ($this->_testurls as $key => $val) {
            $url = new Horde_Imap_Client_Url('pop://' . $key);
            $val['protocol'] = 'pop';
            unset(
                $val['mailbox'],
                $val['section'],
                $val['uid'],
                $val['uidvalidity']
            );
            foreach ($val as $key2 => $val2) {
                $this->assertEquals(
                    $val2,
                    $url->$key2
                );
            }
        }
    }

    // RFC 5092 URL parsing
    public function testImapUrlParsing()
    {
        foreach ($this->_testurls as $key => $val) {
            $url = new Horde_Imap_Client_Url('imap://' . $key);
            $val['protocol'] = 'imap';
            foreach ($val as $key2 => $val2) {
                $this->assertEquals(
                    $val2,
                    $url->$key2
                );
            }
        }
    }

    public function testSerialize()
    {
        end($this->_testurls);

        $url = unserialize(serialize(
            new Horde_Imap_Client_Url('imap://' . key($this->_testurls))
        ));

        $this->assertEquals(
            'imap',
            $url->protocol
        );
    }

}
