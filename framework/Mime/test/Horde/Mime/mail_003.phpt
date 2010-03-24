--TEST--
Horde_Mime_Mail encoding test
--FILE--
<?php

require dirname(__FILE__) . '/mail_dummy.inc';
require_once 'Horde/String.php';
require 'Horde/Nls.php';
require_once 'Horde/Util.php';

$mail = new Horde_Mime_Mail(array('subject' => 'Schöner Betreff',
                                  'body' => "Hübsche Umlaute \n und Leerzeichen.",
                                  'to' => 'Empfänger <recipient@example.com>',
                                  'from' => 'sender@example.com',
                                  'charset' => 'iso-8859-1'));
$mail->addHeader('Cc', 'Der schöne Peter <peter@example.com>', 'iso-8859-15');

$dummy = Mail::factory('dummy');
$mail->send($dummy);
echo $dummy->send_output;

?>
--EXPECTF--
Subject: =?iso-8859-1?b?U2No9m5lcg==?= Betreff
To: =?iso-8859-1?b?RW1wZuRuZ2Vy?= <recipient@example.com>
From: sender@example.com
Cc: Der =?iso-8859-15?b?c2No9m5l?= Peter <peter@example.com>
Message-ID: <%d.%s@mail.example.com>
User-Agent: Horde Application Framework 4.0
Date: %s, %d %s %d %d:%d:%d %s%d
Content-Type: text/plain; charset=iso-8859-1; format=flowed; DelSp=Yes
MIME-Version: 1.0
Content-Disposition: inline
Content-Transfer-Encoding: quoted-printable

H=FCbsche Umlaute
  und Leerzeichen.
