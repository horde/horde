<?php
/**
 * Copyright 2010-2017 Horde LLC (http://www.horde.org/)
 *
 * @category   Horde
 * @copyright  2010-2016 Horde LLC
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    Mime
 * @subpackage UnitTests
 */

/**
 * Tests for the Horde_Mime_Mail class.
 *
 * @author     Michael Slusarz <slusarz@horde.org>
 * @category   Horde
 * @copyright  2010-2016 Horde LLC
 * @internal
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    Mime
 * @subpackage UnitTests
 */
class Horde_Mime_MailTest extends PHPUnit_Framework_TestCase
{
    public static function setUpBeforeClass()
    {
        $_SERVER['SERVER_NAME'] = 'mail.example.com';
        setlocale(LC_ALL, 'C');
    }

    public static function tearDownAfterClass()
    {
        unset($_SERVER['SERVER_NAME']);
        setlocale(LC_ALL, '');
    }

    public function testConstructor()
    {
        $mail = new Horde_Mime_Mail(array(
            'Subject' => 'My Subject',
            'body' => "This is\nthe body",
            'To' => 'recipient@example.com',
            'From' => 'sender@example.com',
            'charset' => 'iso-8859-15'
        ));

        $dummy = new Horde_Mail_Transport_Mock();
        $mail->send($dummy);
        $sent = str_replace("\r\n", "\n", $dummy->sentMessages[0]);

        $this->assertStringMatchesFormat(
'Subject: My Subject
To: recipient@example.com
From: sender@example.com
Message-ID: <%d.%s@mail.example.com>
User-Agent: Horde Application Framework %d
Date: %s, %d %s %d %d:%d:%d %s
Content-Type: text/plain; charset=iso-8859-15; format=flowed; DelSp=Yes
MIME-Version: 1.0',
            $sent['header_text']
        );

        $this->assertEquals(
            "This is\nthe body\n",
            $sent['body']
        );

        $this->assertEquals(
            array('recipient@example.com'),
            $sent['recipients']
        );
    }

    public function testMethods()
    {
        $mail = new Horde_Mime_Mail();
        $mail->addHeader('Subject', 'My Subject');
        $mail->setBody("This is\nthe body", 'iso-8859-15');
        $mail->addHeader('To', 'recipient@example.com');
        $mail->addHeader('Cc', 'null@example.com');
        $mail->addHeader('Bcc', 'invisible@example.com');
        $mail->addHeader('From', 'sender@example.com');
        $mail->removeHeader('Cc');

        $dummy = new Horde_Mail_Transport_Mock();
        $mail->send($dummy);
        $sent = str_replace("\r\n", "\n", $dummy->sentMessages[0]);

        $this->assertStringMatchesFormat(
'Subject: My Subject
To: recipient@example.com
From: sender@example.com
Message-ID: <%d.%s@mail.example.com>
User-Agent: Horde Application Framework %d
Date: %s, %d %s %d %d:%d:%d %s%d
Content-Type: text/plain; charset=iso-8859-15; format=flowed; DelSp=Yes
MIME-Version: 1.0',
            $sent['header_text']
        );

        $this->assertEquals(
            "This is\nthe body\n",
            $sent['body']
        );

        $this->assertEquals(
            array('recipient@example.com',
                  'invisible@example.com'),
            $sent['recipients']
        );
    }

    public function testEncoding()
    {
        $mail = new Horde_Mime_Mail(array(
            'Subject' => 'Schöner Betreff',
            'body' => "Hübsche Umlaute \n und Leerzeichen.",
            'To' => 'Empfänger <recipient@example.com>',
            'From' => 'sender@example.com',
            'Cc' => 'Der schöne Peter <peter@example.com>',
            'charset' => 'iso-8859-1'
        ));

        $dummy = new Horde_Mail_Transport_Mock();
        $mail->send($dummy);
        $sent = str_replace("\r\n", "\n", $dummy->sentMessages[0]);

        $this->assertStringMatchesFormat(
'Subject: =?iso-8859-1?b?U2No9m5lcg==?= Betreff
To: =?iso-8859-1?b?RW1wZuRuZ2Vy?= <recipient@example.com>
From: sender@example.com
Cc: Der =?iso-8859-1?b?c2No9m5l?= Peter <peter@example.com>
Message-ID: <%d.%s@mail.example.com>
User-Agent: Horde Application Framework %d
Date: %s, %d %s %d %d:%d:%d %s%d
Content-Type: text/plain; charset=iso-8859-1; format=flowed; DelSp=Yes
MIME-Version: 1.0
Content-Transfer-Encoding: quoted-printable',
            $sent['header_text']
        );

        $this->assertEquals(
            "H=FCbsche Umlaute\n  und Leerzeichen.\n",
            // Some broken PHP versions will insert =20 instead of spaces.
            str_replace(
                '=20',
                ' ',
                $sent['body']
            )
        );

        $this->assertEquals(
            array('recipient@example.com',
                  'peter@example.com'),
            $sent['recipients']
        );
    }

