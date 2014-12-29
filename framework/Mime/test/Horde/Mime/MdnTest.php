<?php
/**
 * Copyright 2014 Horde LLC (http://www.horde.org/)
 *
 * @category   Horde
 * @copyright  2014 Horde LLC
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    Mime
 * @subpackage UnitTests
 */

/**
 * Tests for the Horde_Mime_Mdn object.
 *
 * @author     Michael Slusarz <slusarz@horde.org>
 * @category   Horde
 * @copyright  2014 Horde LLC
 * @internal
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    Mime
 * @subpackage UnitTests
 */
class Horde_Mime_MdnTest extends PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider getMdnReturnAddrProvider
     */
    public function testGetMdnReturnAddr($email)
    {
        $h = new Horde_Mime_Headers();
        $ob = new Horde_Mime_Mdn($h);

        if (!is_null($email)) {
            $ob->addMdnRequestHeaders($email);
        }

        $this->assertEquals(
            strval($email),
            $ob->getMdnReturnAddr()
        );
    }

    public function getMdnReturnAddrProvider()
    {
        $email = 'foo1@example.com, Test <foo2@example.com>';

        $rfc822 = new Horde_Mail_Rfc822();
        $mail_ob = $rfc822->parseAddressList($email);

        return array(
            array(null),
            array('foo@example.com'),
            array($email),
            array($mail_ob)
        );
    }

    /**
     * @dataProvider UserConfirmationNeededProvider
     */
    public function testUserConfirmationNeeded($h, $expected)
    {
        $ob = new Horde_Mime_Mdn($h);
        if ($expected) {
            $this->assertTrue($ob->userConfirmationNeeded());
        } else {
            $this->assertFalse($ob->userConfirmationNeeded());
        }
    }

    public function userConfirmationNeededProvider()
    {
        $out = array();

        $h = new Horde_Mime_Headers();
        $out[] = array(clone $h, true);

        $h->addHeader('Return-Path', 'foo@example.com');
        $out[] = array(clone $h, false);

        $h->addHeader('Return-Path', 'foo2@example.com');
        $out[] = array(clone $h, true);

        $h->replaceHeader('Return-Path', 'foo@example.com');

        $h->addHeader(Horde_Mime_Mdn::MDN_HEADER, 'FOO@example.com');
        $out[] = array(clone $h, true);

        $h->replaceHeader(Horde_Mime_Mdn::MDN_HEADER, 'foo@EXAMPLE.com');
        $out[] = array(clone $h, false);

        return $out;
    }

    public function testGenerate()
    {
        $locale = setlocale(LC_MESSAGES, 0);
        setlocale(LC_MESSAGES, 'C');
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

        setlocale(LC_MESSAGES, $locale);
    }

}
