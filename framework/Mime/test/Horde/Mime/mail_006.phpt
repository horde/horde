--TEST--
Horde_Mime_Mail::addAttachment() test
--FILE--
<?php

require dirname(__FILE__) . '/mail_dummy.inc';

$mail = new Horde_Mime_Mail('My Subject', "This is\nthe body",
                            'recipient@example.com', 'sender@example.com',
                            'iso-8859-15');
$mail->addAttachment(dirname(__FILE__) . '/attachment.bin');
$mail->addAttachment(dirname(__FILE__) . '/mail_dummy.inc', 'my_name.html', 'text/html', 'iso-8859-15');

echo $mail->send('dummy');

?>
--EXPECTF--
Subject: My Subject
To: recipient@example.com
From: sender@example.com
Message-ID: <%d.%s@mail.example.com>
User-Agent: Horde Application Framework 3.2
Date: %s, %d %s %d %d:%d:%d %s%d
MIME-Version: 1.0
Content-Type: multipart/mixed;
	boundary="=_%s"
Content-Transfer-Encoding: 7bit

This message is in MIME format.

--=_%s
Content-Type: text/plain;
	charset=iso-8859-15;
	DelSp="Yes";
	format="flowed"
Content-Disposition: inline
Content-Transfer-Encoding: 7bit

This is
the body

--=_%s
Content-Type: application/octet-stream;
	name="attachment.bin"
Content-Disposition: attachment;
	filename="attachment.bin"
Content-Transfer-Encoding: base64

WnfDtmxmIEJveGvDpG1wZmVyIGphZ2VuIFZpa3RvciBxdWVyIMO8YmVyIGRlbiBncm/Dn2VuIFN5
bHRlciBEZWljaC4K

--=_%s
Content-Type: text/html;
	charset=iso-8859-15;
	name="my_name.html"
Content-Disposition: attachment;
	filename="my_name.html"
Content-Transfer-Encoding: 7bit

<?php
/**
 * @package Mail
 */

require dirname(__FILE__) . '/../MIME/Mail.php';
$_SERVER['SERVER_NAME'] = 'mail.example.com';

class Mail_dummy extends Mail {
    function send($recipients, $headers, $body)
    {
        list(,$text_headers) = Mail::prepareHeaders($headers);
        return $text_headers . "\n\n" . $body;
    }
}

--=_%s--