    public function testAddPart()
    {
        $mail = new Horde_Mime_Mail(array(
            'Subject' => 'My Subject',
            'body' => "This is\nthe body",
            'To' => 'recipient@example.com',
            'From' => 'sender@example.com',
            'charset' => 'iso-8859-15'
        ));
        $mail->addPart(
            'text/plain',
            'This is a plain text',
            'iso-8859-1',
            'inline'
        );
        $mail->addPart(
            'application/octet-stream',
            file_get_contents(__DIR__ . '/fixtures/attachment.bin'),
            null,
            'attachment'
        );

        $dummy = new Horde_Mail_Transport_Mock();
        $mail->send($dummy);
        $sent = str_replace("\r\n", "\n", $dummy->sentMessages[0]);

        $this->assertStringMatchesFormat(
'Subject: My Subject
To: recipient@example.com
From: sender@example.com
Message-ID: <%d.%s@mail.example.com>
User-Agent: Horde Application Framework %d
Date: %s, %d %s %d %d:%d:%d %s%d
Content-Type: multipart/mixed; boundary="=_%s"
MIME-Version: 1.0',
            $sent['header_text']
        );

        $this->assertStringMatchesFormat(
"This message is in MIME format.

--=_%s
Content-Type: text/plain; charset=iso-8859-15; format=flowed; DelSp=Yes

This is
the body

--=_%s
Content-Type: text/plain; charset=iso-8859-1
Content-Disposition: inline

This is a plain text
--=_%s
Content-Type: application/octet-stream
Content-Disposition: attachment
Content-Transfer-Encoding: base64

WnfDtmxmIEJveGvDpG1wZmVyIGphZ2VuIFZpa3RvciBxdWVyIMO8YmVyIGRlbiBncm/Dn2VuIFN5
bHRlciBEZWljaC4K
--=_%s--\n",
            $sent['body']
        );

        $this->assertEquals(
            array('recipient@example.com'),
            $sent['recipients']
        );
    }

