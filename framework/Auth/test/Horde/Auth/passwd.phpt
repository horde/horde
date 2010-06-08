--TEST--
Horde_Auth_Passwd:: test
--FILE--
<?php

require_once dirname(__FILE__) . '/../../../lib/Horde/Auth.php';
require_once dirname(__FILE__) . '/../../../lib/Horde/Auth/Base.php';
require_once dirname(__FILE__) . '/../../../lib/Horde/Auth/Passwd.php';

$auth = Horde_Auth::factory('passwd', array('filename' => dirname(__FILE__) . '/test.passwd'));

// List users
var_dump($auth->listUsers());

// Authenticate
var_dump($auth->authenticate('user', array('password' => 'password')));

?>
--EXPECT--
array(1) {
  [0]=>
  string(4) "user"
}
bool(true)
