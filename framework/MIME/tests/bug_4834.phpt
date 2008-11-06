--TEST--
Bug #4834 Wrong encoding of email lists with groups.
--FILE--
<?php

require dirname(__FILE__) . '/../lib/Horde/MIME.php';
echo MIME::encodeAddress('"John Doe" <john@example.com>, Group: peter@example.com, jane@example.com;');

?>
--EXPECT--
John Doe <john@example.com>, Group: peter@example.com, jane@example.com;