    public function testAddHtml()
    {
        $mail = new Horde_Mime_Mail(array(
            'Subject' => 'My Subject',
            'To' => 'recipient@example.com',
            'From' => 'sender@example.com',
            'charset' => 'iso-8859-1'
        ));
        $mail->setBody("This is\nthe plain text body.");

        $dummy = new Horde_Mail_Transport_Mock();
        $mail->send($dummy);
        $sent = str_replace("\r\n", "\n", $dummy->sentMessages[0]);

        $this->assertStringMatchesFormat(
'Subject: My Subject
To: recipient@example.com
From: sender@example.com
Message-ID: <%d.%s@mail.example.com>
User-Agent: Horde Application Framework %d
Date: %s, %d %s %d %d:%d:%d %s%d
Content-Type: text/plain; charset=iso-8859-1; format=flowed; DelSp=Yes
MIME-Version: 1.0',
            $sent['header_text']
        );

        $this->assertEquals(
            "This is\nthe plain text body.\n",
            $sent['body']
        );

        $this->assertEquals(
            array('recipient@example.com'),
            $sent['recipients']
        );

        $mail = new Horde_Mime_Mail(array(
            'Subject' => 'My Subject',
            'To' => 'recipient@example.com',
            'From' => 'sender@example.com'
        ));
        $mail->setHTMLBody(
            "<h1>Header Title</h1>\n<p>This is<br />the html text body.</p>",
            'iso-8859-1',
            false
        );

        $dummy = new Horde_Mail_Transport_Mock();
        $mail->send($dummy);
        $sent = str_replace("\r\n", "\n", $dummy->sentMessages[0]);

        $this->assertStringMatchesFormat(
'Subject: My Subject
To: recipient@example.com
From: sender@example.com
Message-ID: <%d.%s@mail.example.com>
User-Agent: Horde Application Framework %d
Date: %s, %d %s %d %d:%d:%d %s%d
Content-Type: text/html; charset=iso-8859-1
MIME-Version: 1.0',
            $sent['header_text']
        );

        $this->assertEquals(
            "<h1>Header Title</h1>\n<p>This is<br />the html text body.</p>",
            $sent['body']
        );

        $this->assertEquals(
            array('recipient@example.com'),
            $sent['recipients']
        );

        $mail = new Horde_Mime_Mail(array(
            'Subject' => 'My Subject',
            'To' => 'recipient@example.com',
            'From' => 'sender@example.com',
            'charset' => 'iso-8859-1'
        ));
        $mail->setHTMLBody("<h1>Header Title</h1>\n<p>This is<br />the html text body.</p>");

        $dummy = new Horde_Mail_Transport_Mock();
        $mail->send($dummy);
        $sent = str_replace("\r\n", "\n", $dummy->sentMessages[0]);

        $this->assertStringMatchesFormat(
'Subject: My Subject
To: recipient@example.com
From: sender@example.com
Message-ID: <%d.%s@mail.example.com>
User-Agent: Horde Application Framework %d
Date: %s, %d %s %d %d:%d:%d %s%d
Content-Type: multipart/alternative; boundary="=_%s"
MIME-Version: 1.0',
            $sent['header_text']
        );

        $this->assertStringMatchesFormat(
"This message is in MIME format.

--=_%s
Content-Type: text/plain; charset=iso-8859-1; format=flowed; DelSp=Yes
Content-Description: Plaintext Version of Message

HEADER TITLE

This is
the html text body.

--=_%s
Content-Type: text/html; charset=iso-8859-1
Content-Description: HTML Version of Message

<h1>Header Title</h1>
<p>This is<br />the html text body.</p>
--=_%s--\n",
            $sent['body']
        );

        $this->assertEquals(
            array('recipient@example.com'),
            $sent['recipients']
        );
    }

    public function testAddAttachment()
    {
        $mail = new Horde_Mime_Mail(array(
            'Subject' => 'My Subject',
            'body' => "This is\nthe body",
            'To' => 'recipient@example.com',
            'From' => 'sender@example.com',
            'charset' => 'iso-8859-15'
        ));
        $mail->addAttachment(__DIR__ . '/fixtures/attachment.bin');
        $mail->addAttachment(
            __DIR__ . '/fixtures/uudecode.txt',
            'my_name.html',
            'text/html',
            'iso-8859-15'
        );

        $dummy = new Horde_Mail_Transport_Mock();
        $mail->send($dummy);
        $sent = str_replace("\r\n", "\n", $dummy->sentMessages[0]);

        $this->assertStringMatchesFormat(
'Subject: My Subject
To: recipient@example.com
From: sender@example.com
Message-ID: <%d.%s@mail.example.com>
User-Agent: Horde Application Framework %d
Date: %s, %d %s %d %d:%d:%d %s%d
Content-Type: multipart/mixed; boundary="=_%s"
MIME-Version: 1.0',
            $sent['header_text']
        );

        $this->assertStringMatchesFormat(
"This message is in MIME format.

--=_%s
Content-Type: text/plain; charset=iso-8859-15; format=flowed; DelSp=Yes

This is
the body

--=_%s
Content-Type: application/octet-stream; name=attachment.bin
Content-Disposition: attachment; filename=attachment.bin
Content-Transfer-Encoding: base64

WnfDtmxmIEJveGvDpG1wZmVyIGphZ2VuIFZpa3RvciBxdWVyIMO8YmVyIGRlbiBncm/Dn2VuIFN5
bHRlciBEZWljaC4K
--=_%s
Content-Type: text/html; charset=iso-8859-15; name=my_name.html
Content-Disposition: attachment; filename=my_name.html


Ignore this text.

begin 644 test.txt
+5&5S=\"!S=')I;F<`
`
end

More text to ignore.

begin 755 test2.txt
*,FYD('-T<FEN9P``
`
end


--=_%s--\n",
            $sent['body']
        );

        $this->assertEquals(
            array('recipient@example.com'),
            $sent['recipients']
        );
    }

