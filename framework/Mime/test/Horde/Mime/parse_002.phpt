--TEST--
Horde_Mime_Part::parseMessage() [Horde_Mime_Part] test
--FILE--
<?php

require_once dirname(__FILE__) . '/../../../lib/Horde/Mime.php';
require_once dirname(__FILE__) . '/../../../lib/Horde/Mime/Address.php';
require_once dirname(__FILE__) . '/../../../lib/Horde/Mime/Headers.php';
require_once dirname(__FILE__) . '/../../../lib/Horde/Mime/Part.php';
require_once 'Horde/String.php';
require_once 'Mail/RFC822.php';

$data = file_get_contents(dirname(__FILE__) . '/fixtures/sample_msg.txt');

$ob = Horde_Mime_Part::parseMessage($data);
print_r($ob->getType());

?>
--EXPECTF--
multipart/mixed
