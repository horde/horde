--TEST--
Horde_Mime_Mail::addPart() test
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
$mail->addPart('text/plain', 'This is a plain text', 'iso-8859-1', 'inline');
$mail->addPart('application/octet-stream',
               file_get_contents(dirname(__FILE__) . '/fixtures/attachment.bin'),
               null, 'attachment');

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
Content-Type: text/plain; charset=iso-8859-1
Content-Disposition: inline
Content-Transfer-Encoding: 7bit

This is a plain text
--=_%s
Content-Type: application/octet-stream
Content-Disposition: attachment
Content-Transfer-Encoding: base64

WnfDtmxmIEJveGvDpG1wZmVyIGphZ2VuIFZpa3RvciBxdWVyIMO8YmVyIGRlbiBncm/Dn2VuIFN5
bHRlciBEZWljaC4K
--=_%s--
