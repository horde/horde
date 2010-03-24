--TEST--
Horde_Mime_Mail methods test
--FILE--
<?php

require_once 'Horde/String.php';
require_once 'Horde/Util.php';
require dirname(__FILE__) . '/mail_dummy.inc';

$mail = new Horde_Mime_Mail();
$mail->addHeader('Subject', 'My Subject');
$mail->setBody("This is\nthe body", 'iso-8859-15');
$mail->addHeader('To', 'recipient@example.com');
$mail->addHeader('Cc', 'null@example.com');
$mail->addHeader('Bcc', 'invisible@example.com');
$mail->addHeader('From', 'sender@example.com');
$mail->removeHeader('Cc');

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
Content-Type: text/plain; charset=iso-8859-15; format=flowed; DelSp=Yes
MIME-Version: 1.0
Content-Disposition: inline
Content-Transfer-Encoding: 7bit

This is
the body
