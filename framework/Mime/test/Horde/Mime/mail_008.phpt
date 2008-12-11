--TEST--
Horde_Mime_Mail flowed text test
--FILE--
<?php

require dirname(__FILE__) . '/mail_dummy.inc';

$mail = new Horde_Mime_Mail();
$mail->addHeader('Subject', 'My Subject');
$mail->addHeader('To', 'recipient@example.com');
$mail->setBody('Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo
consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse
cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.');

echo $mail->send('dummy');

?>
--EXPECTF--
Subject: My Subject
To: recipient@example.com
Message-ID: <%d.%s@mail.example.com>
User-Agent: Horde Application Framework 3.2
Date: %s, %d %s %d %d:%d:%d %s%d
MIME-Version: 1.0
Content-Type: text/plain;
	charset=iso-8859-1;
	DelSp="Yes";
	format="flowed"
Content-Disposition: inline
Content-Transfer-Encoding: 7bit

Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do  
eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad  
minim veniam, quis nostrud exercitation ullamco laboris nisi ut  
aliquip ex ea commodo
consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse
cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat  
cupidatat non proident, sunt in culpa qui officia deserunt mollit anim  
id est laborum.
