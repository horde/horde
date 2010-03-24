--TEST--
Horde_Mime_Mail reusing test
--FILE--
<?php

require dirname(__FILE__) . '/mail_dummy.inc';
require_once 'Horde/String.php';
require_once 'Horde/Util.php';

$dummy = Mail::factory('dummy');

$mail = new Horde_Mime_Mail(array('subject' => 'My Subject',
                                  'body' => "This is\nthe body",
                                  'to' => 'recipient@example.com',
                                  'from' => 'sender@example.com',
                                  'charset' => 'iso-8859-15'));

$mail->send($dummy);
$raw = $dummy->send_output;

echo $raw;
preg_match('/^Message-ID: (.*)$/m', $raw, $id1);

echo "====================================================================\n";

$mail->addHeader('To', 'Änderung <other@example.com>', 'utf-8');
$raw = $mail->send(array('type' => 'dummy'));

$mail->send($dummy);
$raw = $dummy->send_output;

preg_match('/^Message-ID: (.*)$/m', $raw, $id2);

echo "====================================================================\n";

var_dump($id1[1] != $id2[1]);

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
====================================================================
Subject: My Subject
From: sender@example.com
User-Agent: Horde Application Framework 4.0
Content-Type: text/plain; charset=iso-8859-15; format=flowed; DelSp=Yes
MIME-Version: 1.0
Content-Disposition: inline
Content-Transfer-Encoding: 7bit
To: =?utf-8?b?w4RuZGVydW5n?= <other@example.com>
Message-ID: <%d.%s@mail.example.com>
Date: %s, %d %s %d %d:%d:%d %s%d

This is
the body

====================================================================
bool(true)

