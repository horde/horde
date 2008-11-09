--TEST--
MIME_Mail constructor test
--FILE--
<?php

require dirname(__FILE__) . '/mail_dummy.inc';

$mail = new MIME_Mail('My Subject', "This is\nthe body",
                      'recipient@example.com', 'sender@example.com',
                      'iso-8859-15');
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
Content-Type: text/plain;
	charset=iso-8859-15;
	DelSp="Yes";
	format="flowed"
Content-Disposition: inline
Content-Transfer-Encoding: 7bit

This is
the body
