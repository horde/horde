<?php
/**
 * Copyright 2011-2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category   Horde
 * @copyright  2011-2013 Horde LLC
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    Imap_Client
 * @subpackage UnitTests
 */

/**
 * Tests for IMAP URL parsing.
 *
 * @author     Michael Slusarz <slusarz@horde.org>
 * @category   Horde
 * @copyright  2011-2013 Horde LLC
 * @ignore
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    Imap_Client
 * @subpackage UnitTests
 */
class Horde_Imap_Client_UrlParseTest extends Horde_Test_Case
{
    private $_testurls = array(
        'test.example.com/',
        'test.example.com:143/',
        'testuser@test.example.com/',
        'testuser@test.example.com:143/',
        ';AUTH=PLAIN@test.example.com/',
        ';AUTH=PLAIN@test.example.com:143/',
        ';AUTH=*@test.example.com:143/',
        'testuser;AUTH=*@test.example.com:143/',
        'testuser;AUTH=PLAIN@test.example.com:143/',
        'test.example.com/INBOX.Quarant%26AOQ-ne;UIDVALIDITY=1240054819/;UID=39193/;SECTION=HEADER',
    );

    private $_expected = array(
        array('hostspec' => 'test.example.com',
              'port' => 143,
              'relative' => false,
              'mailbox' => ''),
        array('hostspec' => 'test.example.com',
              'port' => 143,
              'relative' => false,
              'mailbox' => ''),
        array('hostspec' => 'test.example.com',
              'port' => 143,
              'relative' => false,
              'username' => 'testuser',
              'mailbox' => ''),
        array('hostspec' => 'test.example.com',
              'port' => 143,
              'relative' => false,
              'username' => 'testuser',
              'mailbox' => ''),
        array('hostspec' => 'test.example.com',
              'port' => 143,
              'relative' => false,
              'auth' => 'PLAIN',
              'mailbox' => ''),
        array('hostspec' => 'test.example.com',
              'port' => 143,
              'relative' => false,
              'auth' => 'PLAIN',
              'mailbox' => ''),
        array('hostspec' => 'test.example.com',
              'port' => 143,
              'relative' => false,
              'mailbox' => ''),
        array('hostspec' => 'test.example.com',
              'port' => 143,
              'username' => 'testuser',
              'relative' => false,
              'mailbox' => ''),
        array('hostspec' => 'test.example.com',
              'port' => 143,
              'relative' => false,
              'username' => 'testuser',
              'auth' => 'PLAIN',
              'mailbox' => ''),
        array('hostspec' => 'test.example.com',
              'port' => 143,
              'relative' => false,
              'section' => 'HEADER',
              'uid' => 39193,
              'uidvalidity' => 1240054819,
              'mailbox' => 'INBOX.Quarant&AOQ-ne'),
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
            $url = new Horde_Imap_Client_Url('pop://' . $val);
            $expected = $this->_expected[$key];
            $expected['protocol'] = 'pop';
            unset($expected['mailbox'],
                  $expected['section'],
                  $expected['uid'],
                  $expected['uidvalidity']);
            foreach ($expected as $key2 => $val2) {
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
            $url = new Horde_Imap_Client_Url('imap://' . $val);
            $expected = $this->_expected[$key];
            $expected['protocol'] = 'imap';
            foreach ($expected as $key2 => $val2) {
                $this->assertEquals(
                    $val2,
                    $url->$key2
                );
            }
        }
    }

    public function testSerialize()
    {
        $url = unserialize(serialize(
            new Horde_Imap_Client_Url('imap://' . end($this->_testurls))
        ));

        $this->assertEquals(
            'imap',
            $url->protocol
        );
    }

}
