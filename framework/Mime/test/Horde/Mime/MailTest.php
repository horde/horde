<?php
/**
 * Tests for the Horde_Mime_Mail class.
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
 *
 * @author     Michael Slusarz <slusarz@curecanti.org>
 * @category   Horde
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @package    Mime
 * @subpackage UnitTests
 */

/**
 * @author     Michael Slusarz <slusarz@curecanti.org>
 * @category   Horde
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @package    Mime
 * @subpackage UnitTests
 */
class Horde_Mime_MailTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $_SERVER['SERVER_NAME'] = 'mail.example.com';
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

        $dummy = Horde_Mail::factory('Mock');
        $mail->send($dummy);

        // Need PHPUnit 3.5+
        if (method_exists($this, 'assertStringMatchesFormat')) {
            $this->assertStringMatchesFormat(
'Subject: My Subject
To: recipient@example.com
From: sender@example.com
Message-ID: <%d.%s@mail.example.com>
User-Agent: Horde Application Framework 4
Date: %s, %d %s %d %d:%d:%d %s
Content-Type: text/plain; charset=iso-8859-15; format=flowed; DelSp=Yes
MIME-Version: 1.0',
                str_replace("\r\n", "\n", $dummy->sentMessages[0]['header_text'])
            );
        } else {
            $this->markTestSkipped();
        }

        $this->assertEquals(
            "This is\nthe body\n",
            $dummy->sentMessages[0]['body']
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

        $dummy = Horde_Mail::factory('Mock');
        $mail->send($dummy);

        // Need PHPUnit 3.5+
        if (method_exists($this, 'assertStringMatchesFormat')) {
            $this->assertStringMatchesFormat(
'Subject: My Subject
To: recipient@example.com
From: sender@example.com
Message-ID: <%d.%s@mail.example.com>
User-Agent: Horde Application Framework 4
Date: %s, %d %s %d %d:%d:%d %s%d
Content-Type: text/plain; charset=iso-8859-15; format=flowed; DelSp=Yes
MIME-Version: 1.0',
                str_replace("\r\n", "\n", $dummy->sentMessages[0]['header_text'])
            );
        } else {
            $this->markTestSkipped();
        }

        $this->assertEquals(
            "This is\nthe body\n",
            $dummy->sentMessages[0]['body']
        );
    }

    public function testEncoding()
    {
        $mail = new Horde_Mime_Mail(array(
            'Subject' => Horde_String::convertCharset('Schöner Betreff', 'UTF-8', 'iso-8859-1'),
            'body' => Horde_String::convertCharset("Hübsche Umlaute \n und Leerzeichen.", 'UTF-8', 'iso-8859-1'),
            'To' => Horde_String::convertCharset('Empfänger <recipient@example.com>', 'UTF-8', 'iso-8859-1'),
            'From' => 'sender@example.com',
            'charset' => 'iso-8859-1'
        ));
        $mail->addHeader('Cc', Horde_String::convertCharset('Der schöne Peter <peter@example.com>', 'UTF-8', 'iso-8859-15'), 'iso-8859-15');

        $dummy = Horde_Mail::factory('Mock');
        $mail->send($dummy);

        // Need PHPUnit 3.5+
        if (method_exists($this, 'assertStringMatchesFormat')) {
            $this->assertStringMatchesFormat(
'Subject: =?iso-8859-1?b?U2No9m5lcg==?= Betreff
To: =?iso-8859-1?b?RW1wZuRuZ2Vy?= <recipient@example.com>
From: sender@example.com
Cc: Der =?iso-8859-15?b?c2No9m5l?= Peter <peter@example.com>
Message-ID: <%d.%s@mail.example.com>
User-Agent: Horde Application Framework 4
Date: %s, %d %s %d %d:%d:%d %s%d
Content-Type: text/plain; charset=iso-8859-1; format=flowed; DelSp=Yes
MIME-Version: 1.0
Content-Transfer-Encoding: quoted-printable',
                str_replace("\r\n", "\n", $dummy->sentMessages[0]['header_text'])
            );
        } else {
            $this->markTestSkipped();
        }

        $this->assertEquals(
            "H=FCbsche Umlaute\n  und Leerzeichen.\n",
            $dummy->sentMessages[0]['body']
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
            file_get_contents(dirname(__FILE__) . '/fixtures/attachment.bin'),
            null,
            'attachment'
        );

        $dummy = Horde_Mail::factory('Mock');
        $mail->send($dummy);

        // Need PHPUnit 3.5+
        if (method_exists($this, 'assertStringMatchesFormat')) {
            $this->assertStringMatchesFormat(
'Subject: My Subject
To: recipient@example.com
From: sender@example.com
Message-ID: <%d.%s@mail.example.com>
User-Agent: Horde Application Framework 4
Date: %s, %d %s %d %d:%d:%d %s%d
Content-Type: multipart/mixed; boundary="=_%s"
MIME-Version: 1.0',
                str_replace("\r\n", "\n", $dummy->sentMessages[0]['header_text'])
            );
        } else {
            $this->markTestSkipped();
        }

        // Need PHPUnit 3.5+
        if (method_exists($this, 'assertStringMatchesFormat')) {
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
                $dummy->sentMessages[0]['body']
            );
        } else {
            $this->markTestSkipped();
        }
    }

    public function addHtmlTest()
    {
        $mail = new Horde_Mime_Mail(array(
            'Subject' => 'My Subject',
            'To' => 'recipient@example.com',
            'From' => 'sender@example.com'
        ));
        $mail->setBody("This is\nthe plain text body.");

        $dummy = Horde_Mail::factory('Mock');
        $mail->send($dummy);

        // Need PHPUnit 3.5+
        if (method_exists($this, 'assertStringMatchesFormat')) {
            $this->assertStringMatchesFormat(
'Subject: My Subject
To: recipient@example.com
From: sender@example.com
Message-ID: <%d.%s@mail.example.com>
User-Agent: Horde Application Framework 4
Date: %s, %d %s %d %d:%d:%d %s%d
Content-Type: text/plain; charset=iso-8859-1; format=flowed; DelSp=Yes
MIME-Version: 1.0',
                str_replace("\r\n", "\n", $dummy->sentMessages[0]['header_text'])
            );
        } else {
            $this->markTestSkipped();
        }

        $this->assertEquals(
            "This is\nthe plain text body.",
            $dummy->sentMessages[0]['body']
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

        $dummy = Horde_Mail::factory('Mock');
        $mail->send($dummy);

        // Need PHPUnit 3.5+
        if (method_exists($this, 'assertStringMatchesFormat')) {
            $this->assertStringMatchesFormat(
'Subject: My Subject
To: recipient@example.com
From: sender@example.com
Message-ID: <%d.%s@mail.example.com>
User-Agent: Horde Application Framework 4
Date: %s, %d %s %d %d:%d:%d %s%d
Content-Type: text/html; charset=iso-8859-1
MIME-Version: 1.0',
                str_replace("\r\n", "\n", $dummy->sentMessages[0]['header_text'])
            );
        } else {
            $this->markTestSkipped();
        }

        $this->assertEquals(
            "<h1>Header Title</h1>\n<p>This is<br />the html text body.</p>",
            $dummy->sentMessages[0]['body']
        );

        $mail = new Horde_Mime_Mail(array(
            'Subject' => 'My Subject',
            'To' => 'recipient@example.com',
            'From' => 'sender@example.com'
        ));
        $mail->setHTMLBody("<h1>Header Title</h1>\n<p>This is<br />the html text body.</p>");

        $dummy = Horde_Mail::factory('Mock');
        $mail->send($dummy);

        // Need PHPUnit 3.5+
        if (method_exists($this, 'assertStringMatchesFormat')) {
            $this->assertStringMatchesFormat(
'Subject: My Subject
To: recipient@example.com
From: sender@example.com
Message-ID: <%d.%s@mail.example.com>
User-Agent: Horde Application Framework 4
Date: %s, %d %s %d %d:%d:%d %s%d
Content-Type: multipart/alternative; boundary="=_%s"
MIME-Version: 1.0',
                str_replace("\r\n", "\n", $dummy->sentMessages[0]['header_text'])
            );
        } else {
            $this->markTestSkipped();
        }

        // Need PHPUnit 3.5+
        if (method_exists($this, 'assertStringMatchesFormat')) {
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
                $dummy->sentMessages[0]['body']
            );
        } else {
            $this->markTestSkipped();
        }
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
        $mail->addAttachment(dirname(__FILE__) . '/fixtures/attachment.bin');
        $mail->addAttachment(
            dirname(__FILE__) . '/fixtures/uudecode.txt',
            'my_name.html',
            'text/html',
            'iso-8859-15'
        );

        $dummy = Horde_Mail::factory('Mock');
        $mail->send($dummy);

        // Need PHPUnit 3.5+
        if (method_exists($this, 'assertStringMatchesFormat')) {
            $this->assertStringMatchesFormat(
'Subject: My Subject
To: recipient@example.com
From: sender@example.com
Message-ID: <%d.%s@mail.example.com>
User-Agent: Horde Application Framework 4
Date: %s, %d %s %d %d:%d:%d %s%d
Content-Type: multipart/mixed; boundary="=_%s"
MIME-Version: 1.0',
                str_replace("\r\n", "\n", $dummy->sentMessages[0]['header_text'])
            );
        } else {
            $this->markTestSkipped();
        }

        // Need PHPUnit 3.5+
        if (method_exists($this, 'assertStringMatchesFormat')) {
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
                $dummy->sentMessages[0]['body']
            );
        } else {
            $this->markTestSkipped();
        }
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

        $dummy = Horde_Mail::factory('Mock');
        $mail->send($dummy);

        $mail->addHeader('To', 'recipient2@example.com');
        $mail->send($dummy);

        $hdrs1 = Horde_Mime_Headers::parseHeaders($dummy->sentMessages[0]['header_text']);
        $hdrs2 = Horde_Mime_Headers::parseHeaders($dummy->sentMessages[1]['header_text']);

        $this->assertNotEquals($hdrs1->getValue('message-id'), $hdrs2->getValue('message-id'));
    }

    public function testFlowedText()
    {
        $mail = new Horde_Mime_Mail();
        $mail->addHeader('Subject', 'My Subject');
        $mail->addHeader('To', 'recipient@example.com');
        $mail->setBody(file_get_contents(dirname(__FILE__) . '/fixtures/flowed_msg.txt'));

        $dummy = Horde_Mail::factory('Mock');
        $mail->send($dummy);

        // Need PHPUnit 3.5+
        if (method_exists($this, 'assertStringMatchesFormat')) {
            $this->assertStringMatchesFormat(
'Subject: My Subject
To: recipient@example.com
Message-ID: <%d.%s@mail.example.com>
User-Agent: Horde Application Framework 4
Date: %s, %d %s %d %d:%d:%d %s%d
Content-Type: text/plain; charset=iso-8859-1; format=flowed; DelSp=Yes
MIME-Version: 1.0',
                str_replace("\r\n", "\n", $dummy->sentMessages[0]['header_text'])
            );
        } else {
            $this->markTestSkipped();
        }

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
            $dummy->sentMessages[0]['body']
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

        $dummy = Horde_Mail::factory('Mock');
        $mail->send($dummy);

        $this->assertEquals(
            "\n",
            $dummy->sentMessages[0]['body']
        );
    }

}