    public function testReusing()
    {
        $mail = new Horde_Mime_Mail(array(
            'Subject' => 'My Subject',
            'body' => "This is\nthe body",
            'To' => 'recipient@example.com',
            'From' => 'sender@example.com',
            'charset' => 'iso-8859-15'
        ));

        $dummy = new Horde_Mail_Transport_Mock();
        $mail->send($dummy);
        $sent1 = str_replace("\r\n", "\n", $dummy->sentMessages[0]);

        $mail->addHeader('To', 'recipient2@example.com');
        $mail->send($dummy);
        $sent2 = str_replace("\r\n", "\n", $dummy->sentMessages[1]);

        $mail->setBody("This is\nanother body");
        $mail->send($dummy);
        $sent3 = str_replace("\r\n", "\n", $dummy->sentMessages[2]);

        $hdrs1 = Horde_Mime_Headers::parseHeaders($sent1['header_text']);
        $hdrs2 = Horde_Mime_Headers::parseHeaders($sent2['header_text']);

        $this->assertNotEquals($hdrs1->getValue('message-id'), $hdrs2->getValue('message-id'));

        $this->assertEquals(
            array('recipient@example.com'),
            $sent1['recipients']
        );
        $this->assertEquals(
            array('recipient2@example.com'),
            $sent2['recipients']
        );

        $this->assertEquals(
            "This is\nanother body\n",
            $sent3['body']
        );
    }

    public function testFlowedText()
    {
        $mail = new Horde_Mime_Mail(array(
            'charset' => 'ISO-8859-1',
            'Subject' => 'My Subject',
            'To' => 'recipient@example.com',
            'From' => 'foo@example.com',
            'body' => file_get_contents(__DIR__ . '/fixtures/flowed_msg.txt')));

        $dummy = new Horde_Mail_Transport_Mock();
        $mail->send($dummy);
        $sent = str_replace("\r\n", "\n", $dummy->sentMessages[0]);

        $this->assertStringMatchesFormat(
'Subject: My Subject
To: recipient@example.com
From: foo@example.com
Message-ID: <%d.%s@mail.example.com>
User-Agent: Horde Application Framework %d
Date: %s, %d %s %d %d:%d:%d %s%d
Content-Type: text/plain; charset=iso-8859-1; format=flowed; DelSp=Yes
MIME-Version: 1.0',
            $sent['header_text']
        );

        $this->assertEquals(
'Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do  
eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad  
minim veniam, quis nostrud exercitation ullamco laboris nisi ut  
aliquip ex ea commodo
consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse
cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat  
cupidatat non proident, sunt in culpa qui officia deserunt mollit anim  
id est laborum.

',
            $sent['body']
        );

        $this->assertEquals(
            array('recipient@example.com'),
            $sent['recipients']
        );
    }

    public function testEmptyBody()
    {
        $mail = new Horde_Mime_Mail(array(
            'Subject' => 'My Subject',
            'To' => 'recipient@example.com',
            'From' => 'sender@example.com',
            'charset' => 'iso-8859-15'
        ));

        $dummy = new Horde_Mail_Transport_Mock();
        $mail->send($dummy);
        $sent = str_replace("\r\n", "\n", $dummy->sentMessages[0]);

        $this->assertEquals(
            '',
            $sent['body']
        );

        $this->assertEquals(
            array('recipient@example.com'),
            $sent['recipients']
        );
    }

