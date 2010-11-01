--TEST--
Check the Kolab Share handler
--FILE--
<?php

$conf['kolab']['enabled'] = true;

require_once 'PEAR.php';

require_once dirname(__FILE__) . '/../Share.php';

$shares = new Horde_Share_Kolab('test');
?>
--EXPECT--
