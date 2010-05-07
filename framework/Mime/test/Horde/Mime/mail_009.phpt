--TEST--
Horde_Mime_Mail empty body test.
--FILE--
<?php

require_once 'Horde/String.php';
require_once 'Horde/Util.php';
require dirname(__FILE__) . '/mail_dummy.inc';

$mail = new Horde_Mime_Mail(array('subject' => 'My Subject',
                                  'to' => 'recipient@example.com',
                                  'from' => 'sender@example.com',
                                  'charset' => 'iso-8859-15'));
$dummy = Mail::factory('dummy');
$mail->send($dummy);
echo $dummy->send_output;

?>
--EXPECTF--
Subject: My Subject
To: recipient@example.com
From: sender@example.com
Message-ID: <%d.%s@mail.example.com>
User-Agent: Horde Application Framework 4
Date: %s, %d %s %d %d:%d:%d %s%d
Content-Type: application/octet-stream
MIME-Version: 1.0
