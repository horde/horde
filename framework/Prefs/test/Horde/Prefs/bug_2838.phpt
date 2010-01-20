--TEST--
Test for Bug #2838, overwriting of preferences when multiple scopes are retrieved.
--FILE--
<?php

$horde_base = '/path/to/horde';
require_once HORDE_BASE . '/lib/Application.php';
Horde_Registry::appInit('horde', array('authentication' => 'none'));

$prefs = Horde_Prefs::factory('session', 'horde', 'testuser', 'testpw');
$prefs->retrieve('imp');
$prefs->setValue('last_login', 'test');
echo $prefs->getValue('last_login') . "\n";

$prefs->retrieve('ingo');
echo $prefs->getValue('last_login') . "\n";

?>
--EXPECT--
test
test
