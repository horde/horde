--TEST--
MIME_Mail encoding test
--FILE--
<?php

require dirname(__FILE__) . '/mail_dummy.inc';
require 'Horde/NLS.php';

$mail = new MIME_Mail('Schöner Betreff', "Hübsche Umlaute \n und Leerzeichen.",
                      'Empfänger <recipient@example.com>',
                      'sender@example.com', 'iso-8859-1');
$mail->addHeader('Cc', 'Der schöne Peter <peter@example.com>', 'iso-8859-15');
echo $mail->send('dummy');

?>
--EXPECTF--
Subject: =?iso-8859-1?b?U2No9m5lcg==?= Betreff
To: =?iso-8859-1?b?RW1wZuRuZ2Vy?= <recipient@example.com>
From: sender@example.com
Cc: Der =?iso-8859-15?b?c2No9m5l?= Peter <peter@example.com>
Message-ID: <%d.%s@mail.example.com>
User-Agent: Horde Application Framework 3.2
Date: %s, %d %s %d %d:%d:%d %s%d
MIME-Version: 1.0
Content-Type: text/plain;
	charset=iso-8859-1;
	DelSp="Yes";
	format="flowed"
Content-Disposition: inline
Content-Transfer-Encoding: quoted-printable

H=FCbsche Umlaute
  und Leerzeichen.
