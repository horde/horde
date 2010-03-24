--TEST--
Horde_Mime_Mail::addAttachment() test
--FILE--
<?php

require dirname(__FILE__) . '/mail_dummy.inc';
require_once 'Horde/String.php';
require_once 'Horde/Util.php';

$mail = new Horde_Mime_Mail(array('subject' => 'My Subject',
                                  'body' => "This is\nthe body",
                                  'to' => 'recipient@example.com',
                                  'from' => 'sender@example.com',
                                  'charset' => 'iso-8859-15'));
$mail->addAttachment(dirname(__FILE__) . '/fixtures/attachment.bin');
$mail->addAttachment(dirname(__FILE__) . '/mail_dummy.inc', 'my_name.html', 'text/html', 'iso-8859-15');

$dummy = Mail::factory('dummy');
$mail->send($dummy);
echo $dummy->send_output;

?>
--EXPECTF--
Subject: My Subject
To: recipient@example.com
From: sender@example.com
Message-ID: <%d.%s@mail.example.com>
User-Agent: Horde Application Framework 4.0
Date: %s, %d %s %d %d:%d:%d %s%d
Content-Type: multipart/mixed; boundary="=_%s"
MIME-Version: 1.0
Content-Transfer-Encoding: 7bit

This message is in MIME format.

--=_%s
Content-Type: text/plain; charset=iso-8859-15; format=flowed; DelSp=Yes
Content-Disposition: inline
Content-Transfer-Encoding: 7bit

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
Content-Transfer-Encoding: 7bit

<?php
/**
 * @package Mail
 */

require_once 'Mail.php';
require_once 'Mail/RFC822.php';
require_once 'Horde/Browser.php';
require_once 'Horde/Text/Flowed.php';
require dirname(__FILE__) . '/../../../lib/Horde/Mime.php';
require dirname(__FILE__) . '/../../../lib/Horde/Mime/Address.php';
require dirname(__FILE__) . '/../../../lib/Horde/Mime/Headers.php';
require dirname(__FILE__) . '/../../../lib/Horde/Mime/Magic.php';
require dirname(__FILE__) . '/../../../lib/Horde/Mime/Mail.php';
require dirname(__FILE__) . '/../../../lib/Horde/Mime/Part.php';
$_SERVER['SERVER_NAME'] = 'mail.example.com';

class Mail_dummy extends Mail {
    function send($recipients, $headers, $body)
    {
        list(,$text_headers) = Mail::prepareHeaders($headers);
        return $text_headers . "\n\n" . $body;
    }
}

--=_%s--
