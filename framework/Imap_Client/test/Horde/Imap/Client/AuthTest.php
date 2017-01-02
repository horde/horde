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
 * Tests for the Imap Client ACL Auth features.
 *
 * @author     Michael Slusarz <slusarz@horde.org>
 * @category   Horde
 * @copyright  2011-2016 Horde LLC
 * @ignore
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    Imap_Client
 * @subpackage UnitTests

 */
class Horde_Imap_Client_AuthTest extends PHPUnit_Framework_TestCase
{
    public static function setUpBeforeClass()
    {
        require_once __DIR__ . '/Stub/DigestMD5.php';
        require_once __DIR__ . '/Stub/Scram.php';
    }

    /**
     * @dataProvider digestMd5Provider
     */
    public function testDigestMd5($c)
    {
        $ob = new Horde_Imap_Client_Stub_Auth_DigestMD5(
            $c['user'],
            $c['pass'],
            $c['challenge'],
            $c['hostname'],
            $c['service'],
            $c['cnonce']
        );

        $this->assertEquals(
            $c['expected'],
            $ob->response
        );
    }

    public function digestMd5Provider()
    {
        return array(
            array(
                // IMAP example from RFC 2831 [4]
                array(
                    'user' => 'chris',
                    'pass' => 'secret',
                    'challenge' => base64_decode('cmVhbG09ImVsd29vZC5pbm5vc29mdC5jb20iLG5vbmNlPSJPQTZNRzl0RVFHbTJoaCIscW9wPSJhdXRoIixhbGdvcml0aG09bWQ1LXNlc3MsY2hhcnNldD11dGYtOA=='),
                    'hostname' => 'elwood.innosoft.com',
                    'service' => 'imap',
                    'cnonce' => 'OA6MHXh6VqTrRk',
                    'expected' => 'd388dad90d4bbd760a152321f2143af7'
                )
            )
        );
    }

    /**
     * @dataProvider scramProvider
     */
    public function testScram($c)
    {
        $ob = new Horde_Imap_Client_Stub_Auth_Scram(
            $c['user'],
            $c['pass'],
            $c['hash']
        );
        $ob->setNonce($c['nonce']);

        $this->assertEquals(
            $c['c1'],
            $ob->getClientFirstMessage()
        );

        $this->assertTrue($ob->parseServerFirstMessage($c['s1']));

        $this->assertEquals(
            $c['c2'],
            $ob->getClientFinalMessage()
        );

        $this->assertTrue($ob->parseServerFinalMessage($c['s2']));
    }

    public function scramProvider()
    {
        return array(
            array(
                // Example from RFC 5802 [5]
                array(
                    'user' => 'user',
                    'pass' => 'pencil',
                    'hash' => 'SHA1',
                    'nonce' => 'fyko+d2lbbFgONRv9qkxdawL',
                    'c1' => 'n,,n=user,r=fyko+d2lbbFgONRv9qkxdawL',
                    's1' => 'r=fyko+d2lbbFgONRv9qkxdawL3rfcNHYJY1ZVvWVs7j,s=QSXCR+Q6sek8bf92,i=4096',
                    'c2' => 'c=biws,r=fyko+d2lbbFgONRv9qkxdawL3rfcNHYJY1ZVvWVs7j,p=v0X8v3Bz2T0CJGbJQyF0X+HI4Ts=',
                    's2' => 'v=rmF9pqV8S7suAoZWja4dJRkFsKQ='
                )
            )
        );
    }

}
