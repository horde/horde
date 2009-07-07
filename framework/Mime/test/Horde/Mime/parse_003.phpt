--TEST--
Horde_Mime_Part::getRawPartText() test
--FILE--
<?php

require_once dirname(__FILE__) . '/../../../lib/Horde/Mime.php';
require_once dirname(__FILE__) . '/../../../lib/Horde/Mime/Address.php';
require_once dirname(__FILE__) . '/../../../lib/Horde/Mime/Headers.php';
require_once dirname(__FILE__) . '/../../../lib/Horde/Mime/Part.php';
require_once 'Horde/String.php';
require_once 'Mail/RFC822.php';

$data = file_get_contents(dirname(__FILE__) . '/fixtures/sample_msg.txt');

print_r(Horde_Mime_Part::getRawPartText($data, 'body', '2.1'));
echo "---\n";
print_r(Horde_Mime_Part::getRawPartText($data, 'header', '3'));

?>
--EXPECTF--
Test text.

---
Content-Type: image/png; name=index.png
Content-Disposition: attachment; filename=index.png
Content-Transfer-Encoding: base64
