--TEST--
Check the Kolab Share handler
--FILE--
<?php

require_once 'Horde/Kolab/Test/Storage.php';
$test = new Horde_Kolab_Test_Storage();

$world = $test->prepareBasicSetup();

$test->assertTrue($world['auth']->authenticate('wrobel@example.org',
                                               array('password' => 'none')));

$test->prepareNewFolder($world['storage'], 'Calendar', 'event');

require_once dirname(__FILE__) . '/../Share.php';

$shares = new Horde_Share_Kolab('kronolith');

$keys = array_keys($shares->listShares('wrobel@example.org'));
foreach ($keys as $key) {
  echo $key . "\n";
}
?>
--EXPECT--
INBOX%2FCalendar