    public function testParsingAndSending()
    {
        $rfc822_in = 'Subject: Test
From: mike@theupstairsroom.com
Content-Type: text/plain;
    charset=us-ascii
Message-Id: <9517149F-ADF2-4D24-AA6F-0010D6AFA3EE@theupstairsroom.com>
Date: Sat, 17 Mar 2012 13:29:10 -0400
To: =?utf-8?Q?Mich=C3=B1el_Rubinsky?= <mrubinsk@horde.org>
Content-Transfer-Encoding: 7bit
Mime-Version: 1.0 (1.0)

Testing 123
--
Mike';

        $headers = Horde_Mime_Headers::parseHeaders($rfc822_in);
        $message_part = Horde_Mime_Part::parseMessage($rfc822_in);
        $this->assertEquals('Michñel Rubinsky <mrubinsk@horde.org>', $headers->getValue('To'));

        $mail = new Horde_Mime_Mail();
        $part = $message_part[$message_part->findBody()];
        $body = $part->getContents();
        $this->assertEquals('Testing 123
--
Mike', $body);

        $mail->addHeaders($headers->toArray());
        $dummy = new Horde_Mail_Transport_Mock();
        $mail->send($dummy);
    }

    public function testBug13709()
    {
        $p_part = new Horde_Mime_Part();
        $p_part->setType('text/plain');
        $p_part->setContents('Foo bär');
        $h_part = new Horde_Mime_Part();
        $h_part->setType('text/html');
        $h_part->setContents('Foo<br />
<br />
&quot;smith, Jane (IAM)&quot; &lt;<a href="mailto:Jane.smith@kit.edu">Jane.smith@kit.edu</a>&gt; wrote:<br />
<br />
<blockquote type="cite" style="border-left:2px solid blue;margin-left:2px;padding-left:12px;"><html><head><meta http-equiv="Content-Type" content="text/html charset=windows-1252"></head><body style="word-wrap: break-word; -webkit-nbsp-mode: space; -webkit-line-break: after-white-space;">Hallo Jörk,<div><br></div><div>hoffe es geht dir gut und bei HHHH ist alles okay?</div><div><br></div><div>Ich wollte gerade auf die XXX III Homepage schauen und haben im Browser nur <a href="http://example.com">example.com</a> eingegeben.</div><div>Damit bin ich auf einer Seite mit XXX II - Logo gelandet, die sofort Passwort und Nutzername abgefragt hat.</div><div>Ist diese Seite auch von Euch?</div><div><br></div><div>Liebe Grüße,</div><div>Jane</div><div><br></div><div><br></div><div><br></div><div><br></div><div><br><div>
<div><div style="orphans: 2; text-align: -webkit-auto; widows: 2; word-wrap: break-word; -webkit-nbsp-mode: space; -webkit-line-break: after-white-space; font-size: 10px;">--------------------------------------------------<br>Dr. Jane smith</div><div style="orphans: 2; text-align: -webkit-auto; widows: 2; word-wrap: break-word; -webkit-nbsp-mode: space; -webkit-line-break: after-white-space; font-size: 10px;">( geb. Repper)<br>Karlsruhe Institute of Technology (KIT)&nbsp;<br>IAM-WK@INT&nbsp;<br>Hermann-von-Helmholtz-Platz 1, Building 640,&nbsp;</div><div style="orphans: 2; text-align: -webkit-auto; widows: 2; word-wrap: break-word; -webkit-nbsp-mode: space; -webkit-line-break: after-white-space; font-size: 10px;">76344&nbsp;Eggenstein-Leopoldshafen, Germany<br><br>Phone CN: +49 721 608-26960</div><div style="orphans: 2; text-align: -webkit-auto; widows: 2; word-wrap: break-word; -webkit-nbsp-mode: space; -webkit-line-break: after-white-space; font-size: 10px;">Phone CS: +49 721 608-47447<br>Web:&nbsp;<a href="http://www.kit.edu/">http://www.kit.edu/</a>&nbsp;<br><br>KIT – University of the State of Baden-Wuerttemberg&nbsp;and<br>National Research Center of the Helmholtz&nbsp;Association</div></div>
</div>
<br></div></body></html></blockquote><br /><br />');


        $base_part = new Horde_Mime_Part();
        $base_part->setType('multipart/alternative');
        $base_part[] = $p_part;
        $base_part[] = $h_part;
        $headers = $base_part->addMimeHeaders();
        $headers->addHeader('From', 'sender@example.com');
        $headers->addHeader('Subject', 'My Subject');
        $mailer = new Horde_Mail_Transport_Mock();
        $base_part->send('recipient@example.com', $headers, $mailer, array('encode' => Horde_Mime_Part::ENCODE_8BIT));
        $sent = current($mailer->sentMessages);
        $sent_mime = Horde_Mime_Part::parseMessage($sent['header_text'] . "\n\n" . $sent['body']);
        $headers = Horde_Mime_Headers::parseHeaders(
            $sent_mime[$sent_mime->findBody('plain')]->toString(array(
                'headers' => true,
                'encode' => Horde_Mime_Part::ENCODE_8BIT
            ))
        );
        $this->assertEquals('8bit', $headers->getHeader('Content-Transfer-Encoding')->value_single);
        $headers = Horde_Mime_Headers::parseHeaders(
            $sent_mime[$sent_mime->findBody('html')]->toString(array(
                'headers' => true,
                'encode' => Horde_Mime_Part::ENCODE_8BIT
            ))
        );
        $this->assertEquals('quoted-printable', $headers->getHeader('Content-Transfer-Encoding'));
    }

}
