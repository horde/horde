--TEST--
phpunit BowlingGameSpec ../_files/BowlingGameSpec.php
--FILE--
<?php
$_SERVER['argv'][1] = '--no-configuration';
$_SERVER['argv'][2] = 'BowlingGameSpec';
$_SERVER['argv'][3] = dirname(dirname(__FILE__)) . '/_files/BowlingGameSpec.php';

require_once 'PHPUnit/Autoload.php';
PHPUnit_TextUI_Command::main();
?>
--EXPECTF--
PHPUnit %s by Sebastian Bergmann.

.....

Time: %i %s, Memory: %sMb

OK (5 tests, 5 assertions)
