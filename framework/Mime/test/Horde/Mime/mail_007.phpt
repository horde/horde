--TEST--
Horde_Mime_Mail reusing test
--FILE--
<?php

require dirname(__FILE__) . '/mail_dummy.inc';

$mail = new Horde_Mime_Mail('My Subject', "This is\nthe body",
                            'recipient@example.com', 'sender@example.com',
                            'iso-8859-15');
echo $mail->send('dummy');
$id = $mail->_headers->getValue('message-id');

echo "====================================================================\n";

$mail->addHeader('To', 'Änderung <other@example.com>', 'utf-8');
echo $mail->send('dummy');

echo "====================================================================\n";

var_dump($id != $mail->_headers->getValue('message-id'));

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
====================================================================
Subject: My Subject
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
To: =?utf-8?b?w4RuZGVydW5n?= <other@example.com>

This is
the body

====================================================================
bool(true)

