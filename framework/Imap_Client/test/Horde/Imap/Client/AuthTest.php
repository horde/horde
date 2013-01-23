<?php
/**
 * Copyright 2011-2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Imap_Client
 */

/**
 * Tests for the Imap Client ACL Auth features.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @ignore
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Imap_Client
 */
class Horde_Imap_Client_AuthTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        require_once __DIR__ . '/Stub/DigestMD5.php';
    }

    public function testDigestMd5()
    {
        // Using IMAP example from RFC 2831 [4]
        $ob = new Horde_Imap_Client_Stub_Auth_DigestMD5(
            'chris',
            'secret',
            base64_decode('cmVhbG09ImVsd29vZC5pbm5vc29mdC5jb20iLG5vbmNlPSJPQTZNRzl0RVFHbTJoaCIscW9wPSJhdXRoIixhbGdvcml0aG09bWQ1LXNlc3MsY2hhcnNldD11dGYtOA=='),
            'elwood.innosoft.com',
            'imap',
            'OA6MHXh6VqTrRk'
        );

        $this->assertEquals(
            'd388dad90d4bbd760a152321f2143af7',
            $ob->response
        );
    }
}
