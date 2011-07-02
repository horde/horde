<?php
/**
 * Tests for IMAP URL parsing.
 *
 * PHP version 5
 *
 * @category Horde
 * @package  Imap_Client
 * @author   Michael Slusarz <slusarz@horde.org>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Imap_Client
 */

/* Prepare the test setup. */
require_once dirname(__FILE__) . '/Autoload.php';

/**
 * Tests for IMAP URL parsing.
 *
 * Copyright 2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category Horde
 * @package  Imap_Client
 * @author   Michael Slusarz <slusarz@horde.org>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Imap_Client
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
        'testuser;AUTH=PLAIN@test.example.com:143/'
    );

    private $_expected = array(
        array('hostspec' => 'test.example.com',
              'port' => 143,
              'relative' => false),
        array('hostspec' => 'test.example.com',
              'port' => 143,
              'relative' => false),
        array('hostspec' => 'test.example.com',
              'port' => 143,
              'relative' => false,
              'username' => 'testuser'),
        array('hostspec' => 'test.example.com',
              'port' => 143,
              'relative' => false,
              'username' => 'testuser'),
        array('hostspec' => 'test.example.com',
              'port' => 143,
              'relative' => false,
              'auth' => 'PLAIN'),
        array('hostspec' => 'test.example.com',
              'port' => 143,
              'relative' => false,
              'auth' => 'PLAIN'),
        array('hostspec' => 'test.example.com',
              'port' => 143,
              'relative' => false),
        array('hostspec' => 'test.example.com',
              'port' => 143,
              'username' => 'testuser',
              'relative' => false),
        array('hostspec' => 'test.example.com',
              'port' => 143,
              'relative' => false,
              'username' => 'testuser',
              'auth' => 'PLAIN'),
    );

    public function testBadUrl()
    {
        $imap_utils = new Horde_Imap_Client_Utils();

        $out = $imap_utils->parseUrl('NOT A VALID URL');

        $this->assertNotEmpty($out);
        $this->assertArrayHasKey('mailbox', $out);
        $this->assertEquals(
            'NOT A VALID URL',
            $out['mailbox']
        );
    }

    // RFC 2384 URL parsing
    public function testPopUrlParsing()
    {
        $imap_utils = new Horde_Imap_Client_Utils();

        foreach ($this->_testurls as $key => $val) {
            $result = $imap_utils->parseUrl('pop://' . $val);
            $this->assertNotEmpty($result);
            $expected = $this->_expected[$key];
            $expected['type'] = 'pop';
            $this->assertEquals($expected, $result);
        }
    }

    // RFC 5092 URL parsing
    public function testImapUrlParsing()
    {
        $imap_utils = new Horde_Imap_Client_Utils();

        foreach ($this->_testurls as $key => $val) {
            $result = $imap_utils->parseUrl('imap://' . $val);
            $this->assertNotEmpty($result);
            $expected = $this->_expected[$key];
            $expected['type'] = 'imap';
            $expected['mailbox'] = '';
            $this->assertEquals($expected, $result);
        }
    }

}
