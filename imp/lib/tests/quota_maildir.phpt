--TEST--
IMP_Quota_maildir test.
--FILE--
<?php

require_once dirname(__FILE__) . '/../Application.php';
Horde_Registry::appInit('imp', array(
    'authentication' => 'none',
    'cli' => true
));

$quota = IMP_Quota::factory('Maildir', array(
    'path' => dirname(__FILE__) . '/fixtures'
));

var_export($quota->getQuota());

?>
--EXPECT--
array (
  'limit' => 1000000000,
  'usage' => 550839239,
)
