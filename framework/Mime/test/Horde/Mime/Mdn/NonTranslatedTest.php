<?php
/**
 * Copyright 2014-2017 Horde LLC (http://www.horde.org/)
 *
 * @category   Horde
 * @copyright  2014-2016 Horde LLC
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    Mime
 * @subpackage UnitTests
 */

/**
 * Tests for the Horde_Mime_Mdn object that require translations to not occur.
 *
 * @author     Michael Slusarz <slusarz@horde.org>
 * @category   Horde
 * @copyright  2014-2016 Horde LLC
 * @internal
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    Mime
 * @subpackage UnitTests
 */
class Horde_Mime_Mdn_NonTranslatedTest extends PHPUnit_Framework_TestCase
{
    private $oldlocale;

    public function setUp()
    {
        $this->oldlocale = setlocale(LC_MESSAGES, 0);
        setlocale(LC_MESSAGES, 'C');
    }

    public function tearDown()
    {
        setlocale(LC_MESSAGES, $this->oldlocale);
    }

    public function testGenerate()
    {
        $h = new Horde_Mime_Headers();
        $ob = new Horde_Mime_Mdn($h);

        try {
            $ob->generate(true, true, 'deleted', 'foo', null);
            $this->fail('Expected Exception');
        } catch (RuntimeException $e) {}

        $date = 'Tue, 18 Nov 2014 20:14:17 -0700';
        $mdn_addr = 'Aäb <foo@example.com>';

        $h->addHeader('Date', $date);
        $h->addHeader('Subject', 'Test');
        $h->addHeader('To', '"BAR" <bar@example.com>');

        $ob->addMdnRequestHeaders($mdn_addr);

        $mailer = new Horde_Mail_Transport_Mock();

        $ob->generate(
            true,
            true,
            'displayed',
            'test.example.com',
            $mailer,
            array(
                'from_addr' => 'bar@example.com'
            ),
            array('error'),
            array('error' => 'Foo')
        );

        $sent = str_replace("\r\n", "\n", $mailer->sentMessages[0]);

        $this->assertEquals(
            'auto-replied',
            $sent['headers']['Auto-Submitted']
        );
        $this->assertEquals(
            'bar@example.com',
            $sent['headers']['From']
        );
        $this->assertEquals(
            $mdn_addr,
            Horde_Mime::decode($sent['headers']['To'])
        );
        $this->assertEquals(
            'Disposition Notification',
            $sent['headers']['Subject']
        );

        $this->assertStringMatchesFormat(
'This message is in MIME format.

--=%s
Content-Type: text/plain; format=flowed; DelSp=Yes

The message sent on Tue, 18 Nov 2014 20:14:17 -0700 to BAR  
<bar@example.com> with subject "Test" has been displayed.

This is no guarantee that the message has been read or understood.

--=%s
Content-Type: message/disposition-notification

Reporting-UA: test.example.com; Horde Application Framework 5
Final-Recipient: rfc822;bar@example.com
Disposition: manual-action/MDN-sent-manually; displayed/error
Error: Foo

--=%s
Content-Type: message/rfc822

Date: Tue, 18 Nov 2014 20:14:17 -0700
Subject: Test
To: BAR <bar@example.com>
Disposition-Notification-To: =?utf-8?b?QcOkYg==?= <foo@example.com>

--=%s
',
            $sent['body']
        );
    }

}
