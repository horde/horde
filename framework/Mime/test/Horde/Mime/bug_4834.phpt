--TEST--
Bug #4834 Wrong encoding of email lists with groups.
--FILE--
<?php

require_once 'Mail/RFC822.php';
require_once 'Horde/Browser.php';
require_once 'Horde/String.php';
require dirname(__FILE__) . '/../../../lib/Horde/Mime.php';
require dirname(__FILE__) . '/../../../lib/Horde/Mime/Address.php';
echo Horde_Mime::encodeAddress('"John Doe" <john@example.com>, Group: peter@example.com, jane@example.com;');

?>
--EXPECT--
John Doe <john@example.com>, Group: peter@example.com, jane@example.com;
