--TEST--
Horde_Mime_Mail HTML test
--FILE--
<?php

require dirname(__FILE__) . '/mail_dummy.inc';
require_once 'Horde/String.php';
require_once 'Horde/Text/Filter.php';
require_once 'Horde/Text/Filter/Html2text.php';
require_once 'Horde/Util.php';

$mail = new Horde_Mime_Mail(array('subject' => 'My Subject',
                                  'to' => 'recipient@example.com',
                                  'from' => 'sender@example.com'));
$mail->setBody("This is\nthe plain text body.");
echo $mail->send(array('type' => 'dummy'));

echo "====================================================================\n";

$mail = new Horde_Mime_Mail(array('subject' => 'My Subject',
                                  'to' => 'recipient@example.com',
                                  'from' => 'sender@example.com'));
$mail->setHTMLBody("<h1>Header Title</h1>\n<p>This is<br />the html text body.</p>",
                   'iso-8859-1', false);
echo $mail->send(array('type' => 'dummy'));

echo "====================================================================\n";

$mail = new Horde_Mime_Mail(array('subject' => 'My Subject',
                                  'to' => 'recipient@example.com',
                                  'from' => 'sender@example.com'));
$mail->setHTMLBody("<h1>Header Title</h1>\n<p>This is<br />the html text body.</p>");

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
Content-Type: text/plain; charset=iso-8859-1; format=flowed; DelSp=Yes
MIME-Version: 1.0
Content-Disposition: inline
Content-Transfer-Encoding: 7bit

This is
the plain text body.
====================================================================
Subject: My Subject
To: recipient@example.com
From: sender@example.com
Message-ID: <%d.%s@mail.example.com>
User-Agent: Horde Application Framework 4.0
Date: %s, %d %s %d %d:%d:%d %s%d
Content-Type: text/html; charset=iso-8859-1
MIME-Version: 1.0
Content-Disposition: inline
Content-Transfer-Encoding: 7bit

<h1>Header Title</h1>
<p>This is<br />the html text body.</p>
====================================================================
Subject: My Subject
To: recipient@example.com
From: sender@example.com
Message-ID: <%d.%s@mail.example.com>
User-Agent: Horde Application Framework 4.0
Date: %s, %d %s %d %d:%d:%d %s%d
Content-Type: multipart/alternative; boundary="=_%s"
MIME-Version: 1.0
Content-Transfer-Encoding: 7bit

This message is in MIME format.

--=_%s
Content-Type: text/plain; charset=iso-8859-1; format=flowed; DelSp=Yes
Content-Description: Plaintext Version of Message
Content-Disposition: inline
Content-Transfer-Encoding: 7bit



HEADER TITLE

This is
the html text body.

--=_%s
Content-Type: text/html; charset=iso-8859-1
Content-Description: HTML Version of Message
Content-Disposition: inline
Content-Transfer-Encoding: 7bit

<h1>Header Title</h1>
<p>This is<br />the html text body.</p>
--=_%s--
